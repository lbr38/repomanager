<?php

namespace Controllers\Service\Unit;

class WebsocketServer extends \Controllers\Service\Service
{
    private $wssController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->wssController = new \Controllers\Websocket\WebsocketServer();
    }

    /**
     *  Run the websocket server
     */
    public function run() : void
    {
        // Default port is 8081
        $port = 8081;

        // If a .wss file exists, read the port from it
        if (file_exists(ROOT . '/.wss')) {
            $content = trim(file_get_contents(ROOT . '/.wss'));

            if (is_numeric($content)) {
                $port = $content;
            }

            unset($content);
        }

        parent::log('Launching websocket server on port ' . $port);

        // Run the websocket server
        $this->wssController->run($port);
    }
}
