<?php

namespace Controllers\Api\V3\Task;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;
use Controllers\Api\V3\Schedule;
use Controllers\Task\Task as TaskController;
use Controllers\Task\Listing;
use Controllers\User\Permission\Task as TaskPermission;

/**
 *  /tasks: tasks executed or scheduled on repositories and mirrors
 */
class Tasks extends Controller
{
    private const STATUSES = ['running', 'queued', 'scheduled', 'done'];

    /**
     *  List tasks, optionally by status: ?status=running|queued|scheduled|done
     */
    public function list(): Response
    {
        $listingController = new Listing();
        $status = $this->request->query('status');

        $tasks = match ($status) {
            null        => $listingController->get(),
            'running'   => $listingController->getRunning(),
            'queued'    => $listingController->getQueued(),
            'scheduled' => $listingController->getScheduled(),
            'done'      => $listingController->getDone(),
            default     => throw HttpException::invalidQueryParameter('Invalid status ' . $status . ' (' . implode(', ', self::STATUSES) . ')', 'status')
        };

        return Response::page($this->request, array_map(self::format(...), $tasks));
    }

    /**
     *  Return a task details
     */
    public function details(): Response
    {
        return Response::ok(self::get($this->task()));
    }

    /**
     *  Return the details of a task by its Id, in the API format
     */
    public static function get(int $id): array
    {
        return self::format((new TaskController())->getById($id));
    }

    /**
     *  Delete a task
     */
    public function delete(): Response
    {
        self::checkPermission(TaskPermission::allowedAction('delete'), 'You are not allowed to delete a task');

        $id = $this->task();

        (new TaskController())->delete([$id]);

        return Response::message('Task #' . $id . ' deleted');
    }

    /**
     *  Enable, disable, stop or relaunch a task
     */
    public function action(): Response
    {
        $taskController = new TaskController();
        $action = $this->request->param('action');
        $id = $this->task();

        self::checkPermission(TaskPermission::allowedAction($action), 'You are not allowed to ' . $action . ' a task');

        switch ($action) {
            case 'enable':
                $taskController->enable([$id]);
                return Response::message('Task #' . $id . ' enabled');

            case 'disable':
                $taskController->disable([$id]);
                return Response::message('Task #' . $id . ' disabled');

            case 'stop':
                $taskController->stop($id);
                return Response::message('Task #' . $id . ' stopped');

            default:
                return Response::task(self::get($taskController->relaunch($id)), 'Task #' . $id . ' relaunched');
        }
    }

    /**
     *  Return the task Id specified in the route, throw a 404 error if it does not exist
     */
    private function task(): int
    {
        $id = $this->request->id();

        if (!(new TaskController())->exists($id)) {
            throw new HttpException(404, 'Task #' . $id . ' not found');
        }

        return $id;
    }

    /**
     *  Return a task details from its database row
     */
    private static function format(array $task): array
    {
        $params = json_decode($task['Raw_params'] ?? '', true);

        if (!is_array($params)) {
            $params = [];
        }

        // Details of the repository or mirror targeted by the task
        $repository = null;

        if (!empty($params['repo-info']) and is_array($params['repo-info'])) {
            foreach ($params['repo-info'] as $key => $value) {
                $repository[str_replace('-', '_', $key)] = $value;
            }
        }

        return [
            'id'              => (int) $task['Id'],
            'parent_id'       => empty($task['Parent_task_id']) ? null : (int) $task['Parent_task_id'],
            'relaunched_from' => empty($task['Relaunched_from_task_id']) ? null : (int) $task['Relaunched_from_task_id'],
            'type'            => $task['Type'],
            'domain'          => ($params['domain'] ?? '') == 'mirror' ? 'mirror' : 'repository',
            'action'          => $params['action'] ?? null,
            'title'           => html_entity_decode(strip_tags(TaskController::generateLiteralAction($task)['title'] ?? ''), ENT_QUOTES),
            'status'          => $task['Status'],
            'date'            => $task['Date'],
            'time'            => $task['Time'],
            'duration'        => $task['Duration'] === null ? null : (int) $task['Duration'],
            'launched_by'     => $task['Launched_by'] ?? null,
            'repository'      => $repository,
            'schedule'        => Schedule::toApi((array) ($params['schedule'] ?? []))
        ];
    }
}
