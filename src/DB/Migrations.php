<?php
namespace LunexiaLiveFeedback\DB;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Migrations {
    protected $tables = [];
    protected $wpdb;
    protected $current_version = '1.0.0';
    protected $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        $this->tables = [
            'feedback'    => $wpdb->prefix . 'lunexia_lf_feedback',
            'comments'    => $wpdb->prefix . 'lunexia_lf_comments',
            'attachments' => $wpdb->prefix . 'lunexia_lf_attachments',
            'activity'    => $wpdb->prefix . 'lunexia_lf_activity',
            'assignments' => $wpdb->prefix . 'lunexia_lf_assignments',
            'migrations'  => $wpdb->prefix . 'lunexia_lf_migrations',
        ];
    }

    public function install() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Migrate any legacy lvf_ tables to lunexia_lf_ if present
        $this->maybe_rename_legacy_tables();

        // Create migrations table first to track applied migrations
        $this->create_migrations_table();

        // Run all pending migrations
        $this->run_migrations();

        // Log successful installation
        $this->log_migration( 'install', 'Plugin activation completed', true );
    }

    public function migrate() {
        $this->maybe_rename_legacy_tables();
        $this->run_migrations();
    }

    protected function maybe_rename_legacy_tables() {
        global $wpdb;
        $legacy_map = [
            $wpdb->prefix . 'lvf_feedback'    => $this->tables['feedback'],
            $wpdb->prefix . 'lvf_comments'    => $this->tables['comments'],
            $wpdb->prefix . 'lvf_attachments' => $this->tables['attachments'],
            $wpdb->prefix . 'lvf_activity'    => $this->tables['activity'],
            $wpdb->prefix . 'lvf_assignments' => $this->tables['assignments'],
            $wpdb->prefix . 'lvf_migrations'  => $this->tables['migrations'],
        ];

        foreach ( $legacy_map as $old_table => $new_table ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );

            if ( $old_exists === $old_table && $new_exists !== $new_table ) {
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "ALTER TABLE `{$old_table}` RENAME TO `{$new_table}`" );
            }
        }
    }

    protected function create_migrations_table() {
        $table = $this->tables['migrations'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(32) DEFAULT 'success',
            error_message TEXT DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY migration_unique (migration),
            KEY status (status)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    protected function run_migrations() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Ensure the migration tracking table exists before applying schema updates.
        $this->create_migrations_table();

        $migrations = [
            '1.0.0_create_feedback_table' => [ $this, 'migration_1_0_0_create_feedback_table' ],
            '1.0.1_add_device_type_to_feedback' => [ $this, 'migration_1_0_1_add_device_type_to_feedback' ],
            '1.0.2_ensure_feedback_columns' => [ $this, 'migration_1_0_2_ensure_feedback_columns' ],
            '1.0.3_add_guest_fields_to_feedback' => [ $this, 'migration_1_0_3_add_guest_fields_to_feedback' ],
            '1.0.0_create_comments_table' => [ $this, 'migration_1_0_0_create_comments_table' ],
            '1.0.4_add_guest_fields_to_comments' => [ $this, 'migration_1_0_4_add_guest_fields_to_comments' ],
            '1.0.0_create_attachments_table' => [ $this, 'migration_1_0_0_create_attachments_table' ],
            '1.0.0_create_activity_table' => [ $this, 'migration_1_0_0_create_activity_table' ],
            '1.0.0_create_assignments_table' => [ $this, 'migration_1_0_0_create_assignments_table' ],
        ];

        $batch = $this->get_next_batch();

        foreach ( $migrations as $name => $callback ) {
            if ( $this->migration_executed( $name ) ) {
                continue;
            }

            try {
                $wpdb->last_error = '';
                call_user_func( $callback );
                if ( ! empty( $wpdb->last_error ) ) {
                    throw new \Exception( $wpdb->last_error );
                }
                $this->log_migration( $name, 'Migration executed successfully', true, $batch );
            } catch ( \Exception $e ) {
                $this->log_migration( $name, 'Migration failed: ' . $e->getMessage(), false, $batch );
                error_log( 'Lunexia Live Feedback Migration error: ' . $e->getMessage() );
                throw $e;
            }
        }
    }

    protected function migration_executed( $name ) {
        global $wpdb;
        $table = $this->tables['migrations'];
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE migration = %s AND status = %s",
                $name,
                'success'
            )
        );
        return $result > 0;
    }

    protected function get_next_batch() {
        global $wpdb;
        $table = $this->tables['migrations'];
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = $wpdb->get_var( "SELECT MAX(batch) FROM {$table}" );
        return ( $result ?? 0 ) + 1;
    }

    protected function log_migration( $migration, $message, $success = true, $batch = null ) {
        global $wpdb;
        $wpdb->insert(
            $this->tables['migrations'],
            [
                'migration' => $migration,
                'batch' => $batch ?? 1,
                'status' => $success ? 'success' : 'error',
                'error_message' => ! $success ? $message : null,
            ],
            [ '%s', '%d', '%s', '%s' ]
        );
    }

    // Individual migration methods
    protected function migration_1_0_0_create_feedback_table() {
        $table = $this->tables['feedback'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            page_url TEXT NOT NULL,
            selector VARCHAR(255) DEFAULT NULL,
            coord_x INT NOT NULL DEFAULT 0,
            coord_y INT NOT NULL DEFAULT 0,
            viewport_width INT NOT NULL DEFAULT 0,
            viewport_height INT NOT NULL DEFAULT 0,
            screen_width INT NOT NULL DEFAULT 0,
            screen_height INT NOT NULL DEFAULT 0,
            browser_info TEXT,
            device_type VARCHAR(32) DEFAULT 'desktop',
            author_id BIGINT(20) UNSIGNED DEFAULT NULL,
            status VARCHAR(32) DEFAULT 'open',
            priority VARCHAR(32) DEFAULT 'normal',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            screenshot_id BIGINT(20) UNSIGNED DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY page_url (page_url(191)),
            KEY author_id (author_id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    protected function migration_1_0_1_add_device_type_to_feedback() {
        global $wpdb;
        $table = $this->tables['feedback'];
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $column_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'device_type' )
        );

        if ( empty( $column_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN device_type VARCHAR(32) DEFAULT 'desktop' AFTER browser_info" );
        }
    }

    protected function migration_1_0_2_ensure_feedback_columns() {
        global $wpdb;
        $table = $this->tables['feedback'];

        // Ensure updated_at column exists
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $updated_at_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'updated_at' )
        );

        if ( empty( $updated_at_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at" );
        }

        // Ensure created_at column exists
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $created_at_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'created_at' )
        );

        if ( empty( $created_at_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER priority" );
        }

        // Ensure device_type column exists
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $device_type_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'device_type' )
        );

        if ( empty( $device_type_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN device_type VARCHAR(32) DEFAULT 'desktop' AFTER browser_info" );
        }
    }

    protected function migration_1_0_0_create_comments_table() {
        $table = $this->tables['comments'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feedback_id BIGINT(20) UNSIGNED NOT NULL,
            parent_id BIGINT(20) UNSIGNED DEFAULT NULL,
            author_id BIGINT(20) UNSIGNED DEFAULT NULL,
            content TEXT NOT NULL,
            meta JSON DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY feedback_id (feedback_id),
            KEY parent_id (parent_id),
            KEY author_id (author_id)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    protected function migration_1_0_0_create_attachments_table() {
        $table = $this->tables['attachments'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feedback_id BIGINT(20) UNSIGNED DEFAULT NULL,
            comment_id BIGINT(20) UNSIGNED DEFAULT NULL,
            attachment_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY feedback_id (feedback_id),
            KEY comment_id (comment_id),
            KEY attachment_id (attachment_id)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    protected function migration_1_0_0_create_activity_table() {
        $table = $this->tables['activity'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feedback_id BIGINT(20) UNSIGNED NOT NULL,
            actor_id BIGINT(20) UNSIGNED DEFAULT NULL,
            action VARCHAR(191) NOT NULL,
            details TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY feedback_id (feedback_id),
            KEY actor_id (actor_id),
            KEY action (action)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    protected function migration_1_0_0_create_assignments_table() {
        $table = $this->tables['assignments'];
        $sql = "CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            feedback_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_assignment (feedback_id, user_id),
            KEY feedback_id (feedback_id),
            KEY user_id (user_id)
        ) {$this->charset_collate};";
        dbDelta( $sql );
    }

    public function uninstall() {
        global $wpdb;
        // Drop tables in reverse order
        $tables_to_drop = [
            $this->tables['assignments'],
            $this->tables['activity'],
            $this->tables['attachments'],
            $this->tables['comments'],
            $this->tables['feedback'],
            $this->tables['migrations'],
            // Legacy tables cleanup
            $wpdb->prefix . 'lvf_assignments',
            $wpdb->prefix . 'lvf_activity',
            $wpdb->prefix . 'lvf_attachments',
            $wpdb->prefix . 'lvf_comments',
            $wpdb->prefix . 'lvf_feedback',
            $wpdb->prefix . 'lvf_migrations',
        ];

        foreach ( $tables_to_drop as $table ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
        }

        error_log( 'Lunexia Live Feedback: Plugin uninstalled and database cleaned up' );
    }

    /**
     * Get all executed migrations
     */
    public function get_executed_migrations() {
        global $wpdb;
        $table = $this->tables['migrations'];
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY executed_at DESC"
        );
    }

    /**
     * Verify database integrity
     */
    public function verify_database() {
        global $wpdb;
        $issues = [];

        // Check if tables exist
        foreach ( $this->tables as $key => $table ) {
            if ( 'migrations' === $key ) {
                continue;
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                    DB_NAME,
                    $table
                )
            );
            if ( ! $exists ) {
                $issues[] = "Table {$table} does not exist";
            }
        }

        // Check charset
        foreach ( $this->tables as $key => $table ) {
            if ( 'migrations' === $key ) {
                continue;
            }
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $result = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
                    DB_NAME,
                    $table
                )
            );
            if ( $result && strpos( $result->TABLE_COLLATION, 'utf8' ) === false ) {
                $issues[] = "Table {$table} has incorrect charset/collation";
            }
        }

        return [
            'valid' => count( $issues ) === 0,
            'issues' => $issues,
            'tables' => array_keys( $this->tables ),
        ];
    }

    protected function migration_1_0_3_add_guest_fields_to_feedback() {
        global $wpdb;
        $table = $this->tables['feedback'];

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_session_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_session' )
        );

        if ( empty( $guest_session_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_session VARCHAR(255) DEFAULT NULL AFTER author_id" );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_name_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_name' )
        );

        if ( empty( $guest_name_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_name VARCHAR(255) DEFAULT NULL AFTER guest_session" );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_email_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_email' )
        );

        if ( empty( $guest_email_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL AFTER guest_name" );
        }

        // Add index for guest_session to speed up queries
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $index_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", 'guest_session' )
        );

        if ( empty( $index_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD KEY guest_session (guest_session)" );
        }
    }

    protected function migration_1_0_4_add_guest_fields_to_comments() {
        global $wpdb;
        $table = $this->tables['comments'];

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_session_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_session' )
        );

        if ( empty( $guest_session_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_session VARCHAR(255) DEFAULT NULL AFTER author_id" );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_name_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_name' )
        );

        if ( empty( $guest_name_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_name VARCHAR(255) DEFAULT NULL AFTER guest_session" );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $guest_email_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", 'guest_email' )
        );

        if ( empty( $guest_email_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD COLUMN guest_email VARCHAR(255) DEFAULT NULL AFTER guest_name" );
        }

        // Add index for guest_session to speed up queries
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $index_exists = $wpdb->get_row(
            $wpdb->prepare( "SHOW INDEX FROM {$table} WHERE Key_name = %s", 'guest_session' )
        );

        if ( empty( $index_exists ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $wpdb->query( "ALTER TABLE {$table} ADD KEY guest_session (guest_session)" );
        }
    }
}
