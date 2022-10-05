/**
 *  Rechargement régulier du bandeau de navigation pour
 *  faire apparaitre / disparaitre les opérations en cours ou terminées
 */
setInterval(function () {
    reloadHeader();
}, 5000);

/**
 *
 *  Fonctions utiles
 *
 */

function openSlide(id)
{
    $(id).animate({
        width: '100vw'
    }).show();
}

function closeSlide(id)
{
    $(id).animate({
        width: '0'
    }).hide('100');
}

/**
 *  Rechargement du bandeau de navigation
 */
function reloadHeader()
{
    $("#header-refresh").load("run.php?reload #header-refresh > *");
}

/**
 * Afficher un message d'alerte (success ou error)
 * @param {*} message
 * @param {*} type
 */
function printAlert(message, type = null, timeout = 2500)
{
    $('#newalert').remove();

    if (type == null) {
        $('footer').append('<div id="newalert" class="alert">' + message + '</div>');
    }
    if (type == "error") {
        $('footer').append('<div id="newalert" class="alert-error">' + message + '</div>');
    }
    if (type == "success") {
        $('footer').append('<div id="newalert" class="alert-success">' + message + '</div>');
    }

    if (timeout != 'none') {
        window.setTimeout(function () {
            $('#newalert').fadeTo(1500, 0).slideUp(1000, function () {
                $('#newalert').remove();
            });
        }, timeout);
    }
}

function deleteConfirm(message, myfunction, confirmBox = 'Delete')
{
    /**
     *  D'abord on supprime toute alerte déjà active et qui ne serait pas fermée
     */
    $("#newConfirmAlert").remove();

    var $content = '<div id="newConfirmAlert" class="confirmAlert"><span class="confirmAlert-message">' + message + '</span><div class="confirmAlert-buttons-container"><span class="pointer btn-doConfirm">' + confirmBox + '</span><span class="pointer btn-doCancel">Cancel</span></div></div>';

    $('footer').append($content);

    /**
     *  Si on clique sur le bouton 'Supprimer'
     */
    $('.btn-doConfirm').click(function () {
        /**
         *  Exécution de la fonction passée en paramètre
         */
        myfunction();

        /**
         *  Puis suppression de l'alerte
         */
        $("#newConfirmAlert").slideToggle(150, function () {
            $("#newConfirmAlert").remove();
        });
    });

    /**
     *  Si on clique sur le bouton 'Annuler'
     */
    $('.btn-doCancel').click(function () {
        /**
         *  Suppression de l'alerte
         */
        $("#newConfirmAlert").slideToggle(150, function () {
            $("#newConfirmAlert").remove();
        });
    });
}

/**
 * Rechargement du contenu d'un élément, par son Id
 * @param {string} id
 */
function reloadContentById(id)
{
    $('#' + id).load(location.href + ' #' + id + ' > *');
}

/**
 * Rechargement du contenu d'un élément par sa classe
 * @param {string} className
 */
function reloadContentByClass(className)
{
    $('.' + className).load(location.href + ' .' + className + ' > *');
}

/**
 *  Copie du contenu d'un élement dans le presse-papier
 *  @param {*} containerid
 */
function copyToClipboard(containerid)
{
    var range = document.createRange();
    range.selectNode(containerid);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    document.execCommand("copy");
    window.getSelection().removeAllRanges();

    printAlert('Copied to clipboard', 'success');
}

/**
 * Convert select tag to a select2 by specified id
 * @param {*} id
 */
function idToSelect2(id)
{
    $(id).select2({
        closeOnSelect: false,
        placeholder: 'Select...'
    });
}

/**
 * Convert select tag to a select2 by specified class
 * @param {*} className
 */
function classToSelect2(className)
{
    $(className).select2({
        closeOnSelect: false,
        placeholder: 'Select...'
    });
}

/**
 *  Event: hide slided window on escape button press
 */
$(document).keyup(function (e) {
    if (e.key === "Escape") {
        $('.param-slide-container').animate({
            width: '0'
        }).hide('100');
    }
});