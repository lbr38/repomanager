<h5>REPOS ARCHIVÉS</h5>
<table class="list-repos">
    <thead>
        <tr>
        <?php
          echo "<td>Nom</td>";
          if ($OS_TYPE == 'deb') {
            echo "<td>Distribution</td>";
            echo "<td>Section</td>";
          }
          echo "<td>Date</td>";
          echo "<td>Description</td>";
        ?>
        </tr>
    </thead>
    <tbody>
        <tr>
        <?php
        $repo_file = file_get_contents($REPO_ARCHIVE_FILE);
        $rows = explode("\n", $repo_file);

        foreach($rows as $row => $data) {
          if(!empty($data) AND $data !== "[ARCHIVE]") { // on ne traite pas les lignes vides ni la ligne [ARCHIVE] (1ère ligne du fichier)
            //get row data
            $row_data = explode(',', $data);
            
            if ($OS_TYPE == "rpm") {
              $repoName = str_replace(['Name=', '"'], '', $row_data[0]);
              $repoDate = str_replace(['Date=', '"'], '', $row_data[2]);
              $repoDescription = str_replace(['Description=', '"'], '', $row_data[3]);
            }
            if ($OS_TYPE == "deb") {
              $repoName = str_replace(['Name=', '"'], '', $row_data[0]);
              $repoDist = str_replace(['Dist=', '"'], '', $row_data[2]);
              $repoSection = str_replace(['Section=', '"'], '', $row_data[3]);
              $repoDate = str_replace(['Date=', '"'], '', $row_data[4]);
              $repoDescription = str_replace(['Description=', '"'], '', $row_data[5]);
            }
        
            //display data
            echo "<tr>";
            echo "<td>$repoName</td>";
            if ($OS_TYPE == "deb") {
              echo "<td>$repoDist</td>";
              echo "<td>$repoSection</td>";
            }
            echo "<td>$repoDate</td>";
            echo "<td title=\"${repoDescription}\">$repoDescription</td>"; // avec un title afin d'afficher une info-bulle au survol (utile pour les descriptions longues)
            echo "</tr>";
          }
        }?>
    </tbody>
</table>