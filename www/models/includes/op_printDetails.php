<?php
trait op_printDetails {
    /**
    *   Génération d'un tableau récapitulatif de l'opération
    *   Valide pour : 
    *    - un nouveau repo/section 
    *    - une mise à jour de repo/section
    *    - une reconstruction des métadonnées d'un repo/section
    */
    public function op_printDetails($params) {
        extract($params);

        ob_start();

        /**
         *  Affichage du tableau récapitulatif de l'opération
         */
        include(ROOT.'/templates/tables/op-new-update-duplicate-reconstruct.inc.php');

        $this->logcontent = ob_get_clean();
        file_put_contents($this->log->steplog, $this->logcontent);

        return true;
    }
}
?>