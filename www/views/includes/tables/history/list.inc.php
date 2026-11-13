<?php
use Controllers\History\Display; ?>

<div class="reloadable-table div-generic-blue" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>" per-page="<?= $reloadableTablePerPage ?>">
    <div class="flex align-item-center column-gap-15 margin-bottom-15">
        <h6 class="margin-top-0 margin-bottom-0">ACTIONS</h6>
        <?php
        // Status labels are clickable to filter actions by status, the active filter is highlighted and can be clicked again to be removed
        $stateFilter = $filters['state'] ?? '';

        if ($successfulCount > 0 or $stateFilter == 'success') {
            echo '<span class="label-green label-medium history-state-filter-btn pointer' . ($stateFilter == 'error' ? ' opacity-30' : '') . '" state="success" title="' . ($stateFilter == 'success' ? 'Show all actions' : 'Show successful actions only') . '">' . $successfulCount . ' successful</span>';
        }

        if ($failedCount > 0 or $stateFilter == 'error') {
            echo '<span class="label-red label-medium history-state-filter-btn pointer' . ($stateFilter == 'success' ? ' opacity-30' : '') . '" state="error" title="' . ($stateFilter == 'error' ? 'Show all actions' : 'Show failed actions only') . '">' . $failedCount . ' failed</span>';
        } ?>
    </div>

    <div class="flex align-item-center column-gap-10 row-gap-10 flex-wrap margin-bottom-15">
        <input id="history-search" class="flex-1" type="text" maxlength="100" value="<?= htmlspecialchars($filters['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Search by action, user, IP address or changed value" title="Search by action, user, IP address or changed value" />

        <select id="history-category" class="history-filter" title="Filter by category">
            <option value="">All categories</option>
            <?php
            foreach ($categories as $key => $category) : ?>
                <option value="<?= $key ?>" <?= ($filters['category'] ?? '') == $key ? 'selected' : '' ?>><?= $category['label'] ?></option>
                <?php
            endforeach ?>
        </select>

        <select id="history-user" class="history-filter" title="Filter by user">
            <option value="">All users</option>
            <?php
            foreach ($users as $user) :
                $userLabel = trim($user['First_name'] . ' ' . $user['Last_name']);
                $userLabel = !empty($userLabel) ? $userLabel . ' (' . $user['Username'] . ')' : $user['Username']; ?>
                <option value="<?= $user['Id'] ?>" <?= ($filters['user'] ?? '') == $user['Id'] ? 'selected' : '' ?>><?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php
            endforeach ?>
        </select>
    </div>

    <?php
    if (empty($reloadableTableContent)) :
        if (!empty($filters)) : ?>
            <p class="note">No action matches your search.</p>
            <?php
        else : ?>
            <div class="empty-state">
                <p class="empty-state-title">No history entries found.</p>
                <p class="note">Actions such as creating repositories, scheduling tasks, or changing settings will appear here.</p>
            </div>
            <?php
        endif;
    endif;

    if (!empty($reloadableTableContent)) : ?>
        <div class="flex flex-direction-column row-gap-10">
            <?php
            foreach ($reloadableTableContent as $item) :
                $details  = Display::details($item['Details'] ?? null);
                $category = $categories[$item['Category'] ?? ''] ?? $categories['other'];
                $date     = DateTime::createFromFormat('Y-m-d', $item['Date']);

                if (!empty($item['Username'])) {
                    $account = $item['Username'];
                } else if (!empty($item['Id_user'])) {
                    $account = '#' . $item['Id_user'];
                } else {
                    $account = 'Unknown account';
                } ?>

                <div class="history-item <?= $item['State'] == 'error' ? 'accent-red' : 'accent-green' ?> pointer" history-id="<?= $item['Id'] ?>" title="Show details">
                    <div class="flex align-item-center column-gap-20 min-width-0">
                        <img class="icon-np lowopacity-cst" src="/assets/icons/<?= $category['icon'] ?>.svg" title="<?= $category['label'] ?>" />

                        <div class="flex flex-direction-column row-gap-5 min-width-0">
                            <div class="flex align-item-center column-gap-6 row-gap-5 flex-wrap">
                                <span class="task-item-id">#<?= $item['Id'] ?></span>
                                <span class="dot mediumopacity-cst">●</span>
                                <span class="task-item-date"><?= $date ? $date->format('d-m-Y') : htmlspecialchars($item['Date']) ?> <?= htmlspecialchars($item['Time']) ?></span>
                                <span class="dot mediumopacity-cst">●</span>
                                <span class="task-item-launched-by" title="Performed by <?= htmlspecialchars($account, ENT_QUOTES, 'UTF-8') ?>"><img src="/assets/icons/user.svg" class="icon-small mediumopacity-cst" /><?= htmlspecialchars($account) ?></span>
                                <span class="dot mediumopacity-cst">●</span>
                                <span class="task-item-launched-by"><?= $category['label'] ?></span>
                            </div>

                            <div class="task-item-action history-item-action"><?= Display::action($item['Action']) ?></div>
                        </div>
                    </div>

                    <div class="task-item-repo">
                        <?php
                        if (!empty($details['changes'])) {
                            $count = count($details['changes']);
                            echo '<span class="label-yellow" title="Values changed by this action">' . $count . ' change' . ($count > 1 ? 's' : '') . '</span>';
                        }

                        if ($item['State'] == 'error') {
                            echo '<span class="label-red">Failed</span>';
                        } ?>
                    </div>

                    <div class="task-item-status">
                        <span class="history-item-toggle"></span>
                    </div>
                </div>

                <div class="history-item-details div-generic-blue hide" history-id="<?= $item['Id'] ?>">
                    <?php
                    if (!empty($details['changes'])) : ?>
                        <div class="overflowx-auto">
                            <div class="history-changes">
                                <h6 class="margin-top-0 margin-bottom-0">CHANGED VALUE</h6>
                                <h6 class="margin-top-0 margin-bottom-0">BEFORE</h6>
                                <h6 class="margin-top-0 margin-bottom-0">AFTER</h6>
                                <?php
                                foreach ($details['changes'] as $change) {
                                    if (is_array($change)) {
                                        echo Display::change($change);
                                    }
                                } ?>
                            </div>
                        </div>
                        <?php
                    endif;

                    if (!empty($details['params'])) : ?>
                        <div>
                            <h6 class="margin-top-0 margin-bottom-10">PARAMETERS</h6>
                            <div class="grid grid-rfr-1-3 column-gap-20 row-gap-15">
                                <?php
                                foreach ($details['params'] as $label => $value) : ?>
                                    <div>
                                        <p class="mediumopacity-cst"><?= htmlspecialchars((string) $label) ?></p>
                                        <?= Display::value(is_array($value) ? array_map('strval', $value) : (string) $value) ?>
                                    </div>
                                    <?php
                                endforeach ?>
                            </div>
                        </div>
                        <?php
                    endif ?>

                    <div>
                        <h6 class="margin-top-0 margin-bottom-10">REQUEST</h6>
                        <div class="grid grid-rfr-1-3 column-gap-20 row-gap-15">
                            <div>
                                <p class="mediumopacity-cst">IP address</p>
                                <p><?= !empty($item['Ip']) ? htmlspecialchars($item['Ip']) : 'Unknown' ?></p>
                            </div>

                            <div>
                                <p class="mediumopacity-cst">X-Forwarded-For</p>
                                <p><?= !empty($item['Ip_forwarded']) ? htmlspecialchars($item['Ip_forwarded']) : '-' ?></p>
                            </div>

                            <div>
                                <p class="mediumopacity-cst">Account</p>
                                <p title="<?= !empty($item['Id_user']) ? 'User #' . (int) $item['Id_user'] : '' ?>"><?= htmlspecialchars($account) ?></p>
                            </div>

                            <div class="history-full-width">
                                <p class="mediumopacity-cst">User agent</p>
                                <p class="wordbreakall"><?= !empty($item['User_agent']) ? htmlspecialchars($item['User_agent']) : 'Unknown' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            endforeach ?>
        </div>

        <div class="flex justify-end margin-top-10">
            <?php \Controllers\Layout\Table\Render::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
        </div>
        <?php
    endif ?>
</div>
