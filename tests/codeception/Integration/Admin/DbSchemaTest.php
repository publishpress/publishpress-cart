<?php

namespace Tests\Integration\Admin;

use PPCart_DB_Schema;
use PPCart_DB_Table_Schema;
use Tests\NoTransactionWPTestCase;

class DbSchemaTest extends NoTransactionWPTestCase
{
    /**
     * @var \IntegrationTester
     */
    protected $tester;

    /** @var string|null */
    private $filter_table;

    public function set_up()
    {
        parent::set_up();

        global $wpdb;

        $suppress = $wpdb->suppress_errors(true);

        foreach ([ 'tax_rate', 'order_items', 'order_itemmeta', 'downloads' ] as $family) {
            $table = ppcart_live_table($family);

            if ('' === $table) {
                continue;
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Reset plugin-owned tables between DDL tests.
            $wpdb->query("TRUNCATE TABLE `{$table}`");
        }

        $wpdb->suppress_errors($suppress);

        PPCart_DB_Schema::service()->repair_all();
    }

    public function test_IT_377_fresh_install_reports_healthy_schema(): void
    {
        $report = PPCart_DB_Schema::service()->check_all();

        $this->assertTrue($report->is_healthy());
    }

    public function test_IT_377_dropped_table_is_reported_and_repaired(): void
    {
        global $wpdb;

        $table = ppcart_live_table('tax_rate');
        $this->dropPluginTable($table);

        $report = PPCart_DB_Schema::service()->check_all();
        $this->assertFalse($report->is_healthy());

        $repaired = PPCart_DB_Schema::service()->repair_all();
        $this->assertTrue($repaired->is_healthy());
        $this->assertTableExists($table);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Test verifies row writes after repair.
        $wpdb->insert(
            $table,
            [
                'tax_rate_country'  => 'US',
                'tax_rate_state'    => 'CA',
                'tax_rate_postcode' => '90210',
                'tax_rate_city'     => 'LA',
                'tax_rate'          => '10',
            ]
        );

        $this->assertGreaterThan(0, (int) $wpdb->insert_id);
    }

    public function test_IT_377_dropped_column_is_reported_and_repaired_preserving_rows(): void
    {
        global $wpdb;

        $table = ppcart_live_table('tax_rate');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Seed plugin-owned table for schema repair test.
        $wpdb->insert(
            $table,
            [
                'tax_rate_country'  => 'US',
                'tax_rate_state'    => 'NY',
                'tax_rate_postcode' => '10001',
                'tax_rate_city'     => 'NYC',
                'tax_rate'          => '8',
                'tax_rate_title'    => 'State tax',
            ]
        );
        $row_id = (int) $wpdb->insert_id;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Simulate missing column for repair test.
        $wpdb->query("ALTER TABLE `{$table}` DROP COLUMN tax_rate_title");

        $this->assertFalse(PPCart_DB_Schema::service()->check_all()->is_healthy());

        $repaired = PPCart_DB_Schema::service()->repair_all();
        $this->assertTrue($repaired->is_healthy());
        $this->assertColumnExists($table, 'tax_rate_title');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Verify row survived column repair.
        $rate = $wpdb->get_var($wpdb->prepare("SELECT tax_rate FROM %i WHERE id = %d", $table, $row_id));
        $this->assertSame('8', $rate);
    }

    public function test_IT_377_dropped_order_key_index_is_reported_and_repaired(): void
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');
        $this->seedDownloadRow('repair-index-drop', 'order-key-drop-index');

        $this->dropTableIndex($table, 'order_key');
        $this->assertFalse(PPCart_DB_Schema::service()->check_all()->is_healthy());

        $repaired = PPCart_DB_Schema::service()->repair_all();
        $this->assertTrue($repaired->is_healthy());
        $this->assertSame('0', $this->getIndexNonUnique($table, 'order_key'));
    }

    public function test_IT_377_non_unique_order_key_index_is_rebuilt_as_unique(): void
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');
        $this->seedDownloadRow('repair-index-unique', 'order-key-unique-rebuild');

        $this->dropTableIndex($table, 'order_key');
        $this->createTableIndex($table, 'order_key', [ 'order_key' ]);

        $this->assertSame('1', $this->getIndexNonUnique($table, 'order_key'));
        $this->assertFalse(PPCart_DB_Schema::service()->check_all()->is_healthy());

        $repaired = PPCart_DB_Schema::service()->repair_all();
        $this->assertTrue($repaired->is_healthy());
        $this->assertSame('0', $this->getIndexNonUnique($table, 'order_key'));
    }

    public function test_IT_377_duplicate_order_key_rows_block_unique_rebuild(): void
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');
        $this->dropTableIndex($table, 'order_key');

        $this->seedDownloadRow('dup-a', 'duplicate-key');
        $this->seedDownloadRow('dup-b', 'duplicate-key');

        $this->createTableIndex($table, 'order_key', [ 'order_key' ]);

        $report = PPCart_DB_Schema::service()->repair_all();
        $array  = $report->to_array();

        $this->assertFalse($report->is_healthy());
        $this->assertNotEmpty($array['tables'][ $table ]['fix_errors']);
        $this->assertSame('1', $this->getIndexNonUnique($table, 'order_key'));
    }

    public function test_IT_377_failed_repair_does_not_print_database_errors(): void
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');
        $this->dropTableIndex($table, 'order_key');
        $this->seedDownloadRow('print-a', 'duplicate-print-key');
        $this->seedDownloadRow('print-b', 'duplicate-print-key');
        $this->createTableIndex($table, 'order_key', [ 'order_key' ]);

        $show_errors = $wpdb->show_errors(true);
        ob_start();
        try {
            $report = PPCart_DB_Schema::service()->repair_all();
        } finally {
            $output = (string) ob_get_clean();
            $wpdb->show_errors($show_errors);
        }

        $this->assertSame('', $output);
        $this->assertNotEmpty($report->to_array()['tables'][ $table ]['fix_errors']);
        $this->assertTrue($wpdb->show_errors);
    }

    public function test_IT_377_running_repair_twice_does_not_create_order_key_2(): void
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');
        $this->seedDownloadRow('repair-twice', 'order-key-repair-twice');

        $this->dropTableIndex($table, 'order_key');
        $this->createTableIndex($table, 'order_key', [ 'order_key' ]);

        PPCart_DB_Schema::service()->repair_all();
        PPCart_DB_Schema::service()->repair_all();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Inspect index names after double repair.
        $names = $wpdb->get_col($wpdb->prepare('SHOW INDEX FROM %i', $table), 2);
        $order_key_names = array_values(array_filter($names, static function ($name) {
            return 0 === strpos((string) $name, 'order_key');
        }));

        $this->assertSame([ 'order_key' ], array_values(array_unique($order_key_names)));
    }

    public function test_IT_377_filter_registered_table_is_created_by_repair(): void
    {
        global $wpdb;

        $this->filter_table = $wpdb->prefix . 'ppcart_db_schema_filter_test';
        $this->dropPluginTable($this->filter_table);
        $this->clearSchemaRegistryCache();

        add_filter(
            'ppcart_db_table_schemas',
            [ $this, 'registerFilterTestSchema' ]
        );

        $this->assertFalse(PPCart_DB_Schema::service()->check_all()->is_healthy());
        $repaired = PPCart_DB_Schema::service()->repair_all();

        $this->assertTrue($repaired->is_healthy());
        $this->assertTableExists($this->filter_table);

        remove_filter('ppcart_db_table_schemas', [ $this, 'registerFilterTestSchema' ]);
        $this->dropPluginTable($this->filter_table);
        $this->filter_table = null;
        $this->clearSchemaRegistryCache();
    }

    public function test_IT_377_ajax_repair_returns_403_without_manage_options(): void
    {
        $user_id = $this->factory()->user->create([ 'role' => 'subscriber' ]);
        wp_set_current_user($user_id);

        $_POST = [
            'nonce' => wp_create_nonce('ppcart_fix_db_schema'),
        ];
        $_REQUEST['nonce'] = $_POST['nonce'];

        add_filter('wp_doing_ajax', '__return_true');
        add_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);

        ob_start();
        try {
            PPCart_DB_Schema::admin()->ajax_fix_db_schema();
        } catch (\RuntimeException $exception) {
            $this->assertSame('wp_die', $exception->getMessage());
        } finally {
            remove_filter('wp_doing_ajax', '__return_true');
            remove_filter('wp_die_ajax_handler', [ $this, 'getAjaxDieHandler' ]);
            unset($_POST['nonce'], $_REQUEST['nonce']);
        }

        $response = json_decode(trim((string) ob_get_clean()), true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
    }

    public function test_IT_377_maintenance_markup_keeps_fix_button_hooks_after_admin_kses(): void
    {
        $html = wp_kses(PPCart_DB_Schema::admin()->render_maintenance_html(), ppcart_admin_allowed_html());

        $this->assertMatchesRegularExpression('/<button[^>]*\sdata-ppcart-fix-db-schema[\s=>]/', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*\sdata-nonce="[^"]+"/', $html);
        $this->assertStringContainsString('data-ppcart-fix-db-schema-result', $html);
        $this->assertStringContainsString('data-ppcart-db-schema-status', $html);
    }

    /**
     * @return callable
     */
    public function getAjaxDieHandler()
    {
        return static function ($message = '', $title = '', $args = []): void {
            throw new \RuntimeException('wp_die');
        };
    }

    /**
     * @param PPCart_DB_Table_Schema[] $schemas
     * @return PPCart_DB_Table_Schema[]
     */
    public function registerFilterTestSchema($schemas)
    {
        if (! is_array($schemas) || null === $this->filter_table) {
            return $schemas;
        }

        $schemas[] = new PPCart_DB_Table_Schema(
            $this->filter_table,
            [
                'row_id' => 'bigint(20) NOT NULL AUTO_INCREMENT',
            ],
            [
                'PRIMARY' => [
                    'columns' => [ 'row_id' ],
                    'unique'  => true,
                ],
            ],
            'Filter test table'
        );

        return $schemas;
    }

    /**
     * @param string $table_name
     * @return void
     */
    private function dropPluginTable($table_name)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Test helper without PublishPress Future cache.
        $wpdb->query('DROP TABLE IF EXISTS `' . esc_sql((string) $table_name) . '`');
    }

    /**
     * @param string $file_id
     * @param string $order_key
     * @return void
     */
    private function seedDownloadRow($file_id, $order_key = 'order-key-seed')
    {
        global $wpdb;

        $table = ppcart_live_table('downloads');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Seed plugin-owned downloads table.
        $wpdb->insert(
            $table,
            [
                'file_id'    => $file_id,
                'order_id'   => 1,
                'order_key'  => $order_key,
                'product_id' => 1,
            ]
        );
    }

    /**
     * @param string $table
     * @param string $index_name
     * @return string
     */
    private function getIndexNonUnique($table, $index_name)
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Schema inspection in test.
        $rows = $wpdb->get_results($wpdb->prepare('SHOW INDEX FROM %i', $table), ARRAY_A);

        if (! is_array($rows)) {
            return '';
        }

        foreach ($rows as $row) {
            if (isset($row['Key_name'], $row['Non_unique']) && $index_name === $row['Key_name']) {
                return (string) $row['Non_unique'];
            }
        }

        return '';
    }

    /**
     * @return void
     */
    private function clearSchemaRegistryCache()
    {
        $service_ref = new \ReflectionClass(PPCart_DB_Schema::service());
        $registry    = $service_ref->getProperty('registry');
        $registry->setAccessible(true);
        $registry_instance = $registry->getValue(PPCart_DB_Schema::service());

        $cache = new \ReflectionClass($registry_instance);
        $prop  = $cache->getProperty('schemas_cache');
        $prop->setAccessible(true);
        $prop->setValue($registry_instance, null);
    }
}
