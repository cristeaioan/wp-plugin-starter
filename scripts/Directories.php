<?php

class Directories {

    /**
     * Holds the path to the plugin root.
     *
     * @var string
     */
    public static $plugin_root = __DIR__ . '/..';

    /**
     * Holds the list of directories used when setting up the plugin.
     *
     * @var array
     */
    public static $directories = [
        'src' => 'src',
        'src_admin' => 'src/Admin',
        'src_admin_pages' => 'src/Admin/Pages',
        'src_integrations' => 'src/Integrations',
        'assets' => 'assets',
    ];

    /**
     * Initializes the class by creating the directories.
     */
    public static function init() {
        foreach ( self::$directories as $key => $relative_path ) {
            self::$directories[$key] = self::create_dir($relative_path);
        }
    }

    /**
     * Creates a directory at the given path.
     *
     * @param string $relative_path
     */
    public static function create_dir( $relative_path ) {
        $full_path = self::$plugin_root . '/' . $relative_path;
        if ( !is_dir($full_path) ) {
            mkdir($full_path, 0755, true);
        }

        return rtrim($full_path, '/\\') . '/';
    }

}