/**
 *  Event: show tooltip on hover on element with data-tooltip attribute
 *  Usage: <span data-tooltip="Some text">...</span>
 *  Optional: data-tooltip-position="top|bottom|left|right" (default: top)
 */
$(document).on('mouseenter', '[data-tooltip]', function () {
    const text = $(this).attr('data-tooltip');

    if (empty(text)) {
        return;
    }

    mytooltip.showText(this, text, {
        position: $(this).attr('data-tooltip-position')
    });
});

/**
 *  Event: show HTML tooltip on hover on element with data-tooltip-template attribute
 *  Usage: <span data-tooltip-template>Label<template><p>HTML content</p></template></span>
 *  Optional: data-tooltip-position="top|bottom|left|right" (default: top)
 */
$(document).on('mouseenter', '[data-tooltip-template]', function () {
    const template = $(this).children('template').get(0);

    if (template === undefined) {
        return;
    }

    mytooltip.show(this, template.innerHTML, {
        position: $(this).attr('data-tooltip-position')
    });
});
