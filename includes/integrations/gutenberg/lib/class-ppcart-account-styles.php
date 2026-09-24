<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/account-styles/trait-ppcart-account-style-classes.php';
require_once __DIR__ . '/account-styles/trait-ppcart-account-style-attributes.php';
require_once __DIR__ . '/account-styles/trait-ppcart-account-style-helpers.php';

class PPCart_Account_Styles
{
    use PPCart_Account_Style_Classes;
    use PPCart_Account_Style_Attributes;
    use PPCart_Account_Style_Helpers;
}
