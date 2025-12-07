<?php

namespace Controllers\Utils\Generate\Html;

class Label
{
    /**
     *  Generate environment tag
     */
    public static function envtag(string $name, string|null $css = null) : string
    {
        // Default class and colors
        $class = 'env';
        $color = '#000000';
        $background = '#ffffff';

        // Retrieve color from ENVS array
        if (defined('ENVS')) {
            foreach (ENVS as $env) {
                if ($env['Name'] == $name and !empty($env['Color'])) {
                    $background = $env['Color'];
                    // Get contrasting text color
                    $color = \Controllers\Utils\Generate\Html\Color::contrastingText($background);
                }
            }
        }

        if ($css == 'fit') {
            $class = 'env-fit';
        }

        return '<span class="' . $class . '" style="background-color: ' . $background . '; color: ' . $color . '">' . $name . '</span>';
    }

    /**
     *  Generate white label
     */
    public static function white(string $string): string
    {
        return '<span class="label-white">' . $string . '</span>';
    }

    /**
     *  Generate black label
     */
    public static function black(string $string): string
    {
        return '<span class="label-black">' . $string . '</span>';
    }
}
