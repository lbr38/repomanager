<div class="reloadable-table div-generic-blue" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex flex-wrap row-gap-5 justify-space-between">
        <div>
            <h6 class="margin-top-0">ACTIONS</h6>
            <p class="note">View all actions performed by users.</p>
        </div>

        <div class="flex align-item-center column-gap-10">
            <p>Filter</p>

            <select id="user-select" class="select-large">
                <option value="">All users</option>
                <?php
                foreach ($users as $user) {
                    if (!empty($userId) and $user['Id'] == $userId) {
                        echo '<option value="' . $user['Id'] . '" selected>' . $user['First_name'] . ' ' . $user['Last_name'] . ' (' . $user['Username'] . ')</option>';
                    } else {
                        echo '<option value="' . $user['Id'] . '">' . $user['First_name'] . ' ' . $user['Last_name'] . ' (' . $user['Username'] . ')</option>';
                    }
                } ?>
            </select>
        </div>
    </div>

    <?php
    if (empty($reloadableTableContent)) {
        echo '<p class="note margin-top-15">No entries found!</p>';
    }

    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-rfr-1-2 column-gap-15 row-gap-15 bck-blue-alt margin-top-5">
                <div class="grid grid-rfr-1-2 column-gap-15 row-gap-15 align-item-center">
                    <div class="flex align-item-center column-gap-15">
                        <?php
                        if ($item['State'] == 'error') {
                            echo '<img src="/assets/icons/error.svg" class="icon-np" title="Success" />';
                        }
                        if ($item['State'] == 'success') {
                            echo '<img src="/assets/icons/check.svg" class="icon-np" tiel="Error" />';
                        } ?>

                        <div class="flex flex-direction-column row-gap-5">
                            <p><b><?= $item['Date'] ?> <?= $item['Time'] ?></b></p>
                            <div class="flex align-item-center column-gap-5">
                                <img src="/assets/icons/user.svg" class="icon-np icon-medium mediumopacity-cst" />
                                <?php
                                if (!empty($item['Username'])) {
                                    $account = $item['Username'];
                                } else if (!empty($item['Id_user'])) {
                                    $account = '#' . $item['Id_user'];
                                } else {
                                    $account = 'Unknown account';
                                } ?>
                                <p class="mediumopacity-cst" title="The account that performed the action"><?= $account ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-direction-column row-gap-5">
                        <p><b>Action</b></p>
                        <p><?= htmlspecialchars_decode($item['Action']) ?></p>
                    </div>
                </div>

                <div>
                    <?php
                    $ip = 'Unknown';
                    $ipForwarded = '';
                    $userAgent = 'Unknown';

                    if (!empty($item['Ip'])) {
                        $ip = $item['Ip'];
                    }
                    if (!empty($item['Ip_forwarded']) and $item['Ip_forwarded'] != $item['Ip']) {
                        $ip .= ' (X-Forwarded-For: ' . $item['Ip_forwarded'] . ')';
                    }
                    if (!empty($item['User_agent'])) {
                        $userAgent = $item['User_agent'];
                    } ?>

                    <p><b>IP and user agent</b></p>
                    <p class="mediumopacity-cst"><?= $ip ?> - <?= $userAgent ?></p>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
