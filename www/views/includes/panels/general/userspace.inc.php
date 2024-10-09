<?php ob_start(); ?>

<h6>LOGIN</h6>
<p><?= $_SESSION['username'] ?></p>

<h6>ROLE</h6>
<p><?= $_SESSION['role'] ?></p>

<h6>API KEY</h6>
<p id="user-apikey">(hashed) Generate a new key to retrieve it in clear.</p>

<br>
<div class="flex column-gap-10">
    <button type="button" id="user-generate-apikey-btn" class="btn-medium-blue" title="Generate a new key">Generate new key</button>
</div>

<br><br>

<a href="/logout" title="Logout">
    <button class="btn-small-red" >Logout</button>
</a>

<h5>PERSONAL INFORMATIONS</h5>

<div>
    <form id="user-edit-info" autocomplete="off">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>" />
        <h6>FIRST NAME</h6>
        <input type="text" class="input-large" name="first-name" value="<?php echo !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>">

        <h6>LAST NAME</h6>
        <input type="text" class="input-large" name="last-name" value="<?php echo !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>">

        <h6>EMAIL</h6>
        <input type="email" class="input-large" name="email" value="<?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>">

        <br><br>
        <button class="btn-small-green">Save</button>
    </form>
</div>

<h5>CHANGE PASSWORD</h5>
            
<div>
    <form id="user-change-password" autocomplete="off">
        <input type="hidden" name="username" value="<?= $_SESSION['username'] ?>" />
        <h6>CURRENT PASSWORD</h6>
        <input type="password" class="input-large" name="actual-password" required />

        <h6>NEW PASSWORD</h6>
        <input type="password" class="input-large" name="new-password" required />

        <h6>NEW PASSWORD (confirm)</h6>
        <input type="password" class="input-large" name="new-password-confirm" required />

        <br><br>
        <button class="btn-small-green">Save</button>
    </form>
</div>

<?php
$content = ob_get_clean();
$slidePanelName = 'general/userspace';
$slidePanelTitle = 'USERSPACE';

include(ROOT . '/views/includes/slide-panel.inc.php');
