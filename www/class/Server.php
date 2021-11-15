<?php
global $WWW_DIR;
require_once("${WWW_DIR}/class/Database-servers.php");

class Server {
    public $db;

    public $id;
    public $ip;
    public $hostname;
    public $onlineStatus;

    public function __construct(array $variables = []) {
        extract($variables);

        /**
         *  Instanciation d'une db car on peut avoir besoin de récupérer certaines infos en BDD
         */
        try {
            $this->db = new Database_servers();
        } catch(Exception $e) {
            die('Erreur : '.$e->getMessage());
        }

        if (!empty($serverId)) {
            $this->id = $serverId;
        }
        if (!empty($serverIp)) {
            $this->ip = $serverIp;
        }
        if (!empty($serverHostname)) {
            $this->hostname = $serverHostname;
        }
    }

    /**
     *  Ajoute un nouveau serveur en BDD
     */
    public function register() {
        /**
         *  Si on n'a renseigné ni IP ni hostname alors on quitte
         */
        if (empty($this->ip) AND empty($this->hostname)) {
            return;
        }

        /**
         *  Si vide, on récupère l'adresse IP grace au hostname
         */
        if (empty($this->ip)) $this->ip = trim(exec("dig $this->hostname +short"));

        /**
         *  Si vide, on récupère le hostname du serveur grace à son adresse IP
         */
        if (empty($this->hostname)) $this->hostname = rtrim(exec("dig -x $this->ip +short"), '.');

        /**
         *  A cette étape si il manque l'IP alors on quitte
         *  Si le hostname est vide ce n'est pas très grave
         */
        if (empty($this->ip)) {
            printAlert("Impossible de déterminer l'adresse IP du serveur", 'error');
            return;
        }
        if (empty($this->hostname)) {
            $this->hostname = '';
        }

        /**
         *  On vérifie que l'IP n'existe pas déjà en BDD
         */
        if (!empty($this->ip)) {
            if ($this->serverIpExists($this->ip) === true) {
                printAlert("Un serveur avec l'adresse IP <b>$this->ip</b> existe déjà", 'error');
                return;
            }
        }
        /**
         *  On vérifie que le hostname n'existe pas déjà en BDD
         */
        if (!empty($this->hostname)) {
            if ($this->serverHostnameExists($this->hostname) === true) {
                printAlert("Un serveur avec le nom <b>$this->hostname</b> existe déjà", 'error');
                return;
            }
        }        

        /**
         *  On tente un premier ping pour déterminer si le serveur est accessible ou non
         *  Timeout de 2 secondes max
         */
        $testPing = exec("ping -c 1 -W 2 $this->hostname", $output, $testPingResult);
        if ($testPingResult == 0)
            $this->onlineStatus = 'online';
        else
            $this->onlineStatus = 'unreachable';

        /**
         *  Ajout en BDD
         */
        $date = date('Y-m-d');
        $time = date('H:i');      

        $stmt = $this->db->prepare("INSERT INTO servers (Ip, Hostname, Online_status, Online_status_date, Online_status_time, Last_update_status, Last_update_date, Last_update_time, Last_update_report, Available_packages_count, Status) VALUES (:ip, :hostname, :online_status, :date, :time, 'none', 'none', 'none', 'none', '-1', 'enabled')");
        $stmt->bindValue(':ip', $this->ip);
        $stmt->bindValue(':hostname', $this->hostname);
        $stmt->bindValue(':online_status', $this->onlineStatus);
        $stmt->bindValue(':date', $date);
        $stmt->bindValue(':time', $time);
        $stmt->execute();

        printAlert("Le serveur <b>$this->hostname ($this->ip)</b> a été ajouté", 'success');
    }

    /**
     *  Suppression d'un serveur
     */
    public function unregister() {
        $stmt = $this->db->prepare("DELETE FROM servers WHERE Id=:id");
        $stmt->bindValue(':id', $this->id);
        $stmt->execute();

        printAlert("Serveur supprimé", 'success');
    }

    /**
     *  Mise à jour d'un serveur
     */
    public function update() {
        /**
         *  D'abord on récupère l'IP du serveur à mettre à jour
         */
        $stmt = $this->db->prepare("SELECT Ip FROM servers WHERE Id=:id");
        $stmt->bindValue(':id', $this->id);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas = $row;

        if (empty($datas['Ip'])) {
            printAlert("Impossible de déterminer l'adresse IP du serveur à mettre à jour", 'error');
            return;
        }

        $this->ip = $datas['Ip'];

        /**
         *  Envoi d'un ping pour ordonner au serveur de se mettre à jour
         */
        exec("ping -W2 -c 1 -p 486920686572652e $this->ip", $output, $pingResult);
        if ($pingResult != 0) {
            printAlert("Impossible d'envoyer la demande de mise à jour au serveur (injoignable)", 'error');
            return;
        }

        printAlert("Demande de mise à jour envoyée", 'success');
    }

    /**
     *  Vérifie si le serveur existe en BDD
     */
    private function serverIpExists(string $ip) {
        $stmt = $this->db->prepare("SELECT Ip FROM servers WHERE Ip=:ip");
        $stmt->bindValue(':ip', $ip);
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true)
            return false;
        else
            return true;
    }

    /**
     *  Vérifie si le hostname du serveur existe en BDD
     */
    private function serverHostnameExists(string $hostname) {
        $stmt = $this->db->prepare("SELECT Hostname FROM servers WHERE Hostname=:hostname");
        $stmt->bindValue(':ip', $hostname);
        $result = $stmt->execute();

        if ($this->db->isempty($result) === true)
            return false;
        else
            return true;
    }

    /**
     *  Liste tous les serveurs
     */
    public function listAll() {
        $result = $this->db->query("SELECT * FROM servers");

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $datas[] = $row;

        return $datas;
    }

    /**
     *  Compte le nombre total de serveurs en BDD
     */
    public function totalServers() {
        $result = $this->db->query("SELECT Id FROM servers");

        $total = $this->db->count($result);

        return $total;
    }

    /**
     *  Compte le nombre de serveur à jour (Available_packages_count = 0)
     */
    public function totalUptodate() {
        $result = $this->db->query("SELECT Id FROM servers WHERE Available_packages_count = '0'");

        $total = $this->db->count($result);

        return $total;
    }

    /**
     *  Compte le nombre de serveur qui ne sont pas à jour (Available_packages_count > 0)
     */
    public function totalNotUptodate() {
        $result = $this->db->query("SELECT Id FROM servers WHERE Available_packages_count > '0'");

        $total = $this->db->count($result);

        return $total;
    }

    /**
     *  Compte le nombre de serveur dont les paquets à mettre à jour est inconnu (Last_update_status = -1)
     *  Généralement il s'agit de serveurs qui viennent juste d'être intégrés
     */
    public function totalUptodate_unknown() {
        $result = $this->db->query("SELECT Id FROM servers WHERE Available_packages_count = '-1'");

        $total = $this->db->count($result);

        return $total;
    }
}