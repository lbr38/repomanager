<?php

namespace Models\Cve;

use Exception;

class Cve extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open main database
         */
        $this->getConnection('main');
    }

    /**
     *  Return CVE
     */
    public function get(string $id)
    {
        $cve = '';

        try {
            $stmt = $this->db->prepare("SELECT * FROM cve WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cve = $row;
        }

        return $cve;
    }

    /**
     *  Return CVE Id by its name
     */
    public function getIdByName(string $name)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM cve WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return CVE cpe details
     */
    public function getCpe(string $id)
    {
        $cpe = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM cve_cpe WHERE Id_cve = :id ORDER BY Part ASC, Vendor ASC, Product ASC, Version ASC");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cpe[] = $row;
        }

        return $cpe;
    }

    /**
     *  Return CVE references
     */
    public function getReferences(string $id)
    {
        $references = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM cve_reference WHERE Id_cve = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $references[] = $row;
        }

        return $references;
    }

    /**
     *  Return all CVEs Id
     */
    public function getAllId()
    {
        $cves = array();

        try {
            $stmt = $this->db->prepare("SELECT Id FROM cve ORDER BY Id ASC");
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cves[] = $row['Id'];
        }

        return $cves;
    }

    /**
     *  Return all CVEs
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset, int $offset, string|null $filter)
    {
        $cves = array();

        try {
            if (!empty($filter)) {
                $query = "SELECT cve.Id, cve.Name, cve.Date, cve.Time, cve.Updated_date, cve.Updated_time, cve.Cpe23Uri, cve.Description, cve.Cvss2_score, cve.Cvss3_score,
                cve_cpe.Part, cve_cpe.Vendor, cve_cpe.Product, cve_cpe.Version, cve_cpe.Id_cve
                FROM cve
                LEFT JOIN cve_cpe ON cve_cpe.Id_cve = cve.Id
                $filter
                ORDER BY Updated_date DESC, Updated_time DESC";
            } else {
                $query = "SELECT * FROM cve ORDER BY Updated_date DESC, Updated_time DESC";
            }

            /**
             *  If offset is specified
             */
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cves[] = $row;
        }

        return $cves;
    }

    /**
     *  Return all CVEs matching search string
     */
    public function getAllIdBySearch(string $search)
    {
        $cves = array();

        try {
            $stmt = $this->db->prepare("SELECT DISTINCT cve.Id
            FROM cve
            LEFT JOIN cve_cpe ON cve_cpe.Id_cve = cve.Id
            WHERE Name LIKE :search OR Vendor LIKE :search OR Product LIKE :search OR Version LIKE :search OR Description LIKE :search
            ORDER BY Updated_date DESC, Updated_time DESC");
            $stmt->bindValue(':search', '%' . $search . '%');
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cves[] = $row['Id'];
        }

        return $cves;
    }

    /**
     *  Get affected hosts by CVE Id
     */
    public function getAffectedHosts(string $cveId, string $status)
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Id, Host_id, Product, Version FROM cve_affected_hosts WHERE Id_cve = :cveId AND Status = :status");
            $stmt->bindValue(':cveId', $cveId);
            $stmt->bindValue(':status', $status);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Check if CVE Id exists in database
     */
    public function exists(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM cve WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Check if CVE nameId exists in database
     */
    public function nameExists(string $nameId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM cve WHERE Name = :name");
            $stmt->bindValue(':name', $nameId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Set new CVE
     */
    public function new(string $nameId, string $date, string $time, string $updatedDate, string $updatedTime, string $cpe23UriRawStr, array $cpe23Uri, string $description, array $references, string $cvss2Score, string $cvss3Score)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO cve ('Name', 'Date', 'Time', 'Updated_date', 'Updated_time', 'Cpe23Uri', 'Description', 'Cvss2_score', 'Cvss3_score') VALUES (:nameId, :date, :time, :updatedDate, :updatedTime, :cpe23uri, :description, :cvss2Score, :cvss3Score)");
            $stmt->bindValue(':nameId', $nameId);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':updatedDate', $updatedDate);
            $stmt->bindValue(':updatedTime', $updatedTime);
            $stmt->bindValue(':cpe23uri', $cpe23UriRawStr);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':cvss2Score', $cvss2Score);
            $stmt->bindValue(':cvss3Score', $cvss3Score);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  Get inserted CVE row Id
         */
        $cveId = $this->getLastInsertRowID();

        if (!empty($cpe23Uri)) {
            foreach ($cpe23Uri as $cpe) {
                $part = $cpe['part'];
                $vendor = $cpe['vendor'];
                $product = $cpe['product'];
                $version = $cpe['version'];

                /**
                 *  Insert cpe if not already exist for this CVE
                 */
                if (!$this->cpeExist($part, $vendor, $product, $version, $cveId)) {
                    try {
                        $stmt = $this->db->prepare("INSERT INTO cve_cpe ('Part', 'Vendor', 'Product', 'Version', 'Id_cve') VALUES (:part, :vendor, :product, :version, :cveId)");
                        $stmt->bindValue(':part', $part);
                        $stmt->bindValue(':vendor', $vendor);
                        $stmt->bindValue(':product', $product);
                        $stmt->bindValue(':version', $version);
                        $stmt->bindValue(':cveId', $cveId);
                        $result = $stmt->execute();
                    } catch (\Exception $e) {
                        $this->db->logError($e);
                    }
                }
            }
        }

        if (!empty($references)) {
            foreach ($references as $reference) {
                try {
                    $stmt = $this->db->prepare("INSERT INTO cve_reference ('Name', 'Url', 'Source', 'Tags', 'Id_cve') VALUES (:name, :url, :source, :tags, :cveId)");
                    $stmt->bindValue(':name', $reference['name']);
                    $stmt->bindValue(':url', $reference['url']);
                    $stmt->bindValue(':source', $reference['source']);
                    $stmt->bindValue(':tags', $reference['tags']);
                    $stmt->bindValue(':cveId', $cveId);
                    $result = $stmt->execute();
                } catch (\Exception $e) {
                    $this->db->logError($e);
                }
            }
        }
    }

    /**
     *  Update existing CVE
     */
    public function update(string $nameId, string $date, string $time, string $updatedDate, string $updatedTime, string $cpe23UriRawStr, array $cpe23Uri, string $description, array $references, string $cvss2Score, string $cvss3Score)
    {
        try {
            $stmt = $this->db->prepare("UPDATE cve SET Date = :date, Time = :time, Updated_date = :updatedDate, Updated_time = :updatedTime, Cpe23Uri = :cpe23uri, Description = :description, Cvss2_score = :cvss2Score, Cvss3_score = :cvss3Score WHERE Name = :nameId");
            $stmt->bindValue(':nameId', $nameId);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':updatedDate', $updatedDate);
            $stmt->bindValue(':updatedTime', $updatedTime);
            $stmt->bindValue(':cpe23uri', $cpe23UriRawStr);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':cvss2Score', $cvss2Score);
            $stmt->bindValue(':cvss3Score', $cvss3Score);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  Get inserted CVE row Id
         */
        $cveId = $this->getLastInsertRowID();

        if (empty($cpe23Uri)) {
            return;
        }

        foreach ($cpe23Uri as $cpe) {
            $part = $cpe['part'];
            $vendor = $cpe['vendor'];
            $product = $cpe['product'];
            $version = $cpe['version'];

            /**
             *  Insert cpe if not already exist for this CVE
             */
            if (!$this->cpeExist($part, $vendor, $product, $version, $cveId)) {
                try {
                    $stmt = $this->db->prepare("INSERT INTO cve_cpe ('Part', 'Vendor', 'Product', 'Version', 'Id_cve') VALUES (:part, :vendor, :product, :version, :cveId)");
                    $stmt->bindValue(':part', $part);
                    $stmt->bindValue(':vendor', $vendor);
                    $stmt->bindValue(':product', $product);
                    $stmt->bindValue(':version', $version);
                    $stmt->bindValue(':cveId', $cveId);
                    $result = $stmt->execute();
                } catch (\Exception $e) {
                    $this->db->logError($e);
                }
            }
        }
    }

    /**
     *  Check if cpe exist
     */
    public function cpeExist(string $part, string $vendor, string $product, string $version, string $cveId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM cve_cpe WHERE Part = :part AND Vendor = :vendor AND Product = :product AND Version = :version AND Id_cve = :cveId");
            $stmt->bindValue(':part', $part);
            $stmt->bindValue(':vendor', $vendor);
            $stmt->bindValue(':product', $product);
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':cveId', $cveId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Add a new affected host
     */
    public function setAffectedHost(string $cveId, string $hostId, string $productName, string $productVersion, string $state)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO cve_affected_hosts ('Host_id', 'Product', 'Version', 'Status', 'Id_cve') VALUES (:hostId, :productName, :productVersion, :state, :cveId)");
            $stmt->bindValue(':hostId', $hostId);
            $stmt->bindValue(':productName', $productName);
            $stmt->bindValue(':productVersion', $productVersion);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':cveId', $cveId);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    public function searchCpeProductVersion(string $product, string $version)
    {
        $cves = array();
        // $startTime = hrtime(true);

        try {
            $stmt = $this->db->prepare("SELECT DISTINCT cve.Id
            FROM cve
            LEFT JOIN cve_cpe
            ON cve_cpe.Id_cve = cve.Id
            WHERE (cve_cpe.Product = :product OR cve_cpe.Product LIKE :product_start OR cve_cpe.Product LIKE :product_end)
            AND cve_cpe.Version LIKE :version");
            $stmt->bindValue(':product', $product);
            $stmt->bindValue(':product_start', $product . '\_%');
            $stmt->bindValue(':product_end', '%\_' . $product);
            $stmt->bindValue(':version', $version . '%');
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        // $endTime = hrtime(true);
        // $executionTime = ($endTime - $startTime) / 1e9;
        // echo $executionTime . "s" . PHP_EOL;

        if ($this->db->isempty($result)) {
            return;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $cves[] = $row['Id'];
        }

        return $cves;
    }
}
