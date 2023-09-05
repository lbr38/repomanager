<?php

namespace Controllers\Repo\Operation\Package;

use Exception;

trait Sign
{
    /**
     *  Sign the packages with GPG (RPM only)
     *  Exclusive to rpm packages because with deb it's the Release file that is signed
     */
    private function signPackage()
    {
        $warning = 0;

        ob_start();

        /**
         *  Signature des paquets du repo avec GPG
         *  Redhat seulement car sur Debian c'est le fichier Release qui est signé lors de la création du repo
         */
        if ($this->repo->getPackageType() == 'rpm' and $this->repo->getTargetGpgResign() == 'yes') {
            $this->log->step('SIGNING PACKAGES (GPG)');

            echo '<div class="hide signRepoDiv"><pre>';
            $this->log->steplogWrite();

            /**
             *  Récupération de tous les fichiers RPMs de manière récursive
             */
            $rpmFiles = \Controllers\Common::findRecursive(REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName(), 'rpm', true);
            $totalPackages = count($rpmFiles);
            $packageCounter = 0;

            $signError = 0;

            /**
             *  On traite chaque fichier trouvé
             */
            foreach ($rpmFiles as $rpmFile) {
                /**
                 *  On a besoin d'un fichier de macros gpg, on signe uniquement si le fichier de macros est présent, sinon on retourne une erreur
                 */
                if (!file_exists(MACROS_FILE)) {
                    throw new Exception('GPG macros file for rpm does not exist.');
                }

                if (!file_exists($rpmFile)) {
                    throw new Exception('RPM file ' . $rpmFile . ' not found (deleted?).');
                }

                /**
                 *  Print package counter
                 */
                echo '(' . $packageCounter . '/' . $totalPackages . ')  ➙ ';

                $this->log->steplogWrite();

                /**
                 *  Sign package
                 */
                $myprocess = new \Controllers\Process('/usr/bin/rpmsign --macros=' . MACROS_FILE . ' --addsign ' . $rpmFile, array('GPG_TTY' => '$(tty)'));

                /**
                 *  Exécution
                 */
                $myprocess->setBackground(true);
                $myprocess->execute();

                /**
                 *  Récupération du pid du process lancé
                 *  Puis écriture du pid de rpmsign/rpmresign (lancé par proc_open) dans le fichier PID principal, ceci afin qu'il puisse être killé si l'utilisateur le souhaite
                 */
                // $this->operation->addsubpid($myprocess->getPid());

                /**
                 *  Affichage de l'output du process en continue dans un fichier
                 */
                $myprocess->getOutput($this->log->getStepLog());

                /**
                 *  Si la signature du paquet en cours s'est mal terminée, on incrémente $signError pour
                 *  indiquer une erreur et on sort de la boucle pour ne pas traiter le paquet suivant
                 */
                if ($myprocess->getExitCode() != 0) {
                    $signError++;
                    break;
                }

                $myprocess->close();

                $packageCounter++;
            }
            echo '</pre></div>';

            $this->log->steplogWrite();

            /**
             *  A vérifier car depuis l'écriture de la class Process, les erreurs semblent mieux gérées :
             *
             *  Si il y a un pb lors de la signature, celui-ci renvoie systématiquement le code 0 même si il est en erreur.
             *  Du coup on vérifie directement dans l'output du programme qu'il n'y a pas eu de message d'erreur et si c'est le cas alors on incrémente $return
             */
            $noSecretKeyError = 0;
            $gpgError = 0;
            $canNotResignError = 0;
            $signErrorGlobalMessage = '';

            if (preg_match('/gpg: signing failed/', file_get_contents($this->log->getStepLog()), $matchErrorList)) {
                $signError++;
                if (!empty($matchErrorList)) {
                    foreach ($matchErrorList as $matchError) {
                        $signErrorGlobalMessage .= $matchError . '<br>';
                    }
                }
            }
            if (preg_match('/No secret key/', file_get_contents($this->log->getStepLog()), $matchErrorList)) {
                $noSecretKeyError++;
                if (!empty($matchErrorList)) {
                    foreach ($matchErrorList as $matchError) {
                        $signErrorGlobalMessage .= $matchError . '<br>';
                    }
                }
            }
            if (preg_match('/error: gpg/', file_get_contents($this->log->getStepLog()), $matchErrorList)) {
                $gpgError++;
                if (!empty($matchErrorList)) {
                    foreach ($matchErrorList as $matchError) {
                        $signErrorGlobalMessage .= $matchError . '<br>';
                    }
                }
            }
            if (preg_match("/Can't resign/", file_get_contents($this->log->getStepLog()), $matchErrorList)) {
                $canNotResignError++;
                if (!empty($matchErrorList)) {
                    foreach ($matchErrorList as $matchError) {
                        $signErrorGlobalMessage .= $matchError . '<br>';
                    }
                }
            }
            /**
             *  Cas particulier, on affichera un warning si le message suivant a été détecté dans les logs
             */
            if (preg_match("/gpg: WARNING:/", file_get_contents($this->log->getStepLog()))) {
                ++$warning;
            }
            if (preg_match("/warning:/", file_get_contents($this->log->getStepLog()))) {
                ++$warning;
            }

            if ($warning != 0) {
                $this->log->stepWarning();
            }

            if ($signError != 0) {
                /**
                 *  Si l'action est reconstruct alors on ne supprime pas ce qui a été fait (sinon ça supprime le repo!)
                 */
                if ($this->operation->getAction() != "reconstruct") {
                    /**
                     *  Suppression de ce qui a été fait :
                     */
                    exec('rm -rf "' . REPOS_DIR . '/' . $this->repo->getTargetDateFormatted() . '_' . $this->repo->getName() . '"');
                }

                if (!empty($signErrorGlobalMessage)) {
                    throw new Exception('packages signature has failed: ' . $signErrorGlobalMessage);
                } else {
                    throw new Exception('packages signature has failed');
                }
            }

            $this->log->stepOK();
        }

        // return true;
    }
}
