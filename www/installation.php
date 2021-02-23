<!DOCTYPE html>
<html>
<?php include('common-head.inc.php'); ?>

<header>
  <ul id="menu">
    <span id="title"><a href="index.php">Repomanager</a></span>
    <span id="version">BETA</span>
    <li><a href="installation.php">Installation</a></li>
  </ul>
</header>

<?php
  $WWW_DIR = dirname(__FILE__, 1);

  // Création du fichier de conf si n'existe pas (c'est pour ça qu'on est là)
  if (!is_dir("${WWW_DIR}/configurations")) { mkdir("${WWW_DIR}/configurations", 0770, true); }
  if (!file_exists("${WWW_DIR}/configurations/repomanager.conf")) { 
    touch("${WWW_DIR}/configurations/repomanager.conf");

    // On injecte les paramètres de configuration, certains avec des valeurs par défaut, d'autres avec des valeurs vides que l'utilisateur devra compléter
    $configuration = "[CONFIGURATION]";
    $configuration = "${configuration}\nPACKAGE_TYPE = \"\"";
    $configuration = "${configuration}\nEMAIL_DEST = \"\"";
    $configuration = "${configuration}\nMANAGE_PROFILES = \"\"";
    $configuration = "${configuration}\nREPO_CONF_FILES_PREFIX = \"\"";
    $configuration = "${configuration}\nDEBUG_MODE = \"disabled\"";
    $configuration = "${configuration}\n\n[GPG]";
    $configuration = "${configuration}\nGPG_SIGN_PACKAGES = \"\"";
    $configuration = "${configuration}\nGPG_KEYID = \"\"";
    $configuration = "${configuration}\n\n[UPDATE]";
    $configuration = "${configuration}\nUPDATE_AUTO = \"no\"";
    $configuration = "${configuration}\nUPDATE_BACKUP_ENABLED = \"yes\"";
    $configuration = "${configuration}\nUPDATE_BACKUP_DIR = \"${WWW_DIR}/backups\"";
    $configuration = "${configuration}\nUPDATE_BRANCH = \"\"";
    $configuration = "${configuration}\n\n[WWW]";
    $configuration = "${configuration}\nWWW_USER = \"" . get_current_user() . '"';
    $configuration = "${configuration}\nWWW_HOSTNAME = \"" . $_SERVER['HTTP_HOST'] . '"';
    $configuration = "${configuration}\nWWW_REPOS_DIR_URL = \"\"";
    $configuration = "${configuration}\n\n[AUTOMATISATION]";
    $configuration = "${configuration}\nAUTOMATISATION_ENABLED = \"no\"";
    $configuration = "${configuration}\nALLOW_AUTOUPDATE_REPOS = \"no\"";
    $configuration = "${configuration}\nALLOW_AUTOUPDATE_REPOS_ENV = \"no\"";
    $configuration = "${configuration}\nALLOW_AUTODELETE_ARCHIVED_REPOS = \"no\"";
    $configuration = "${configuration}\nRETENTION = \"2\"";
    $configuration = "${configuration}\n\n[CRON]";
    $configuration = "${configuration}\nCRON_DAILY_ENABLED = \"\"";
    $configuration = "${configuration}\nCRON_GENERATE_REPOS_CONF = \"yes\"";
    $configuration = "${configuration}\nCRON_APPLY_PERMS = \"yes\"";
    $configuration = "${configuration}\nCRON_PLAN_REMINDERS_ENABLED = \"no\"";

    file_put_contents("${WWW_DIR}/configurations/repomanager.conf", $configuration);
  }
?>

<body>

<!-- section 'conteneur' principal englobant toutes les sections de gauche -->
<section class="main">
    <section class="center">
      <h2>Assistant d'installation de Repomanager</h2>
        <p></p>
        <form action="install.php" method="post" autocomplete="off">










        </form>
    </section>
</section>

</body>
</html>