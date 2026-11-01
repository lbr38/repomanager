<?php
use \Controllers\Layout\Table\Render as TableRender;
use \Controllers\Utils\Generate\Html\Label; ?>

<div class="reloadable-table margin-top-15" table="<?= $table ?>" offset="<?= $reloadableTableOffset ?>">
    <div class="flex flex-direction-column row-gap-10">
        <?php
        if (empty($reloadableTableContent)) : ?>
            <div class="empty-state">
                <p class="empty-state-title">Nothing for now!</p>
                <p class="note">The host did not send any history yet.</p>
            </div>
            <?php
        endif;

        if (!empty($reloadableTableContent)) :
            foreach ($reloadableTableContent as $date => $packageState) : ?>
                <div class="host-event-item event-packages-btn pointer" host-id="<?= $id ?>" event-date="<?= $date ?>">
                    <div class="host-event-date">
                        <img src="/assets/icons/calendar.svg" class="icon-np lowopacity-cst" />
                        <p><b><?= DateTime::createFromFormat('Y-m-d', $date)->format('d-m-Y') ?></b></p>
                    </div>

                    <div class="package-states host-event-states">
                        <?php
                        foreach ($packageState as $state => $packages) {
                            if (!empty($packages)) {
                                echo Label::packageState($state, count($packages));
                            }
                        } ?>
                    </div>
                </div>

                <?php
            endforeach;

            unset($date, $packageState, $state, $packages); ?>

            <div class="flex justify-end margin-top-10">
                <?php TableRender::paginationBtn($reloadableTableCurrentPage, $reloadableTableTotalPages); ?>
            </div>
            <?php
        endif ?>
    </div>
</div>
