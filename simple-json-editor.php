<?php
/**
 * Plugin Name: SJE - Simple JSON Editor
 * Plugin URI: https://github.com/pflry/simple-json-editor
 * Description: An elegant and easy-to-use JSON editor for your WordPress theme files
 * Version: 1.0.0
 * Author: Paul Fleury
 * Author URI: https://paulfleury.fr
 * License: GPLv3
 * Text Domain: sje-simple-json-editor
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
  exit;
}

// Définir les constantes du plugin
define('SJE_VERSION', '1.0.0');
define('SJE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SJE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Charger la classe principale
require_once SJE_PLUGIN_DIR . 'includes/class-sje.php';

// Charger les icônes svg
require_once SJE_PLUGIN_DIR . 'includes/class-sje-icons.php';

// Initialiser le plugin
function run_sje() {
  $plugin = new SJE();
  $plugin->run();
}
add_action('plugins_loaded', 'run_sje');