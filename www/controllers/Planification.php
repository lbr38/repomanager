<?php

namespace Controllers;

use Exception;

class Planification
{
    private $id;
    private $model;
    private $action;
    private $validActions = array('update');
    private $targetGpgCheck;
    private $targetGpgResign;
    private $onlySyncDifference;
    private $status;
    private $error;
    private $logfile;
    private $type;
    private $day = null;
    private $date = null;
    private $time = null;
    private $frequency = null;
    private $snapId = null;
    private $groupId = null;
    private $targetEnv = null;
    private $reminder = null;
    private $mailRecipient = null;
    private $notificationOnSuccess;
    private $notificationOnError;
    private $logList = array();

    private $log;
    private $repo;
    private $operation;
    private $group;

    public function __construct()
    {
        $this->model = new \Models\Planification();
    }

    public function setId(string $id)
    {
        $this->id = $id;
    }

    public function setDay(array $days)
    {
        $planDay = '';

        /**
         *  On sépare chaque jour spécifié par une virgule
         */
        foreach ($days as $day) {
            $planDay .= \Controllers\Common::validateData($day) . ',';
        }

        /**
         *  Suppression de la dernière virgule
         */
        $this->day = rtrim($planDay, ",");
    }

    public function setDate(string $date)
    {
        $this->date = $date;
    }

    public function setTime(string $time)
    {
        $this->time = $time;
    }

    public function setType(string $type)
    {
        $this->type = $type;
    }

    public function setFrequency(string $frequency)
    {
        $this->frequency = $frequency;
    }

    public function setAction(string $action)
    {
        $this->action = $action;
    }

    public function setMailRecipient(array $mailRecipients)
    {
        foreach ($mailRecipients as $mail) {
            if (!\Controllers\Common::validateMail(\Controllers\Common::validateData($mail))) {
                throw new Exception('Invalid email address format for ' . $mail);
            }
        }

        $this->mailRecipient = implode(',', $mailRecipients);
    }

    public function setReminder($reminders)
    {
        /**
         *  Si la planification est de type 'regular' (planification récurrente) et que la fréquence est "every-day" ou "every-hour" alors on ne set pas de rappel
         */
        if ($this->type == 'regular' and ($this->frequency == "every-day" or $this->frequency == "every-hour")) {
            return;
        }

        $planReminder = '';

        /**
         *  Si $reminders est un array
         */
        if (is_array($reminders)) {
            /**
             *  On sépare chaque jour de rappel par une virgule
             */
            foreach ($reminders as $reminder) {
                $planReminder .= \Controllers\Common::validateData($reminder) . ',';
            }
        }

        /**
         *  Suppression de la dernière virgule
         */
        $this->reminder = rtrim($planReminder, ",");
    }

    public function setNotification(string $type, string $state)
    {
        if ($type == "on-error") {
            $this->notificationOnError = $state;
        }

        if ($type == "on-success") {
            $this->notificationOnSuccess = $state;
        }
    }

    public function setTargetGpgCheck(string $state)
    {
        $this->targetGpgCheck = \Controllers\Common::validateData($state);
    }

    public function setTargetGpgResign(string $state)
    {
        $this->targetGpgResign = $state;
    }

    public function setOnlySyncDifference(string $state)
    {
        $this->onlySyncDifference = $state;
    }

    public function setTargetEnv(string $targetEnv)
    {
        $this->targetEnv = $targetEnv;
    }

    public function setSnapId(string $id)
    {
        $this->snapId = $id;
    }

    public function setGroupId(string $id)
    {
        $this->groupId = $id;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getMailRecipient()
    {
        return $this->mailRecipient;
    }

    public function getMailRecipientFormatted()
    {
        /**
         *  Lorsque plusieurs adresses mail sont resneignées, on formatte le résultat retourné pour un meilleur affichage et sans virgule séparant les adresses
         *  Si une seule adresse mail est renseignée, on ne fait rien
         */
        if (preg_match('/,/', $this->mailRecipient)) {
            $mailRecipientFormatted = '';

            $mailRecipient = explode(',', $this->mailRecipient);

            foreach ($mailRecipient as $mail) {
                $mailRecipientFormatted .= $mail . '<br>';
            }

            /**
             *  Suppression du dernier saut de ligne
             */
            $mailRecipientFormatted = rtrim($mailRecipientFormatted, '<br>');

            return $mailRecipientFormatted;
        }

        return $this->mailRecipient;
    }

    /**
     *  Return planification info, by Id
     */
    public function get(string $id)
    {
        return $this->model->get($id);
    }

    /**
     *  Création d'une nouvelle planification
     */
    public function new()
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to create a planification');
        }

        $myrepo = new \Controllers\Repo\Repo();
        $mygroup = new \Controllers\Group('repo');
        $myenv = new \Controllers\Environment();

        /**
         *  Vérification du type
         */
        if (empty($this->type)) {
            throw new Exception('Type must be specified');
        }

        /**
         *  Vérification de la fréquence si il s'agit d'une tâche récurrente
         */
        if ($this->type == "regular" and empty($this->frequency)) {
            throw new Exception('Frequency must be specified');
        }

        /**
         *  Vérification du/des jour(s) dans le cas où il s'agit d'une planification récurrente "toutes les semaines"
         */
        if ($this->type == "regular" and $this->frequency == "every-week" and empty($this->day)) {
            throw new Exception('Weekday(s) must be specified');
        }

        /**
         *  Vérification de la date (dans le cas où il s'agit d'une planification)
         */
        if ($this->type == 'plan' and empty($this->date)) {
            throw new Exception('Date must be specified');
        }

        /**
         *  Vérification de l'heure (dans le cas où il s'agit d'une planification ou d'une tâche récurrente "tous les jours" ou "toutes les semaines")
         */
        if ($this->type == 'plan' or ($this->type == 'regular' and $this->frequency == 'every-day') or ($this->type == 'regular' and $this->frequency == 'every-week')) {
            if (empty($this->time)) {
                throw new Exception('Time must be specified');
            }
        }

        /**
         *  Vérification de l'action
         */
        if (empty($this->action)) {
            throw new Exception('Action must be specified');
        }

        if ($this->action != 'update' and !preg_match('/->/', $this->action)) {
            throw new Exception('Action is invalid');
        }

        /**
         *  Si l'action contient un '->' on vérifie que les environnements existent
         */
        if (preg_match('/->/', $this->action)) {
            /**
             *  On récupère chacun des environnement pour vérifier si ils existent
             */
            $envs = explode('->', $this->action);

            foreach ($envs as $env) {
                if ($myenv->exists($env) === false) {
                    throw new Exception('Unknown environment ' . $env);
                }
            }
        }

        /**
         *  Si aucun repo et aucun groupe n'a été renseigné alors on quitte
         */
        if (empty($this->snapId) and empty($this->groupId)) {
            throw new Exception('A repository snapshot or a group must be specified');
        }

        /**
         *  Si un repo ET un groupe ont été renseignés alors on quitte
         */
        if (!empty($this->snapId) and !empty($this->groupId)) {
            throw new Exception('You can only specify a repository snapshot OR a group');
        }

        /**
         *  Cas où on ajoute un Id de snaphsot
         */
        if (!empty($this->snapId)) {
            /**
             *  On vérifie que l'Id de snapshot renseigné existe
             */
            $myrepo->setSnapId($this->snapId);

            if ($myrepo->existsSnapId($this->snapId) === false) {
                throw new Exception('Specified repository snapshot does not exist');
            }
        }

        /**
         *  Cas où on ajoute un Id de groupe
         */
        if (!empty($this->groupId)) {
            /**
             *  On vérifie que l'Id de groupe renseigné existe
             */
            if ($mygroup->existsId($this->groupId) === false) {
                throw new Exception('Specified group does not exist');
            }
        }

        /**
         *  Cas où on souhaite faire pointer un environnement
         */
        if (!empty($this->targetEnv)) {
            if ($myenv->exists($this->targetEnv) === false) {
                throw new Exception('Environment ' . $this->targetEnv . ' does not exist');
            }
        }

        /**
         *  On vérifie que la/les adresses renseignées sont valides
         *  Si la chaine contient une virgule alors il y a plusieurs adresses renseignées
         *  On va devoir exploser la chaine pour pouvoir tester chaque adresse mail, puis reconstruire la chaine en ne conservant que les adresses valides
         */
        if (!empty($this->mailRecipient)) {
            if (preg_match('/,/', $this->mailRecipient)) {
                $mailRecipientTest = explode(',', $this->mailRecipient);

                /**
                 *  On vérifie que chaque adresse mail est valide
                 */
                foreach ($mailRecipientTest as $mail) {
                    if (\Controllers\Common::validateMail(\Controllers\Common::validateData($mail)) === false) {
                        throw new Exception('Invalid email address ' . $mail);
                    }
                }

            /**
             *  Cas où 1 seule adresse mail a été renseignée
             */
            } else {
                if (\Controllers\Common::validateMail(\Controllers\Common::validateData($this->mailRecipient)) === false) {
                    throw new Exception('Invalid email address ' . $mail);
                }
            }
        } else {
            /**
             *  Si aucune adresse mail de contact n'a été fournie, on passe les paramètres de notifications à 'no'
             */
            $this->setNotification('on-error', 'no');
            $this->setNotification('on-success', 'no');
        }

        /**
         *  Insertion en base de données
         */
        $this->model->add(
            $this->type,
            $this->frequency,
            $this->day,
            $this->date,
            $this->time,
            $this->action,
            $this->snapId,
            $this->groupId,
            $this->targetEnv,
            $this->targetGpgCheck,
            $this->targetGpgResign,
            $this->onlySyncDifference,
            $this->notificationOnError,
            $this->notificationOnSuccess,
            $this->mailRecipient,
            $this->reminder
        );

        unset($myrepo, $mygroup, $myenv);
    }

    /**
     *  Suppression d'une planification
     */
    public function remove(string $planId)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to remove a planification');
        }

        $this->model->setStatus($planId, 'canceled');
    }

    /**
     *  Disable recurrent plan
     */
    public function suspend(string $planId)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to suspend a planification');
        }

        $this->model->setStatus($planId, 'disabled');
    }

    /**
     *  Enable recurrent plan
     */
    public function enable(string $planId)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to enable a planification');
        }

        $this->model->setStatus($planId, 'queued');
    }

    /**
     *  Exécution d'une planification
     */
    public function exec()
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->group = new \Controllers\Group('repo');

        /**
         *  On vérifie que l'Id de planification spécifié existe en base de données
         */
        if ($this->model->existsId($this->id) === false) {
            throw new Exception('Task id ' . $this->id . 'does not exist');
        }

        /**
         *  On vérifie que la planification n'est pas déjà en cours d'exécution (eg. si quelqu'un l'a lancé en avance avec le paramètre exec-now)
         */
        if ($this->model->getStatus($this->id) == 'running') {
            throw new Exception('This task is already running');
        }

        /**
         *  On génère un nouveau log pour cette planification
         *  Ce log général reprendra tous les sous-logs de chaque opération lancée par cette planification.
         */
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('plan', $this->operation->getPid());

        /**
         *  Passe le status de la planification à "running", jusqu'à maintenant le status était "queued"
         */
        $this->model->setStatus($this->id, 'running');

        /**
         *  0. Démarre l'enregistrement de l'output de la planification
         */
        ob_start();

        try {
            // VERIFICATIONS //

            /**
             *  3. Récupération des détails de la planification en cours d'exécution, afin de savoir quels
             *  repos ou quel groupe sont impliqués et quelle action effectuer
             */
            $this->getInfo($this->id);

            /**
             *  4. Vérification de l'action renseignée
             */
            $this->paramCheck();

        /**
         *  Clôture du try/catch de la partie Vérifications
         *  Si une erreur est catché alors on sort de la planification
         */
        } catch (\Exception $e) {
            $this->close(1, 'Scheduled task check error: ' . $e->getMessage());
            return;
        }

        /**
         *
         *  Execution
         *
         */

        /**
         *  On placera dans ce tableau les repos qui ont été traités par cette planification.
         */
        $processedRepos = array();

        /**
         *  1. Cas où on traite 1 repo seulement
         */
        if (!empty($this->repo->getName()) and empty($this->group->getName())) {
            /**
             *  Si l'action de planification == update
             */
            if ($this->action == 'update') {
                $operationParams = array(
                    'action' => 'update',
                    'planId' => $this->id,
                    'snapId' => $this->repo->getSnapId(),
                    'targetGpgCheck' => $this->targetGpgCheck,
                    'targetGpgResign' => $this->targetGpgResign,
                    'targetEnv' => $this->targetEnv,
                    'targetArch' => $this->repo->getArch(),
                    'onlySyncDifference' => $this->onlySyncDifference
                );

                try {
                    $updateController = new \Controllers\Repo\Operation\Update('0000', $operationParams);
                    $updateController->execute();
                    $status = 'done';
                } catch (\Exception $e) {
                    $status = 'error';
                    $error = $e->getMessage();
                }

                /**
                 *  Retrieve operation log location
                 */
                $logLocation = $updateController->log->getLocation();

                /**
                 *  On ajoute le repo à la liste des repo traités par cette planification
                 */
                if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                    $processedRepos[] = array('Repo' => $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection(), 'Status' => $status, 'Log' => $logLocation);
                } else {
                    $processedRepos[] = array('Repo' => $this->repo->getName(), 'Status' => $status, 'Log' => $logLocation);
                }

                /**
                 *  Si l'opération est en erreur, on quitte avec un message d'erreur
                 */
                if ($status == 'error') {
                    $this->close(2, 'An error occured while processing this repo: ' . $error, $processedRepos);
                    return;
                }
            }
        }

        /**
         *  2. Cas où on traite un groupe de repos
         */
        if (!empty($this->group->getName())) {
            /**
             *  Comme on boucle pour traiter plusieurs repos, on ne peut pas tout quitter en cas d'erreur tant qu'on a pas bouclé sur tous les repos.
             *  Du coup on initialise une variable qu'on incrémentera en cas d'erreur.
             *  A la fin si cette variable > 0 alors on pourra quitter ce script en erreur ($this->close 1)
             */
            $groupError = 0;

            /**
             *  On placera dans ce tableau les repos qui ont été traités par cette planification.
             */
            $processedRepos = array();

            /**
             *  Traitement
             */

            /**
             *  On récupère la liste des repos dans ce groupe
             */
            $groupReposList = $this->getGroupRepoList();

            /**
             *  On traite chaque ligne de groupList
             */
            foreach ($groupReposList as $repo) {
                /**
                 *  On (re)instancie le repo à chaque boucle afin qu'il soit bien initialisé
                 */
                $this->repo = new \Controllers\Repo\Repo();

                /**
                 *  On ne connait que l'Id du repo mais pas les informations concernant ses snapshots.
                 *  Or la fonction update() de Repo() a besoin de connaitre un Id de snapshot à mettre à jour.
                 *  On va donc lui donner l'Id du snapshot le + récent du repo
                 */
                $repoId = $repo['repoId'];

                /**
                 *  Récupération de l'Id du snapshot le + récent
                 */
                $mostRecentSnapId = $this->repo->getLastSnapshotId($repoId);

                /**
                 *  On peut récupérer toutes les informations du repo à partir de l'Id de repo et de l'Id de snapshot
                 */
                $this->repo->getAllById($repoId, $mostRecentSnapId, '');

                /**
                 *  Si le snapshot de repo est de type 'local' alors on passe au repo suivant
                 */
                if ($this->repo->getType() == 'local') {
                    continue;
                }

                /**
                 *  Si l'action de planification == update
                 */
                if ($this->action == 'update') {
                    $operationParams = array(
                        'action' => 'update',
                        'planId' => $this->id,
                        'snapId' => $this->repo->getSnapId(),
                        'targetGpgCheck' => $this->targetGpgCheck,
                        'targetGpgResign' => $this->targetGpgResign,
                        'targetEnv' => $this->targetEnv,
                        'targetArch' => $this->repo->getArch(),
                        'onlySyncDifference' => $this->onlySyncDifference
                    );

                    try {
                        $updateController = new \Controllers\Repo\Operation\Update('0000', $operationParams);
                        $updateController->execute();
                        $status = 'done';
                    } catch (\Exception $e) {
                        $status = 'error';
                        $groupError++;
                    }

                    /**
                     *  Retrieve operation log location
                     */
                    $logLocation = $updateController->log->getLocation();
                }

                /**
                 *  On ajoute le repo et son status (error ou done) à la liste des repo traités par cette planifications
                 */
                if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                    $processedRepos[] = array('Repo' => $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection(), 'Status' => $status, 'Log' => $logLocation);
                } else {
                    $processedRepos[] = array('Repo' => $this->repo->getName(), 'Status' => $status, 'Log' => $logLocation);
                }
            }

            /**
             *  Si on a rencontré des erreurs dans la boucle, alors on quitte avec un message d'erreur
             */
            if ($groupError > 0) {
                $this->close(2, 'An error occured while processing this group, check the logs', $processedRepos);
                return;
            }
        }

        /**
         *  Si on est arrivé jusqu'ici alors on peut quitter sans erreur
         */
        $this->close(0, null, $processedRepos);
    }

    /**
     *  Générer les messages de rappels
     *  Retourne le message approprié
     */
    public function generateReminders()
    {
        /**
         *  Vérifications
         */
        try {
            /**
             *  On instancie des objets Operation et Group
             */
            $this->repo = new \Controllers\Repo\Repo();
            $this->group = new \Controllers\Group('repo');

            /**
             *  1. Récupération des informations de la planification
             */
            $this->getInfo($this->id);

            /**
             *  2. Vérification de l'action renseignée
             */
            $this->checkParam();

        /**
         *  Cloture du try/catch pour la partie Vérifications
         */
        } catch (\Exception $e) {
            return $e->getMessage();
        }


        // TRAITEMENT //

        /**
         *  Cas où la planif à rappeler ne concerne qu'un seul repo/section
         */
        if (!empty($this->repo->getName())) {

            /**
             *  Cas où l'action prévue est une mise à jour
             */
            if ($this->action == "update") {
                if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                    return 'Update repo:<br> ➞ <span class="label-black">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span>';
                } else {
                    return 'Update repo:<br> ➞ <span class="label-black">' . $this->repo->getName() . '</span>';
                }
            }
        }

        /**
         *  Cas où la planif à rappeler concerne un groupe de repo
         */
        if (!empty($this->group->getName())) {
            /**
             *  Cas où l'action prévue est une mise à jour
             */
            if ($this->action == "update") {
                $message = 'Update repos of the <span class="label-black">' . $this->group->getName() . '</span> group:<br>';
            }

            /**
             *  On récupère la liste des repos dans ce groupe
             */
            $groupReposList = $this->getGroupRepoList();

            foreach ($groupReposList as $line) {
                /**
                 *  Pour chaque ligne on récupère les infos du repo/section
                 */
                $this->repo->setName($line['Name']);
                if (!empty($line['Dist']) and !empty($line['Section'])) {
                    $this->repo->setDist($line['Dist']);
                    $this->repo->setSection($line['Section']);
                }

                if (!empty($this->repo->getDist()) and !empty($this->repo->getSection())) {
                    $message .= ' ➞ <span class="label-black">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span><br>';
                } else {
                    $message .= ' ➞ <span class="label-black">' . $this->repo->getName() . '</span><br>';
                }
            }

            return $message;
        }
    }

    /**
     *  Clôture d'une planification exécutée
     *  Génère le récapitulatif, le fichier de log et envoi un mail d'erreur si il y a eu une erreur.
     */
    private function close($planError, $planErrorMessage, $processedRepos = null)
    {
        $content = '';

        /**
         *  Suppression des lignes vides dans le message d'erreur si il y en a
         */
        if (!empty($planErrorMessage)) {
            $planErrorMessage = trim($planErrorMessage);
        }

        /**
         *  Mise à jour du status de la planification en BDD
         *  On ne met à jour uniquement si le type de planification = 'plan'.
         *  Pour les planifications régulières (type 'regular') il faut les remettre en status queued.
         */
        if ($this->type == 'plan') {
            if ($planError == 0) {
                $this->model->closeUpdateStatus($this->id, 'done', null, $this->log->getName());
            } else {
                $this->model->closeUpdateStatus($this->id, 'error', $planErrorMessage, $this->log->getName());
            }
        }
        if ($this->type == 'regular') {
            if ($planError == 0) {
                $this->model->closeUpdateStatus($this->id, 'queued', null, $this->log->getName());
            } else {
                $this->model->closeUpdateStatus($this->id, 'queued', $planErrorMessage, $this->log->getName());
            }

            /**
             *  On met à jour l'Id du snapshot si celui-ci a changé (e.g. la planification a créé un nouveau snapshot à la date du jour)
             *  Cela permet à la planification de toujours mettre à jour le dernier snapshot en date
             *  On fait cela dans le cas d'une mise à jour sur un repo (et pas sur un groupe)
             */
            if (empty($this->group->getName())) {
                $this->model->setSnapId($this->id, $this->repo->getSnapId());
            }
        }

        /**
         *  Si l'erreur est de type 1 (erreur lors des vérifications de l'opération), on affiche les erreurs avec echo, elles seront capturées par ob_get_clean() et affichées dans le fichier de log
         *  On ajoute également les données connues de la planification, le tableau récapitulatif n'ayant pas pu être généré par l'opération puisqu'on a rencontré une erreur avant qu'elle ne se lance.
         */
        if ($planError == 1) : ?>
            <div class="div-generic-blue">
                <p class="redtext"><?= $planErrorMessage ?></p><br>
        
                <p><b>Scheduled tasks details:</b></p>
                <table>
                    <tr>
                        <td><b>Action: </b></td>
                        <td><?= $this->action ?></td>
                    </tr>
                    <?php
                    if (!empty($this->group->getName())) {
                        echo "<tr><td><b>Group: </b></td><td>" . $this->group->getName() . "</td></tr>";
                    }
                    if (!empty($this->repo->getName())) {
                        echo "<tr><td><b>Repo: </b></td><td>" . $this->repo->getName() . "</td></tr>";
                    }
                    if (!empty($this->repo->getDist())) {
                        echo "<tr><td><b>Distribution: </b></td><td>" . $this->repo->getDist() . "</td></tr>";
                    }
                    if (!empty($this->repo->getSection())) {
                        echo "<tr><td><b>Section: </b></td><td>" . $this->repo->getSection() . "</td></tr>";
                    } ?>
                </table>
            </div>
            <?php
        endif;

        // Contenu du fichier de log de la planification //

        /**
         *  Case there was an error during the checks
         */
        if ($planError == 1) {
            $content = ob_get_clean();
        }

        /**
         *  If operation was successful or got errors, retrieve all operations logs and append them to the planification log
         */
        if ($planError != 1) {
            /**
             *  Wait 5 seconds to be sure all operations logs are written
             */
            sleep(5);

            /**
             *  Retrieve all operations log content
             */
            if (!empty($processedRepos)) {
                foreach ($processedRepos as $repo) {
                    if (file_exists($repo['Log'])) {
                        $content .= file_get_contents($repo['Log']) . '<br><hr><br>';
                    }
                }
            }
        }

        /**
         *  Génération du fichier de log final à partir d'un template, le contenu précédemment récupéré est alors inclu dans le template
         */
        include(ROOT . '/templates/planification_log.inc.php');
        $this->log->write($logContent);

        // Contenu du mail de la planification //

        /**
         *  On génère la liste du repo, ou du groupe traité
         */
        if (!empty($processedRepos)) {
            /**
             *  Ajout de l'action effectuée
             */
            $proceedReposMessage = '<br><br>Action: <b>' . $this->action . '</b><br><br>';

            $proceedReposMessage .= 'Processed repos:<br>';

            /**
             *  On trie l'array par status des repos afin de regrouper tous les repos OK et tous les repos en erreur
             */
            array_multisort(array_column($processedRepos, 'Status'), SORT_DESC, $processedRepos);

            /**
             *  On parcourt la liste des repos traités
             */
            if (is_array($processedRepos)) {
                foreach ($processedRepos as $processedRepo) {
                    $proceedReposMessage .= '- <b>' . $processedRepo['Repo'] . '</b>';

                    if ($processedRepo['Status'] == 'error') {
                        $proceedReposMessage .= ' (error)';
                    }

                    $proceedReposMessage .= '<br>';
                }
            }
        }

        /**
         *  Envoi d'un mail si les notifications sont activées
         */

        /**
         *  Envoi d'un mail si il n'y a pas eu d'erreurs
         */
        if (!empty($this->mailRecipient)) {
            if ($this->notificationOnSuccess == 'yes' && $planError == 0) {
                /**
                 *  Préparation du message à inclure dans le mail
                 */
                if ($this->type == 'plan') {
                    $mailSubject   = '[ OK ] - Scheduled task #' . $this->id . ' on ' . WWW_HOSTNAME;
                    $mailPreview = 'A task has completed.';
                }
                if ($this->type == 'regular') {
                    $mailSubject   = '[ OK ] - Regular scheduled task #' . $this->id . ' on ' . WWW_HOSTNAME;
                    $mailPreview = 'A regular task has completed.';
                }

                $mailMessage = 'This task has completed successfully.' . PHP_EOL;

                /**
                 *  On ajoute le repo ou le groupe traité à la suite du message
                 */
                if (!empty($proceedReposMessage)) {
                    $mailMessage .= $proceedReposMessage . PHP_EOL;
                }

                /**
                 *  Send mail
                 */
                $mymail = new \Controllers\Mail($this->mailRecipient, $mailSubject, $mailMessage, 'https://' . WWW_HOSTNAME . '/run?view-logfile=' . $this->log->getName(), 'Check the details');
            }

            /**
             *  Envoi d'un mail si il y a eu des erreurs
             */
            if ($this->notificationOnError == 'yes' && $planError != 0) {
                /**
                 *  Préparation du message à inclure dans le mail
                 */
                if ($this->type == 'plan') {
                    $mailSubject   = '[ ERROR ] - Scheduled task #' . $this->id . ' on ' . WWW_HOSTNAME;
                    $mailPreview = 'A task has failed.';
                }
                if ($this->type == 'regular') {
                    $mailSubject   = '[ ERROR ] - Recurrent scheduled tasks #' . $this->id . ' on ' . WWW_HOSTNAME;
                    $mailPreview = 'A recurrent task has failed.';
                }

                $mailMessage = 'This scheduled task encountered an error.' . PHP_EOL;

                /**
                 *  On ajoute le repo ou le groupe traité à la suite du message
                 */
                if (!empty($proceedReposMessage)) {
                    $mailMessage .= $proceedReposMessage . PHP_EOL;
                }

                /**
                 *  Send mail
                 */
                $mymail = new \Controllers\Mail($this->mailRecipient, $mailSubject, $mailMessage, 'https://' . WWW_HOSTNAME . '/run?view-logfile=' . $this->log->getName(), 'Check the details');
            }
        }
    }

/**
 *  VERIFICATIONS
 *  Code d'erreurs : CP "Check Planification"
 */
    /**
     *  Récupération de la liste des repo dans le groupe
     */
    private function getGroupRepoList()
    {
        $myrepoListing = new \Controllers\Repo\Listing();

        /**
         *  On récupère tous les repos membres du groupe
         */
        $repos = $myrepoListing->listNameByGroup($this->group->getName());

        if (empty($repos)) {
            throw new Exception('No repository found in ' . $this->group->getName() . ' group');
        }

        return $repos;
    }

    /**
     *  Return list of planifications with specified status
     *  It is possible to add an offset to the request
     */
    public function getByStatus(array $status = ['running', 'queued', 'disabled', 'done'], bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getByStatus($status, $withOffset, $offset);
    }

    /**
     *  Liste la dernière planification exécutée
     */
    public function listLast()
    {
        return $this->model->listLast();
    }

    /**
     *  Liste la prochaine planification qui sera exécutée
     */
    public function listNext()
    {
        return $this->model->listNext();
    }

    /**
     *  Return log file name of all operations launched by this planification
     */
    private function getOperationLogName(int $id)
    {
        return $this->model->getOperationLogName($id);
    }

    /**
    *   Récupère toutes les infos d'une planification
    *   Un objet Operation doit avoir été instancié pour récupérer les infos concernant le repo concerné par cette planification
    */
    private function getInfo(string $id)
    {
        $planInfo = $this->get($id);

        /**
         *  On défini les propriété de la planification à partir des infos récupérées
         */

        /**
         *  Type de planification
         */
        $this->setType($planInfo['Type']);

        /**
         *  La date et l'heure sont utiles à récupérer pour la génération des rappels
         */
        if (!empty($planInfo['Date'])) {
            $this->setDate($planInfo['Date']);
        }
        if (!empty($planInfo['Time'])) {
            $this->setTime($planInfo['Time']);
        }

        /**
         *  Fréquence d'exécution (si type 'regular')
         */
        if (!empty($planInfo['Frequency'])) {
            $this->frequency = $planInfo['Frequency'];
        }

        /**
         *  Action
         */
        $this->setAction($planInfo['Action']);

        /**
         *  Id du repo ou du groupe
         */
        if (!empty($planInfo['Id_snap'])) {
            $this->repo->setSnapId($planInfo['Id_snap']);
            $this->repo->getAllById(null, $this->repo->getSnapId());
        }
        if (!empty($planInfo['Id_group'])) {
            $this->group->setId($planInfo['Id_group']);
            $groupName = $this->group->getNameById($planInfo['Id_group']);
            $this->group->setName($groupName);
        }

        /**
         *  Environnement cible si il y en a un
         */
        if (!empty($planInfo['Target_env'])) {
            $this->setTargetEnv($planInfo['Target_env']);
        }

        /**
         *  Les paramètres GPG Check et GPG Resign sont conservées de côté et seront pris en compte au début de l'exécution de update()
         */
        if (!empty($planInfo['Gpgcheck'])) {
            $this->setTargetGpgCheck($planInfo['Gpgcheck']);
        }
        if (!empty($planInfo['Gpgresign'])) {
            $this->setTargetGpgResign($planInfo['Gpgresign']);
        }

        if (!empty($planInfo['OnlySyncDifference'])) {
            $this->onlySyncDifference = $planInfo['OnlySyncDifference'];
        }

        /**
         *  Rappels de planification
         */
        $this->setReminder($planInfo['Reminder']);

        /**
         *  Notifications par mail si erreur ou si terminé
         */
        $this->setNotification('on-error', $planInfo['Notification_error']);
        $this->setNotification('on-success', $planInfo['Notification_success']);

        /**
         *  Adresse mail de destination
         */
        if (!empty($planInfo['Mail_recipient'])) {
            $this->setMailRecipient(explode(',', $planInfo['Mail_recipient']));
        }
    }

    /**
     *  Passe le status d'une planification en stopped
     */
    public function stop(string $planId)
    {
        $this->model->setStatus($planId, 'stopped');
    }

    /**
     *  Check scheduled tasks params
     */
    private function paramCheck()
    {
        if (PLANS_ENABLED != 'true') {
            throw new Exception('Scheduled tasks are disabled');
        }

        if (empty($this->action)) {
            throw new Exception('No action specified');
        }

        if (!in_array($this->action, $this->validActions)) {
            throw new Exception('Action is invalid');
        }

        /**
         *  Action param check
         */
        if ($this->action == 'update') {
            if (empty($this->targetGpgCheck)) {
                throw new Exception('Source repository GPG signature check is not specified');
            }

            if (empty($this->targetGpgResign)) {
                throw new Exception('Repository GPG signing is not specified');
            }
        }

        /**
         *  Check if repo or group is specified
         */
        if (empty($this->repo->getName()) and empty($this->group->getName())) {
            throw new Exception('Repository or group not specified');
        }

        /**
         *  Either repo or group can be specified, not both
         */
        if (!empty($this->repo->getName()) and !empty($this->group->getName())) {
            throw new Exception('Both repository and group cannot be specified');
        }

        /**
         *  If repo is specified, check if it exists (it may have been deleted since the planification was created)
         */
        if (!empty($this->repo->getName())) {
            /**
             *  Check if snapshot id exists
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Snapshot id ' . $this->repo->getSnapId() . ' does not exist');
            }
        }

        /**
         *  If group is specified, check if it exists (it may have been deleted since the planification was created)
         *  Then get the whole group list
         */
        if (!empty($this->group->getName())) {
            /**
             *  Check if group id exists
             */
            if ($this->group->existsId($this->group->getId()) === false) {
                throw new Exception('Group id ' . $this->group->getId() . ' does not exist');
            }
        }
    }
}
