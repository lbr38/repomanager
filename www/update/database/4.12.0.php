<?php
/**
 *  4.12.0 update
 */

/**
 *  Add new 'Definition' column to the sources table
 */
if (!$this->db->columnExist('sources', 'Definition') === true) {
    $this->db->exec("ALTER TABLE sources ADD COLUMN Definition TEXT");
}

/**
 *  Add new 'Method' column to the sources table
 */
if (!$this->db->columnExist('sources', 'Method') === true) {
    $this->db->exec("ALTER TABLE sources ADD COLUMN Method VARCHAR(255)");
}

/**
 *  Migrate all source repositories to the new 'Definition' column
 */
$mysource = new \Controllers\Repo\Source\Source();

/**
 *  Get all current sources
 */
$sources = [];
$stmt = $this->db->prepare("SELECT * FROM sources");
$result = $stmt->execute();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $sources[] = $row;
}

if (empty($sources)) {
    return;
}

foreach ($sources as $source) {
    try {
        $params = [];
        $id = $source['Id'];
        $name = $source['Name'];
        $type = $source['Type'];
        $url = $source['Url'];

        /**
         *  First get definition template
         */
        $definition = $mysource->template($type);

        /**
         *  Then replace with the current source informations
         */
        $definition['type'] = $type;
        $definition['name'] = $name;
        $definition['url'] = $url;

        if (!empty($source['Ssl_certificate_path'])) {
            $definition['ssl-certificate-path'] = $source['Ssl_certificate_path'];
        }
        if (!empty($source['Ssl_private_key_path'])) {
            $definition['ssl-private-key-path'] = $source['Ssl_private_key_path'];
        }
        if (!empty($source['Ssl_ca_certificate_path'])) {
            $definition['ssl-ca-certificate-path'] = $source['Ssl_ca_certificate_path'];
        }

        /**
         *  Edit source repo definition
         */
        $mysource->edit($id, $definition);
    } catch (\Exception $e) {
        echo 'Error while migrating source repository #' . $id . ' (' . $name . '): ' . $e->getMessage() . PHP_EOL;
    }
}
