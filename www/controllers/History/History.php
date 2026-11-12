<?php

namespace Controllers\History;

use Exception;

class History
{
    protected $model;

    // Categories of the history lines, with their label and icon
    public const CATEGORIES = [
        'authentication' => ['label' => 'Authentication', 'icon' => 'key'],
        'repository'     => ['label' => 'Repositories', 'icon' => 'package'],
        'mirror'         => ['label' => 'Mirrors', 'icon' => 'cloud-download'],
        'task'           => ['label' => 'Tasks', 'icon' => 'rocket'],
        'group'          => ['label' => 'Groups', 'icon' => 'folder'],
        'profile'        => ['label' => 'Host profiles', 'icon' => 'profile'],
        'user'           => ['label' => 'Users', 'icon' => 'user'],
        'settings'       => ['label' => 'Settings', 'icon' => 'cog'],
        'other'          => ['label' => 'Other', 'icon' => 'info']
    ];

    public function __construct()
    {
        $this->model = new \Models\History\History();
    }

    /**
     *  Return history lines matching the specified filters, most recent first
     *  Filters: user (Id), category, state, search
     */
    public function get(array $filters, int $limit, int $offset) : array
    {
        return $this->model->get($filters, $limit, $offset);
    }

    /**
     *  Return the number of history lines matching the specified filters
     */
    public function count(array $filters) : int
    {
        return $this->model->count($filters);
    }

    /**
     *  Return the number of failed local authentications for the specified username, from the current IP address,
     *  during the last specified minutes
     */
    public function countFailedLogins(string $username, int $minutes) : int
    {
        $since = time() - ($minutes * 60);

        return $this->model->countFailedLogins($username, $_SERVER['REMOTE_ADDR'] ?? '', date('Y-m-d', $since), date('H:i:s', $since));
    }

    /**
     *  Cleanup old history lines
     */
    public function cleanup(int $days) : void
    {
        $this->model->cleanup(date('Y-m-d', strtotime('-' . $days . ' days')));
    }
}
