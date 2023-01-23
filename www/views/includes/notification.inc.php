<div id="notification-div" class="param-slide-container">
    <div class="param-slide">
        <img id="hide-notification-btn" src="resources/icons/close.svg" class="close-btn float-right lowopacity" title="Close" />
        <div id="notification-reloadable-div">

            <h3>NOTIFICATIONS</h3>

            <?php
            if (NOTIFICATION == 0) :
                echo '<p>Nothing for now!</p>';
            else :
                foreach (NOTIFICATION_MESSAGES as $notification) :
                    if (!empty($notification['Title'])) {
                        echo '<h4><b>' . $notification['Title'] . '</b></h4>';
                    } ?>
                    
                    <div class="flex justify-space-between">
                        <p><?= htmlspecialchars_decode($notification['Message']) ?></p>
                    
                        <?php
                        if (!empty($notification['Id'])) : ?>
                            <div class="slide-btn align-self-center acquit-notification-btn" notification-id="<?= $notification['Id'] ?>" title="Mark as read">
                                <img src="resources/icons/enabled.svg" />
                                <span>Mark as read</span>
                            </div>
                            <?php
                        endif ?>
                    </div>

                    <br><br>
                    <?php
                endforeach;
            endif ?>
        </div>
    </div>
</div>