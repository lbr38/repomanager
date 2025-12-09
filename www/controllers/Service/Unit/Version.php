<?php

namespace Controllers\Service\Unit;

use Exception;

class Version extends \Controllers\Service\Service
{
    public function __construct(string $unit)
    {
        parent::__construct($unit);
    }

    /**
     *  Get latest version from github
     */
    public function get() : void
    {
        try {
            $httpRequestController = new \Controllers\HttpRequest();

            parent::log('Checking for a new version on github...');

            try {
                $httpRequestController->get([
                    'url'          => VERSION_FILE_URL,
                    'outputToFile' => DATA_DIR . '/version.available',
                    'timeout'      => 30,
                    'proxy'        => PROXY ?? null,
                ]);
            } catch (Exception $e) {
                throw new Exception('error while checking for new version from Github: ' . $e->getMessage());
            }

            // Also get all releases list from github (parse json and only get the tag names)
            try {
                $httpRequestController->get(
                    [
                        'url'          => RELEASES_URL,
                        'outputToFile' => DATA_DIR . '/releases.available',
                        'timeout'      => 30,
                        'proxy'        => PROXY ?? null,
                        'headers'      => [
                            'User-Agent: repomanager',
                            'Accept: application/vnd.github.v3+json',
                        ]
                    ],
                    // Parse the JSON
                    true,
                    // And extract only the name of the releases
                    'name'
                );
            } catch (Exception $e) {
                throw new Exception('error while retrieving releases from Github: ' . $e->getMessage());
            }

            parent::log('Version successfully checked');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        } finally {
            unset($httpRequestController);
        }
    }
}
