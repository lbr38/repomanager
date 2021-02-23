<?php

function completeOperation($TEMP_DIR, $PID) {
	touch("$TEMP_DIR/$PID/completed");
	closeOperation($PID);
}

/**
 * ETAPE 1
 */

function printDetails(array $variables = []) {
	extract($variables);
	global $OS_FAMILY;
	global $HOSTS_CONF;
	global $DATE_JMA;
	
	ob_start();

	if ($OS_FAMILY == "Redhat") { echo "<h5>CREATION D'UN NOUVEAU REPO</h5>"; }
	if ($OS_FAMILY == "Debian") { echo "<h5>CREATION D'UNE NOUVELLE SECTION DE REPO</h5>"; }
	
	// Récupération d'informations supplémentaires (Debian)
	if ($OS_FAMILY == "Debian") {
		$hostFullUrl = exec("grep '^Name=\"${repoHostName}\",Url=' $HOSTS_CONF | awk -F ',' '{print $2}' | cut -d'=' -f2 | sed 's/\"//g'"); // Récupère l'url complète
		$repoHost = exec("echo '$hostFullUrl' | cut -d'/' -f1");
		// Extraction de la racine de l'hôte (ex pour : ftp.fr.debian.org/debian ici la racine sera debian
		$repoRoot = exec("echo '$hostFullUrl' | sed 's/${repoHost}//g'");
		if (empty($repoHost)) {
			throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer l\'adresse de l\'hôte source');
		}
		if (empty($repoRoot)) {
			throw new Exception('<br><span class="redtext">Erreur : </span>impossible de déterminer la racine de l\'URL hôte');
		}
	}

	// Affichage du récapitulatif de l'opération
	echo '<table>';
	if ($OS_FAMILY == "Redhat") {
		echo "<tr>
			<td>Repo source :</td>
			<td><b>$repoRealname</b></td>
		</tr>
		<tr>
			<td>Nom du repo :</td>
			<td><b>$repoName</b></td>
		</tr>";
	}
	if ($OS_FAMILY == "Debian") {
		echo "<tr>
			<td>Hôte source :</td>
			<td><b>$hostFullUrl</b></td>
		</tr>
		<tr>
			<td>Nom du repo :</td>
			<td><b>$repoName</b></td>
		</tr>
		<tr>
			<td>Distribution :</td>
			<td><b>$repoDist</b></td>
		</tr>
		<tr>
			<td>Section :</td>
			<td><b>$repoSection</b></td>
		</tr>";
	}
	echo "<tr>
			<td>Vérification des signatures GPG :</td>
			<td><b>$repoGpgCheck</b></td>
		</tr>
		<tr>
			<td>Signature du repo :</td>
			<td><b>$repoGpgResign</b></td>
		</tr>";
	if (!empty($repoGroup)) {
	echo "<tr>
			<td>Ajout à un groupe :</td>
			<td><b>$repoGroup</b></td>
		</tr>";
	}
	echo "</table>";

	$logcontent = ob_get_clean();
	file_put_contents($stepLog, $logcontent);

	if ($OS_FAMILY == "Redhat") {  return true; }
	if ($OS_FAMILY == "Debian") { return array($repoHost, $repoRoot); }
}

/**
 * ETAPE 2 => en commun avec updateRepo sauf la partie // On vérifie quand même que le repo n'existe pas déjà 
 */

function getPackages(array $variables = []) {
	extract($variables);
	global $OS_FAMILY;
	global $OS_VERSION;
	global $REPOS_LIST;
  	global $REPOS_DIR;
  	global $DEFAULT_ENV;
	global $GPGHOME;
	global $REPOMANAGER_YUM_DIR;
	global $DATE_JMA;

	ob_start();

	//// VERIFICATIONS ////

	// On vérifie quand même que le repo n'existe pas déjà :
	if ($OS_FAMILY == "Redhat") {
		$checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Realname=\"${repoRealname}\",Env=\"${DEFAULT_ENV}\"' $REPOS_LIST");
		if (!empty($checkIfRepoExists)) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>le repo <b>$repoName</b> existe déjà en <b>${DEFAULT_ENV}</b>");
		}
	}
	if ($OS_FAMILY == "Debian") {
		$checkIfRepoExists = exec("egrep '^Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\,Env=\"${DEFAULT_ENV}\"' $REPOS_LIST"); 
		if (!empty($checkIfRepoExists)) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>la section <b>$repoSection</b> du repo <b>$repoName</b> existe déjà en <b>${DEFAULT_ENV}</b>");
		}
	}

	$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

	//// TRAITEMENT ////

	// Création du répertoire du repo/section
	if ($OS_FAMILY == "Redhat") {
		if (is_dir("${REPOS_DIR}/${DATE_JMA}_${repoName}")) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>le répertoire <b>${REPOS_DIR}/${DATE_JMA}_${repoName}</b>existe déjà");
		}

		if (!mkdir("${REPOS_DIR}/${DATE_JMA}_${repoName}", 0770, true)) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/${DATE_JMA}_${repoName}</b> a échouée");
		}
	}

	if ($OS_FAMILY == "Debian") {
		if (is_dir("${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}")) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>le répertoire <b>${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}</b>existe déjà");
		}

		if (!mkdir("${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}", 0770, true)) {
			throw new Exception("<br><span class=\"redtext\">Erreur : </span>la création du répertoire <b>${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}</b> a échouée");
		}
	}

	$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

	// Récupération des paquets
	echo '<br>Récupération des paquets ';
	echo '<span class="getPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="getPackagesOK greentext hide">✔</span><span class="getPackagesKO redtext hide">✕</span>';
	echo '<div class="hide getPackagesDiv"><pre>';
	$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();
	if ($OS_FAMILY == "Redhat") {
		if ($repoGpgCheck == "no") {
			if ($OS_VERSION == "7") {
				exec("cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf -l --repoid=${repoRealname} --norepopath --download_path='${REPOS_DIR}/${DATE_JMA}_${repoName}/' >> $stepLog", $output, $result);
			}
			if ($OS_VERSION == "8") {
				exec("cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --nogpgcheck --repoid=${repoRealname} --download-path '${REPOS_DIR}/${DATE_JMA}_${repoName}/' >> $stepLog", $output, $result);
			}
		} else { // Dans tous les autres cas (même si rien n'a été précisé) on active gpgcheck
			if ($OS_VERSION == "7") {
				exec("cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --gpgcheck -l --repoid=${repoRealname} --norepopath --download_path='${REPOS_DIR}/${DATE_JMA}_${repoName}/' >> $stepLog", $output, $result);
			}
			if ($OS_VERSION == "8") {
				exec("cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && reposync --config=${REPOMANAGER_YUM_DIR}/repomanager.conf --repoid=${repoRealname} --download-path '${REPOS_DIR}/${DATE_JMA}_${repoName}/' >> $stepLog", $output, $result);
			}
		}
		echo '</pre></div>';
		
		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

		if ($result == 0) {
			echo '<style>';
			echo '.getPackagesLoading { display: none; }';
			echo '.getPackagesOK { display: inline-block; }';
			echo '</style>';
		} else {
			echo '<style>';
			echo '.getPackagesLoading { display: none; }';
			echo '.getPackagesKO { display: inline-block; }';
			echo '</style>';
			echo "<br><span class=\"redtext\">Erreur : </span>reposync a rencontré un problème lors de la création du miroir";
			echo "<br>Suppression de ce qui a été fait : ";
			exec("rm -rf '${REPOS_DIR}/${DATE_JMA}_${repoName}'");
			echo '<span class="greentext">OK</span>';
			throw new Exception();
		}
		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();
	}

	if ($OS_FAMILY == "Debian") {
		// Dans le cas où on a précisé de ne pas vérifier les signatures GPG :
		if ($repoGpgCheck == "no") {
			exec("/usr/bin/debmirror --no-check-gpg --nosource --passive --method=http --root=${repoRoot} --dist=${repoDist} --host=${repoHost} --section=${repoSection} --arch=amd64 ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> $stepLog", $output, $result);
		} else { // Dans tous les autres cas (même si rien n'a été précisé)
			exec("/usr/bin/debmirror --check-gpg --keyring=${GPGHOME}/trustedkeys.gpg --nosource --passive --method=http --root=${repoRoot} --dist=${repoDist} --host=${repoHost} --section=${repoSection} --arch=amd64 ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection} --getcontents --ignore-release-gpg --progress --i18n --include='Translation-fr.*\.bz2' --postcleanup >> $stepLog", $output, $result);
		}
		echo '</pre></div>';

		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

		if ($result == 0) {
			echo '<style>';
			echo '.getPackagesLoading { display: none; }';
			echo '.getPackagesOK { display: inline-block; }';
			echo '</style>';
		} else {
			echo '<style>';
			echo '.getPackagesLoading { display: none; }';
			echo '.getPackagesKO { display: inline-block; }';
			echo '</style>';
			echo '<br><span class="redtext">Erreur : </span>debmirror a rencontré un problème lors de la création du miroir';
			echo '<br>Suppression de ce qui a été fait : ';
			exec("rm -rf '${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}'");
			echo '<span class="greentext">OK</span>';
			throw new Exception();
		}
		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();
	}
	return true;
}

/**
 * ETAPE 3 => en commun avec updateRepo
 */

function signPackages(array $variables = []) {
	extract($variables);
	global $OS_FAMILY;
  	global $REPOS_DIR;
	global $WWW_HOSTNAME;
	global $GPG_KEYID;
	global $GPGHOME;
	global $PASSPHRASE_FILE;
	global $DATE_JMA;

	ob_start();

	// Signature des paquets/du repo avec GPG
	// Si c'est Redhat/Centos on resigne les paquets
	// Si c'est Debian on signe le repo (Release.gpg)
	if ($repoGpgResign == "yes") {
		if ($OS_FAMILY == "Redhat") {
			echo '<br>Signature des paquets (GPG) ';
			echo '<span class="signPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="signPackagesOK greentext hide">✔</span><span class="signPackagesKO redtext hide">✕</span>';
			echo '<div class="hide signRepoDiv"><pre>';
			
			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

			// On se mets à la racine du repo
			// Activation de globstar (**), cela permet à bash d'aller chercher des fichiers .rpm récursivement, peu importe le nb de sous-répertoires
			if (file_exists("/usr/bin/rpmresign")) {
				exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && /usr/bin/rpmresign --path '${GPGHOME}' --name '${GPG_KEYID}' --passwordfile '${PASSPHRASE_FILE}' **/*.rpm >> $stepLog", $output, $result);
			} else {
				exec("shopt -s globstar && cd '${REPOS_DIR}/${DATE_JMA}_${repoName}' && rpmsign --addsign **/*.rpm >> $stepLog", $output, $result);	// Sinon on utilise rpmsign et on demande le mdp à l'utilisateur (pas possible d'utiliser un fichier passphrase)
			}
			echo '</pre></div>';

			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

			if ($result == 0) {
				echo '<style>';
				echo '.signPackagesLoading { display: none; }';
				echo '.signPackagesOK { display: inline-block; }';
				echo '</style>';
			} else {
				echo '<style>';
				echo '.signPackagesLoading { display: none; }';
				echo '.signPackagesKO { display: inline-block; }';
				echo '</style>';
				echo "<span class=\"redtext\">Erreur : </span>la signature des paquets a échouée";
				echo "<br>Suppression de ce qui a été fait : ";
				exec ("rm -rf '${REPOS_DIR}/${DATE_JMA}_${repoName}'");
				echo '<span class="greentext">OK</span>';
				throw new Exception();
			}
			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND);
		}

		if ($OS_FAMILY == "Debian") {
			// On va utiliser un répertoire temporaire pour travailler
			$TMP_DIR = '/tmp/deb_packages';
			mkdir("$TMP_DIR", 0770, true);
			echo '<br>Signature du repo (GPG) ';
			echo '<span class="signPackagesLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="signPackagesOK greentext hide">✔</span><span class="signPackagesKO redtext hide">✕</span>';
			echo '<div class="hide signRepoDiv"><pre>';

			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();
			
			// On se mets à la racine de la section
			// On recherche tous les paquets .deb et on les déplace dans le répertoire temporaire
			exec("cd ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/ && find . -name '*.deb' -exec mv '{}' $TMP_DIR \;");
			// Après avoir déplacé tous les paquets on peut supprimer tout le contenu de la section
			exec("rm -rf ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/*");
			// Création du répertoire conf et des fichiers de conf du repo
			mkdir("${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/conf", 0770, true);
			// Création du fichier "distributions"
			// echo -e "Origin: Repo $repoName sur ${WWW_HOSTNAME}\nLabel: apt repository\nCodename: ${repoDist}\nArchitectures: i386 amd64\nComponents: ${repoSection}\nDescription: Miroir du repo ${repoName}, distribution ${repoDist}, section ${repoSection}\nSignWith: ${GPG_KEYID}\nPull: ${repoSection}" > conf/distributions
			file_put_contents("${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/conf/distributions", "Origin: Repo $repoName sur ${WWW_HOSTNAME}\nLabel: apt repository\nCodename: ${repoDist}\nArchitectures: i386 amd64\nComponents: ${repoSection}\nDescription: Miroir du repo ${repoName}, distribution ${repoDist}, section ${repoSection}\nSignWith: ${GPG_KEYID}\nPull: ${repoSection}".PHP_EOL);
			// Création du fichier "options"
			// echo -e "basedir ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}\nask-passphrase" > conf/options
			file_put_contents("${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/conf/options", "basedir ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}\nask-passphrase".PHP_EOL);
			// Création du repo en incluant les paquets deb du répertoire temporaire, et signature du fichier Release
			exec("cd ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/ && /usr/bin/reprepro --gnupghome ${GPGHOME} includedeb ${repoDist} ${TMP_DIR}/*.deb >> $stepLog", $output, $result);
			echo '</pre></div>';
			
			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

			// Suppression du répertoire temporaire
			exec("rm -rf '$TMP_DIR'");
			if ($result == 0) {
				echo '<style>';
				echo '.signPackagesLoading { display: none; }';
				echo '.signPackagesOK { display: inline-block; }';
				echo '</style>';
			} else {
				echo '<style>';
				echo '.signPackagesLoading { display: none; }';
				echo '.signPackagesKO { display: inline-block; }';
				echo '</style>';
				echo "<br><span class=\"redtext\">Erreur : </span>la signature de la section <b>$repoSection</b> du repo <b>$repoName</b> a échouée";
				echo '<br>Suppression de ce qui a été fait : ';
				exec("rm -rf '${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}'");
				echo '<span class="greentext">OK</span>';
				throw new Exception();
			}
			$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND);
		}
	}
	return true;
}

/**
 * ETAPE 4 => en commun avec updateRepo
 */

function createRepo(array $variables = []) {
	extract($variables);
	global $OS_FAMILY;
	global $REPOS_LIST;
  	global $GROUPS_CONF;
  	global $REPOS_DIR;
  	global $DEFAULT_ENV;
	global $DATE_JMA;

	ob_start();

	// Création des metadata du repo (Redhat/centos uniquement)
	if ($OS_FAMILY == "Redhat") {
		echo '<br>Création du dépôt (metadata) ';
		echo '<span class="createRepoLoading">en cours<img src="images/loading.gif" class="icon" /></span><span class="createRepoOK greentext hide">✔</span><span class="createRepoKO redtext hide">✕</span>';
		echo '<div class="hide createRepoDiv"><pre>';

		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

		exec("createrepo -v ${REPOS_DIR}/${DATE_JMA}_${repoName}/ >> $stepLog", $output, $result);
		echo '</pre></div>';

		$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

		if ($result == 0) {
			echo '<style>';
			echo '.createRepoLoading { display: none; }';
			echo '.createRepoOK { display: inline-block; }';
			echo '</style>';
		} else {
			echo '<style>';
			echo '.createRepoLoading { display: none; }';
			echo '.createRepoKO { display: inline-block; }';
			echo '</style>';
			echo "<br><span class=\"redtext\">Erreur : </span>la création du repo a échouée";
			echo "<br>Suppression de ce qui a été fait : ";
			exec("rm -rf '${REPOS_DIR}/${DATE_JMA}_${repoName}'");
			echo '<span class="greentext">OK</span>';
			throw new Exception();
		}
	}

	$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND); ob_start();

	// Création du lien symbolique (environnement)
	if ($OS_FAMILY == "Redhat") {
		exec("cd ${REPOS_DIR}/ && ln -sfn ${DATE_JMA}_${repoName}/ ${repoName}_${DEFAULT_ENV}", $output, $result);
	}
	if ($OS_FAMILY == "Debian") {
		exec("cd ${REPOS_DIR}/${repoName}/${repoDist}/ && ln -sfn ${DATE_JMA}_${repoSection}/ ${repoSection}_${DEFAULT_ENV}", $output, $result);
	}
	if ($result != 0) {
		echo "<br><span class=\"redtext\">Erreur : </span>la finalisation du repo a échouée";
		throw new Exception();
	}
}

/**
 * ETAPE 5
 */

function updateReposLists(array $variables = []) {
	extract($variables);
	global $OS_FAMILY;
	global $REPOS_LIST;
  	global $GROUPS_CONF;
  	global $REPOS_DIR;
  	global $DEFAULT_ENV;
	global $DATE_JMA;

	// Ajout des informations dans repos.list
	if ($OS_FAMILY == "Redhat") {
		if (!file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Realname=\"${repoRealname}\",Env=\"${DEFAULT_ENV}\",Date=\"${DATE_JMA}\",Description=\"${repoDescription}\"", FILE_APPEND)) {
			echo "<br><span class=\"redtext\">Erreur :</span>l'ajout du repo <b>$repoName</b> à la liste des repos actifs a échoué";
			throw new Exception();
		}
	}

	if ($OS_FAMILY == "Debian") {
		if (!file_put_contents("$REPOS_LIST", "Name=\"${repoName}\",Host=\"${repoHostName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\",Env=\"${DEFAULT_ENV}\",Date=\"${DATE_JMA}\",Description=\"${repoDescription}\"", FILE_APPEND)) {
			echo "<br><span class=\"redtext\">Erreur :</span>l'ajout de la section <b>$repoSection</b> du repo <b>$repoName</b> à la liste des repos actifs a échoué";
			throw new Exception();
		}
	}

	// Ajout au groupe si un groupe a été renseigné
	if (!empty($repoGroup)) {
		$checkIfGroupExists = shell_exec("grep '$repoGroup' $GROUPS_CONF");
		if (!empty($checkIfGroupExists)) {
			if ($OS_FAMILY == "Redhat") {
				// on formatte la chaine à insérer
				$groupNewContent = "Name=\"${repoName}\"";
			}
			if ($OS_FAMILY == "Debian") {
				// on formatte la chaine à insérer
				$groupNewContent = "Name=\"${repoName}\",Dist=\"${repoDist}\",Section=\"${repoSection}\"";
			}
			// ensuite on commence par récupérer le n° de ligne où sera insérée la nouvelle chaine. Ici la commande sed affiche les numéros de lignes du groupe et tous ses repos actuels jusqu'à rencontrer une 
			// ligne vide (celle qui nous intéresse car on va insérer le nouveau repo à cet endroit), on ne garde donc que le dernier n° de ligne qui s'affiche (tail -n1) :  
			$lineToInsert = exec("sed -n '/\[${repoGroup}\]/,/^$/=' $GROUPS_CONF | tail -n1");
			// enfin, on insert la nouvelle ligne au numéro de ligne récupéré :
			exec("sed -i '${lineToInsert}i\\${groupNewContent}' $GROUPS_CONF");
		} else {
			echo "<br><span class=\"redtext\">Erreur : </span>impossible d'ajouter au groupe <b>$repoGroup</b> car il n'existe pas";
		}
	}

	// Application des droits sur le repo/section créé
	if ($OS_FAMILY == "Redhat") {
		exec("find ${REPOS_DIR}/${DATE_JMA}_${repoName}/ -type f -exec chmod 0660 {} \;");
		exec("find ${REPOS_DIR}/${DATE_JMA}_${repoName}/ -type d -exec chmod 0770 {} \;");
		/*if [ $? -ne "0" ];then
			echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur le repo <b>$repoName</b> a échoué"
		fi*/
	}
	if ($OS_FAMILY == "Debian") {
		exec("find ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/ -type f -exec chmod 0660 {} \;");
		exec("find ${REPOS_DIR}/${repoName}/${repoDist}/${DATE_JMA}_${repoSection}/ -type d -exec chmod 0770 {} \;");
		/*if [ $? -ne "0" ];then
			echo "<br><span class=\"redtext\">Erreur :</span>l'application des permissions sur la section <b>$repoSection</b> a échoué"
		fi*/
	}

	// Génération du fichier de conf repo en local (ces fichiers sont utilisés pour les profils)
	if ($OS_FAMILY == "Redhat") { generateConf_rpm($repoName, 'default'); }
	if ($OS_FAMILY == "Debian") { generateConf_deb($repoName, $repoDist, $repoSection, 'default'); }

	echo '<br><span class="greentext">Opération terminée</span><br>';

	$logcontent = ob_get_clean(); file_put_contents($stepLog, $logcontent, FILE_APPEND);

	return true;
}


/**
 * Import des variables nécessaires
 */

$WWW_DIR = dirname(__FILE__, 2);
require "${WWW_DIR}/functions/load_common_variables.php";
require "${WWW_DIR}/functions/common-functions.php";
require "${WWW_DIR}/functions/generateConf.php";
 
// Cas où ce script a été appelé en externe avec des arguments, on récupère ces arguments et on exécute directement la fonction de génération de conf
if (!empty($argv)) {
  if ($OS_FAMILY == "Redhat") {
	if (!empty($argv[1])) { $PID = $argv[1]; } else { throw new Exception("Erreur : pid non défini"); }
	if (!empty($argv[2])) { $LOGNAME = $argv[2]; } else { throw new Exception("Erreur : logname non défini"); }
	if (!empty($argv[3])) { $repoName = $argv[3]; } else { throw new Exception("Erreur : nom du repo non défini"); }
	if (!empty($argv[4])) { $repoRealname = $argv[4]; } else { throw new Exception("Erreur : vrai nom du repo non défini"); }
	if (!empty($argv[5])) { $repoGpgCheck = $argv[5]; } else { throw new Exception("Erreur : gpg check non défini"); }
	if (!empty($argv[6])) { $repoGpgResign = $argv[6]; } else { throw new Exception("Erreur : gpg resign non défini"); }
	if (!empty($argv[7])) { $repoGroup = $argv[7]; if ($repoGroup == "nogroup") { $repoGroup = ''; } } else { $repoGroup = ''; }
	if (!empty($argv[8])) { $repoDescription = $argv[8]; if ($repoDescription == "nodescription") { $repoDescription = ''; } } else { $repoDescription = ''; }
  }
 
  // Debian : on attends 2 autres arguments (dist et section)
  if ($OS_FAMILY == "Debian") {
	if (!empty($argv[1])) { $PID = $argv[1]; } else { throw new Exception("Erreur : pid non défini"); }
	if (!empty($argv[2])) { $LOGNAME = $argv[2]; } else { throw new Exception("Erreur : logname non défini"); }
	if (!empty($argv[3])) { $repoName = $argv[3]; } else { throw new Exception("Erreur : nom du repo non défini"); }
	if (!empty($argv[4])) { $repoDist = $argv[4]; } else { throw new Exception("Erreur : nom de la distribution non défini"); }
	if (!empty($argv[5])) { $repoSection = $argv[5]; } else { throw new Exception("Erreur : nom de la section non défini"); }
	if (!empty($argv[6])) { $repoHostName = $argv[6]; } else { throw new Exception("Erreur : hostname non défini"); }
	if (!empty($argv[7])) { $repoGpgCheck = $argv[7]; } else { throw new Exception("Erreur : gpg check non défini"); }
	if (!empty($argv[8])) { $repoGpgResign = $argv[8]; } else { throw new Exception("Erreur : gpg resign non défini"); }
	if (!empty($argv[9])) { $repoGroup = $argv[9]; if ($repoGroup == "nogroup") { $repoGroup = ''; } } else { $repoGroup = ''; }
	if (!empty($argv[10])) { $repoDescription = $argv[10]; if ($repoDescription == "nodescription") { $repoDescription = ''; } } else { $repoDescription = ''; }
  }
}

//// TRAITEMENT ////

$status = "running";
$steps = 5;

/**
 * ETAPE 0 : Création d'un répertoire temporaire
 */

 if (is_dir("${TEMP_DIR}/${PID}")) { exec("rm -rf ${TEMP_DIR}/${PID}"); }
 if (!is_dir("${TEMP_DIR}/${PID}")) { mkdir("${TEMP_DIR}/${PID}", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/0")) { mkdir("${TEMP_DIR}/${PID}/0", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/1")) { mkdir("${TEMP_DIR}/${PID}/1", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/2")) { mkdir("${TEMP_DIR}/${PID}/2", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/3")) { mkdir("${TEMP_DIR}/${PID}/3", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/4")) { mkdir("${TEMP_DIR}/${PID}/4", 0770, true); }
 if (!is_dir("${TEMP_DIR}/${PID}/5")) { mkdir("${TEMP_DIR}/${PID}/5", 0770, true); }

 // Lancement du script externe qui va construire le fichier de log principal à partir des petits fichiers de log de chaque étape
 exec("php ${WWW_DIR}/operations/check_running.php $MAIN_LOGS_DIR/$LOGNAME $TEMP_DIR/$PID $steps >/dev/null 2>/dev/null &");

/**
 * ETAPE 1 : Détails de l'opération
 */

 try {

	$stepLog = "$TEMP_DIR/$PID/1/1.log";
	if (!file_exists($stepLog)) { touch($stepLog); }
	if ($OS_FAMILY == "Redhat") { printDetails(compact('stepLog', 'repoName', 'repoRealname', 'repoGpgCheck', 'repoGpgResign', 'repoGroup', 'repoDescription'));}
 	if ($OS_FAMILY == "Debian") { list($repoHost, $repoRoot) = printDetails(compact('stepLog', 'repoName', 'repoDist', 'repoSection', 'repoHostName', 'repoGpgCheck', 'repoGpgResign', 'repoGroup', 'repoDescription'));}
 
 } catch(Exception $e) {

	file_put_contents($stepLog, $e->getMessage(), FILE_APPEND);
	completeOperation($TEMP_DIR, $PID); exit(1);

 }

/**
 * ETAPE 2 : Récupération des paquets
 */

 try {

	$stepLog = "$TEMP_DIR/$PID/2/2.log";
	if (!file_exists($stepLog)) { touch($stepLog); }
	if ($OS_FAMILY == "Redhat") { getPackages(compact('stepLog', 'repoName', 'repoRealname', 'repoGpgCheck'));}
	if ($OS_FAMILY == "Debian") { getPackages(compact('stepLog', 'repoName', 'repoDist', 'repoSection', 'repoHostName', 'repoGpgCheck', 'repoHost', 'repoRoot'));}

 } catch(Exception $e) {

	file_put_contents($stepLog, $e->getMessage(), FILE_APPEND);
	completeOperation($TEMP_DIR, $PID); exit(1);
 }

/**
 * ETAPE 3 : Signature des paquets / du repo
 */

 try {

	$stepLog = "$TEMP_DIR/$PID/3/3.log";
	if (!file_exists($stepLog)) { touch($stepLog); }
	if ($OS_FAMILY == "Redhat") { signPackages(compact('stepLog', 'repoName', 'repoGpgResign'));}
	if ($OS_FAMILY == "Debian") { signPackages(compact('stepLog', 'repoName', 'repoDist', 'repoSection', 'repoGpgResign'));}

 } catch(Exception $e) {

	file_put_contents($stepLog, $e->getMessage(), FILE_APPEND);
	completeOperation($TEMP_DIR, $PID); exit(1);
 }

/**
 * ETAPE 4 : Création du repo
 */

 try {

	$stepLog = "$TEMP_DIR/$PID/4/4.log";
	if (!file_exists($stepLog)) { touch($stepLog); }
	if ($OS_FAMILY == "Redhat") { createRepo(compact('stepLog', 'repoName', 'repoRealname', 'repoGroup', 'repoDescription'));}
	if ($OS_FAMILY == "Debian") { createRepo(compact('stepLog', 'repoName', 'repoDist', 'repoSection', 'repoHostName', 'repoGroup', 'repoDescription'));}

 } catch(Exception $e) {

	file_put_contents($stepLog, $e->getMessage(), FILE_APPEND);
	completeOperation($TEMP_DIR, $PID); exit(1);
 }

/**
 * ETAPE 5 : mise à jour des fichiers de liste
 */

try {

	$stepLog = "$TEMP_DIR/$PID/5/5.log";
	if (!file_exists($stepLog)) { touch($stepLog); }
	if ($OS_FAMILY == "Redhat") { updateReposLists(compact('stepLog', 'repoName', 'repoRealname', 'repoGroup', 'repoDescription'));}
	if ($OS_FAMILY == "Debian") { updateReposLists(compact('stepLog', 'repoName', 'repoDist', 'repoSection', 'repoHostName', 'repoGroup', 'repoDescription'));}

 } catch(Exception $e) {

	file_put_contents($stepLog, $e->getMessage(), FILE_APPEND);
	completeOperation($TEMP_DIR, $PID); exit(1);
 }

// Cloture de l'opération si tout s'est bien passé
completeOperation($TEMP_DIR, $PID);
exit(0);
?>