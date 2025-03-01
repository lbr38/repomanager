/**
 *  Event: print repository installation commands on environment change
 */
$(document).on('change','#repo-install-select-env',function () {
    event.preventDefault();

    /**
     *  Get environment name
     */
    var env = $(this).val();

    /**
     *  If no environment is selected, hide the install commands
     */
    if (env == '') {
        $('div#repository-install-commands-container').hide();
        return;
    }

    /**
     *  For each repository, print the installation commands
     */
    $('pre.repository-install-commands').each(function () {
        var url = $(this).attr('url');
        var hostname = $(this).attr('hostname');
        var prefix = $(this).attr('prefix');
        var packageType = $(this).attr('package-type');
        var name = $(this).attr('name');
        var dist = $(this).attr('dist');
        var component = $(this).attr('component');

        if (packageType == 'deb') {
            html  = 'cat << EOF > /etc/apt/sources.list.d/' + prefix + '' + name + '_' + dist + '_' + component + '.list\n';
            html += 'deb ' + url + '/' + name + '/' + dist + '/' + component + '_' + env + ' ' + dist + ' ' + component + '\n';
            html += 'EOF';
        }

        if (packageType == 'rpm') {
            html  = 'cat << EOF > /etc/yum.repos.d/' + prefix + name + '.repo\n'
            html += '['+ prefix + name + '_' + env + ']\n'
            html += 'name=' + name + ' repo on ' + hostname + '\n'
            html += 'baseurl=' + url + '/' + name + '_' + env + '\n'
            html += 'enabled=1\n'
            html += 'gpgkey=' + url + '/gpgkeys/' + hostname + '.pub\n'
            html += 'gpgcheck=1\n'
            html += 'EOF';
        }

        /**
         *  Print the environment next to the repositories
         */
        printEnv(env, '.repository-install-env');

        /**
         *  Append the installation commands
         */
        $(this).html(html);

        /**
         *  Print the installation commands
         */
        $('div#repository-install-commands-container').show();
    });

    return false;
});
