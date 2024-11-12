<section class="section-main">
    <h3>HISTORY</h3>

    <p>Most actions executed by users are listed here.</p>
    <br>

    <div class="div-generic-blue">
        <br>
        <form action="/history" method="post" autocomplete="off">
            <input type="hidden" name="action" value="filterByUser" />
            <p>Filter by user:</p>

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
        if (empty($historyLines)) {
            echo '<p>No action have been found for this user.</p>';
        } else { ?>
            <table class="table-generic-blue">
                <thead>
                    <tr>
                        <td class="td-100">Date</td>
                        <td class="td-100">User</td>
                        <td class="td-100">Action</td>
                        <td>State</td>
                    </tr>
                </thead>

                <?php
                foreach ($historyLines as $historyLine) : ?>
                    <tr>
                        <td class="td-100">
                            <b><?= $historyLine['Date'] ?> <?= $historyLine['Time'] ?></b>
                        </td>
                        <td class="td-100">
                            <?= $historyLine['Username'] ?>
                        </td>
                        <td class="td-100">
                            <?= htmlspecialchars_decode($historyLine['Action']) ?>
                        </td>
                        <?php
                        if ($historyLine['State'] == "success") {
                            echo '<td><img src="/assets/icons/check.svg" class="icon" /> <span>Success</span></td>';
                        }
                        if ($historyLine['State'] == "error") {
                            echo '<td><img src="/assets/icons/error.svg" class="icon" /> <span>Error</span></td>';
                        } ?>
                    </tr>
                    <?php
                endforeach ?>
            </table>
            <?php
        } ?>
    </div>
</section>