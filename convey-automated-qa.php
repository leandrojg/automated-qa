<?php
/*
Plugin Name: Automated QA
Plugin URI: https://github.com/leandrojg/automated-qa
Description: WordPress plugin that will allow us to check certain tasks on our clients' websites to ensure they are functioning optimally.
Version: 1.1.1
Author: Convey Digital
Author URI: https://www.conveydigital.com/
License: GPL2
Text Domain: convey-automated-qa
Domain Path: /languages
*/

// Si este archivo se llama directamente, se termina la ejecución.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Define la versión del plugin.
define( 'CONVEY_AUTOMATED_QA_VERSION', '1.1.1' );

/**
 * Clase para manejar actualizaciones automáticas desde GitHub.
 */
class GitHub_Updater {
    private $slug;
    private $plugin_data;
    private $username;
    private $repo;
    private $plugin_file;
    private $github_response;

    public function __construct($plugin_file, $username, $repo) {
        add_filter("pre_set_site_transient_update_plugins", [$this, "set_update_plugins"]);
        add_filter("plugins_api", [$this, "set_plugins_api"], 10, 3);
        add_filter("upgrader_post_install", [$this, "post_install"], 10, 3);

        $this->plugin_file = $plugin_file;
        $this->username = $username;
        $this->repo = $repo;
    }

    private function init_plugin_data() {
        $this->slug = plugin_basename($this->plugin_file);
        $this->plugin_data = get_plugin_data($this->plugin_file);
    }

    public function set_update_plugins($transient) {
        if (empty($transient->checked)) return $transient;
        $this->init_plugin_data();
        $remote_version = $this->get_latest_version();

        if (version_compare($this->plugin_data['Version'], $remote_version, '<')) {
            $plugin = [
                'slug' => $this->slug,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->username}/{$this->repo}",
                'package' => $this->get_latest_zip_url(),
            ];
            $transient->response[$this->slug] = (object) $plugin;
        }

        return $transient;
    }

    public function set_plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->slug) return $result;

        $this->init_plugin_data();
        $result = (object) [
            'name' => $this->plugin_data['Name'],
            'slug' => $this->slug,
            'version' => $this->get_latest_version(),
            'author' => $this->plugin_data['Author'],
            'homepage' => $this->plugin_data['PluginURI'],
            'requires' => '5.0',
            'tested' => '6.0',
            'download_link' => $this->get_latest_zip_url(),
            'sections' => [
                'description' => $this->plugin_data['Description'],
                'changelog' => 'Revisa el changelog en GitHub para los detalles de esta versión.',
            ],
        ];

        return $result;
    }

    public function post_install($true, $hook_extra, $result) {
        global $wp_filesystem;
        $this->init_plugin_data();
        $plugin_folder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->slug);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;
        return $result;
    }

    private function get_latest_version() {
        $response = $this->github_response();
        return isset($response->tag_name) ? $response->tag_name : false;
    }

    private function get_latest_zip_url() {
        $response = $this->github_response();
        return isset($response->zipball_url) ? $response->zipball_url : false;
    }

    private function github_response() {
        if (!empty($this->github_response)) return $this->github_response;

        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        $response = wp_remote_get($url);

        if (is_wp_error($response)) return false;

        $this->github_response = json_decode(wp_remote_retrieve_body($response));
        return $this->github_response;
    }
}

// Inicializa el actualizador si es un administrador
if (is_admin()) {
    new GitHub_Updater(__FILE__, 'leandrojg', 'automated-qa');
}

/**
 * Código para la activación del plugin.
 */
function activate_convey_automated_qa() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa-activator.php';
	Convey_Automated_Qa_Activator::activate();
}

/**
 * Código para la desactivación del plugin.
 */
function deactivate_convey_automated_qa() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa-deactivator.php';
	Convey_Automated_Qa_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_convey_automated_qa' );
register_deactivation_hook( __FILE__, 'deactivate_convey_automated_qa' );

/**
 * Incluye la clase principal del plugin.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-convey-automated-qa.php';

/**
 * Ejecuta el plugin.
 */
function run_convey_automated_qa() {
	$plugin = new Convey_Automated_Qa();
	$plugin->run();
}

run_convey_automated_qa();
