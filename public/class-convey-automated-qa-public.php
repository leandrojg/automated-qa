<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.conveydigital.com/
 * @since      1.0.0
 *
 * @package    Convey_Automated_Qa
 * @subpackage Convey_Automated_Qa/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Convey_Automated_Qa
 * @subpackage Convey_Automated_Qa/public
 * @author     Convey Digital <info@conveydigital.com>
 */
class Convey_Automated_Qa_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {

        $this->plugin_name = $plugin_name;
        $this->version     = $version;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/convey-automated-qa-public.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/convey-automated-qa-public.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Encrypt data using AES-256-CBC.
     *
     * @param    string    $data       The data to encrypt.
     * @param    string    $secretKey  The encryption key.
     * @return   string    The encrypted data.
     */
    private function encrypt( $data, $secretKey ) {
        $method   = 'aes-256-cbc';
        $ivSize   = openssl_cipher_iv_length( $method );
        $iv       = openssl_random_pseudo_bytes( $ivSize );
        $encrypted = openssl_encrypt( $data, $method, $secretKey, OPENSSL_RAW_DATA, $iv );
        return base64_encode( $iv . $encrypted );
    }

    /**
     * Create custom endpoint for external access.
     *
     * @since 1.0.0
     */
    public function render_endpoint() {
        $request_uri = $_SERVER['REQUEST_URI'];
        $parsed_url  = parse_url( $request_uri );
        $path        = $parsed_url['path'];

        if ( $path == CD_QA_ENDPOINT_URL ) {
            $headers = apache_request_headers();
            if ( isset( $headers['Cd-Auth-Key'] ) && $headers['Cd-Auth-Key'] === CD_QA_ENDPOINT_KEY ) {
                status_header( 200 );
                $convey_qa          = $this->convey_qa();
                $json_convey_qa     = json_encode( $convey_qa );
                $encrypted_convey_qa = $this->encrypt( $json_convey_qa, CD_QA_RESPONSE_KEY );
                echo $encrypted_convey_qa;
                exit;
            }
        }
    }

    /**
     * Return JSON with QA values.
     *
     * @return array
     */
    public function convey_qa() {
        global $wpdb;
        $admin_email = $wpdb->get_var( "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'admin_email'" );

        $return = [
            'admin_email'              => $admin_email,
            'wordpress_core_version'   => $this->get_wordpress_core(),
            'php_version'              => $this->get_php_version(),
            'mysql_version'            => $this->get_mysql_version(),
            'core_plugins'             => $this->get_installed_plugins_status(),
            'plugin_updates'           => $this->get_plugin_updates(),
            'options_table_values'     => $this->get_options_table_values(),
            'inactive_editors'         => $this->get_inactive_editors(),
            'plugin_wp_security'       => $this->get_plugin_wp_security_settings(),
        ];

        return $return;
    }

    /**
     * Retrieves the status of all installed plugins.
     *
     * @return array
     */
    private function get_installed_plugins_status() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $all_plugins    = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $update_plugins = get_plugin_updates();

        $plugin_status = [];

        foreach ( $all_plugins as $plugin_file => $plugin_data ) {
            $plugin_slug = dirname( $plugin_file );
            $is_active   = in_array( $plugin_file, $active_plugins );
            $has_update  = isset( $update_plugins[ $plugin_file ] );

            $plugin_status[ $plugin_slug ] = [
                'name'             => $plugin_data['Name'],
                'installed'        => true,
                'active'           => $is_active,
                'version'          => $plugin_data['Version'],
                'update_available' => $has_update,
            ];
        }

        return $plugin_status;
    }

    /**
     * Retrieves option table values.
     *
     * @return array
     */
    private function get_options_table_values() {
        $option_table_keys = [
            'blog_public',
            'comments_notify',
            'users_can_register',
            'default_comment_status',
            'default_ping_status',
            'default_pingback_flag',
            'comment_moderation',
            'moderation_notify',
            'comment_registration',
            'current_theme',
            'home',
            'siteurl',
        ];

        $option_table_values = [];

        foreach ( $option_table_keys as $option_table_key ) {
            $option_table_values[ $option_table_key ] = get_option( $option_table_key );
        }

        return $option_table_values;
    }

    /**
     * Retrieves the MySQL version.
     *
     * @return string
     */
    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->db_version();
    }

    /**
     * Retrieves the PHP version.
     *
     * @return string
     */
    private function get_php_version() {
        return phpversion();
    }

    /**
     * Retrieves inactive editors and administrators.
     *
     * @return array
     */
    private function get_inactive_editors() {
        $now = new DateTime();

        $users = get_users( [
            'role__in' => [
                'Administrator',
                'Editor',
            ],
        ] );

        global $wpdb;

        $users = array_map(
            function( $user ) use ( $wpdb, $now ) {
                $last_login = $wpdb->get_row(
                    $wpdb->prepare(
                        "
                        SELECT * 
                        FROM {$wpdb->prefix}wsal_occurrences
                        WHERE user_id = %d
                            AND event_type = 'login'
                        ORDER BY created_on DESC
                        LIMIT 1
                        ",
                        $user->ID
                    )
                );

                if ( empty( $last_login ) ) {
                    return false;
                }

                $last_login_date = new DateTime( '@' . $last_login->created_on );

                return [
                    'email'      => $user->data->user_email,
                    'last_login' => $last_login_date->diff( $now )->days,
                ];
            },
            $users
        );

        $users = array_filter(
            $users,
            function( $user ) {
                return $user !== false;
            }
        );

        return array_values( $users );
    }

    /**
     * Retrieves "All In One WP Security" plugin settings.
     *
     * @return array
     */
    private function get_plugin_wp_security_settings() {
        return get_option( 'aio_wp_security_configs' );
    }

    /**
     * Retrieves WordPress core version with updates available.
     *
     * @return array
     */
    private function get_wordpress_core() {
        if ( ! function_exists( 'get_core_updates' ) ) {
            require_once ABSPATH . '/wp-admin/includes/update.php';
        }

        $core_updates_available = get_core_updates();
        $current_core_version   = get_bloginfo( 'version' );

        return [
            'current_core_version'    => $current_core_version,
            'core_updates_available'  => $core_updates_available,
        ];
    }

    /**
     * Retrieves plugins with updates available.
     *
     * @return array
     */
    private function get_plugin_updates() {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if ( ! function_exists( 'get_plugin_updates' ) ) {
            require_once ABSPATH . 'wp-admin/includes/update.php';
        }

        $all_plugins    = get_plugins();
        $update_plugins = get_plugin_updates();
        $plugin_updates = [];

        foreach ( $update_plugins as $plugin_file => $plugin_info ) {
            $plugin_slug = dirname( $plugin_file );

            $plugin_updates[ $plugin_slug ] = [
                'current_version' => $all_plugins[ $plugin_file ]['Version'],
                'new_version'     => $plugin_info->update->new_version,
            ];
        }

        return $plugin_updates;
    }

    /**
     * Find value in a path (unused function).
     *
     * @param string $q    Query string to look for.
     * @param string $path Path where to look for.
     * @return string
     */
    private function php_grep( $q, $path ) {
        $ret   = '';
        $fp    = opendir( $path );
        $slash = stristr( $_SERVER['SERVER_SOFTWARE'], 'win' ) ? "\\" : "/";

        while ( $f = readdir( $fp ) ) {
            if ( preg_match( "#^\.+$#", $f ) ) {
                continue; // Ignore symbolic links.
            }

            $file_full_path = $path . $slash . $f;

            if ( is_dir( $file_full_path ) ) {
                $ret .= $this->php_grep( $q, $file_full_path );
            } elseif ( ! stristr( $file_full_path, '.php' ) ) {
                continue; // Ignore all files except PHP files.
            } elseif ( stristr( file_get_contents( $file_full_path ), $q ) ) {
                $ret .= "$file_full_path\n";
            }
        }

        return $ret;
    }
}
