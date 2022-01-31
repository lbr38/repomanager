<?php
/**
 *  Classe regroupant quelques fonctions communes / génériques
 */

class Common
{
    /**
     *  Fonction de vérification / conversion des données envoyées par formulaire
     */
    static function validateData($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    /**
     *  Fonction de vérification du format d'une adresse email
     */
    static function validateMail(string $mail) {
        $mail = trim($mail);

        if (filter_var($mail, FILTER_VALIDATE_EMAIL)) return true;

        return false;
    }

    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres
     */
    static function is_alphanum(string $data, array $additionnalValidCaracters = []) {
        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ignore dans le test en les remplacant temporairement par du vide
         */
        if (!empty($additionnalValidCaracters)) {
            if (!ctype_alnum(str_replace($additionnalValidCaracters, '', $data))) {
                //printAlert('Vous ne pouvez renseigner que des chiffres ou des lettres', 'error');
                return false;
            }

        /**
         *  Si on n'a pas passé de caractères supplémentaires alors on teste simplement la chaine avec ctype_alnum
         */
        } else {
            if (!ctype_alnum($data)) {
                //printAlert('Vous ne pouvez renseigner que des chiffres ou des lettres', 'error');
                return false;
            }
        }

        return true;    
    }


    /**
     *  Vérifie que la chaine passée ne contient que des chiffres ou des lettres, un underscore ou un tiret
     *  Retire temporairement les tirets et underscore de la chaine passée afin qu'elle soit ensuite testée par la fonction PHP ctype_alnum
     */
    static function is_alphanumdash(string $data, array $additionnalValidCaracters = []) {
        /**
         *  Si une chaine vide a été transmise alors c'est valide
         */
        if (empty($data)) {
            return true;
        }
    
        /**
         *  array contenant quelques exceptions de caractères valides
         */
        $validCaracters = array('-', '_');
    
        /**
         *  Si on a passé en argument des caractères supplémentaires à autoriser alors on les ajoute à l'array $validCaracters
         */
        if (!empty($additionnalValidCaracters)) {
            $validCaracters = array_merge($validCaracters, $additionnalValidCaracters);
        }
    
        if(!ctype_alnum(str_replace($validCaracters, '', $data))) {
            //throw new Exception('Vous ne pouvez renseigner que des chiffres, des lettres ou des tirets');
            return false;
        }
    
        return true;
    }
}