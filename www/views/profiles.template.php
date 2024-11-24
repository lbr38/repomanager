<section class="section-main">
    <h3>PROFILES</h3>

    <p>
        You can create and manage configuration profiles for your client hosts that use <a href="https://github.com/lbr38/linupdate"><b>linupdate</b></a> with <b>reposerver</b> module enabled.<br>
        On every package update, hosts will first retrieve their configuration from this reposerver including the list of repositories they have access to, packages to exclude, services to restart after update, etc...<br>
    </p>

    <br><br>

    <div id="profilesDiv">
        <h5>CREATE A NEW PROFILE</h5>
        <form id="newProfileForm" autocomplete="off">
            <input id="newProfileInput" type="text" class="input-medium" />
            <button type="submit" class="btn-xxsmall-green" title="Add">+</button>
        </form>
        
        <br><br><br>

        <?php \Controllers\Layout\Container\Render::render('profiles/list'); ?>
    </div>
</section>