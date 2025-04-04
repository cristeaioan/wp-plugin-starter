<?php

function prompt( $text, $default = '' ) {
    echo $text . ($default ? " [{$default}]" : '') . ": ";
    $input = trim(fgets(STDIN));
    return $input ?: $default;
}

/*
 * Prompt user for plugin details.
 */
$plugin_name = prompt('Plugin name', '');
$plugin_description = prompt('Plugin description', '');
$plugin_uri = prompt('Plugin URL', '');
$plugin_author = prompt('Author', '');
$plugin_author_uri = prompt('Author URL', '');

$namespace_input = prompt('Namespace', '');
$namespace = trim($namespace_input, '\\');

// Dependencies.
$dependent_theme = prompt('Dependent theme name');

$add_dependent_plugins = strtolower(prompt('Add dependent plugins (yes/no)?', 'no'));
$dependent_plugins = array();
if ( in_array($add_dependent_plugins, array('yes', 'y')) ) {
    do {
        $dependent_plugin_name = prompt('→ Plugin name');
        $dependent_plugin_path = prompt('→ Plugin path');

        if ( $dependent_plugin_name && $dependent_plugin_path ) {
            $dependent_plugins[] = array(
                'name' => $dependent_plugin_name,
                'path' => $dependent_plugin_path,
            );
        }

        $add_another_dependent_plugin = strtolower(prompt('➕ Add another plugin? (yes/no)', 'no'));
    } while ( in_array($add_another_dependent_plugin, array('yes', 'y')) );
}

// Create the plugin slug and constant prefix.
$plugin_slug = strtolower(str_replace(' ', '-', $plugin_name));
$plugin_const_prefix = strtoupper(str_replace([' ', '-'], '_', $plugin_slug));

// Make sure src directory exists
$src_path = __DIR__ . '/../src/';
if ( !is_dir($src_path) ) {
    mkdir($src_path, 0755, true);
}

/*
 * Create default directories.
 */
$directories = array('assets');
foreach ( $directories as $directory ) {
    if ( !is_dir($directory) ) {
        mkdir($directory, 0755, true);
        echo "✅ Created the '{$directory}' directory.\n";
    }
}

/*
 * Generate the required files.
 */
// Main plugin file.
$global_function_name = str_replace('-', '_', $plugin_slug);

$plugin_file = __DIR__ . "/../{$plugin_slug}.php";
$plugin_file_contents = <<<PHP
<?php
/*
	Plugin Name: {$plugin_name}
	Plugin URI: {$plugin_uri}
	Description: {$plugin_description}
	Author: {$plugin_author}
	Version: 1.0.0
	Author URI: {$plugin_author_uri}
*/

namespace {$namespace};

/**
 * Plugin name.
 *
 * @since 1.0.0
 */
if ( !defined( '{$plugin_const_prefix}_PLUGIN_NAME' ) ) {
    define( '{$plugin_const_prefix}_PLUGIN_NAME', '{$plugin_name}' );
}

/**
 * Plugin version.
 *
 * @since 1.0.0
 */
if ( !defined( '{$plugin_const_prefix}_PLUGIN_VER' ) ) {
    define( '{$plugin_const_prefix}_PLUGIN_VER', '1.0.0' );
}

/**
 * Plugin main file path.
 *
 * @since 1.0.0
 */
if ( !defined( '{$plugin_const_prefix}_PLUGIN_FILE' ) ) {
    define( '{$plugin_const_prefix}_PLUGIN_FILE', __FILE__ );
}

/**
 * Autoloader.
 *
 * @since 1.0.0
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Global function holder.
 *
 * @since 1.0.0
 *
 * @return Core
 */
function {$global_function_name}() {
    static \$core;

    if ( !isset(\$core) ) {
        \$core = new Core();
    }

    return \$core;
}

{$global_function_name}();
PHP;

file_put_contents($plugin_file, $plugin_file_contents);
echo "✅ Created plugin main file: '{$plugin_slug}.php'\n";

// Core class
$core_class_contents = <<<PHP
<?php

namespace {$namespace};

class Core {
    
    /**
     * URL to the plugin directory.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public \$plugin_url;

    /**
     * Path to the plugin directory.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public \$plugin_path;
    
    /**
     * URL to the plugin assets directory.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public \$assets_url;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
        \$this->plugin_url  = rtrim(plugin_dir_url( __DIR__ ), '/\\\');
        \$this->plugin_path = rtrim(plugin_dir_path( __DIR__ ), '/\\\');
        \$this->assets_url = \$this->plugin_url . '/assets';
        \$this->init();
    }

    /**
     * Initializes the class.
     *
     * @since 1.0.0
     */
    public function init() {
        /*
         * Check for theme and plugins dependencies.
         * If at least one of them is not active, then put a full stop to this plugin.
         */
        new DependenciesCheck();
    }

}
PHP;

file_put_contents($src_path . 'Core.php', $core_class_contents);
echo "✅ Created the 'Core' class.\n";

// Dependencies check class.
$dependent_plugins_array_code = "array()";
if ( !empty($dependent_plugins) ) {
    $dependent_plugins_array_code = "array(\n";
    foreach ( $dependent_plugins as $plugin ) {
        $name = addslashes($plugin['name']);
        $path = addslashes($plugin['path']);
        $dependent_plugins_array_code .= "\t\t\tarray(\n\t\t\t\t'name' => '{$name}',\n\t\t\t\t'path' => '{$path}'\n\t\t\t),\n";
    }
    $dependent_plugins_array_code .= "\t)";
}

$dependencies_check_contents = <<<PHP
<?php

namespace {$namespace};
use td_util;

class DependenciesCheck {

    public function __construct() {
        \$dependent_theme_name = '{$dependent_theme}';
        \$dependent_theme_active = true;
        \$active_theme = wp_get_theme();

        if ( !empty(\$dependent_theme_name) ) {
            if ( !\$active_theme->exists() || strpos( \$active_theme->name, \$dependent_theme_name ) === false ) {
                \$dependent_theme_active = false;
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        \$dependent_plugins = {$dependent_plugins_array_code};
        \$dependent_plugins_inactive = array();

        if( !empty(\$dependent_plugins) ) {
            foreach ( \$dependent_plugins as \$dependent_plugin ) {
                if( !is_plugin_active( \$dependent_plugin['path'] ) ) {
                    \$dependent_plugins_inactive[] = \$dependent_plugin;
                }
            }
        }

        if ( !\$dependent_theme_active || !empty( \$dependent_plugins_inactive ) ) {
            add_action( 'admin_notices', function () use ( \$dependent_theme_name, \$dependent_theme_active, \$dependent_plugins_inactive ) {
                \$buffy = '<div class="notice notice-error is-dismissible td-plugins-deactivated-notice">';
                    \$buffy .= '<p>';
                        \$buffy .= 'The <b>' . {$plugin_const_prefix}_PLUGIN_NAME . '</b> plugin requires the ';
        
                        if ( !\$dependent_theme_active ) {
                            \$buffy .= ' <b>' . \$dependent_theme_name . '</b> theme';
                        }
        
                        if ( !empty( \$dependent_plugins_inactive ) ) {
                            \$buffy .= !\$dependent_theme_active ? ' and ' : '';
                            foreach ( \$dependent_plugins_inactive as \$key => \$dependent_plugin ) {
                                \$buffy .= '<b>' . \$dependent_plugin['name'] . '</b>';
                                end(\$dependent_plugins_inactive);
                                if( \$key !== key( \$dependent_plugins_inactive ) ) {
                                    \$buffy .= ', ';
                                }
                            }
                            \$buffy .= ' plugin' . ( count( \$dependent_plugins_inactive ) > 1 ? 's' : '' );
                        }
        
                        \$buffy .= '!';
                    \$buffy .= '</p>';
                \$buffy .= '</div>';
                echo \$buffy;
            });

            return false;
        }

        return true;
    }
    
}
PHP;

file_put_contents($src_path . 'DependenciesCheck.php', $dependencies_check_contents);
echo "✅ Created the 'DependenciesCheck' class.\n";

/*
 * Update composer.json.
 */
$composer_path = __DIR__ . '/../composer.json';
$composer_json = json_decode(file_get_contents($composer_path), true);

$composer_json['name'] = strtolower(explode('\\', $namespace)[0]) . '/' . $plugin_slug;
$composer_json['autoload']['psr-4'] = [
    "{$namespace}\\" => "src/"
];
unset($composer_json['scripts']);

file_put_contents($composer_path, json_encode($composer_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "🔁 Updated composer.json\n";

/*
 * Run composer dump-autoload.
 */
echo "⚙️ Running composer dump-autoload...\n";
exec('composer dump-autoload -o', $output);
echo implode("\n", $output) . "\n";

/*
 * Whether to delete the 'scripts' folder.
 */
$delete_scripts_folder = strtolower(prompt("\nDo you want to delete the 'scripts' folder? (yes/no)", 'yes'));
if ( in_array($delete_scripts_folder, array('yes', 'y')) ) {
    $scripts_folder_path = 'scripts';
    if ( is_dir($scripts_folder_path) ) {
        echo "🔄 Deleting the 'scripts' folder...\n";
        exec("rm -rf {$scripts_folder_path}");
        echo "✅ 'scripts' folder deleted.\n";
    } else {
        echo "⚠️ The 'scripts' folder does not exist.\n";
    }
}

echo "\n✅ Setup complete! Your plugin '{$plugin_name}' is ready.\n";