<?php

/**
 *  v3.3.0 database update
 */

/**
 *  Check if Type column exists in source table
 */
if ($this->db->columnExist('sources', 'Type') === false) {
    /**
     *  If Type column is not found then add it
     */
    $this->db->exec("ALTER TABLE sources ADD Type CHAR(3)");

    /**
     *  Fill columns that were created:
     */
    $this->db->exec("UPDATE sources SET Type = 'deb'");

    /**
     *   Create a new sources table with NOT NULL constraint this time:
     */
    $this->db->exec("CREATE TABLE IF NOT EXISTS sources_new (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Type CHAR(3) NOT NULL,
    Name VARCHAR(255) NOT NULL,
    Url VARCHAR(255) NOT NULL,
    Gpgkey VARCHAR(255))");

    /**
     *  Copy all content from sources to sources_new:
     */
    $this->db->exec("INSERT INTO sources_new SELECT
    Id,
    Type,
    Name,
    Url,
    Gpgkey FROM sources");

    /**
     *  Delete sources table and recreate it:
     */
    $this->db->exec("DROP TABLE sources");
    $this->db->exec("CREATE TABLE IF NOT EXISTS sources (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Type CHAR(3) NOT NULL,
    Name VARCHAR(255) NOT NULL,
    Url VARCHAR(255) NOT NULL,
    Gpgkey VARCHAR(255))");

    /**
     *  Copy all content from sources_new to sources:
     */
    $this->db->exec("INSERT INTO sources SELECT * FROM sources_new");

    /**
     *  Drop sources_new:
     */
    $this->db->exec("DROP TABLE sources_new");
}


/**
 *  Import all existing RPM repo files content into database
 */

if (!is_dir('/etc/yum.repos.d/repomanager')) {
    return;
}

if (\Controllers\Common::dirIsEmpty('/etc/yum.repos.d/repomanager')) {
    return;
}

$repoFiles = glob('/etc/yum.repos.d/repomanager/*.repo');

if (empty($repoFiles)) {
    return;
}

/**
 *  Parse all existing RPM repo files if there are, then import their URL in database
 */
foreach ($repoFiles as $repoFile) {
    $repoName = '';
    $repoUrl = '';
    $repoGpgKey = '';
    $mysource = new \Controllers\Source();

    $repoFileContent = file_get_contents($repoFile);

    preg_match('/\[.*\]/', $repoFileContent, $repoNameMatch);
    preg_match('/baseurl ?=.*/', $repoFileContent, $repoUrlMatch);
    preg_match('/gpgkey ?=.*/', $repoFileContent, $repoGpgKeyMatch);

    if (!empty($repoNameMatch)) {
        $repoName = str_replace('[', '', $repoNameMatch[0]);
        $repoName = str_replace(']', '', $repoName);
    }

    if (!empty($repoUrlMatch)) {
        $repoUrl = preg_split('/=/', $repoUrlMatch[0]);
        $repoUrl = trim($repoUrl[1]);
    }

    /**
     *  Only get GPG key if it is a URL
     */
    if (!empty($repoGpgKeyMatch)) {
        $repoGpgKey = preg_split('/=/', $repoGpgKeyMatch[0]);
        $repoGpgKey = trim($repoGpgKey[1]);

        /**
         *  If the GPG key is not an URL, then reset $repoGpgKey to be ignored from importing
         */
        if (!preg_match('#^https?://#', $repoGpgKey)) {
            $repoGpgKey = '';
        }
    }

    echo PHP_EOL . '- Processing file "' . $repoFile . '"' . PHP_EOL;

    if (!empty($repoName) and !empty($repoUrl)) {
        echo '  Importing RPM source repo "' . $repoName . '" into database' . PHP_EOL;

        /**
         *  Check if a source repo with the same name already exists in database
         */
        if ($mysource->exists('rpm', $repoName)) {
            echo '   -> IGNORING: A ' . $repoName . ' source repo already exists in database.' . PHP_EOL;
            continue;
        }

        /**
         *  Importing source repo
         */
        try {
            $mysource->new('rpm', $repoName, $repoUrl, $repoGpgKey);
        } catch (Exception $e) {
            echo '   -> ERROR: while importing source repo into database: ' . $e->getMessage() . PHP_EOL;
        }

        continue;
    }

    echo '  WARNING: Could not import this repo. Repo name or baseurl may have not been found inside the repo file. Please import it manualy from the webUI.' . PHP_EOL;
}
