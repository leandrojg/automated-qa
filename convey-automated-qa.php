<?php
/**
 * Plugin Name: Automated QA
 * Plugin URI: https://github.com/leandrojg/automated-qa
 * Description: A WordPress plugin that performs QA tasks on client sites to ensure optimal functionality.
 * Version: 1.1.1
 * Author: Convey Digital
 * Author URI: https://www.conveydigital.com/
 * Text Domain: convey-automated-qa
 */

// Include the GitHub Updater class
include_once 'GitHub_Updater.php';

/**
 * Initialize the GitHub Updater with specified repository details.
 */
function initialize_github_updater() {
    $updater = new GitHub_Updater('automated-qa', 'leandrojg', 'automated-qa');
    
    // Perform an update check on plugin initialization
    $updater->check_for_updates();
}
add_action('init', 'initialize_github_updater');

/**
 * GitHub_Updater class
 */
class GitHub_Updater {
    private $slug;
    private $username;
    private $repo;
    private $plugin_data;
    private $plugin_file;
    private $github_response;

    /**
     * Constructor sets up plugin details
     */
    public function __construct($plugin_slug, $github_username, $github_repo) {
        $this->slug = $plugin_slug;
        $this->username = $github_username;
        $this->repo = $github_repo;
        $this->plugin_file = plugin_basename(__FILE__);
        $this->plugin_data = get_plugin_data(__FILE__);
        
        // Logging for initialization
        error_log('GitHub_Updater initialized.');

        // Add filters for the update and transient data
        add_filter("pre_set_site_transient_update_plugins", array($this, "set_github_update_transient"));
        add_filter("plugins_api", array($this, "set_github_plugin_info"), 10, 3);
    }

    /**
     * Check GitHub for the latest plugin version and prepare update data
     */
    public function check_for_updates() {
        $this->github_response = $this->get_github_release_info();

        if ($this->github_response) {
            $local_version = $this->plugin_data['Version'];
            $remote_version = $this->github_response->tag_name;

            error_log("Local version: $local_version");
            error_log("Remote version: $remote_version");

            if (version_compare($local_version, $remote_version, '<')) {
                error_log("Update detected.");
            }
        } else {
            error_log("GitHub response not retrieved.");
        }
    }

    /**
     * Set the update transient with GitHub data if a new version is found
     */
    public function set_github_update_transient($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $this->check_for_updates();

        if ($this->github_response && version_compare($this->plugin_data['Version'], $this->github_response->tag_name, '<')) {
            $plugin_info = array(
                'slug' => $this->slug,
                'new_version' => $this->github_response->tag_name,
                'url' => $this->github_response->html_url,
                'package' => $this->github_response->zipball_url
            );

            error_log("Transient response: " . print_r($plugin_info, true));

            $transient->response[$this->plugin_file] = (object) $plugin_info;
        }
        
        return $transient;
    }

    /**
     * Provide GitHub plugin information for WordPress plugin update details
     */
    public function set_github_plugin_info($false, $action, $response) {
        if ($response->slug !== $this->slug) {
            return $false;
        }

        $this->check_for_updates();

        $response->last_updated = $this->github_response->published_at;
        $response->slug = $this->slug;
        $response->plugin_name  = $this->plugin_data['Name'];
        $response->version = $this->github_response->tag_name;
        $response->author = $this->plugin_data['AuthorName'];
        $response->homepage = $this->plugin_data['PluginURI'];
        $response->download_link = $this->github_response->zipball_url;

        return $response;
    }

    /**
     * Get release information from GitHub
     */
    private function get_github_release_info() {
        $url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases/latest";
        $response = wp_remote_get($url, array('headers' => array('User-Agent' => 'WordPress Plugin Updater')));
        
        if (is_wp_error($response)) {
            error_log("GitHub API error: " . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body);
    }
}