<section class="section-main reloadable-container" container="profiles/list">
    <?php
    if (!empty($profiles)) : ?>
        <h5>CURRENT PROFILES</h5>

        <div class="profiles-container">
            <?php
            /**
             *  Print all profiles and their configuration
             */
            foreach ($profiles as $profile) :
                /**
                 *  Retrieve profile configuration
                 */
                $packageExclude         = explode(',', $profile['Package_exclude']);
                $packageExcludeMajor    = explode(',', $profile['Package_exclude_major']);
                $serviceRestart         = explode(',', $profile['Service_restart']);
                $profileReposMembersIds = $myprofile->reposMembersIdList($profile['Id']);

                /**
                 *  Retrieve hosts count using this profile, if any, and if hosts management is enabled
                 */
                if (MANAGE_HOSTS == 'true') {
                    $hostsCount = $myhost->countByProfile($profile['Name']);
                } ?>

                <div>
                    <div class="table-container grid-fr-2-1 justify-space-between profile-config-btn pointer" profile-id="<?= $profile['Id'] ?>" title="<?= $profile['Name'] ?> configuration">
                        <div>
                            <p><?= $profile['Name'] ?></p>

                            <?php
                            if (MANAGE_HOSTS == 'true') :
                                if ($hostsCount <= 1) {
                                    $hostsCount = $hostsCount . ' host';
                                } else {
                                    $hostsCount = $hostsCount . ' hosts';
                                } ?>
                                <p class="lowopacity-cst" title="<?= $hostsCount ?> using this profile"><?= $hostsCount ?> using this profile</p>
                                <?php
                            endif ?>
                        </div>

                        <div class="flex column-gap-15 justify-end">
                            <img src="/assets/icons/duplicate.svg" class="profile-duplicate-btn icon-lowopacity" profile-id="<?= $profile['Id'] ?>" title="Duplicate <?= $profile['Name'] ?> profile configuration" />
                            <img src="/assets/icons/delete.svg" class="profile-delete-btn icon-lowopacity" profile-id="<?= $profile['Id'] ?>" profile-name="<?= $profile['Name'] ?>" title="Delete <?= $profile['Name'] ?> profile" />
                        </div>
                    </div>

                    <div class="profile-config-div hide margin-bottom-5 detailsDiv" profile-id="<?= $profile['Id'] ?>">
                        <form class="profile-config-form" profile-id="<?= $profile['Id'] ?>" autocomplete="off">
                            <h6 class="required">NAME</h6>
                            <input type="text" name="profile-name" value="<?= $profile['Name'] ?>" />

                            <h6>REPOSITORIES</h6>
                            <p class="note">Specify which repositories the host will have access to.</p>
                            <select name="profile-repos" multiple>
                                <?php
                                /**
                                 *  For each repo, we check if it's already present in the profile, if so it will be displayed as selected in the dropdown list, if not it will be available in the dropdown list
                                 */
                                $reposList = $myrepoListing->listNameOnly(true);

                                foreach ($reposList as $repo) :
                                    if (in_array($repo['Id'], $profileReposMembersIds)) {
                                        if ($repo['Package_type'] == 'rpm') {
                                            echo '<option value="' . $repo['Id'] . '" selected>' . $repo['Name'] . '</option>';
                                        }
                                        if ($repo['Package_type'] == 'deb') {
                                            echo '<option value="' . $repo['Id'] . '" selected>' . $repo['Name'] . ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'] . '</option>';
                                        }
                                    } else {
                                        if ($repo['Package_type'] == 'rpm') {
                                            echo '<option value="' . $repo['Id'] . '">' . $repo['Name'] . '</option>';
                                        }
                                        if ($repo['Package_type'] == 'deb') {
                                            echo '<option value="' . $repo['Id'] . '">' . $repo['Name'] . ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'] . '</option>';
                                        }
                                    }
                                endforeach ?>
                            </select>

                            <?php
                            /**
                             *  List selectables packages in the list of packages to exclude
                             */
                            $listPackages = $myprofile->getPackages(); ?>

                            <h6>PACKAGE EXCLUSION</h6>
                            
                            <h6>EXCLUDE MAJOR VERSION</h6>
                            <p class="note">Specify which packages the host should exclude from updates if the update is a major version change.</p>
                            <p class="note">You can use <code>.*</code> as a wildcard. <code>mysql.*</code></p>
                            <select name="profile-exclude-major" multiple>
                                <?php
                                /**
                                 *  For each package in this list, if it appears in $packageExcludeMajor then we display it as selected "selected"
                                 */
                                foreach ($listPackages as $package) {
                                    if (in_array($package, $packageExcludeMajor)) {
                                        echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                    } else {
                                        echo '<option value="' . $package . '">' . $package . '</option>';
                                    }

                                    /**
                                     *  Do the same thing for this same package followed by a wildcard (ex: apache.*)
                                     */
                                    if (in_array("${package}.*", $packageExcludeMajor)) {
                                        echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                    } else {
                                        echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                    }
                                } ?>
                            </select>
                            
                            <h6>ALWAYS EXCLUDE</h6>
                            <p class="note">Specify which packages the host should exclude from updates (no matter the version).</p>
                            <p class="note">You can use <code>.*</code> as a wildcard. <code>mysql.*</code></p>
                            <select name="profile-exclude" multiple>
                                <?php
                                foreach ($listPackages as $package) {
                                    if (in_array($package, $packageExclude)) {
                                        echo '<option value="' . $package . '" selected>' . $package . '</option>';
                                    } else {
                                        echo '<option value="' . $package . '">' . $package . '</option>';
                                    }

                                    /**
                                     *  Do the same thing for this same package followed by a wildcard (ex: apache.*)
                                     */
                                    if (in_array("${package}.*", $packageExclude)) {
                                        echo '<option value="' . $package . '.*" selected>' . $package . '.*</option>';
                                    } else {
                                        echo '<option value="' . $package . '.*">' . $package . '.*</option>';
                                    }
                                } ?>
                            </select>

                            <h6>RESTART SERVICES</h6>
                            <p class="note">Specify what services the host should restart after updates.</p>
                            <p class="note">You can conditionally restart a service from a package update by using the following syntax: <code>service_name:package_name</code></p>
                            <p class="note">e.g: restart httpd if any php package is updated: <code>httpd:php.*</code></p>

                            <?php
                            /**
                             *  List of selectable services in the list of services to restart
                             *  Made from the list of pre-defined services in the database and merged with the list of services to restart configured for this profile (sometimes it can have some services with conditionnal package which are not saved in database, so we need to merge them to be able to display them in the dropdown list)
                             *  array_unique: Remove duplicates entries
                             *  array_filter: Remove empty values (in case $serviceRestart is empty)
                             */
                            $servicesList = $myprofile->getServices();
                            $services = array_filter(array_unique(array_merge($servicesList, $serviceRestart))); ?>

                            <select name="profile-service-restart" multiple>
                                <?php
                                foreach ($services as $service) {
                                    if (in_array($service, $serviceRestart)) {
                                        echo '<option value="' . $service . '" selected>' . $service . '</option>';
                                    } else {
                                        echo '<option value="' . $service . '">' . $service . '</option>';
                                    }
                                } ?>
                            </select>

                            <h6>NOTES</h6>
                            <p class="note">Add any notes you want to keep about this profile.</p>
                            <textarea name="profile-notes" class="textarea-100 margin-bottom-10"><?= $profile['Notes'] ?></textarea>
                            
                            <button type="submit" class="btn-large-green">Save</button>
                        </form>
                    </div>
                </div>
                <?php
            endforeach ?>
        </div>

        <script>
            $(document).ready(function() {
                selectToSelect2('select[name=profile-repos]', 'Select repo 🖉');
                selectToSelect2('select[name=profile-exclude-major]', 'Select package 🖉', true);
                selectToSelect2('select[name=profile-exclude]', 'Select package 🖉', true);
                selectToSelect2('select[name=profile-service-restart]', 'Select service 🖉', true);
            });
        </script>
        <?php
    endif ?>
</section>
