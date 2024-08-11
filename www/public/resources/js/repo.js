
/**
 *  Search for a repo (search input)
 */
function searchRepo()
{
    /**
     *  If input is empty, then show all repos and quit
     */
    if (!$("#repo-search-input").val()) {
        $('.repos-list-group, .repos-list-group-flex-div').show();
        return;
    }

    printLoading();

    /**
     *  Retrieve search input value
     *  Convert to uppercase to ignore case when searching
     */
    search = $("#repo-search-input").val().toUpperCase();

    /**
     *  Remove all spaces from search
     */
    search = search.replaceAll(' ', '');

    /**
     *  First, hide all repos groups
     */
    $('.repos-list-group, .repos-list-group-flex-div').hide();

    /**
     *  Then search in every repo group of there is a repo or dist or section matching the search
     */
    $('.repos-list-group').each(function () {
        /**
         *  Retrieve all repos lines
         */
        $('.repos-list-group-flex-div').each(function () {
            repoName = $(this).find('.item-repo').attr('name');
            repoDist = $(this).find('.item-repo').attr('dist');
            repoSection = $(this).find('.item-repo').attr('section');
            repoReleasever = $(this).find('.item-repo').attr('releasever');

            /**
             *  If repo name contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (repoName.toUpperCase().indexOf(search) > -1) {
                $(this).show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo dist contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (repoDist.toUpperCase().indexOf(search) > -1) {
                $(this).show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo section contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (repoSection.toUpperCase().indexOf(search) > -1) {
                $(this).show();
                $(this).parents('.repos-list-group').show();
            }

            /**
             *  If repo releasever contains the search then display 'repos-list-group-flex-div' and its parent 'repos-list-group'
             */
            if (repoReleasever.toUpperCase().indexOf(search) > -1) {
                $(this).show();
                $(this).parents('.repos-list-group').show();
            }
        });
    });

    hideLoading();
}

/**
 *  Events listeners
 */

/**
 *  Event: create new repo: print description field only if an env is specified
 */
$(document).on('change','#new-repo-target-env-select',function () {
    if ($('#new-repo-target-env-select').val() == "") {
        $('#new-repo-target-description-tr').hide();
    } else {
        $('#new-repo-target-description-tr').show();
    }
}).trigger('change');

/**
 *  Event: print/hide all repos groups
 */
$(document).on('click','#hideAllReposGroups',function () {
    var state = $(this).attr('state');

    /**
     *  If actual state is 'visible' then hide all groups
     */
    if (state == 'visible') {
        /**
         *  Change state to 'hidden'
         */
        $(this).attr('state', 'hidden');
        $(this).find('img').attr('src', 'assets/icons/down.svg');

        /**
         *  Retrieve all groups and hide them if they are visible
         */
        $('.repo-list-group-container').each(function () {
            /**
             *  Retrieve group id
             */
            var id = $(this).attr('group-id');

            /**
             *  If the group is visible then hide it, else do nothing
             */
            if ($(this).is(":visible")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        /**
         *  Change all up/down icons to 'down'
         */
        $('img.hideGroup').attr('src', 'assets/icons/down.svg');
    }

    /**
     *  If actual state is 'hidden' then show all groups
     */
    if (state == 'hidden') {
        /**
         *  Change state to 'visible'
         */
        $(this).attr('state', 'visible');
        $(this).find('img').attr('src', 'assets/icons/up.svg');

        /**
         *  Retrieve all groups and show them if they are hidden
         */
        $('.repo-list-group-container').each(function () {
            /**
             *  Retrieve group id
             */
            var id = $(this).attr('group-id');

            /**
             *  If the group is hidden then show it, else do nothing
             */
            if ($(this).is(":hidden")) {
                slide('.repo-list-group-container[group-id="' + id + '"]');
            }
        });

        /**
         *  Change all up/down icons to 'up'
         */
        $('img.hideGroup').attr('src', 'assets/icons/up.svg');
    }
});

/**
 *  Event: show / hide repos group content
 */
$(document).on('click','.hideGroup',function () {
    var id = $(this).attr('group-id');
    var state = $(this).attr('state');

    if (state == 'visible') {
        $(this).attr('state', 'hidden');
        $(this).attr('src', 'assets/icons/down.svg');
    }

    if (state == 'hidden') {
        $(this).attr('state', 'visible');
        $(this).attr('src', 'assets/icons/up.svg');
    }

    slide('.repo-list-group-container[group-id="' + id + '"]');
});

/**
 *  Event: print env delete and install buttons
 */
$(document).on('mouseenter','.item-env, .item-env-info',function () {
    var envId = $(this).attr('env-id');
    $('#repos-list-container').find('.item-env-info[env-id="' + envId + '"]').css('visibility', 'visible');
});

/**
 *  Event: hide env delete and install buttons
 */
$(document).on('mouseleave','.item-env, .item-env-info',function () {
    $('#repos-list-container').find('.item-env-info').css('visibility', 'hidden');
});

/**
 *  Event : modification de la description d'un repo
 */
$(document).on('keypress','.repoDescriptionInput',function () {
    var keycode = (event.keyCode ? event.keyCode : event.which);
    if (keycode == '13') {
        /**
         *  Récupération des valeurs suivantes :
         *   - L'Id du repo à modifier
         *   - Le status du repo
         *   - La description
         */
        var envId = $(this).attr('env-id');
        var description = $(this).val();

        setRepoDescription(envId, description);
    }
    event.stopPropagation();
});

/**
 *  Event: generate repo configuration for client
 */
$(document).on('click','.client-configuration-btn',function () {
    /**
     *  Delete all other divs if any
     */
    $(".divReposConf").remove();

    /**
     *  Retrieve repo infos
     */
    var packageType = $(this).attr('package-type');
    var repoName = $(this).attr('repo');
    var repoEnv = $(this).attr('env');

    /**
     *  If packageType is 'deb' then retrieve dist and section
     */
    if (packageType == "deb") {
        var repoDist = $(this).attr('dist');
        var repoSection = $(this).attr('section');
        var arch = $(this).attr('arch');

        /**
         *  If dist name contains a slash, replace it by a dash to avoid creating a file with a slash in its name
         */
        var repoDistFormatted = repoDist.replace('/', '-');
    }

    var repo_dir_url = $(this).attr('repo-dir-url');
    var repo_conf_files_prefix = $(this).attr('repo-conf-files-prefix');
    var www_hostname = $(this).attr('www-hostname');

    if (packageType == "rpm") {
        var commands = 'echo -e "[' + repo_conf_files_prefix + '' + repoName + '_' + repoEnv + ']\nname=' + repoName + ' repo on ' + www_hostname + '\nbaseurl=' + repo_dir_url + '/' + repoName + '_' + repoEnv + '\nenabled=1\ngpgkey=' + repo_dir_url + '/gpgkeys/' + www_hostname + '.pub\ngpgcheck=1" > /etc/yum.repos.d/' + repo_conf_files_prefix + '' + repoName + '.repo';
    }
    if (packageType == "deb") {
        var commands = 'curl -sS ' + repo_dir_url + '/gpgkeys/' + www_hostname + '.pub | gpg --dearmor > /etc/apt/trusted.gpg.d/' + www_hostname + '.gpg\n\n';
        commands    += 'echo "deb ' + repo_dir_url + '/' + repoName + '/' + repoDist + '/' + repoSection + '_' + repoEnv + ' ' + repoDist + ' ' + repoSection + '" > /etc/apt/sources.list.d/' + repo_conf_files_prefix + '' + repoName + '_' + repoDistFormatted + '_' + repoSection + '.list';

        /**
         *  If 'src' arch is present in $arch then add src repo
         */
        if (arch.includes('src')) {
            commands += '\necho "deb-src ' + repo_dir_url + '/' + repoName + '/' + repoDist + '/' + repoSection + '_' + repoEnv + ' ' + repoDist + ' ' + repoSection + '" >> /etc/apt/sources.list.d/' + repo_conf_files_prefix + '' + repoName + '_' + repoDistFormatted + '_' + repoSection + '.list';
        }
    }

    /**
     *  Generation of the div
     */
    $('body').append('<div class="divReposConf hide"><span><img title="Close" class="divReposConf-close close-btn lowopacity" src="/assets/icons/close.svg" /></span><h3>INSTALLATION</h3><h5>Use the code below to install the repo on a host:</h5><div id="divReposConfCommands-container"><pre id="divReposConfCommands">' + commands + '</pre><img src="/assets/icons/duplicate.svg" class="icon-lowopacity" title="Copy to clipboard" onclick="copyToClipboard(divReposConfCommands)" /></div></div>');

    /**
     *  Print
     */
    $('.divReposConf').show();
});

/**
 *  Event : fermeture de la configuration client
 */
$(document).on('click','.divReposConf-close',function () {
    $(".divReposConf").remove();
});


/**
 *  Ajax : Modifier la description d'un repo
 *  @param {string} repoId
 *  @param {string} repoDescription
 */
function setRepoDescription(envId, repoDescription)
{
    $.ajax({
        type: "POST",
        url: "/ajax/controller.php",
        data: {
            controller: "repo",
            action: "setRepoDescription",
            envId: envId,
            description: repoDescription
        },
        dataType: "json",
        success: function (data, textStatus, jqXHR) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'success');
        },
        error: function (jqXHR, ajaxOptions, thrownError) {
            jsonValue = jQuery.parseJSON(jqXHR.responseText);
            printAlert(jsonValue.message, 'error');
        },
    });
}
