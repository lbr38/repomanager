<?php
/**
 *  4.12.0 update
 */

$mysource = new \Controllers\Repo\Source\Source();

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

/**
 *  Get all current sources
 */
$sources = [];
$stmt = $this->db->prepare("SELECT * FROM sources");
$result = $stmt->execute();
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $sources[] = $row;
}

if (!empty($sources)) {
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
                if (file_exists($source['Ssl_certificate_path']) and is_readable($source['Ssl_certificate_path'])) {
                    $content = file_get_contents($source['Ssl_certificate_path']);

                    if ($content !== false) {
                        $definition['ssl-certificate'] = $content;
                    }
                }
            }
            if (!empty($source['Ssl_private_key_path'])) {
                if (file_exists($source['Ssl_private_key_path']) and is_readable($source['Ssl_private_key_path'])) {
                    $content = file_get_contents($source['Ssl_private_key_path']);

                    if ($content !== false) {
                        $definition['ssl-private-key'] = $content;
                    }
                }
            }
            if (!empty($source['Ssl_ca_certificate_path'])) {
                if (file_exists($source['Ssl_ca_certificate_path']) and is_readable($source['Ssl_ca_certificate_path'])) {
                    $content = file_get_contents($source['Ssl_ca_certificate_path']);

                    if ($content !== false) {
                        $definition['ssl-ca-certificate'] = $content;
                    }
                }
            }

            /**
             *  Edit source repo definition
             */
            $mysource->edit($id, $definition);
        } catch (Exception $e) {
            throw new Exception('could not migrate source repository #' . $id . ' (' . $name . '): ' . $e->getMessage() . PHP_EOL);
        }
    }
}

/**
 *  Finally, drop some NOT NULL columns from the sources table
 */
if ($this->db->columnExist('sources', 'Name') === true) {
    $this->db->exec("ALTER TABLE sources DROP COLUMN Name");
}
if ($this->db->columnExist('sources', 'Url') === true) {
    $this->db->exec("ALTER TABLE sources DROP COLUMN Url");
}
if ($this->db->columnExist('sources', 'Type') === true) {
    $this->db->exec("ALTER TABLE sources DROP COLUMN Type");
}

$this->db->exec("VACUUM");
