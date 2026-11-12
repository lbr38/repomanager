<?php

namespace Controllers\Api\V3;

use Controllers\Api\HttpException;
use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\User\Permission\Mirror as MirrorPermission;

/**
 *  Base class of the API v3 handlers
 */
abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     *  Throw a 403 error if the user is not allowed to perform the action
     */
    protected static function checkPermission(bool $allowed, string $message = 'You are not allowed to perform this action'): void
    {
        if (!$allowed) {
            throw new HttpException(403, $message);
        }
    }

    /**
     *  Validate and execute (or schedule) task(s), return the response with the task Id
     *  The message describes the operation, e.g. 'Sync of mirror #3'
     */
    protected function launch(array $tasksParams, string $message): Response
    {
        // Permissions are also checked by the tasks validation, checking them first returns a 403 error
        foreach ($tasksParams as $taskParams) {
            if (($taskParams['domain'] ?? '') == 'mirror') {
                $allowed = MirrorPermission::allowedAction($taskParams['action']);
            } else {
                $allowed = RepoPermission::allowedAction($taskParams['action']);
            }

            if (!$allowed) {
                throw new HttpException(403, 'You are not allowed to execute this action (' . $taskParams['action'] . ')');
            }
        }

        $taskId = (new \Controllers\Task\Launcher())->launch($tasksParams);

        $scheduled = ($tasksParams[0]['schedule']['scheduled'] ?? 'false') == 'true';

        return Response::task(\Controllers\Api\V3\Task\Tasks::get($taskId), $message . ($scheduled ? ' scheduled' : ' started'));
    }
}
