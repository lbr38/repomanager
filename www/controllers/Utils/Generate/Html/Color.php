<?php

namespace Controllers\Utils\Generate\Html;

class Color
{
    /**
     *  Get the best contrasting text color (black or white) for a given background color
     */
    public static function contrastingText($color) : string
    {
        // Convert hexadecimal color to RGB
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));

        // Calculate YIQ (luma) value
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Return white for dark colors and black for light colors
        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }

    /**
     *  Get a random color from a valid hex colors list
     */
    public static function random() : string
    {
        // Define a list of valid colors
        // Color must match well with background color #0e1e2f or #182b3e
        $colors = [
            'rgb(75, 192, 192)',
            '#5993ec',
            '#e0b05f',
            '#24d794',
            '#EFBDEB',
            '#F85A3E',
            '#8EB1C7',
            '#1AC8ED',
            '#E9D758',
            // additional harmonious / high-contrast choices
            '#4DD0E1', // soft cyan
            '#1ABC9C', // turquoise
            '#5DADE2', // sky blue
            '#2980B9', // strong blue
            '#FF6F61', // coral
            '#FF8C42', // warm orange
            '#F1C40F', // golden
            '#E1B000', // mustard
            '#A3E635', // lime
            '#7ED957', // light green
            '#C39BD3', // lavender
            '#FADADD', // pale pink
            '#F5E1C8', // warm beige
            '#9FB3C8', // desaturated slate
            '#F8F9FA', // near-white for subtle contrast
        ];

        $randomColorId = array_rand($colors, 1);

        return $colors[$randomColorId];
    }
}
