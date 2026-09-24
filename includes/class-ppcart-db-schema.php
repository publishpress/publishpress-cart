<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/db-schema/class-ppcart-db-table-schema.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-issue.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-report.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-free-definitions.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-registry.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-table-inspector.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-column-type-policy.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-comparator.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-table-fixer.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-service.php';
require_once __DIR__ . '/db-schema/class-ppcart-db-schema-admin.php';

/**
 * Static facade for database schema check and repair.
 */
final class PPCart_DB_Schema
{
    /** @var bool */
    private static $initialized = false;

    /** @var PPCart_DB_Schema_Service|null */
    private static $service;

    /** @var PPCart_DB_Schema_Admin|null */
    private static $admin;

    /**
     * @return void
     */
    public static function init()
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        global $wpdb;

        if (! isset($wpdb) || ! is_object($wpdb)) {
            return;
        }

        $free       = new PPCart_DB_Schema_Free_Definitions();
        $registry   = new PPCart_DB_Schema_Registry($free);
        $inspector  = new PPCart_DB_Table_Inspector($wpdb);
        $comparator = new PPCart_DB_Schema_Comparator();
        $fixer      = new PPCart_DB_Table_Fixer($wpdb);

        self::$service = new PPCart_DB_Schema_Service($registry, $inspector, $comparator, $fixer);
        self::$admin   = new PPCart_DB_Schema_Admin(self::$service);
        self::$admin->register();
    }

    /**
     * @return PPCart_DB_Schema_Service
     */
    public static function service()
    {
        self::init();

        return self::$service;
    }

    /**
     * @return PPCart_DB_Schema_Admin
     */
    public static function admin()
    {
        self::init();

        return self::$admin;
    }
}
