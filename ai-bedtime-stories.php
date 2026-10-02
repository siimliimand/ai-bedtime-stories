<?php
/**
 * Plugin Name:       AI Bedtime Stories
 * Description:       Generates bedtime story ideas and full Brothers Grimm-inspired stories using the z.ai GLM API (Coding Plan endpoint).
 * Version:           1.1.49
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Text Domain:       ai-bedtime-stories
 */

if (!defined('ABSPATH')) {
	exit;
}

define('ABS_VERSION', '1.1.49');
define('ABS_PLUGIN_FILE', __FILE__);
define('ABS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABS_PLUGIN_URL', plugin_dir_url(__FILE__));

load_plugin_textdomain('ai-bedtime-stories', false, basename(ABS_PLUGIN_DIR) . '/languages');

require_once ABS_PLUGIN_DIR . 'includes/class-abs-api.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-settings.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-cpt.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-ideas.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-publish.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-frontend.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-listen.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-dashboard.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-admin-listens.php';
require_once ABS_PLUGIN_DIR . 'includes/class-abs-library.php';

add_action('plugins_loaded', static function (): void {
	if (class_exists(ABS_Settings::class) && method_exists(ABS_Settings::class, 'init')) {
		ABS_Settings::init();
	}

	if (class_exists(ABS_CPT::class) && method_exists(ABS_CPT::class, 'init')) {
		ABS_CPT::init();
	}

	if (class_exists(ABS_Ideas::class) && method_exists(ABS_Ideas::class, 'init')) {
		ABS_Ideas::init();
	}

	if (class_exists(ABS_Publish::class) && method_exists(ABS_Publish::class, 'init')) {
		ABS_Publish::init();
	}

	if (class_exists(ABS_Frontend::class) && method_exists(ABS_Frontend::class, 'init')) {
		ABS_Frontend::init();
	}

	if (class_exists(ABS_Listen::class) && method_exists(ABS_Listen::class, 'init')) {
		ABS_Listen::init();
	}

	if (class_exists(ABS_Dashboard::class) && method_exists(ABS_Dashboard::class, 'init')) {
		ABS_Dashboard::init();
	}

	if (class_exists(ABS_Admin_Listens::class) && method_exists(ABS_Admin_Listens::class, 'init')) {
		ABS_Admin_Listens::init();
	}

	if (class_exists(ABS_Library::class) && method_exists(ABS_Library::class, 'init')) {
		ABS_Library::init();
	}
});

register_activation_hook(__FILE__, static function (): void {
	if (class_exists(ABS_Settings::class) && method_exists(ABS_Settings::class, 'seed_defaults')) {
		ABS_Settings::seed_defaults();
	}
});
