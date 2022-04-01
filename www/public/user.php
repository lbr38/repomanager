<!DOCTYPE html>
<html>
<?php
require_once('../models/Autoloader.php');
Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Modification des informations personnelles
 */
if (!empty($_POST['action']) AND $_POST['action'] == 'editPersonnalInfos') {
    $username = $_SESSION['username'];

    /**
     *  Récupération des informations transmises
     */
    // Prénom
    if (!empty($_POST['first_name'])) {
        $firstName = Common::validateData($_POST['first_name']);
    } else {
        $firstName = '';
    }

    // Nom
    if (!empty($_POST['last_name'])) {
        $lastName = Common::validateData($_POST['last_name']);
    } else {
        $lastName = '';
    }

    // Email
    if (!empty($_POST['email'])) {
        $email = Common::validateData($_POST['email']);
    } else {
        $email = '';
    }

    /**
     *  Modification des informations en base de données
     */
    $mylogin = new Login();
    $mylogin->edit($username, $firstName, $lastName, $email);
}

/**
 *  Modification du mot de passe de l'utilisateur
 */
if (!empty($_POST['action']) AND $_POST['action'] == 'changePassword' AND !empty($_POST['actual_password']) AND !empty($_POST['new_password']) AND !empty($_POST['new_password2'])) {
    $mylogin = new Login();
    $mylogin->changePassword($_SESSION['username'], $_POST['actual_password'], $_POST['new_password'], $_POST['new_password2']);
}
?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
    <section class="main">
        <section class="section-center">
            <h3><?php echo strtoupper($_SESSION['username']);?></h3>

            <div class="div-flex div-generic-gray">
                <div class="flex-div-100">
                    <table class="table-generic table-small opacity-80">
                        <tr>
                            <td>LOGIN</td>
                            <td><?php echo $_SESSION['username'];?></td>
                        </tr>
                        <tr>
                            <td>ROLE</td>
                            <td><?php echo $_SESSION['role'];?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="div-flex">
                <div class="flex-div-50 div-generic-gray">
                    <h4>INFORMATIONS PERSONNELLES</h4>
                    <form action="user.php" method="post" autocomplete="off">
                        <input type="hidden" name="action" value="editPersonnalInfos" />
                        <p>Prénom :</p>
                        <input type="text" class="input-large" name="first_name"  value="<?php if (!empty($_SESSION['first_name'])) echo $_SESSION['first_name'];?>" />

                        <p>Nom :</p>
                        <input type="text" class="input-large" name="last_name" value="<?php if (!empty($_SESSION['last_name'])) echo $_SESSION['last_name'];?>" />

                        <p>Email :</p>
                        <input type="email" class="input-large" name="email" value="<?php if (!empty($_SESSION['email'])) echo $_SESSION['email'];?>" />

                        <br>
                        <br>
                        <button class="btn-medium-blue">Enregistrer</button>
                    </form>
                </div>

                <div class="flex-div-50 div-generic-gray">
                    <h4>MODIFIER LE MOT DE PASSE</h4>
                    <form action="user.php" method="post" autocomplete="off">
                        <input type="hidden" name="action" value="changePassword" />

                        <p>Mot de passe actuel :</p>
                        <input type="password" class="input-large" name="actual_password" required />

                        <p>Nouveau mot de passe :</p>
                        <input type="password" class="input-large" name="new_password" required />

                        <p>Nouveau mot de passe (saisir de nouveau) :</p>
                        <input type="password" class="input-large" name="new_password2" required />

                        <br>
                        <br>
                        <button class="btn-medium-blue">Enregistrer</button>
                    </form>                    
                </div>
            </div>
        </section>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>

</body>
</html>