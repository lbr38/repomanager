/**
 *  Masque l'icone de chargement et fait apparaitre l'arborescence des fichiers
 */
$(document).ready(function(){

     setTimeout(function(){
        $('#loading').remove();
        $('#explorer').show();

        // hide all the sub-menus
	    $("span.explorer-toggle").next().hide();

        // add a link nudging animation effect to each link
        $("#explorer a, #explorer span.explorer-toggle").hover(
            function() {
                $(this).stop().animate( {
                    paddingLeft: '10px',
                }, 200);
            },
            function() {
                $(this).stop().animate( {
                    paddingLeft: '0',
                }, 200);
            }
        );

        // set the cursor of the toggling span elements
        $("span.explorer-toggle").css("cursor", "pointer");

        // prepend a plus sign to signify that the sub-menus aren't expanded
        $("span.explorer-toggle").prepend("+ ");

        // add a click function that toggles the sub-menu when the corresponding
        // span element is clicked
        $("span.explorer-toggle").click(function() {
            $(this).next().toggle(200);

            // switch the plus to a minus sign or vice-versa
            var v = $(this).html().substring( 0, 1 );
            if ( v == "+" )
                $(this).html( "-" + $(this).html().substring( 1 ) );
            else if ( v == "-" )
                $(this).html( "+" + $(this).html().substring( 1 ) );
        });
    },1000);
});

/**
 *  Fonction permettant de compter le nb de checkbox de paquets cochée
 */
function countChecked() {
    var countTotal = $('body').find('input[name=packageName\\[\\]]:checked').length
    return countTotal;
};

/**
 *  Event : lorsqu'on clique sur une checkbox, on affiche le bouton 'Supprimer'
 */
$(document).on('click',".packageName-checkbox",function(){ 
    // On compte le nombre de checkbox sélectionnées
    var count_checked = countChecked();
    // Si il y a au moins 1 checkbox sélectionnée alors on affiche le bouton 'Supprimer'
    if (count_checked >= 1) {
        $("#delete-packages-btn").show('200');
    }
    // Si aucune checkbox n'est sélectionnée alors on masque le bouton 'Supprimer'
    if (count_checked == 0) {
        $("#delete-packages-btn").hide('200');
    }
});