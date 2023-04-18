<?php

namespace Controllers;

use Exception;

class Gpg
{
    private $length = 4096;
    private $name = 'Repomanager';
    private $description = 'Repomanager GPG signing key';
    private $keyId = GPG_SIGNING_KEYID;
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
            if (!mkdir(GPGHOME, 0770, true)) {
                throw new Exception('Cannot create GPG directory: ' . GPGHOME);
            }
        }

        /**
         *  'private-keys-v1.d' directory must exists or gnupg will print warning
         */
        if (!is_dir(GPGHOME . '/private-keys-v1.d')) {
            if (!mkdir(GPGHOME . '/private-keys-v1.d', 0770, true)) {
                throw new Exception('Cannot create directory: ' . GPGHOME . '/private-keys-v1.d');
            }
        }

        /**
         *  Create GPG pubkey export directory
         */
        if (!is_dir(REPOS_DIR . '/gpgkeys')) {
            mkdir(REPOS_DIR . '/gpgkeys', 0770, true);
        }

        $this->generateSigningKey();
        $this->exportSigningKey();
        $this->generateRpmMacros();

        /**
         *  Additionnal configuration for reprepro
         */
        if (!file_exists(GPGHOME . '/gpg.conf')) {
            /**
             *  Configure gpg.conf (pinentry-mode loopback) only on non-RHEL systems and on RHEL >7 systems
             */
            if (strtoupper(OS_FAMILY) != 'REDHAT' or (strtoupper(OS_FAMILY) == 'REDHAT' and OS_VERSION > '7')) {
                if (!file_put_contents(GPGHOME . '/gpg.conf', 'pinentry-mode loopback' . PHP_EOL . 'passphrase-file ' . PASSPHRASE_FILE)) {
                    throw new Exception('Cannot write to: ' . GPGHOME . '/gpg.conf');
                }
            }
        }
    }

    /**
     *  Generate GPG signing key
     */
    public function generateSigningKey()
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
        $template .= 'Name-Email: ' . $this->keyId . PHP_EOL;
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
            throw new Exception('Cannot write to: ' . PASSPHRASE_FILE);
        }

        unset($template, $myprocess);
    }

    /**
     *  Export GPG signing key
     */
    public function exportSigningKey()
    {
        /**
         *  If file exists but is empty, delete it
         */
        if (filesize(REPOS_DIR . '/gpgkeys/' . WWW_HOSTNAME . '.pub') == 0) {
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
    public function generateRpmMacros()
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
            $configuration .= '%_gpg_name ' . $this->keyId . PHP_EOL;
            $configuration .= '%_gpg_passphrase_file ' . PASSPHRASE_FILE . PHP_EOL;
            $configuration .= '%__gpg_sign_cmd %{__gpg} gpg --no-verbose --no-armor --batch --pinentry-mode loopback --passphrase-file %{_gpg_passphrase_file} %{?_gpg_digest_algo:--digest-algo %{_gpg_digest_algo}} --no-secmem-warning -u "%{_gpg_name}" -sbo %{__signature_filename} %{__plaintext_filename}';

            if (!file_put_contents(MACROS_FILE, $configuration)) {
                throw new Exception('Cannot write to ' . MACROS_FILE);
            }
        }
    }
}
