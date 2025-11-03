<?php

namespace Controllers;

use Exception;
use \Controllers\Utils\Validate;

class Gpg
{
    private $length = 4096;
    private $name = 'Repomanager';
    private $description = 'Repomanager GPG signing key';
    private $passphrase = '';
    private $httpRequestController;

    public function __construct()
    {
        $this->httpRequestController = new \Controllers\HttpRequest();
    }

    /**
     *  Proceed full GPG configuration
     */
    public function init()
    {
        /**
         *  Create GPGHOME directory if not exists
         */
        if (!is_dir(GPGHOME)) {
            if (!mkdir(GPGHOME, 0700, true)) {
                throw new Exception('Cannot create GPG directory: ' . GPGHOME);
            }
        }

        /**
         *  'private-keys-v1.d' directory must exists or gnupg will print warning
         */
        if (!is_dir(GPGHOME . '/private-keys-v1.d')) {
            if (!mkdir(GPGHOME . '/private-keys-v1.d', 0700, true)) {
                throw new Exception('Cannot create directory: ' . GPGHOME . '/private-keys-v1.d');
            }
        }

        /**
         *  Create GPG pubkey export directory
         */
        if (!is_dir(REPOS_DIR . '/gpgkeys')) {
            if (!mkdir(REPOS_DIR . '/gpgkeys', 0700, true)) {
                throw new Exception('Cannot create directory: ' . REPOS_DIR . '/gpgkeys');
            }
        }

        $this->generateTrustedKeys();
        $this->generateSigningKey();
        $this->exportSigningKey();
        $this->generateRpmMacros();

        /**
         *  Additionnal configuration for gpg
         */
        if (!file_exists(GPGHOME . '/gpg.conf')) {
            /**
             *  Configure gpg.conf (pinentry-mode loopback)
             */
            if (!file_put_contents(GPGHOME . '/gpg.conf', 'pinentry-mode loopback' . PHP_EOL . 'passphrase-file ' . PASSPHRASE_FILE)) {
                throw new Exception('Cannot write to: ' . GPGHOME . '/gpg.conf');
            }
        }
    }

    /**
     *  Generate trustedkeys.gpg file if not exists
     */
    private function generateTrustedKeys()
    {
        if (file_exists(GPGHOME . '/trustedkeys.gpg')) {
            return;
        }

        $myprocess = new Process("/usr/bin/gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --fingerprint");
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception(GPGHOME . "/trustedkeys.gpg file generation has failed: " . $content);
        }
    }

    /**
     *  Return an array with all editors GPG pub keys that were imported into repomanager keyring
     */
    public static function getTrustedKeys()
    {
        $knownGpgKeys = [];

        $myprocess = new Process("/usr/bin/gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --list-key --fixed-list-mode --with-colons --with-fingerprint | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
        $myprocess->execute();
        $content = trim($myprocess->getOutput());
        $myprocess->close();

        /**
         *  Parsing retrieved content
         */
        if (!empty($content)) {
            $gpgKeys = explode(PHP_EOL.PHP_EOL, $content);

            foreach ($gpgKeys as $gpgKey) {
                $gpgKeyId = '';
                $gpgKeyName = '';
                $gpgKey = explode(PHP_EOL, $gpgKey);

                foreach ($gpgKey as $gpgKeyRow) {
                    /**
                     *  Get GPG key Id from fpr row
                     */
                    if (preg_match('/^fpr:/', $gpgKeyRow)) {
                        $gpgKeyId = preg_split('/:/', $gpgKeyRow);
                        $gpgKeyId = trim($gpgKeyId[9]);
                    }

                    /**
                     *  Retrieve GPG key name from uid row
                     */
                    if (preg_match('/^uid:/', $gpgKeyRow)) {
                        $gpgKeyName = preg_split('/:/', $gpgKeyRow);
                        $gpgKeyName = trim($gpgKeyName[9]);
                    }

                    /**
                     *  If both name and Id have been found, had them to the global array
                     */
                    if (!empty($gpgKeyId) and !empty($gpgKeyName)) {
                        $knownGpgKeys[] = ['id' => $gpgKeyId, 'name' => $gpgKeyName];

                        /**
                         *  Only reset Id because a key can have one name (uid) and multiple Id, so do not reset the name until the next key
                         */
                        $gpgKeyId = '';
                    }
                }
            }
        }

        unset($content, $gpgKeys);

        /**
         *  Sort keys array by name
         */
        array_multisort(array_column($knownGpgKeys, 'name'), SORT_ASC|SORT_NATURAL|SORT_FLAG_CASE, $knownGpgKeys);

        return $knownGpgKeys;
    }

    /**
     *  Generate GPG signing key
     */
    private function generateSigningKey()
    {
        /**
         *  Quit if key already exists
         */
        if (file_exists(GPGHOME . '/pubring.gpg') or file_exists(GPGHOME . '/pubring.kbx')) {
            return;
        }

        /**
         *  Quit if no key Id is specified
         */
        if (empty(GPG_SIGNING_KEYID)) {
            throw new Exception('Cannot generate GPG signing key without specified key Id.');
        }

        /**
         *  Generate random passphrase
         */
        $this->passphrase = \Controllers\Utils\Random::strongString(64);

        /**
         *  Generate template
         */
        $template  = 'Key-Type: RSA' . PHP_EOL;
        $template .= 'Key-Length: ' . $this->length . PHP_EOL;
        $template .= 'Key-Usage: sign' . PHP_EOL;
        $template .= 'Name-Real: ' . $this->name . PHP_EOL;
        $template .= 'Name-Comment: ' . $this->description . PHP_EOL;
        $template .= 'Name-Email: ' . GPG_SIGNING_KEYID . PHP_EOL;
        $template .= 'Expire-Date: 0' . PHP_EOL;
        $template .= 'Passphrase: ' . $this->passphrase . PHP_EOL;

        if (!file_put_contents(TEMP_DIR . '/gpg-template-file', $template)) {
            throw new Exception('Cannot write to: ' . TEMP_DIR . '/gpg-template-file');
        }

        /**
         *  Generate key
         */
        $myprocess = new Process('gpg2 --batch --gen-key --homedir ' . GPGHOME . ' --no-permission-warning ' . TEMP_DIR . '/gpg-template-file');
        $myprocess->execute();
        $content = $myprocess->getOutput();
        $myprocess->close();

        /**
         *  If key generation failed, remove pubring files
         */
        if ($myprocess->getExitCode() != 0) {
            if (file_exists(GPGHOME . '/pubring.gpg')) {
                unlink(GPGHOME . '/pubring.gpg');
            }
            if (file_exists(GPGHOME . '/pubring.kbx')) {
                unlink(GPGHOME . '/pubring.kbx');
            }

            throw new Exception("GPG signing key generation failed: " . $content);
        }

        /**
         *  Write passphrase to file
         */
        if (!file_put_contents(PASSPHRASE_FILE, $this->passphrase)) {
            throw new Exception('Cannot write passphrase to: ' . PASSPHRASE_FILE);
        }

        unset($template, $myprocess);
    }

    /**
     *  Export GPG signing key
     */
    private function exportSigningKey()
    {
        /**
         *  If file exists but is empty, delete it
         */
        if (file_exists(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub') && filesize(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub') == 0) {
            unlink(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub');
        }

        if (!file_exists(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub') and !empty(GPG_SIGNING_KEYID)) {
            $myprocess = new Process("/usr/bin/gpg2 --no-permission-warning --homedir '" . GPGHOME . "' --export -a '" . GPG_SIGNING_KEYID . "' > " . REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub 2>/dev/null');
            $myprocess->execute();
            $content = $myprocess->getOutput();
            $myprocess->close();

            /**
             *  If pubkey export failed, delete file
             */
            if ($myprocess->getExitCode() != 0) {
                if (file_exists(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub')) {
                    unlink(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub');
                }

                throw new Exception("GPG pubkey export failed: " . $content);
            }
        }
    }

    /**
     *  Generate GPG macros file for RPM
     */
    private function generateRpmMacros()
    {
        if (!is_dir(DATA_DIR . '/.rpm')) {
            if (!mkdir(DATA_DIR . '/.rpm', 0770, true)) {
                throw new Exception('Cannot create directory: ' . DATA_DIR . '/.rpm');
            }
        }

        /**
         *  Additionnal configuration for GPG
         */
        if (!file_exists(MACROS_FILE)) {
            $configuration = '%__gpg /usr/bin/gpg2' . PHP_EOL;
            $configuration .= '%_gpg_path ' . GPGHOME . PHP_EOL;
            $configuration .= '%_gpg_name ' . GPG_SIGNING_KEYID . PHP_EOL;
            $configuration .= '%_gpg_passphrase_file ' . PASSPHRASE_FILE . PHP_EOL;
            $configuration .= '%__gpg_sign_cmd %{__gpg} gpg --no-verbose --no-armor --batch --pinentry-mode loopback --passphrase-file %{_gpg_passphrase_file} %{?_gpg_digest_algo:--digest-algo %{_gpg_digest_algo}} --no-secmem-warning -u "%{_gpg_name}" -sbo %{__signature_filename} %{__plaintext_filename}';

            if (!file_put_contents(MACROS_FILE, $configuration)) {
                throw new Exception('Cannot write to ' . MACROS_FILE);
            }
        }
    }

    /**
     *  Import a GPG key from URL, fingerprint or plain text
     *  Return an array with all fingerprints found in the GPG key
     */
    public function import(string $gpgKeyUrl, string $gpgKeyFingerprint, string $gpgKeyPlainText)
    {
        $gpgKeyUrl = Validate::string($gpgKeyUrl);
        $gpgKeyFingerprint = Validate::string($gpgKeyFingerprint);
        $gpgKeyPlainText = Validate::string($gpgKeyPlainText);

        /**
         *  If more than one parameter is specified, quit
         */
        if (!empty($gpgKeyUrl) and !empty($gpgKeyFingerprint)) {
            throw new Exception('Cannot import GPG key from URL and fingerprint at the same time');
        }
        if (!empty($gpgKeyUrl) and !empty($gpgKeyPlainText)) {
            throw new Exception('Cannot import GPG key from URL and plain text at the same time');
        }
        if (!empty($gpgKeyFingerprint) and !empty($gpgKeyPlainText)) {
            throw new Exception('Cannot import GPG key from fingerprint and plain text at the same time');
        }

        /**
         *  If gpg key starts with http(s):// then it is a link
         *  Otherwise it is a fingerprint
         */
        if (!empty($gpgKeyUrl) and !preg_match('#^http(s)?://#', $gpgKeyUrl)) {
            throw new Exception('Invalid URL');
        }

        /**
         *  If the user specified a URL in the fingerprint field, quit
         */
        if (!empty($gpgKeyFingerprint) and preg_match('#^http(s)?://#', $gpgKeyFingerprint)) {
            throw new Exception('Invalid fingerprint');
        }

        try {
            /**
             *  Import GPG key from URL
             */
            if (!empty($gpgKeyUrl)) {
                $fingerprints = $this->importFromUrl($gpgKeyUrl);
            }

            /**
             *  Import GPG key from fingerprint
             */
            if (!empty($gpgKeyFingerprint)) {
                $fingerprints = $this->importFromUrl('https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x' . $gpgKeyFingerprint);
            }

            /**
             *  Import GPG key from plain text
             */
            if (!empty($gpgKeyPlainText)) {
                $fingerprints = $this->importPlainText($gpgKeyPlainText);
            }

            if (empty($fingerprints)) {
                throw new Exception('no fingerprints found in the GPG key');
            }
        } catch (Exception $e) {
            throw new Exception('Error while importing GPG key: ' . $e->getMessage());
        }

        return $fingerprints;
    }

    /**
     *  Import a file-based GPG key
     */
    private function importRawContent(string $fileContent) : array
    {
        /**
         *  Quit if user tries to import a GPG from url
         */
        if (preg_match('#http(s)?://#', $fileContent)) {
            throw new Exception('GPG key must be specified in ASCII text format');
        }

        /**
         *  Quit if the user tries to import a file on the system
         */
        if (file_exists($fileContent)) {
            throw new Exception('GPG key must be specified in ASCII text format');
        }

        /**
         *  Create a temporary file with the ASCII text
         */
        $gpgTempFile = TEMP_DIR . '/.repomanager-newgpgkey.tmp';
        if (!file_put_contents($gpgTempFile, $fileContent)) {
            throw new Exception('could not initialize GPG import');
        }

        try {
            /**
             *  First, extract the fingerprints from the GPG key (there could be one or multiple)
             */
            $myprocess = new Process("/usr/bin/gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --show-keys --with-fingerprint --with-colons " . $gpgTempFile . " | grep '^fpr' | cut -d: -f10");
            $myprocess->execute();
            $content = $myprocess->getOutput();
            $myprocess->close();

            if ($myprocess->getExitCode() != 0) {
                throw new Exception('could not retrieve fingerprint(s) from GPG key: <br><br><pre class="codeblock">"' . $content . '</pre>');
            }

            /**
             *  Output will print all fingerprints on multiple lines (one per fingerprint)
             */
            $fingerprints = explode(PHP_EOL, $content);

            /**
             *  Import file into the repomanager trusted keyring
             */
            $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --keyring ' . GPGHOME . '/trustedkeys.gpg --import ' . $gpgTempFile);
            $myprocess->execute();
            $content = $myprocess->getOutput();
            $myprocess->close();

            if ($myprocess->getExitCode() != 0) {
                throw new Exception('<pre class="codeblock margin-top-5">' . $content . '</pre>');
            }

            return $fingerprints;
        } finally {
            /**
             *  Delete temp file
             */
            if (!unlink($gpgTempFile)) {
                throw new Exception('cannot delete temporary file: ' . $gpgTempFile);
            }
        }
    }

    /**
     *  Import a plain text GPG key
     */
    public function importPlainText(string $gpgKey) : array
    {
        $gpgKey = Validate::string($gpgKey);

        /**
         *  Check if the ASCII text contains invalid characters
         */
        if (!Validate::alphaNumeric($gpgKey, ['-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"])) {
            throw new Exception('ASCII GPG key contains invalid characters');
        }

        return $this->importRawContent($gpgKey);
    }

    /**
     *  Import a key from a URL
     *  Return an array with all fingerprints found in the GPG key
     */
    public function importFromUrl(string $url) : array
    {
        /**
         *  Quit if the URL is not valid
         */
        if (!preg_match('#http(s)?://#', $url)) {
            throw new Exception('Invalid URL');
        }

        // Check if the URL is reachable
        try {
            $this->httpRequestController->get([
                'url' => $url,
                'connectTimeout' => 5,
                'proxy' => PROXY ?? null,
            ]);
        } catch (Exception $e) {
            throw new Exception('URL ' . $url . ' is not reachable: ' . $e->getMessage());
        }

        // Download GPG key
        try {
            $output = $this->httpRequestController->get([
                'url'            => $url,
                'connectTimeout' => 5,
                'timeout'        => 10,
                'proxy'          => PROXY ?? null,
            ]);

            if (empty($output)) {
                throw new Exception('empty gpg key response (downloaded file is empty)');
            }
        } catch (Exception $e) {
            throw new Exception('error while downloading GPG key: ' . $e->getMessage());
        }

        /**
         *  Import GPG key
         */
        return $this->importRawContent($output);
    }

    /**
     *  Delete a GPG key
     */
    public function delete(array $gpgKeysIds) : void
    {
        $idErrors = [];

        foreach ($gpgKeysIds as $id) {
            // Deleting key from the keyring, using its ID
            $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --homedir ' . GPGHOME . ' --keyring ' . GPGHOME . '/trustedkeys.gpg --no-greeting --delete-key --batch --yes ' . Validate::string($id));
            $myprocess->execute();

            if ($myprocess->getExitCode() != 0) {
                $idErrors[] = $id;
            }

            $myprocess->close();
        }

        if (!empty($idErrors)) {
            throw new Exception('Error while deleting GPG key(s): ' . implode(', ', $idErrors));
        }
    }
}
