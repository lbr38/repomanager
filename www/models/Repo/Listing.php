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
            $this->db->logError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
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
            $this->db->logError($e);
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
            $this->db->logError($e);
        }

        $repos = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $repos[] = $datas;
        }

        return $repos;
    }
}
