<?php ob_start(); ?>

<?php
if (NOTIFICATION == 0) :
    echo '<p>Nothing for now!</p>';
else :
    foreach (NOTIFICATION_MESSAGES as $notification) :
        if (!empty($notification['Title'])) {
            echo '<h4><b>' . $notification['Title'] . '</b></h4>';
        } ?>
        
        <div class="flex flex-direction-column">
            <p class="margin-bottom-15"><?= htmlspecialchars_decode($notification['Message']) ?></p>
        
            <?php
            if (!empty($notification['Id'])) : ?>
                <div class="slide-btn align-self-center acquit-notification-btn" notification-id="<?= $notification['Id'] ?>" title="Mark as read">
                    <img src="/assets/icons/enabled.svg" />
                    <span>Mark as read</span>
                </div>
                <?php
            endif ?>
        </div>
        <br>
        <?php
    endforeach;
endif;

$content = ob_get_clean();
$slidePanelName = 'general/notification';
$slidePanelTitle = 'NOTIFICATIONS';

include(ROOT . '/views/includes/slide-panel.inc.php');
