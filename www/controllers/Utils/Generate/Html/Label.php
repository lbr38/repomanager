<?php

namespace Controllers\Utils\Generate\Html;

class Label
{
    /**
     *  Generate environment tag
     */
    public static function envtag(string $name, string|null $css = null, string|null $additionalCssClasses = null, string|null $additionalStyle = null) : string
    {
        // Default class and colors
        $class = 'env';
        $color = '#000000';
        $background = '#ffffff';
        $border = '';

        // Retrieve color from ENVS array
        if (defined('ENVS')) {
            foreach (ENVS as $env) {
                if ($env['Name'] == $name and !empty($env['Color'])) {
                    $background = $env['Color'];
                    // Get contrasting text color
                    $color = Color::contrastingText($background);
                }
            }
        }

        // Outlined style: transparent bg, colored border and text
        if ($background == '#ffffff') {
            // No color configured: use a subtle gray outline
            $color = '#c0d0e2';
            $border = '1.5px solid #c0d0e2';
        } else {
            // Use the configured color for border and text
            $color = $background;
            $border = '1.5px solid ' . $background;
        }
        $background = 'transparent';

        if ($css == 'fit') {
            $class = 'env-fit';
        }

        if (!empty($additionalCssClasses)) {
            $class .= ' ' . $additionalCssClasses;
        }

        $style = 'background-color: ' . $background . '; color: ' . $color . '; border: ' . $border;

        if (!empty($additionalStyle)) {
            $style .= '; ' . $additionalStyle;
        }

        return '<span class="' . $class . '" style="' . $style . '">' . $name . '</span>';
    }

    /**
     *  Generate white label
     */
    public static function white(string $string): string
    {
        return '<span class="label-white">' . $string . '</span>';
    }
}
