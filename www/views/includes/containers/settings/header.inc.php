<?php
/**
 *  Settings sections navigation, displayed above the settings sections
 *  Sections are rendered in the 'settings/settings' and 'settings/right-section' containers
 */
$settingsSections = [
    'general'         => ['title' => 'General', 'icon' => 'cog'],
    'tasks'           => ['title' => 'Tasks', 'icon' => 'rocket'],
    'repositories'    => ['title' => 'Repositories', 'icon' => 'package'],
    'package-formats' => ['title' => 'Package formats', 'icon' => 'stack'],
    'environments'    => ['title' => 'Environments', 'icon' => 'tag'],
    'hosts'           => ['title' => 'Hosts', 'icon' => 'server'],
    'sso'             => ['title' => 'SSO', 'icon' => 'sso'],
    'users'           => ['title' => 'Users', 'icon' => 'user'],
    'debug-mode'      => ['title' => 'Debug mode', 'icon' => 'terminal']
]; ?>

<section class="section-main">
    <h3>SETTINGS</h3>

    <div class="flex flex-direction-column row-gap-10 margin-top-15 margin-bottom-30">
        <div class="flex column-gap-10 row-gap-10 justify-space-between flex-wrap">
            <div id="settings-nav" class="flex flex-wrap align-item-center column-gap-20 row-gap-10">
                <?php
                foreach ($settingsSections as $section => $properties) : ?>
                    <div class="flex align-item-center column-gap-5">
                        <div class="slide-btn mediumopacity settings-nav-btn" section="<?= $section ?>" title="<?= $properties['title'] ?> settings">
                            <img src="/assets/icons/<?= $properties['icon'] ?>.svg" />
                            <span><?= $properties['title'] ?></span>
                        </div>
                        <img src="/assets/icons/warning.svg" class="icon-small settings-nav-warning hide" section="<?= $section ?>" title="This section contains a warning" />
                        <span class="label-yellow label-medium settings-nav-unsaved hide" section="<?= $section ?>" title="Unsaved changes in this section"></span>
                    </div>
                    <?php
                endforeach ?>
            </div>

            <div class="flex align-item-center column-gap-10">
                <span class="label-yellow settings-unsaved-label hide"></span>
                <form class="settings-form" autocomplete="off">
                    <button type="submit" class="btn-medium-green" title="Save settings">Save</button>
                </form>
            </div>
        </div>

        <input id="settings-search" type="text" placeholder="Search settings, e.g. proxy, retention, GPG, timezone..." title="Search by setting name, description or section" autocomplete="off" />
        <p id="settings-search-empty" class="note hide">No setting matches this search.</p>
    </div>
</section>
