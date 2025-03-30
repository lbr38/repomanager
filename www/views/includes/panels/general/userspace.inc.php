<?php ob_start(); ?>

<h6>LOGIN</h6>
<input type="text" value="<?= $_SESSION['username'] ?>" readonly />

<h6>ROLE</h6>
<input type="text" value="<?= $_SESSION['role'] ?>" readonly />

<h6>API KEY</h6>
<p class="note">The API key is used to authenticate for most of the API endpoints. Keep it secret.</p>
<input type="text" id="user-apikey" value="(hashed) Generate a new key to retrieve it in clear." />

<div class="flex column-gap-10 margin-top-5">
    <button type="button" id="user-generate-apikey-btn" class="btn-medium-blue" title="Generate a new key">Generate new key</button>
</div>

<h6 class="margin-bottom-5">LOGOUT FROM REPOMANAGER</h6>
<a href="/logout" title="Logout">
    <button class="btn-small-red" >Logout</button>
</a>

<h5>PERSONAL INFORMATIONS</h5>

<div>
    <form id="user-edit-info" autocomplete="off">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>" />
        <h6>FIRST NAME</h6>
        <input type="text" class="input-large" name="first-name" value="<?php echo !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>" <?php echo $_SESSION['type'] != 'local' ? 'readonly' : ''; ?>>

        <h6>LAST NAME</h6>
        <input type="text" class="input-large" name="last-name" value="<?php echo !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>" <?php echo $_SESSION['type'] != 'local' ? 'readonly' : ''; ?>>

        <h6>EMAIL</h6>
        <input type="email" class="input-large" name="email" value="<?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>" <?php echo $_SESSION['type'] != 'local' ? 'readonly' : ''; ?>>

        <br><br>
        <?php if ($_SESSION['type'] == 'local') : ?>
            <button class="btn-small-green">Save</button>
        <?php endif; ?>
    </form>
</div>

<?php
if ($_SESSION['type'] == 'local') : ?>
    <h5>CHANGE PASSWORD</h5>
                
    <div>
        <form id="user-change-password" autocomplete="off">
            <h6 class="required">CURRENT PASSWORD</h6>
            <input type="password" class="input-large" name="actual-password" required />

            <h6 class="required">NEW PASSWORD</h6>
            <input type="password" class="input-large" name="new-password" required />

            <h6 class="required">NEW PASSWORD (confirm)</h6>
            <input type="password" class="input-large" name="new-password-confirm" required />

            <br><br>
            <button class="btn-small-green">Save</button>
        </form>
    </div>
    <?php
endif;

$content = ob_get_clean();
$slidePanelName = 'general/userspace';
$slidePanelTitle = 'USERSPACE';

include(ROOT . '/views/includes/slide-panel.inc.php');
