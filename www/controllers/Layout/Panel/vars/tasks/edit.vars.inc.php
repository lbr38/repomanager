<?php
$taskController = new \Controllers\Task\Task();
$tasks = [];

// If the user is not an admin, check if they have permissions to edit a task
if (!IS_ADMIN and !in_array('edit', USER_PERMISSIONS['tasks']['allowed-actions'])) {
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
