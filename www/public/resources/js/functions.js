/**
 *
 *  Fonctions utiles
 *
 */
function openPanel(name)
{
    $('.slide-panel-container[slide-panel=' + name + ']').css({
        visibility: 'visible'
    }).promise().done(function () {
        $('.slide-panel-container[slide-panel=' + name + ']').find('.slide-panel').animate({
            right: '0'
        })
    })
}

function closePanel()
{
    $('.slide-panel').animate({
        right: '-1000px',
    }).promise().done(function () {
        $('.slide-panel-container').css({
            visibility: 'hidden'
        })
    })
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
        $('footer').append('<div id="newalert" class="alert"><div>' + message + '</div></div>');
    }
    if (type == "error") {
        $('footer').append('<div id="newalert" class="alert-error"><div>' + message + '</div></div>');
    }
    if (type == "success") {
        $('footer').append('<div id="newalert" class="alert-success"><div>' + message + '</div></div>');
    }

    if (timeout != 'none') {
        window.setTimeout(function () {
            $('#newalert').fadeTo(1500, 0).slideUp(1000, function () {
                $('#newalert').remove();
            });
        }, timeout);
    }
}

function confirmBox(message, myfunction, confirmBox = 'Delete')
{
    /**
     *  D'abord on supprime toute alerte déjà active et qui ne serait pas fermée
     */
    $("#newConfirmAlert").remove();

    var $content = '<div id="newConfirmAlert" class="confirmAlert"><span></span><span>' + message + '</span><div class="confirmAlert-buttons-container"><span class="pointer btn-doConfirm">' + confirmBox + '</span><span class="pointer btn-doCancel">Cancel</span></div></div>';

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
        $("#newConfirmAlert").slideToggle(50, function () {
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
        $("#newConfirmAlert").slideToggle(50, function () {
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
function idToSelect2(id, placeholder = null, tags = false)
{
    if (placeholder == null) {
        placeholder = 'Select...';
    }

    $(id).select2({
        closeOnSelect: false,
        placeholder: placeholder,
        tags: tags,
        minimumResultsForSearch: Infinity /* disable search box */
    });
}

/**
 * Convert select tag to a select2 by specified class
 * @param {*} className
 */
function classToSelect2(className, placeholder = null, tags = false)
{
    if (placeholder == null) {
        placeholder = 'Select...';
    }

    $(className).select2({
        closeOnSelect: false,
        placeholder: placeholder,
        tags: tags,
        minimumResultsForSearch: Infinity /* disable search box */
    });
}

 /**
  *  Print OS icon image
  */
function printOsIcon(os = '', os_family = '')
{
    if (os != '') {
        if (os.toLowerCase() == 'centos') {
            return '<img src="assets/icons/products/centos.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/debian|armbian/i)) {
            return '<img src="assets/icons/products/debian.png" class="icon-np" title="' + os + '">';
        } else if (os.toLowerCase().match(/ubuntu|kubuntu|xubuntu|mint/i)) {
            return '<img src="assets/icons/products/ubuntu.png" class="icon-np" title="' + os + '">';
        }
    }

    /**
     *  If OS could not be found and OS family is specified
     */
    if (os_family != '') {
        if (os_family.toLowerCase().match(/debian|ubuntu|kubuntu|xubuntu|armbian|mint/i)) {
            return '<img src="assets/icons/products/debian.png" class="icon-np" title="' + os + '">';
        } else if (os_family.toLowerCase().match(/rhel|centos|fedora/i)) {
            return '<img src="assets/icons/products/redhat.png" class="icon-np" title="' + os + '">';
        }
    }

    /**
     *  Else return generic icon
     */
    return '<img src="assets/icons/products/tux.png" class="icon-np" title="' + os + '">';
}

/**
 * Get cookie value by name
 * @param {*} cname
 * @returns
 */
function getCookie(cname)
{
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');

    for (let i = 0; i <ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }

    return "";
}

/**
 * Set cookie value
 * @param {*} cname
 * @param {*} cvalue
 * @param {*} exdays
 */
function setCookie(cname, cvalue, exdays)
{
    const d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;Secure";
}