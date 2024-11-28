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
            <div class="table-container grid-fr-auto-1-3 bck-blue-alt margin-top-5">
                <p>
                    <?php
                    if ($item['State'] == 'error') {
                        echo '<img src="/assets/icons/error.svg" class="icon" />';
                    }
                    if ($item['State'] == 'success') {
                        echo '<img src="/assets/icons/check.svg" class="icon" />';
                    } ?>
                </p>

                <div>
                    <p><b><?= $item['Date'] ?> <?= $item['Time'] ?></b></p>
                    <p class="lowopacity-cst">Username: <?= $item['Username'] ?></p>
                </div>

                <div>
                    <p><?= htmlspecialchars_decode($item['Action']) ?></p>
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
