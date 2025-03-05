<div class="reloadable-table div-generic-blue" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex justify-space-between">
        <div>
            <h6 class="margin-top-0">ACTIONS</h6>
            <p class="note">View all actions performed by users.</p>
        </div>

        <div class="flex align-item-center column-gap-10">
            <p>Filter by user</p>

            <select id="user-select" class="select-large">
                <option value="">All</option>
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
        echo '<p class="note">No entries found!</p>';
    }

    if (!empty($reloadableTableContent)) :
        foreach ($reloadableTableContent as $item) : ?>
            <div class="table-container grid-2 bck-blue-alt margin-top-5">
                <div class="flex align-item-center column-gap-70">
                    <div class="flex align-item-center column-gap-15">
                        <?php
                        if ($item['State'] == 'error') {
                            echo '<img src="/assets/icons/error.svg" class="icon-np" title="Success" />';
                        }
                        if ($item['State'] == 'success') {
                            echo '<img src="/assets/icons/check.svg" class="icon-np" tiel="Error" />';
                        } ?>

                        <div>
                            <p><b><?= $item['Date'] ?> <?= $item['Time'] ?></b></p>
                            <?php
                            if (!empty($item['Username'])) {
                                $account = 'Account: ' . $item['Username'];
                            } else if (!empty($item['Id_user'])) {
                                $account = 'Account: #' . $item['Id_user'];
                            } else {
                                $account = 'Unknown account';
                            } ?>
                            <p class="mediumopacity-cst"><?= $account ?></p>
                        </div>
                    </div>

                    <p><?= htmlspecialchars_decode($item['Action']) ?></p>
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

                    <p>IP and user agent</p>
                    <p class="mediumopacity-cst"><?= $ip ?> - <?= $userAgent ?></p>
                </div>
            </div>
            <?php
        endforeach; ?>
        
        <div class="flex justify-end">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>

        <?php
    endif ?>
</div>
