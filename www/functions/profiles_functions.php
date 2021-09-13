<?php
/**
 *  Fichier à convertir en fichier de class 'Profile'
 */

/**
 * 	Création d'un nouveau profil
 */
function newProfile($newProfile) {
	global $PROFILES_MAIN_DIR;

	/**
	 *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
	 */
	if (!is_alphanumdash($newProfile)) {
		return;
	}

	/**
	 * 	2. On vérifie qu'un profil du même nom n'existe pas déjà
	 */
	if (file_exists("${PROFILES_MAIN_DIR}/${newProfile}")) {
		printAlert("Erreur : un profil du même nom (<b>$newProfile</b>) existe déjà");
		return;
	}

	/**
	 * 	3. Si pas d'erreur alors on peut créer le répertoire de profil
	 */
	if (!is_dir("${PROFILES_MAIN_DIR}/${newProfile}")) { 
		if (!mkdir("${PROFILES_MAIN_DIR}/${newProfile}", 0775, true)) {
			printAlert("Erreur lors de la création du profil <b>$newProfile</b>");
			return;
		}
	}

	/**
	 * 	4. Créer le fichier de config
	 */
	if (!file_exists("${PROFILES_MAIN_DIR}/${newProfile}/config")) {
		if (!touch("${PROFILES_MAIN_DIR}/${newProfile}/config")) {
			printAlert("Erreur lors de l'initialisation du profil <b>$newProfile</b>");
			return;
		}
	}

	/**
	 * 	5. Créer le fichier de config du profil avec des valeurs vides ou par défaut
	 */
	if (!file_put_contents("${PROFILES_MAIN_DIR}/${newProfile}/config", "EXCLUDE_MAJOR=\"\"\nEXCLUDE=\"\"\nNEED_RESTART=\"\"\nKEEP_CRON=\"no\"\nALLOW_OVERWRITE=\"yes\"\nALLOW_REPOSFILES_OVERWRITE=\"yes\"")) {
		printAlert("Erreur lors de l'initialisation du profil <b>$newProfile</b>");
		return;
	}
	
	/**
	 * 	Affichage d'un message
	 */
	printAlert("Le profil <b>${newProfile}</b> a été créé");
}


/**
 * 	Ajout ou suppression de repos/sections d'un profil
 */
function manageProfileRepos($profileName, $profileRepos) {
	global $PROFILES_MAIN_DIR;
	global $REPOS_PROFILES_CONF_DIR;
	global $REPO_CONF_FILES_PREFIX;
	global $OS_FAMILY;

	$repo = new Repo();

	/**
	 *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
	 */
	if (!is_alphanumdash($profileName)) {
		return false;
	}

	//$profileRepos => validateData fait plus bas

	/**
	 * 	2. D'abord on supprime tous les repos présents dans le répertoire du profil, avant de rajouter seulement ceux qui ont été sélectionnés dans la liste
	 */
	if (is_dir("${PROFILES_MAIN_DIR}/${profileName}/")) {
		if ($OS_FAMILY == "Redhat") {
			exec("rm ${PROFILES_MAIN_DIR}/${profileName}/*.repo -f");
		}
		if ($OS_FAMILY == "Debian") {
			exec("rm ${PROFILES_MAIN_DIR}/${profileName}/*.list -f");
		}
	}

	/**
	 * 	3. Si l'array $profileRepos est vide alors on s'arrête là, le profil restera sans repo configuré. Sinon on continue.
	 * 	Ce n'est pas une erreur alors on retourne true
	 */
	if (empty($profileRepos)) {
		return true;
	}

	/**
	 * 	4. On traite chaque repo sélectionné
	 */
	foreach ($profileRepos as $selectedOption) {
		$addProfileRepo = validateData($selectedOption);

		if ($OS_FAMILY == "Debian") {
			$addProfileRepoExplode = explode('|', $addProfileRepo);
			$addProfileRepo = $addProfileRepoExplode[0];
			$addProfileRepoDist = $addProfileRepoExplode[1];
			$addProfileRepoSection = $addProfileRepoExplode[2];
		}

		/**
		 *  5. On vérifie que le nom du repo ne contient pas des caractères interdits
		 */
		if (!is_alphanumdash($addProfileRepo)) {
			return false;
		}
		if ($OS_FAMILY == "Debian") {
			if (!is_alphanumdash($addProfileRepoDist) OR !is_alphanumdash($addProfileRepoSection)) {
				return false;
			}
		}

		if ($OS_FAMILY == "Redhat") {
			/**
			 * 	 On vérifie que le repo existe, sinon on passe au suivant
			 */
			if ($repo->exists($addProfileRepo) === false) {
				printAlert("Le repo $addProfileRepo n'existe pas");
				continue;
			}

			exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -sfn ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}.repo");
		}

		if ($OS_FAMILY == "Debian" AND !empty($addProfileRepoDist) AND !empty($addProfileRepoSection)) {
			/**
			 * 	On vérifie que la section repo existe, sinon on passe au suivant
			 */
			if ($repo->section_exists($addProfileRepo, $addProfileRepoDist, $addProfileRepoSection) === false) {
				printAlert("La section $addProfileRepoSection du repo $addProfileRepo n'existe pas");
				continue;
			}

			/**
			 * 	Si le nom de la distribution contient un slash, c'est le cas par exemple avec debian-security (buster/updates), alors il faudra remplacer ce slash par [slash] dans le nom du fichier .list
			 */
			$checkIfDistContainsSlash = exec("echo $addProfileRepoDist | grep '/'");
			if (!empty($checkIfDistContainsSlash)) {
				$addProfileRepoDist = str_replace("/", "[slash]","$addProfileRepoDist");
			}
		
			exec("cd ${PROFILES_MAIN_DIR}/${profileName}/ && ln -sfn ${REPOS_PROFILES_CONF_DIR}/${REPO_CONF_FILES_PREFIX}${addProfileRepo}_${addProfileRepoDist}_${addProfileRepoSection}.list");
		}
	}

	/**
	 * 	Cette fonction doit retourner true si elle s'est correctement exécutée
	 */
	return true;
}

/**
 * 	Suppression d'un profil
 */
function deleteProfile($profileName) {
	global $PROFILES_MAIN_DIR;

	/**
	 *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
	 */
	if (!is_alphanumdash($profileName)) {
		return;
	}

	/**
	 * 	2. Suppression du répertoire du profil
	 */
	exec("rm -fr ${PROFILES_MAIN_DIR}/${profileName}/", $output, $return);
	if ($return == 0) {
		// Affichage d'un message
		printAlert("Le profil <b>$profileName</b> a été supprimé");
	} else {
		// Si la suppression s'est mal passée
		printAlert("<span class=\"yellowtext\">Erreur lors de la suppression du profil <b>$profileName</b></span>");
	}
}

/**
 * 	Renommage d'un profil
 */
function renameProfile($actualProfileName, $newProfileName) {
	global $PROFILES_MAIN_DIR;

	/**
	 *  1. On vérifie que le nom du profil ne contient pas des caractères interdits
	 */
	if (!is_alphanumdash($actualProfileName) OR !is_alphanumdash($newProfileName)) {
		return;
	}

	/**
	 * 	2. On vérifie qu'un profil du même nom n'existe pas déjà. Si c'est le cas on affiche un message d'erreur
	 */
	if (is_dir("${PROFILES_MAIN_DIR}/${newProfileName}")) {
		printAlert("Erreur : un profil du même nom (<b>$newProfileName</b>) existe déjà");
		return false;
	}

	/**
	 * 	3. Si pas d'erreur alors on peut renommer le répertoire de profil
	 */
	if (!rename("${PROFILES_MAIN_DIR}/${actualProfileName}", "${PROFILES_MAIN_DIR}/${newProfileName}")) {
		printAlert("Erreur lors du renommage du profil <b>$actualProfileName</b>");
		return;
	}

	/**
	 * 	Affichage d'un message
	 */
	printAlert("Le profil <b>$actualProfileName</b> a été renommé en <b>$newProfileName</b>");

	unset($actualProfileName, $newProfileName);
}
?>