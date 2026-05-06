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

        // For snap-env context: outlined style (transparent bg, colored border and text)
        if (!empty($additionalCssClasses) && str_contains($additionalCssClasses, 'snap-env')) {
            if ($background == '#ffffff') {
                // No color configured: use a subtle gray outline
                $color = '#a0b0c0';
                $border = '1.5px solid #a0b0c0';
            } else {
                // Use the configured color for border and text
                $color = $background;
                $border = '1.5px solid ' . $background;
            }
            $background = 'transparent';
        } elseif ($background == '#ffffff') {
            $border = '1px solid #949494';
        } else {
            $border = '1px solid ' . $background;
        }

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

    /**
     *  Generate black label
     */
    public static function black(string $string): string
    {
        return '<span class="label-black">' . $string . '</span>';
    }
}
