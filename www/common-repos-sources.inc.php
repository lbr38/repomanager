<?php
require_once('class/Source.php');
$source = new Source();

// Cas où on souhaite ajouter un nouveau repo source : 
if (!empty($_POST['addSourceName'])) {
    $source->new(validateData($_POST['addSourceName']), validateData($_POST['addSourceUrl']));
}

// Cas où on souhaite supprimer un repo source :
if (!empty($_GET['action']) AND (validateData($_GET['action']) == "deleteSource") AND !empty($_GET['sourceName'])) {
    $source->delete(validateData($_GET['sourceName']));
}

// Cas où on souhaite renommer un repo source :
if (!empty($_POST['newSourceName']) AND !empty($_POST['actualSourceName']) AND !empty($_POST['newSourceUrl']) AND !empty($_POST['actualSourceUrl'])) {
    $source->name = validateData($_POST['actualSourceName']);
    $source->rename(validateData($_POST['newSourceName']), validateData($_POST['newSourceUrl']));
}

// Cas où on souhaite modifier la conf d'un repo source
if (!empty($_POST['actualSourceName']) AND !empty($_POST['action']) AND validateData($_POST['action']) == "editRepoSourceConf" AND !empty($_POST['option'])) {
    $sourceName = validateData($_POST['actualSourceName']);
    $sourceFile = "$REPOMANAGER_YUM_DIR/${sourceName}.repo"; // Le fichier dans lequel on va écrire
    $options = $_POST['option'];

    $content = "[${sourceName}]".PHP_EOL;
    foreach ($options as $option) {
        $content = $content . $option['name'] . "=" . $option['value'] . PHP_EOL;
    }
    file_put_contents("$REPOMANAGER_YUM_DIR/${sourceName}.repo", $content);
}

?>

<img id="ReposSourcesCloseButton" title="Fermer" class="icon-lowopacity" src="icons/close.png" />
<?php 
    if ($OS_FAMILY == "Redhat") { echo "<h5>REPOS SOURCES</h5>"; }
    if ($OS_FAMILY == "Debian") { echo "<h5>HOTES SOURCES</h5>"; }
?>
<p>Pour créer un miroir, repomanager doit connaitre l'URL du repo source.</p>
<br>

<p><b>Ajouter un nouveau repo source :</b></p>
<?php 
echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
// Cas Redhat/Centos
if ($OS_FAMILY == "Redhat") {
    echo '<span>Nom :</span><br>';
    echo '<input type="text" class="input-large" name="addSourceName" required /><br>';
    echo '<span>Url :</span><br>';
    echo '<select name="addSourceUrlType" class="select-small" required>';
    echo '<option value="baseurl">baseurl</option>';
    echo '<option value="mirrorlist">mirrorlist</option>';
    echo '<option value="metalink">metalink</option>';
    echo '</select>';
    echo '<input type="text" name="addSourceUrl" class="input-large"><br>';
    echo '<span>Ce repo source dispose d\'une clé GPG : </span>';
    echo '<select id="newRepoSourceSelect" class="select-small">';
    echo '<option id="newRepoSourceSelect_no">Non</option>';
    echo '<option id="newRepoSourceSelect_yes">Oui</option>';
    echo '</select>';
    echo '<div class="sourceGpgDiv hide">';
    echo '<br>';
    echo '<span>Vous pouvez utilisez une clé déjà présente dans le trousseau de repomanager ou renseignez l\'URL vers la clé GPG ou bien importer une nouvelle clé GPG au format texte dans le trousseau de repomanager.</span><br>';
    echo '<span>Clé GPG du trousseau de repomanager :</span><br>';
    echo '<select name="existingGpgKey">';
    echo '<option value="">Choisir une clé GPG...</option>';
    $gpgFiles = scandir($RPM_GPG_DIR);
    foreach($gpgFiles as $gpgFile) {
      if (($gpgFile != "..") AND ($gpgFile != ".")) {
        echo "<option value=\"${gpgFile}\">${gpgFile}</option>";
      }
    }
    echo '</select>';
      echo '<span>URL vers une clé GPG :</span><br>';
    echo '<input type="text" name="gpgKeyURL" placeholder="https://"><br>';
    echo '<span>Importer une nouvelle clé GPG :</span><br>';
    echo '<textarea name="gpgKeyText" placeholder="Format ASCII"></textarea>';
    echo '</div>';
}

// Cas Debian
if ($OS_FAMILY == "Debian") {
    echo '<span>Nom :</span><br>';
    echo '<input type="text" class="input-large" name="addSourceName" required /><br>';
    echo '<span>Url :</span><br>';
    echo '<input type="text" class="input-large" name="addSourceUrl" required /><br>';
    echo '<span>Clé GPG (optionnelle) :</span><br>';
    echo '<textarea name="addSourceGpgKey" placeholder="Format ASCII" /></textarea>'; 
}
?>
<br>
<button type="submit" class="button-submit-medium-blue" title="Ajouter">Ajouter</button>
</form>
<br>
<?php
/**
 *  LISTE DES CLES GPG DU TROUSSEAU DE REPOMANAGER
 */

/**
 *  Dans le cas de rpm, les clés gpg sont stockées dans $RPM_GPG_DIR (en principe par défaut /etc/pki/rpm-gpg/repomanager)
*/
if ($OS_FAMILY == "Redhat") {
    $gpgKeys = scandir($RPM_GPG_DIR);
}

/**
 *  Dans le cas de apt, les clés sont stockées dans le trousseau GPG 'trustedkeys.gpg' de repomanager
 */
if ($OS_FAMILY == "Debian") {
    $gpgKeys = shell_exec("gpg --no-default-keyring --keyring ${GPGHOME}/trustedkeys.gpg --list-key --fixed-list-mode --with-colons | sed 's/^pub/\\npub/g' | grep -v '^tru:'");
    $gpgKeys = explode("\n\n", $gpgKeys);
}

if (!empty($gpgKeys)) {
    echo '<p><b>Liste des clés GPG du trousseau de repomanager :</b></p>';
    echo '<table class="table-large">';

    $j=0;
    foreach($gpgKeys as $gpgKey) {
        if ($OS_FAMILY == "Redhat") {
            if (($gpgKey != "..") AND ($gpgKey != ".")) {
                echo '<tr>';
                echo '<td>';
                echo "<img class=\"gpgKeyDeleteToggle${j} icon-lowopacity\" title=\"Supprimer la clé GPG ${gpgKey}\" src=\"icons/bin.png\" />";
                deleteConfirm("Êtes-vous sûr de vouloir supprimer la clé ${gpgKey}", "?action=deleteGpgKey&gpgKeyFile=${gpgKey}", "gpgKeyDeleteDiv${j}", "gpgKeyDeleteToggle${j}");
                echo '</td>';
                echo '<td>';
                echo $gpgKey;
                echo '</td>';
                echo '</tr>';
            }
        }
        if ($OS_FAMILY == "Debian") {
            $gpgKeyID = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^fpr:' | awk -F':' '{print $10}'"); // on récup uniquement l'ID de la clé GPG
            $gpgKeyID = preg_replace('/\s+/', '', $gpgKeyID); // retire tous les espaces blancs
            $gpgKeyName = shell_exec("echo \"$gpgKey\" | sed -n -e '/pub/,/uid/p' | grep '^uid:' | awk -F':' '{print $10}'");
            if (!empty($gpgKeyID) AND !empty($gpgKeyName)) {
                echo '<tr>';
                echo '<td>';
                echo "<img src=\"icons/bin.png\" class=\"gpgKeyDeleteToggle${j} icon-lowopacity\" title=\"Supprimer la clé GPG ${gpgKeyID}\" />";
                deleteConfirm("Êtes-vous sûr de vouloir supprimer la clé ${gpgKeyName}", "?action=deleteGpgKey&gpgKeyID=${gpgKeyID}", "gpgKeyDeleteDiv${j}", "gpgKeyDeleteToggle${j}");
                echo '</td>';
                echo '<td>';
                echo "$gpgKeyName ($gpgKeyID)";
                echo '</td>';
                echo '</tr>';
            }
        }
        ++$j;
    }
    echo '</table>';
} ?>
    

<br>
  	<?php
  	/**
   	 *  AFFICHAGE DES REPOS SOURCES ACTUELS
     */

    /**
     *  1. Récupération de tous les noms de sources
     */

    if ($OS_FAMILY == "Redhat") {
        $sourcesList = scandir($REPOMANAGER_YUM_DIR);
    }
    if ($OS_FAMILY == "Debian") {
        $sourcesList = $source->listAll();
    }

    /**
     *  2. Affichage des groupes si il y en a
     */

    if (!empty($sourcesList)) {
		echo "<p><b>Repos sources actuels :</b></p>";
		$i = 0;

      	foreach($sourcesList as $source) {
            if ($OS_FAMILY == "Redhat") {
                if (($source == "..") OR ($source == ".") OR ($source == "repomanager.conf")) {
                    continue;
                }
                $sourceName = str_replace(".repo", "", $source);
                // on récupère le contenu du fichier
                $content = explode("\n", file_get_contents("${REPOMANAGER_YUM_DIR}/${source}", true));
            }
            if ($OS_FAMILY == "Debian") {
                $sourceName = $source['Name'];
                $sourceUrl = $source['Url'];
            }

        	echo '<div class="sourceDiv">';

			/**
			 *   3. On créé un formulaire pour chaque groupe, car chaque groupe sera modifiable :
			 */

			echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";

			// On veut pouvoir renommer le repo source, donc il faut transmettre le nom de repo source actuel (actualSourceName)
            // Idem pour l'url (Debian seulement)
			echo "<input type=\"hidden\" name=\"actualSourceName\" value=\"${sourceName}\" />";
            if ($OS_FAMILY == "Debian") {
                echo "<input type=\"hidden\" name=\"actualSourceUrl\" value=\"${sourceUrl}\" />";
            }

			echo '<table class="table-large">';
			echo '<tr>';
			// On affiche le nom actuel du repo source dans un input type=text qui permet de renseigner un nouveau nom si on le souhaite (newSourceName)
            // Idem pour l'url (Debian seulement)
			echo "<td><input type=\"text\" value=\"${sourceName}\" name=\"newSourceName\" class=\"input-medium invisibleInput-blue\" /></td>";
            if ($OS_FAMILY == "Debian") {
                echo "<td><input type=\"text\" value=\"${sourceUrl}\" name=\"newSourceUrl\" class=\"input-medium invisibleInput-blue\" /></td>";
            }
		
			// Boutons configuration et suppression du repo source
			echo '<td class="td-fit">';
			if ($OS_FAMILY == "Redhat") {
                echo "<img id=\"sourceConfigurationToggleButton${i}\" class=\"icon-mediumopacity\" title=\"Configuration de $sourceName\" src=\"icons/cog.png\" />";
            }
			echo "<img src=\"icons/bin.png\" class=\"sourceDeleteToggleButton${i} icon-lowopacity\" title=\"Supprimer le repo source ${sourceName}\" />";
			deleteConfirm("Etes-vous sûr de vouloir supprimer le repo source $sourceName", "?action=deleteSource&sourceName=${sourceName}", "sourceDeleteDiv${i}", "sourceDeleteToggleButton${i}");
			echo '</td>';
			echo '</tr>';
			echo '</table>';
            echo '<input type="submit" class="input-hidden" />';
			echo '</form>';

			/**
			 *  4. La liste des repos sources est placée dans un div caché
			 */
            if ($OS_FAMILY == "Redhat") {
                echo "<div id=\"sourceConfigurationTbody${i}\" class=\"hide sourceDivConf\">";
            
                echo '<p>Paramètres :</p>';

                // On va récupérer la configuration du repo source et l'afficher      
                echo "<form action=\"${actual_uri}\" method=\"post\" autocomplete=\"off\">";
                // Il faut transmettre le nom du repo source dans le formulaire, donc on ajoute un input caché avec le nom du repo source
                echo "<input type=\"hidden\" name=\"actualSourceName\" value=\"${sourceName}\" />";
                echo '<input type="hidden" name="action" value="editRepoSourceConf" />';
                $j = 0;
                foreach ($content as $option) {
                    if (empty($option)) { continue; }
                    $optionName = exec("echo '$option' | awk -F'=' '{print $1}'");
                    $optionValue = exec("echo '$option' | cut -d'=' -f 2-");
                    if ($optionName == "[$sourceName]") { continue; }
                    if (substr($optionName, 0, 1 ) === "#") { continue; }

                    echo "<input type=\"text\" class=\"input-small\" name=\"option[$j][name]\" value=\"$optionName\" readonly />";
                    if ($optionValue == "1" OR $optionValue == "0") {
                        echo "<input type=\"radio\" id=\"${sourceName}_${optionName}_enabled_yes\" name=\"option[$j][value]\" value=\"1\" "; if ($optionValue == 1) { echo 'checked />'; } else { echo '/>'; }
                        echo "<label for=\"${sourceName}_${optionName}_enabled_yes\">Yes</label>";
                        echo "<input type=\"radio\" id=\"${sourceName}_${optionName}_enabled_no\" name=\"option[$j][value]\" value=\"0\" "; if ($optionValue == 0) { echo 'checked />'; } else { echo '/>'; }
                        echo "<label for=\"${sourceName}_${optionName}_enabled_no\">No</label>";
                    } else {
                        echo "<input type=\"text\" class=\"input-large\" name=\"option[$j][value]\" value=\"$optionValue\" />";
                    }
                    echo '<br>';
                    ++$j;
                }
                echo '<br>';
                echo '<a href="javascript:;" id="add-new-param">Ajouter un paramètre</a>';
                echo '<br>';
                echo '<button type="submit" class="button-submit-large-blue" title="Enregistrer">Enregistrer</button>';
                echo '</form>';
                echo '<br>';
                echo '</div>'; // cloture de sourceConfigurationTbody${i}

                // Afficher ou masquer la div 'sourceConfigurationTbody' :
                echo "<script>";
                echo "$(document).ready(function(){";
                echo "$(\"#sourceConfigurationToggleButton${i}\").click(function(){";
                echo "$(\"div#sourceConfigurationTbody${i}\").slideToggle(150);";
                echo '$(this).toggleClass("open");';
                echo "});";
                echo "});";
                echo "</script>";

                echo '</div>'; // cloture de sourceDivConf

                echo "
                <script>
                document.getElementById('add-new-param').onclick = function () {
                    let template = '<input type=\"text\" class=\"input-small\" name=\"option[${j}][name]\" readonly /><input type=\"text\" class=\"input-large\" name=\"option[${j}][value]\" />';
                
                    let container = document.getElementById('sourceConfigurationTbody${i}');
                    let toto = document.createElement('span');
                    toto.innerHTML = template;
                    container.appendChild(toto);
                }
                </script>";
                
            }
            ++$i;
            echo '</div>'; // cloture de sourceDiv
      	}
    }
   ?>
 </table>

<script> 
$(document).ready(function(){
    $("#ReposSourcesSlideUpButton").click(function(){            
        // affichage du div permettant de gérer les sources
        $("#sourcesDiv").animate({
            width: '97%',
            padding: '10px',
            opacity: 1
        });
    });
    
    $("#ReposSourcesCloseButton").click(function(){
        // masquage du div permettant de gérer les sources
        $("#sourcesDiv").delay(50).animate({
            opacity: 0,
            width: 0,
            padding: '0px'
        });
    });
});


// Redhat : afficher ou masquer les inputs permettant de renseigner une clé gpg à importer, en fonction de la valeur du select
$(function() {
  $("#newRepoSourceSelect").change(function() {
    if ($("#newRepoSourceSelect_yes").is(":selected")) {
      $(".sourceGpgDiv").show();
    } else {
      $(".sourceGpgDiv").hide();
    }
  }).trigger('change');
});
</script>