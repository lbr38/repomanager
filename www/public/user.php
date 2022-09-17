<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Modification des informations personnelles
 */
if (!empty($_POST['action']) and $_POST['action'] == 'editPersonnalInfos') {
    $username = $_SESSION['username'];

    /**
     *  Récupération des informations transmises
     */
    // Prénom
    if (!empty($_POST['first_name'])) {
        $firstName = \Controllers\Common::validateData($_POST['first_name']);
    } else {
        $firstName = '';
    }

    // Nom
    if (!empty($_POST['last_name'])) {
        $lastName = \Controllers\Common::validateData($_POST['last_name']);
    } else {
        $lastName = '';
    }

    // Email
    if (!empty($_POST['email'])) {
        $email = \Controllers\Common::validateData($_POST['email']);
    } else {
        $email = '';
    }

    /**
     *  Modification des informations en base de données
     */
    $mylogin = new \Models\Login();
    $mylogin->edit($username, $firstName, $lastName, $email);
}

/**
 *  Modification du mot de passe de l'utilisateur
 */
if (!empty($_POST['action']) and $_POST['action'] == 'changePassword' and !empty($_POST['actual_password']) and !empty($_POST['new_password']) and !empty($_POST['new_password2'])) {
    $mylogin = new \Models\Login();
    $mylogin->changePassword($_SESSION['username'], $_POST['actual_password'], $_POST['new_password'], $_POST['new_password2']);
}
?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
    <section class="main">

        <h3>USERSPACE</h3>

        <div class="div-flex div-generic-blue">
            <div class="flex-div-100">
                <table class="table-generic table-small">
                    <tr>
                        <td>LOGIN</td>
                        <td><?= $_SESSION['username'] ?></td>
                    </tr>
                    <tr>
                        <td>ROLE</td>
                        <td><?= $_SESSION['role'] ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="div-flex">
            <div class="flex-div-50">
                <h4>PERSONAL INFORMATIONS</h4>

                <div class="div-generic-blue">
                    <form action="user.php" method="post" autocomplete="off">
                        <input type="hidden" name="action" value="editPersonnalInfos" />
                        <p>First name:</p>
                        <input type="text" class="input-large" name="first_name" value="<?php echo !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>">
                        <br><br>
                        <p>Last name:</p>
                        <input type="text" class="input-large" name="last_name" value="<?php echo !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>">
                        <br><br>
                        <p>Email:</p>
                        <input type="email" class="input-large" name="email" value="<?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>">
                        <br><br>
                        <button class="btn-medium-green">Save</button>
                    </form>
                </div>
            </div>

            <div class="flex-div-50">
                <h4>CHANGE PASSWORD</h4>
                
                <div class="div-generic-blue">
                    <form action="user.php" method="post" autocomplete="off">
                        <input type="hidden" name="action" value="changePassword" />
                        <p>Current password:</p>
                        <input type="password" class="input-large" name="actual_password" required />
                        <br><br>
                        <p>New password:</p>
                        <input type="password" class="input-large" name="new_password" required />
                        <br><br>
                        <p>New password (re-type) :</p>
                        <input type="password" class="input-large" name="new_password2" required />
                        <br><br>
                        <button class="btn-medium-green">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>

</body>
</html>