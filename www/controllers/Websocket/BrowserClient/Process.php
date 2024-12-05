<?php

namespace Controllers\Websocket\BrowserClient;

use Exception;

/**
 *  Class Process extends WebsocketServer to gain access to its methods
 */
class Process extends \Controllers\Websocket\WebsocketServer
{
    /**
     *  Reload containers for all browser clients
     */
    public function reload($socket)
    {
        // Get all containers that need to be reloaded
        $containers = $this->layoutContainerReloadController->get();

        // Quit if there are no containers to reload
        if (empty($containers)) {
            return;
        }

        // For each container, send a reload request to all browser clients
        foreach ($containers as $container) {
            $this->broadcast($socket, 'browser-client', array(
                'type' => 'reload-container',
                'container' => $container['Container']
            ));
        }

        // Clean up the layout container state
        $this->layoutContainerReloadController->clean();
    }
}
