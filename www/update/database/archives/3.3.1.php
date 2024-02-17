<?php

/**
 *  v3.3.1 database update
 */

/**
 *  Check if Ssl_certificate_path column exists in source table
 */
if ($this->db->columnExist('sources', 'Ssl_certificate_path') === false) {
    /**
     *  If Type column is not found then add it
     */
    $this->db->exec("ALTER TABLE sources ADD Ssl_certificate_path VARCHAR(255)");
    $this->db->exec("ALTER TABLE sources ADD Ssl_private_key_path VARCHAR(255)");
}
