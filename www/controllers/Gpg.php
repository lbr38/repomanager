<?php

namespace Controllers;

use Exception;

class Gpg
{
    private $length = 4096;
    private $name = 'Repomanager';
    private $description = 'Repomanager GPG signing key';
    private $passphrase = '';

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
    public function getTrustedKeys()
    {
        $knownGpgKeys = array();

        $myprocess = new Process("/usr/bin/gpg --homedir " . GPGHOME . " --no-default-keyring --keyring " . GPGHOME . "/trustedkeys.gpg --list-key --fixed-list-mode --with-colons --with-fingerprint | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
        $myprocess->execute();
        $content = $myprocess->getOutput();
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
                        $knownGpgKeys[] = array('id' => $gpgKeyId, 'name' => $gpgKeyName);

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
        array_multisort(array_column($knownGpgKeys, 'name'), SORT_ASC, $knownGpgKeys);

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
        $this->passphrase = Common::randomStrongString(64);

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
     *  Import a GPG key
     */
    public function import(string $gpgKey)
    {
        $gpgKey = \Controllers\Common::validateData($gpgKey);

        /**
         *  Check if the ASCII text contains invalid characters
         */
        if (!\Controllers\Common::isAlphanum($gpgKey, array('-', '=', '+', '/', ' ', ':', '.', '(', ')', "\n", "\r"))) {
            throw new Exception('ASCII GPG key contains invalid characters');
        }

        /**
         *  Quit if user tries to import a GPG from url
         */
        if (preg_match('#http(s)?://#', $gpgKey)) {
            throw new Exception('GPG key must be specified in ASCII text format');
        }

        /**
         *  Quit if the user tries to import a file on the system
         */
        if (file_exists($gpgKey)) {
            throw new Exception('GPG key must be specified in ASCII text format');
        }

        /**
         *  Create a temporary file with the ASCII text
         */
        $gpgTempFile = TEMP_DIR . '/.repomanager-newgpgkey.tmp';
        if (!file_put_contents($gpgTempFile, $gpgKey)) {
            throw new Exception('Error: could not initialize GPG import');
        }

        /**
         *  Import file into the repomanager trusted keyring
         */
        $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --keyring ' . GPGHOME . '/trustedkeys.gpg --import ' . $gpgTempFile);
        $myprocess->execute();

        /**
         *  Delete temp file
         */
        unlink($gpgTempFile);

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while importing specified GPG key: <br>' . $myprocess->getOutput());
        }

        $myprocess->close();
    }

    /**
     *  Delete a GPG key
     */
    public function delete(string $gpgKeyId)
    {
        $gpgKeyId = \Controllers\Common::validateData($gpgKeyId);

        /**
         *  Deleting key from the keyring, using its ID
         */
        $myprocess = new \Controllers\Process('/usr/bin/gpg --no-default-keyring --homedir ' . GPGHOME . ' --keyring ' . GPGHOME . '/trustedkeys.gpg --no-greeting --delete-key --batch --yes ' . $gpgKeyId);
        $myprocess->execute();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Error while deleting GPG key: <br>' . $myprocess->getOutput());
        }

        $myprocess->close();
    }
}
