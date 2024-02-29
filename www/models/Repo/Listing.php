<?php

namespace Models\Repo;

use Exception;

class Listing extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Retourne la liste des repos actifs, càd ayant au moins 1 snapshot actif, et leur environnement si il y en a
     */
    public function list()
    {
        try {
            $result = $this->db->query("SELECT
            repos.Id AS repoId,
            repos_snap.Id AS snapId,
            repos_env.Id AS envId,
            repos.Name,
            repos.Dist,
            repos.Section,
            repos.Releasever,
            repos.Source,
            repos.Package_type,
            repos_env.Env,
            repos_snap.Date,
            repos_snap.Time,
            repos_snap.Signed,
            repos_snap.Arch,
            repos_snap.Pkg_translation,
            repos_snap.Type,
            repos_env.Description
            FROM repos 
            LEFT JOIN repos_snap
                ON repos.Id = repos_snap.Id_repo
            LEFT JOIN repos_env 
                ON repos_snap.Id = repos_env.Id_snap
            WHERE repos_snap.Status = 'active'
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_env.Env ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Retourne le liste des noms de repos actifs, par groupe
     *  Utilisée notamment pour les planifications de groupes
     */
    public function listNameByGroup(string $groupName)
    {
        try {
            $stmt = $this->db->prepare("SELECT DISTINCT
            repos.Id AS repoId,
            repos.Name,
            repos.Dist,
            repos.Section
            FROM repos
            LEFT JOIN group_members
                ON repos.Id = group_members.Id_repo
            LEFT JOIN groups
                ON groups.Id = group_members.Id_group
            WHERE groups.Name = :groupname
            ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
            $stmt->bindValue(':groupname', $groupName);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $row;
        }

        return $repos;
    }

    /**
     *  Retourne la liste des repos actifs, par groupe
     */
    public function listByGroup(string $groupName)
    {
        /**
         *  Si le groupe == 'Default' (groupe fictif) alors on affiche tous les repos n'ayant pas de groupe
         */
        try {
            if ($groupName == 'Default') {
                $reposInGroup = $this->db->query("SELECT DISTINCT
                repos.Id AS repoId,
                repos_snap.Id AS snapId,
                repos_env.Id AS envId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Releasever,
                repos.Source,
                repos.Package_type,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_env.Description
                FROM repos 
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                WHERE repos_snap.Status = 'active' AND repos.Id NOT IN (SELECT Id_repo FROM group_members)
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");
            } else {
                $stmt = $this->db->prepare("SELECT DISTINCT
                repos.Id AS repoId,
                repos_snap.Id AS snapId,
                repos_env.Id AS envId,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Releasever,
                repos.Source,
                repos.Package_type,
                repos_env.Env,
                repos_snap.Date,
                repos_snap.Time,
                repos_snap.Signed,
                repos_snap.Arch,
                repos_snap.Pkg_translation,
                repos_snap.Type,
                repos_snap.Reconstruct,
                repos_snap.Status,
                repos_env.Description
                FROM repos 
                LEFT JOIN repos_snap
                    ON repos.Id = repos_snap.Id_repo
                LEFT JOIN repos_env 
                    ON repos_env.Id_snap = repos_snap.Id
                LEFT JOIN group_members
                    ON repos.Id = group_members.Id_repo
                LEFT JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = :groupname
                AND repos_snap.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC, repos_snap.Date DESC");

                $stmt->bindValue(':groupname', $groupName);
                $reposInGroup = $stmt->execute();
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $reposIn = array();

        while ($datas = $reposInGroup->fetchArray(SQLITE3_ASSOC)) {
            $reposIn[] = $datas;
        }

        return $reposIn;
    }

    /**
     *  Retourne un array de tous les noms de repos, sans informations des snapshots et environnements associés
     *  Si le paramètre 'true' est passé alors la fonction renverra uniquement les noms des repos qui ont un snapshot actif rattaché
     *  Si le paramètre 'false' est passé alors la fonction renverra tous les noms de repos avec ou sans snapshot rattaché
     */
    public function listNameOnly(bool $bool)
    {
        try {
            if ($bool == false) {
                $result = $this->db->query("SELECT DISTINCT *
                FROM repos
                ORDER BY Name ASC, Dist ASC, Section ASC");
            }

            if ($bool == true) {
                $result = $this->db->query("SELECT DISTINCT
                repos.Id,
                repos.Name,
                repos.Dist,
                repos.Section,
                repos.Source,
                repos.Package_type
                FROM repos
                INNER JOIN repos_snap
                    ON repos_snap.Id_repo = repos.Id
                WHERE repos_snap.Id_repo NOT NULL
                AND repos_snap.Status = 'active'
                ORDER BY repos.Name ASC, repos.Dist ASC, repos.Section ASC");
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }

    /**
     *  Return the list of repos eligible for planifications (repos with at least 1 active snapshot)
     */
    public function listForPlan()
    {
        $data = array();

        try {
            /**
             *  Retrieve all repos name only
             */
            $repos = $this->listNameOnly(true);

            /**
             *  For each repo, retrieve the last active snapshot
             */
            foreach ($repos as $repo) {
                $stmt = $this->db->prepare("SELECT repos_snap.Id AS snapId
                FROM repos_snap
                LEFT JOIN repos
                    ON repos.Id = repos_snap.Id_repo
                WHERE repos.Id = :id
                AND repos_snap.Status = 'active'
                AND repos_snap.Type = 'mirror'
                ORDER BY repos_snap.Date DESC, repos_snap.Time DESC LIMIT 1");

                $stmt->bindValue(':id', $repo['Id']);
                $result = $stmt->execute();

                /**
                 *  Build an array with the repo Id, name and the last active snapshot Id and add it to the $data array
                 */
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $data[] = array(
                        'Id' => $repo['Id'],
                        'Name' => $repo['Name'],
                        'Dist' => $repo['Dist'],
                        'Section' => $repo['Section'],
                        'SnapId' => $row['snapId']
                    );
                }
            }
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        return $data;
    }
}
