<?php

namespace Controllers\Layout\Tab;

class Plans
{
    public static function render()
    {
        \Controllers\Layout\Container\Render::render('repos/list');
        \Controllers\Layout\Container\Render::render('plans/all');

        if (IS_ADMIN) {
            \Controllers\Layout\Panel\RepoGroup::render();
            \Controllers\Layout\Panel\SourceRepo::render();
            \Controllers\Layout\Panel\Operation::render();
        }
    }
}
