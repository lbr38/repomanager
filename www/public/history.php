<!DOCTYPE html>
<html>
<?php
require_once('../controllers/Autoloader.php');
\Controllers\Autoloader::load();
include_once('../includes/head.inc.php');

/**
 *  Seuls les admins ont accès à configuration.php
 */
if (!Controllers\Common::isadmin()) {
    header('Location: index.php');
    exit;
}

/**
 *  Cas où on souhaite filtrer par Id utilisateur
 */
if (!empty($_POST['action']) and $_POST['action'] === "filterByUser" and !empty($_POST['userid'])) {
    $filterByUserId = \Controllers\Common::validateData($_POST['userid']);

    if (!is_numeric($filterByUserId)) {
        printAlert("User Id is invalid");
    } else {
        $filterByUser = "yes";
    }
}

?>

<body>
<?php include_once('../includes/header.inc.php'); ?>

<article>
    <section class="main">
        <section class="section-center">
            <h3>HISTORY</h3>

            <div class="div-flex">
                <div class="flex-div-100 div-generic-gray">
                    <h4>EXECUTED ACTIONS</h4>
                    <form action="history.php" method="post" autocomplete="off">
                        <input type="hidden" name="action" value="filterByUser" />
                        <p>Filter by user:</p>
                        
                        <?php
                        /**
                         *  Récupération de tous les utilisateurs en base de données
                         */
                        $myusers = new \Models\Login();
                        $users = $myusers->getUsers(); ?>

                        <select name="userid" class="select-large">
                            <option value="">All</option>
                            <?php
                            foreach ($users as $user) {
                                if (!empty($filterByUser) and $filterByUser == "yes" and !empty($filterByUserId) and $user['Id'] == $filterByUserId) {
                                    echo '<option value="' . $user['Id'] . '" selected>' . $user['First_name'] . ' ' . $user['Last_name'] . ' (' . $user['Username'] . ')</option>';
                                } else {
                                    echo '<option value="' . $user['Id'] . '">' . $user['First_name'] . ' ' . $user['Last_name'] . ' (' . $user['Username'] . ')</option>';
                                }
                            } ?>
                        </select>
                        <button class="btn-medium-green">Validate</button>
                    </form>

                    <br>

                    <?php
                    /**
                     *  Si un filtrage par utilisateur a été sélectionné, on récupère uniquement les actions de l'utilisateur
                     */
                    if (!empty($filterByUser) and $filterByUser == "yes") {
                        $historyLines = \Models\History::getByUser($filterByUserId);

                    /**
                     *  Sinon on récupère toutes les actions de tous sles utilisateurs
                     */
                    } else {
                        $historyLines = \Models\History::getAll();
                    }

                    if (empty($historyLines)) {
                        echo '<p>No action have been found for this user.</p>';
                    } else { ?>
                        <table class="table-generic-blue">
                            <thead>
                                <tr>
                                    <td class="td-100">Date</td>
                                    <td class="td-100">Action</td>
                                    <td class="td-100">User</td>
                                    <td>State</td>
                                </tr>
                            </thead>
                            <?php
                            foreach ($historyLines as $historyLine) : ?>
                                <tr>
                                    <td class="td-100">
                                        <b><?= $historyLine['Date'] ?></b> at <b><?= $historyLine['Time'] ?></b>
                                    </td>
                                    <td class="td-100">
                                        <?= htmlspecialchars_decode($historyLine['Action']) ?>
                                    </td>
                                    <td class="td-100">
                                        <?= $historyLine['Username'] ?>
                                    </td>
                                    <?php
                                    if ($historyLine['State'] == "success") {
                                        echo '<td><img src="resources/icons/greencircle.png" class="icon-small" />Success</td>';
                                    }
                                    if ($historyLine['State'] == "error") {
                                        echo '<td><img src="resources/icons/redcircle.png" class="icon-small" />Error</td>';
                                    } ?>
                                </tr>
                                <?php
                            endforeach ?>
                        </table>
                    <?php } ?>
                </div>
            </div>
        </section>
    </section>
</article>

<?php include_once('../includes/footer.inc.php'); ?>

</body>
</html>