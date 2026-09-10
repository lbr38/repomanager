<?php

namespace Controllers\Websocket\BrowserClient;

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

        // Keep track of what has been broadcasted to avoid deleting containers that have been added while broadcasting
        $broadcasted = [];

        // For each container, send a reload request to all browser clients
        foreach ($containers as $container) {
            $this->broadcast($socket, 'browser-client', array(
                'type' => 'reload-container',
                'container' => $container['Container']
            ));

            $broadcasted[] = $container['Container'];
        }

        // Only remove what has just been broadcasted: a task may have requested a reload while
        // broadcasting, and it must be kept so it is sent on the next round
        $this->layoutContainerReloadController->delete($broadcasted);
    }
}
