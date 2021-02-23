<?php
# Fonctions de pré-vérifications
# Vérification de l'action

/* Codes d'erreurs
CP = Check Planification
CP01
CP02
CP03 */


// Fonction de pré-vérifications
function checkAction($planID, $planAction) {
  if (empty($planAction)) {
    throw new Exception("Erreur (CP01) : Aucune action n'est spécifiée dans cette planification");
  }
  return true;
}
function checkAction_update_allowed($planID, $ALLOW_AUTOUPDATE_REPOS) {
  // Si la mise à jour des repos n'est pas autorisée, on quitte
  if ("$ALLOW_AUTOUPDATE_REPOS" != "yes") {
    throw new Exception("Erreur (CP02) : La mise à jour des miroirs par planification n'est pas autorisée. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres");
  }
  return true;
}
function checkAction_update_gpgCheck($planID, $planGpgCheck) {
  if (empty($planGpgCheck)) {
    throw new Exception("Erreur (CP03) : Vérification des signatures GPG non spécifié dans cette planification");
  }
  return true;
}
function checkAction_update_gpgResign($planID, $planGpgResign) {
  if (empty($planGpgResign)) {
    throw new Exception("Erreur (CP04) : Signature des paquets avec GPG non spécifié dans cette planification");
  }
  return true;
}
function checkAction_env_allowed($planID, $ALLOW_AUTOUPDATE_REPOS_ENV) {
  // Si le changement d'environnement n'est pas autorisé, on quitte
  if ("$ALLOW_AUTOUPDATE_REPOS_ENV" != "yes") {
    throw new Exception("Erreur (CP05) : Le changement d'environnement par planification n'est pas autorisé. Vous pouvez modifier ce paramètre depuis l'onglet Paramètres.");
  }
  return true;
}
?>