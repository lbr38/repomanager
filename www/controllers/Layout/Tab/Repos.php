<?php

namespace Controllers\Layout\Tab;

class Repos
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('repos/list');
        \Controllers\Layout\Container\Render::render('repos/properties');

        if (IS_ADMIN) {
            \Controllers\Layout\Panel\RepoGroup::render();
            \Controllers\Layout\Panel\SourceRepo::render();
            \Controllers\Layout\Panel\NewRepo::render();
            \Controllers\Layout\Panel\Task::render();
        }
    }
}
