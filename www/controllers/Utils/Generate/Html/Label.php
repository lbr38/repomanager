<?php

namespace Controllers\Utils\Generate\Html;

class Label
{
    /**
     *  Generate environment tag
     */
    public static function envtag(string $name, $additionalCss = ''): string
    {
        $name = trim($name);

        // Default class and colors
        $color = '#000000';
        $background = '#ffffff';
        $border = '';
        $protected = false;

        // Retrieve color from ENVS array
        if (defined('ENVS')) {
            foreach (ENVS as $env) {
                if (($env['Name'] ?? '') === $name) {
                    if (!empty($env['Color'])) {
                        $background = $env['Color'];
                        // Get contrasting text color
                        $color = Color::contrastingText($background);
                    }

                    $protected = ($env['Protected'] ?? 'false') === 'true';
                    break;
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

        // Inline styles mirror the .env class, so that the tag also renders in emails (where classes are not available)
        $style = 'display: inline-block; vertical-align: middle; padding: 5px 14px; border-radius: 20px; letter-spacing: 0.3px; background-color: transparent; color: ' . $color . '; border: ' . $border;
        $textStyle = 'font-family: archivo, Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 600; color: ' . $color;
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $content = '<span class="env flex column-gap-5 align-item-center' . ($additionalCss ? ' ' . $additionalCss : '') . '" style="' . $style . '" title="Environment ' . $name . ' ' . ($protected ? '(protected)' : '') . '">';

        // If environment is protected, add a lock icon
        if ($protected) {
            $content .= '<svg class="icon-small" width="12" height="12" viewBox="0 0 512 512" aria-hidden="true" focusable="false" style="color: ' . $color . '; flex-shrink: 0; vertical-align: middle;"><path d="m 374.60332,188.24922 h -16.94334 v -84.71665 a 101.66,101.66 0 1 0 -203.31998,0 v 84.71665 H 137.39667 A 67.847453,67.847453 0 0 0 69.62335,256.02255 V 442.3992 a 67.847453,67.847453 0 0 0 67.77332,67.77333 H 374.60332 A 67.847453,67.847453 0 0 0 442.37665,442.3992 V 256.02255 a 67.847453,67.847453 0 0 0 -67.77333,-67.77333 z m -50.82999,0 H 188.22667 v -84.71665 a 67.77333,67.77333 0 1 1 135.54666,0 z" fill="currentColor"/></svg>';
        }

        // Leading space separates the lock icon from the name in emails (ignored in the flex layout)
        $content .= ' <span style="' . $textStyle . '">' . $name . '</span></span>';

        return $content;
    }

    /**
     *  Generate white label
     */
    public static function white(string $string, bool $email = false): string
    {
        // Email clients do not load the app stylesheet, so mirror the label-white class inline
        if ($email) {
            $style = 'display: inline-block; vertical-align: middle; padding: 6px 10px; border-radius: 20px; border: 1.5px solid #c0d0e2; background: transparent; color: #c0d0e2; font-family: archivo, Roboto, Helvetica, Arial, sans-serif; font-size: 13px; font-weight: 600; letter-spacing: 0.2px; line-height: 14px; text-align: center;';

            return '<span style="' . $style . '">' . $string . '</span>';
        }

        return '<span class="label-white">' . $string . '</span>';
    }

    /**
     *  Generate mint label
     */
    public static function mint(string $string): string
    {
        return '<span class="label-mint">' . $string . '</span>';
    }
}
