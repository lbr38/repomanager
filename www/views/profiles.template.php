<section class="section-main">
    <h3>PROFILES</h3>

    <p class="note">You can create and manage configuration profiles for hosts that use <a href="https://github.com/lbr38/linupdate" target="_blank" rel="noopener noreferrer" class="font-size-13"><b>linupdate</b></a> with <b>reposerver</b> module enabled. On every package update, the hosts will first retrieve their configuration from Repomanager including the list of repositories they have access to, packages to exclude, services to restart after update, etc...<br>
    </p>

    <br>

    <div id="profilesDiv">
        <h5>CREATE A NEW PROFILE</h5>
        <form id="newProfileForm" autocomplete="off">
            <input id="newProfileInput" type="text" class="input-medium" />
            <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
        </form>
        
        <br><br>

        <?php \Controllers\Layout\Container\Render::render('profiles/list'); ?>
    </div>
</section>