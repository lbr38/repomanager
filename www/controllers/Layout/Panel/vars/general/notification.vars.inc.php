<?php
/**
 *  If an update is available
 */
if (IS_ADMIN && UPDATE_AVAILABLE) {
    $upgradePath = [];

    /**
     *  Check if its a major release version
     *  If first digit of the version is different, its a major release
     */
    $currentVersionDigit = explode('.', VERSION)[0];
    $newVersionDigit     = explode('.', GIT_VERSION)[0];

    // Generate upgrade path by getting releases available from releases.available
    if (file_exists(DATA_DIR . '/releases.available')) {
        $content = file_get_contents(DATA_DIR . '/releases.available');

        if (!empty($content)) {
            try {
                $releases = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $message .= 'cannot retrieve upgrade path (error while parsing JSON): ' . $e->getMessage();
            }

            /**
             *  Generate upgrade path
             */
            if (is_array($releases) && !empty($releases)) {
                asort($releases);

                foreach ($releases as $release) {
                    if (version_compare($release, VERSION, '<=')) {
                        continue;
                    }

                    if ($release === GIT_VERSION) {
                        break;
                    }

                    $upgradePath[] = $release;
                }
            }
        }
    }
}
