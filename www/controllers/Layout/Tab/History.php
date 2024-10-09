<?php

namespace Controllers\Layout\Tab;

class History
{
    public static function render()
    {
        $filterByUser = false;

        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        $myusers = new \Controllers\Login();
        $myhistory = new \Controllers\History();

        /**
         *  Cas où on souhaite filtrer par Id utilisateur
         */
        if (!empty($_POST['action']) and $_POST['action'] === "filterByUser" and !empty($_POST['userid'])) {
            $filterByUserId = \Controllers\Common::validateData($_POST['userid']);

            if (is_numeric($filterByUserId)) {
                $filterByUser = true;
            }
        }

        /**
         *  Case it must be filtered by user
         */
        if ($filterByUser === true) {
            $historyLines = $myhistory->getByUser($filterByUserId);
        } else {
            $historyLines = $myhistory->getAll();
        }

        /**
         *  Getting all usernames
         */
        $users = $myusers->getUsers();

        include_once(ROOT . '/views/history.template.php');
    }
}
