/**
 *  The list of containers that must use Morphdom to update their content
 *  @type {Array}
 */
const containersUsingMorphdom = [
    // 'repos/properties',
];

/**
 *  Default morphdom skip rules, apply to all containers
 *  @type {Array}
 */
const defaultMorphdomSkipRules = [
    { element: 'INPUT[type="checkbox"]', skipIf: 'checked' },
    { element: 'CANVAS', skipIf: 'always' }
];
