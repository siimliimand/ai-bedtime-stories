<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Listen {

	const META_USER = '_abs_listens';
	const META_POST = '_abs_listen_count';
	const META_LOG  = '_abs_listen_log';

	public static function init(): void {
		add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
		add_action('wp_ajax_abs_track_listen', [self::class, 'ajax_track']);
		add_filter('manage_post_posts_columns', [self::class, 'columns']);
		add_action('manage_post_posts_custom_column', [self::class, 'column'], 10, 2);
	}

	public static function register_assets(): void {
		if (!is_user_logged_in() || !is_singular('post')) {
			return;
		}

		$post_id = (int) get_queried_object_id();

		if (!self::post_has_audio($post_id)) {
			return;
		}

		wp_enqueue_script('abs-listen', ABS_PLUGIN_URL . 'assets/listen.js', [], ABS_VERSION, true);
		wp_localize_script('abs-listen', 'absListen', self::listen_config());
	}

	public static function listen_config(): array {
		return [
			'ajax'  => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('abs_listen'),
			'post'  => is_singular('post') ? (int) get_queried_object_id() : 0,
		];
	}

	public static function post_has_audio(int $post_id): bool {
		$story_id = (int) get_post_meta($post_id, '_abs_source_story_id', true);

		if ($story_id < 1) {
			return false;
		}

		$full = get_post_meta($story_id, '_abs_audio_full', true);
		$url  = is_array($full) ? (string) ($full['url'] ?? '') : '';

		return '' !== $url;
	}

	public static function ajax_track(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(['message' => 'not_logged_in'], 401);
		}

		check_ajax_referer('abs_listen', 'nonce');

		$post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
		$event   = isset($_POST['event']) ? sanitize_key(wp_unslash($_POST['event'])) : '';
		$percent = isset($_POST['percent']) ? (float) wp_unslash($_POST['percent']) : 0.0;

		if ($post_id < 1 || !in_array($event, ['start', 'progress', 'end'], true) || 'post' !== get_post_type($post_id) || !self::post_has_audio($post_id)) {
			wp_send_json_error(['message' => 'invalid'], 400);
		}

		$percent = (int) round(max(0.0, min(100.0, $percent)));
		$user_id = get_current_user_id();
		$listens = get_user_meta($user_id, self::META_USER, true);
		$listens = is_array($listens) ? $listens : [];
		$key     = (string) $post_id;
		$entry   = isset($listens[$key]) && is_array($listens[$key]) ? wp_parse_args($listens[$key], ['plays' => 0, 'percent' => 0, 'last' => 0]) : ['plays' => 0, 'percent' => 0, 'last' => 0];
		$log     = get_user_meta($user_id, self::META_LOG, true);
		$log     = is_array($log) ? array_values($log) : [];

		if ('start' === $event) {
			$entry['plays'] = (int) $entry['plays'] + 1;
			update_post_meta($post_id, self::META_POST, (int) get_post_meta($post_id, self::META_POST, true) + 1);

			$log[] = ['post' => $post_id, 'time' => time(), 'percent' => 0];

			if (count($log) > 1000) {
				$log = array_slice($log, -1000);
			}
		} else {
			for ($i = count($log) - 1; $i >= 0; $i--) {
				if ((int) ($log[$i]['post'] ?? 0) === $post_id) {
					$log[$i]['percent'] = max((int) ($log[$i]['percent'] ?? 0), $percent);
					break;
				}
			}
		}

		$entry['percent'] = max((int) $entry['percent'], $percent);
		$entry['last']    = time();
		$listens[$key]    = $entry;

		update_user_meta($user_id, self::META_USER, $listens);
		update_user_meta($user_id, self::META_LOG, $log);

		wp_send_json_success([
			'plays'   => (int) $entry['plays'],
			'percent' => (int) $entry['percent'],
		]);
	}

	public static function columns(array $columns): array {
		$columns['abs_listens'] = 'Kuulamisi';

		return $columns;
	}

	public static function story_listen_stats(): array {
		static $cache = null;

		if (is_array($cache)) {
			return $cache;
		}

		global $wpdb;

		$link_rows = $wpdb->get_results(
			"SELECT post_id, meta_value AS story_id FROM {$wpdb->postmeta} WHERE meta_key = '_abs_source_story_id'",
			ARRAY_A
		);
		$count_rows = $wpdb->get_results(
			"SELECT post_id, meta_value AS cnt FROM {$wpdb->postmeta} WHERE meta_key = '_abs_listen_count'",
			ARRAY_A
		);

		$link_map = [];
		$count_map = [];

		foreach ((array) $link_rows as $row) {
			$pid = (int) $row['post_id'];
			$sid = (int) $row['story_id'];

			if ($pid > 0 && $sid > 0) {
				$link_map[$pid] = $sid;
			}
		}

		foreach ((array) $count_rows as $row) {
			$count_map[(int) $row['post_id']] = (int) $row['cnt'];
		}

		$sessions = [];

		$user_ids = get_users([
			'fields'   => 'ID',
			'meta_key' => self::META_LOG,
			'number'   => 1000,
		]);

		foreach ($user_ids as $user_id) {
			$log = get_user_meta((int) $user_id, self::META_LOG, true);
			$log = is_array($log) ? $log : [];

			foreach ($log as $entry) {
				if (!is_array($entry)) {
					continue;
				}

				$pid = (int) ($entry['post'] ?? 0);

				if ($pid < 1) {
					continue;
				}

				if (!isset($sessions[$pid])) {
					$sessions[$pid] = ['sum' => 0, 'n' => 0];
				}

				$sessions[$pid]['sum'] += max(0, (int) ($entry['percent'] ?? 0));
				$sessions[$pid]['n']++;
			}
		}

		$cache = [];

		foreach ($link_map as $pid => $sid) {
			if (!isset($cache[$sid])) {
				$cache[$sid] = ['count' => 0, 'sum' => 0, 'n' => 0];
			}

			$cache[$sid]['count'] += $count_map[$pid] ?? 0;

			if (isset($sessions[$pid])) {
				$cache[$sid]['sum'] += $sessions[$pid]['sum'];
				$cache[$sid]['n'] += $sessions[$pid]['n'];
			}
		}

		return $cache;
	}

	public static function column(string $column, int $post_id): void {
		if ('abs_listens' !== $column) {
			return;
		}

		if (!self::post_has_audio($post_id)) {
			echo '-';

			return;
		}

		echo esc_html((string) (int) get_post_meta($post_id, self::META_POST, true));
	}
}
