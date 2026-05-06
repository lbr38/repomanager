<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\User\Permission\Task as TaskPermission;

$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentSection = explode('/', trim($currentPath, '/'))[0] ?? '';
?>

<nav id="menu">
    <div id="menu-fixed">
        <div class="flex flex-direction-column row-gap-30">
            <!-- Repositories tab -->
            <div class="menu-item <?= in_array($currentSection, ['', 'repos']) ? 'menu-item-active' : '' ?>">
                <a href="/">
                    <img src="/assets/icons/package.svg" class="icon" title="Repositories" />
                </a>
            </div>

            <!-- Tasks tab -->
            <?php
            if (TaskPermission::allowed()) : ?>
                <div class="menu-item <?= $currentSection === 'run' ? 'menu-item-active' : '' ?>">
                    <a href="/run">
                        <img src="/assets/icons/rocket.svg" class="icon" title="Tasks" />
                    </a>
                </div>
                <?php
            endif ?>

            <!-- Hosts tab -->
            <?php
            if (HostPermission::allowed()) : ?>
                <div class="menu-item <?= $currentSection === 'hosts' ? 'menu-item-active' : '' ?>">
                    <a href="/hosts">
                        <img src="/assets/icons/server.svg" class="icon" title="Hosts" />
                    </a>
                </div>
                <?php
            endif ?>
        </div>

        <div class="flex flex-direction-column row-gap-30">
            <?php
            if (IS_ADMIN) : ?>
                <div class="menu-item <?= $currentSection === 'history' ? 'menu-item-active' : '' ?>">
                    <a href="/history"><img src="/assets/icons/time.svg" class="icon" title="Repomanager history" /></a>
                </div>

                <div class="menu-item <?= $currentSection === 'status' ? 'menu-item-active' : '' ?>">
                    <a href="/status"><img src="/assets/icons/health.svg" class="icon" title="Access system health & monitoring" /></a>
                </div>

                <div class="menu-item <?= $currentSection === 'settings' ? 'menu-item-active' : '' ?>">
                    <a href="/settings"><img src="/assets/icons/cog.svg" class="icon" title="Repomanager settings" /></a>
                </div>
                <?php
            endif ?>
        </div>
    </div>
</nav>
        
