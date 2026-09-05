<?php
$userPreferenceController = new \Controllers\User\Preference\Preference();

// Check if session is valid
if (empty($_SESSION['id'])) {
    throw new Exception('Session id is empty.');
}

// Get user preferences
$preferences = $userPreferenceController->get($_SESSION['id']);

unset($userPreferenceController);
