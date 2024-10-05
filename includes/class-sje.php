<?php

class SJE {
  
  private $theme_directory;
  private $excluded_dirs;
  
  public function __construct() {
    $this->theme_directory = get_template_directory();
    $this->excluded_dirs = array('.', '..', '.git', '.vscode', 'node_modules');
  }

  public function run() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    add_action('wp_ajax_get_theme_directories', array($this, 'ajax_get_theme_directories'));
    add_action('wp_ajax_get_json_files', array($this, 'ajax_get_json_files'));
    add_action('wp_ajax_load_json_file', array($this, 'ajax_load_json_file'));
    add_action('wp_ajax_save_json_file', array($this, 'ajax_save_json_file'));
  }

  public function add_admin_menu() {
    add_management_page(
      'SJE - Simple JSON Editor',
      'Simple JSON Editor',
      'manage_options',
      'sje',
      array($this, 'render_admin_page'), // Fonction de rappel pour afficher la page
      99 // Position dans le menu (optionnel)
    );
  }

  public function render_admin_page() {
    require_once SJE_PLUGIN_DIR . 'admin/partials/admin-display.php';
  }

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

 	public function get_theme_directories($directory = '') {
		$directories = array();
		$scan_dir = $this->theme_directory . '/' . $directory;
		
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

	public function ajax_get_theme_directories() {
		check_ajax_referer('sje_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized access');
		}
		
		$directories = $this->get_theme_directories();
		wp_send_json_success($directories);
	}

	public function get_json_files($directory) {
		$files = array();
		$scan_dir = $this->theme_directory . '/' . $directory;
		
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

	public function ajax_get_json_files() {
		check_ajax_referer('sje_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized access');
		}
		
		$directory = isset($_POST['directory']) ? sanitize_text_field($_POST['directory']) : '';
		$json_files = $this->get_json_files($directory);
		wp_send_json_success($json_files);
	}

	public function ajax_load_json_file() {
		check_ajax_referer('sje_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized access');
		}
		
		$directory = isset($_POST['directory']) ? sanitize_text_field($_POST['directory']) : '';
		$file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
		
		$file_path = $this->theme_directory . '/' . $directory . '/' . $file;
		
		if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) == 'json') {
			$content = file_get_contents($file_path);
			wp_send_json_success(array('content' => $content));
		} else {
			wp_send_json_error(array('message' => 'File not found or not a JSON file.'));
		}
	}

	public function ajax_save_json_file() {
		check_ajax_referer('sje_nonce', 'nonce');
		
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized access');
		}
		
		$directory = isset($_POST['directory']) ? sanitize_text_field($_POST['directory']) : '';
		$file = isset($_POST['file']) ? sanitize_text_field($_POST['file']) : '';
		$content = isset($_POST['content']) ? stripslashes($_POST['content']) : '';
		
		$file_path = $this->theme_directory . '/' . $directory . '/' . $file;
		
		if (pathinfo($file_path, PATHINFO_EXTENSION) == 'json') {
			if (file_put_contents($file_path, $content) !== false) {
				wp_send_json_success(array('message' => 'File saved successfully.'));
			} else {
				wp_send_json_error(array('message' => 'Failed to save file.'));
			}
		} else {
			wp_send_json_error(array('message' => 'Invalid file type.'));
		}
	}

	private function starts_with_dot($string) {
		return strpos($string, '.') === 0;
	}

}