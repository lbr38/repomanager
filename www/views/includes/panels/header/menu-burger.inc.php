<?php ob_start(); ?>

<div class="flex flex-direction-column row-gap-20">
    <div class="flex align-item-center column-gap-10">
        <img src="/assets/icons/package.svg" class="icon" />
        <a href="/"><h5 class="margin-0">REPOSITORIES</h5></a>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/rocket.svg" class="icon" />
        <a href="/run"><h5 class="margin-0">TASKS</h5></a>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/server.svg" class="icon" />
        <a href="/hosts"><h5 class="margin-0">HOSTS</h5></a>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/cog.svg" class="icon" />
        <a href="/settings"><h5 class="margin-0">SETTINGS</h5></a>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/time.svg" class="icon" />
        <a href="/history"><h5 class="margin-0">HISTORY</h5></a>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/alarm.svg" class="icon" />
        <h5 class="margin-0 get-panel-btn" panel="general/notification">NOTIFICATIONS</h5>
    </div>

    <div class="flex column-gap-10">
        <img src="/assets/icons/user.svg" class="icon" />
        <h5 class="margin-0 get-panel-btn" panel="general/userspace">USERSPACE</h5>
    </div>
</div>

<?php
$content = ob_get_clean();
$slidePanelName = 'header/menu-burger';
$slidePanelTitle = 'MENU';

include(ROOT . '/views/includes/slide-panel.inc.php');
