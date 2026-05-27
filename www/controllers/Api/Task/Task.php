<?php

namespace Controllers\Api\Task;

use Controllers\Task\Task as TaskController;
use Exception;

class Task extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        $taskController = new TaskController();

        if (!empty($this->uri[4])) {
            if (!is_numeric($this->uri[4])) {
                throw new Exception('Invalid task ID.');
            }

            // Get task ID from URI
            $id = (int)$this->uri[4];

            // Check if task exists
            if (!$taskController->exists($id)) {
                throw new Exception('Task #' . $id . ' does not exist.');
            }

            /**
             *  List task details by task ID
             *  https://repomanager.mydomain.net/api/v2/task/{id}/
             */
            if (empty($this->uri[5]) and $this->method == 'GET') {
                return ['results' => $taskController->getById($id)];
            }

            if (!empty($this->uri[5])) {
                /**
                 *  Enable a task by task ID
                 *  https://repomanager.mydomain.net/api/v2/task/{id}/enable
                 */
                if ($this->uri[5] == 'enable' and $this->method == 'POST') {
                    $taskController->enable([$id]);

                    return ['results' => 'Task enabled successfully.'];
                }

                /**
                 *  Disable a task by task ID
                 *  https://repomanager.mydomain.net/api/v2/task/{id}/disable
                 */
                if ($this->uri[5] == 'disable' and $this->method == 'POST') {
                    $taskController->disable([$id]);

                    return ['results' => 'Task disabled successfully.'];
                }

                /**
                 *  Delete a task by task ID
                 *  https://repomanager.mydomain.net/api/v2/task/{id}/delete
                 */
                if ($this->uri[5] == 'delete' and $this->method == 'DELETE') {
                    $taskController->delete([$id]);

                    return ['results' => 'Task deleted successfully.'];
                }

                /**
                 *  Stop a task by task ID
                 *  https://repomanager.mydomain.net/api/v2/task/{id}/stop
                 */
                if ($this->uri[5] == 'stop' and $this->method == 'POST') {
                    $taskController->stop($id);

                    return ['results' => 'Task stopped successfully.'];
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
