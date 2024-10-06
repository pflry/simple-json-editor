<?php
/**
 * Main SJE (Simple JSON Editor) class
 *
 * @package SJE
 */

class SJE {
  /**
   * Theme directory path
   *
   * @var string
   */
  private $theme_directory;

  /**
   * Excluded directories
   *
   * @var array
   */
  private $excluded_dirs;

  /**
   * Constructor
   */
  public function __construct() {
    $this->theme_directory = get_template_directory();
    $this->excluded_dirs = array('.', '..', '.git', '.vscode', 'node_modules');
  }

  /**
   * Initialize the plugin
   */
  public function run() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('wp_ajax_get_theme_directories', array($this, 'ajax_get_theme_directories'));
    add_action('wp_ajax_get_json_files', array($this, 'ajax_get_json_files'));
    add_action('wp_ajax_load_json_file', array($this, 'ajax_load_json_file'));
    add_action('wp_ajax_save_json_file', array($this, 'ajax_save_json_file'));
  }

  /**
   * Add admin menu
   */
  public function add_admin_menu() {
    add_management_page(
      __('SJE - Simple JSON Editor', 'sje'),
      __('Simple JSON Editor', 'sje'),
      'manage_options',
      'sje',
      array($this, 'render_admin_page'),
      99
    );
  }

  /**
   * Render admin page
   */
  public function render_admin_page() {
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'sje'));
    }
    require_once SJE_PLUGIN_DIR . 'admin/partials/admin-display.php';
  }

  /**
   * Enqueue admin scripts
   *
   * @param string $hook The current admin page.
   */
  public function enqueue_admin_scripts($hook) {
    if ('tools_page_sje' !== $hook) {
      return;
    }
    
    wp_enqueue_style('sje-admin-style', SJE_PLUGIN_URL . 'admin/css/admin.css', array(), SJE_VERSION);
    
    wp_enqueue_script('ace-editor', SJE_PLUGIN_URL . 'vendor/ace/ace.js', array('jquery'), '1.4.12', true);
    wp_enqueue_script('ace-editor-github-dark-theme', SJE_PLUGIN_URL . 'vendor/ace/theme-github_dark.js', array('ace-editor'), '1.4.12', true);
    
    wp_enqueue_script('sje-admin-script', SJE_PLUGIN_URL . 'admin/js/admin.js', array('jquery', 'ace-editor', 'ace-editor-github-dark-theme'), SJE_VERSION, true);
    
    wp_localize_script('sje-admin-script', 'wpJsonEditor', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('sje_nonce'),
      'svgIcons' => SJE_Icons::get_all_icons()
    ));
  }

  /**
   * Get theme directories containing JSON files
   *
   * @param string $directory The directory to scan.
   * @return array
   */
  public function get_theme_directories($directory = '') {
    $directories = array();
    $scan_dir = wp_normalize_path($this->theme_directory . '/' . $directory);
    
    if (strpos($scan_dir, wp_normalize_path($this->theme_directory)) !== 0) {
      return $directories;
    }
    
    if (is_dir($scan_dir)) {
      $scan_files = scandir($scan_dir);
      foreach ($scan_files as $file) {
        if (!in_array($file, $this->excluded_dirs) && !$this->starts_with_dot($file)) {
          $file_path = $scan_dir . '/' . $file;
          if (is_dir($file_path)) {
            $relative_path = $directory . ($directory ? '/' : '') . $file;
            if ($this->directory_contains_json($file_path)) {
              $directories[] = $relative_path;
            }
            $sub_directories = $this->get_theme_directories($relative_path);
            $directories = array_merge($directories, $sub_directories);
          }
        }
      }
    }
    
    return $directories;
  }

  /**
   * Check if directory contains JSON files
   *
   * @param string $directory The directory to check.
   * @return boolean
   */
  private function directory_contains_json($directory) {
    $scan_files = scandir($directory);
    foreach ($scan_files as $file) {
      if (!in_array($file, $this->excluded_dirs) && !$this->starts_with_dot($file)) {
        $file_path = $directory . '/' . $file;
        if (is_file($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == 'json') {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * AJAX handler for getting theme directories
   */
  public function ajax_get_theme_directories() {
    check_ajax_referer('sje_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Unauthorized access', 'sje')));
    }
    
    $directories = $this->get_theme_directories();
    wp_send_json_success($directories);
  }

  /**
   * Get JSON files in a directory
   *
   * @param string $directory The directory to scan.
   * @return array
   */
  public function get_json_files($directory) {
    $files = array();
    $scan_dir = wp_normalize_path($this->theme_directory . '/' . $directory);
    
    if (strpos($scan_dir, wp_normalize_path($this->theme_directory)) !== 0) {
      return $files;
    }
    
    if (is_dir($scan_dir)) {
      $scan_files = scandir($scan_dir);
      foreach ($scan_files as $file) {
        if (!in_array($file, $this->excluded_dirs) && !$this->starts_with_dot($file) && pathinfo($file, PATHINFO_EXTENSION) == 'json') {
          $files[] = $file;
        }
      }
    }
    
    return $files;
  }

  /**
   * AJAX handler for getting JSON files
   */
  public function ajax_get_json_files() {
    check_ajax_referer('sje_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Unauthorized access', 'sje')));
    }
    
    $directory = isset($_POST['directory']) ? sanitize_text_field(wp_unslash($_POST['directory'])) : '';
    $json_files = $this->get_json_files($directory);
    wp_send_json_success($json_files);
  }

  /**
   * AJAX handler for loading a JSON file
   */
  public function ajax_load_json_file() {
    check_ajax_referer('sje_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => __('Unauthorized access', 'sje')));
    }
    
    $directory = isset($_POST['directory']) ? sanitize_text_field(wp_unslash($_POST['directory'])) : '';
    $file = isset($_POST['file']) ? sanitize_text_field(wp_unslash($_POST['file'])) : '';
    
    $file_path = wp_normalize_path($this->theme_directory . '/' . $directory . '/' . $file);
    
    if (strpos($file_path, wp_normalize_path($this->theme_directory)) !== 0) {
      wp_send_json_error(array('message' => __('Invalid file path', 'sje')));
    }
    
    if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == 'json') {
      global $wp_filesystem;
      if (empty($wp_filesystem)) {
        require_once (ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
      }
      
      $content = $wp_filesystem->get_contents($file_path);
      if ($content === false) {
        wp_send_json_error(array('message' => __('Error reading file', 'sje')));
      } else {
        wp_send_json_success(array('content' => $content));
      }
    } else {
      wp_send_json_error(array('message' => __('File not found or not a JSON file', 'sje')));
    }
  }

  /**
   * AJAX handler for saving a JSON file
   */
  public function ajax_save_json_file() {
    check_ajax_referer('sje_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
      wp_send_json_error(array('message' => 'Unauthorized access'));
    }
    
    $directory = isset($_POST['directory']) ? sanitize_text_field(wp_unslash($_POST['directory'])) : '';
    $file = isset($_POST['file']) ? sanitize_file_name(wp_unslash($_POST['file'])) : '';
    $content = isset($_POST['content']) ? sanitize_text_field(wp_unslash($_POST['content'])) : '';
    
    // Validate JSON
    json_decode($content);
    if (json_last_error() !== JSON_ERROR_NONE) {
      wp_send_json_error(array('message' => 'Invalid JSON format'));
    }
    
    $file_path = wp_normalize_path($this->theme_directory . '/' . $directory . '/' . $file);
    
    // Check if the file is within the theme directory
    if (strpos($file_path, wp_normalize_path($this->theme_directory)) !== 0) {
      wp_send_json_error(array('message' => 'Invalid file path'));
    }
    
    if (pathinfo($file_path, PATHINFO_EXTENSION) !== 'json') {
      wp_send_json_error(array('message' => 'Invalid file type'));
    }
    
    // Use WP_Filesystem
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
      require_once (ABSPATH . '/wp-admin/includes/file.php');
      WP_Filesystem();
    }
    
    $content = wp_json_encode(json_decode($content), JSON_PRETTY_PRINT);
    
    if ($wp_filesystem->put_contents($file_path, $content)) {
      wp_send_json_success(array('message' => 'File saved successfully'));
    } else {
      wp_send_json_error(array('message' => 'Failed to save file'));
    }
  }

  /**
   * Check if a string starts with a dot
   *
   * @param string $string The string to check.
   * @return boolean
   */
  private function starts_with_dot($string) {
    return strpos($string, '.') === 0;
  }
}