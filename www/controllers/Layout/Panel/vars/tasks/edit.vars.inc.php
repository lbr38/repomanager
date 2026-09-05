<?php
use \Controllers\User\Permission\Task as TaskPermission;

$taskController = new \Controllers\Task\Task();
$tasks = [];

// Check if the user has permission to edit tasks
if (!TaskPermission::allowedAction('edit')) {
    throw new Exception('You are not allowed to edit a task.');
}

// Check if tasks Ids are provided in the request
if (empty($item['tasks'])) {
    throw new Exception('No task selected.');
}

// Loop through the provided tasks Ids and retrieve their details
foreach ($item['tasks'] as $id) {
    // Check if task exists
    if (!$taskController->exists($id)) {
        throw new Exception('Task #' . $id . ' does not exist.</p>');
    }

    // Get task details
    $task = $taskController->getById($id);

    try {
        // Get repository and add it to task details
        $task['Repository'] = $taskController->getRepo($id);
    } catch (Exception $e) {
        throw new Exception('Error retrieving repository for task #' . $id . ': ' . $e->getMessage());
    }

    // Add task details to tasks array
    $tasks[] = $task;
}
