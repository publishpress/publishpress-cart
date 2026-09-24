<?php

/**
 * States
 *
 * Returns an array of country states.
 * States should be defined in English and translated native through localisation files.
 * Country codes and states (or province) names should follow the Unicode CLDR recommendation (http://cldr.unicode.org/translation/country-names).
 * Countries defined with empty arrays have no states.
 *
 * @package PublishPressCart/includes
 * @version 2.1.7
 */

defined('ABSPATH') || exit;

$states = [];

$states = array_merge($states, require __DIR__ . '/data/states/states-01.php');
$states = array_merge($states, require __DIR__ . '/data/states/states-02.php');
$states = array_merge($states, require __DIR__ . '/data/states/states-03.php');
$states = array_merge($states, require __DIR__ . '/data/states/states-04.php');

return $states;
