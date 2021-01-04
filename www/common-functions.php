<?php

// Fonction de vérification des données envoyées par formulaire
function validateData($data){
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

function printAlert($message) {
  echo "<div class=\"alert\">";
  echo "<p>${message}</p>";
  echo "</div>";

  echo '<script type="text/javascript">';
  echo '$(document).ready(function () {';
  echo 'window.setTimeout(function() {';
  echo '$(".alert").fadeTo(1000, 0).slideUp(1000, function(){';
  echo '$(this).remove();';
  echo '});';
  echo '}, 2500);';
  echo '});';
  echo '</script>';
}

// vérification d'une nouvelle mise à jour github
function checkUpdate($BASE_DIR, $VERSION) {
  $GIT_VERSION= exec("grep 'GITHUB_VERSION=' ${BASE_DIR}/cron/github.version | awk -F= '{print $2}' | sed 's/\"//g'");
  
  if (empty($GIT_VERSION)) {
    //echo "version : $GIT_VERSION";
    echo "<p>Erreur lors de la vérification des nouvelles mises à jour</p>";
  } elseif ("$VERSION" !== "$GIT_VERSION") {
    echo "<p>Une nouvelle version est disponible</p>";
  }
}
?>