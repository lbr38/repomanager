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
 *  Migrate all source repositories to the new 'Definition' column
 */
$mysource = new \Controllers\Repo\Source\Source();

/**
 *  Get all current sources
 */
$sources = [];
$result = $this->db->exec("SELECT * FROM sources");
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
        // $gpgKey = $source['Gpgkey'];

        /**
         *  First get definition template
         */
        $definition = $mysource->template($type);

        /**
         *  Then replace with the current source informations
         */
        $definition['name'] = $name;
        $definition['url'] = $url;

        if (!empty($source['Ssl_certificate_path'])) {
            $definition['ssl-authentication']['certificate-path'] = $source['Ssl_certificate_path'];
        }
        if (!empty($source['Ssl_private_key_path'])) {
            $definition['ssl-authentication']['private-key-path'] = $source['Ssl_private_key_path'];
        }
        if (!empty($source['Ssl_ca_certificate_path'])) {
            $definition['ssl-authentication']['ca-certificate-path'] = $source['Ssl_ca_certificate_path'];
        }

        /**
         *  Edit source repo definition
         */
        $mysource->edit($id, $definition);
    } catch (\Exception $e) {
        echo 'Error while migrating source repository #' . $id . ' (' . $name . '): ' . $e->getMessage() . PHP_EOL;
    }
}
