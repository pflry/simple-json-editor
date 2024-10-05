<?php
/**
 * Plugin Name: SJE - Simple JSON Editor
 * Plugin URI: https://paulfleury.fr/sje-plugin
 * Description: Un éditeur JSON simple et élégant pour votre thème actif.
 * Version: 1.0
 * Author: Paul Fleury
 * Author URI: https://paulfleury.fr
 * License: GPL2
 * Text Domain: sje
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