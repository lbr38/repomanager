<?php

namespace Controllers\Layout\Tab;

class Settings
{
    public static function render()
    {
        /**
         *  Only admin have access to this page
         */
        if (!IS_ADMIN) {
            header('Location: /');
            exit;
        }

        $mylogin = new \Controllers\Login();
        $users = $mylogin->getUsers();
        $usersEmail = $mylogin->getEmails();

        include_once(ROOT . '/views/settings.template.php');
    }
}
