<?php
final class ABS_Admin_Listens {

	const MENU_SLUG = 'abs-listens';

	public static function init(): void {
		add_action('admin_menu', [__CLASS__, 'menu']);
		add_action('admin_init', [__CLASS__, 'block_admin']);
		add_filter('login_redirect', [__CLASS__, 'login_redirect'], 10, 3);
		add_filter('show_admin_bar', [__CLASS__, 'hide_admin_bar']);
	}

	public static function hide_admin_bar(bool $show): bool {
		return current_user_can('manage_options') ? $show : false;
	}

	public static function block_admin(): void {
		if (wp_doing_ajax() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
			return;
		}

		if (current_user_can('manage_options')) {
			return;
		}

		wp_safe_redirect(self::dashboard_url());
		exit;
	}

	public static function login_redirect(string $redirect_to, string $requested_redirect_to, $user): string {
		if (!$user instanceof WP_User || 0 === (int) $user->ID) {
			return $redirect_to;
		}

		if ('' !== $requested_redirect_to || user_can($user, 'manage_options')) {
			return $redirect_to;
		}

		return self::dashboard_url();
	}

	private static function dashboard_url(): string {
		$page_id = (int) get_option(ABS_Dashboard::PAGE_OPTION);
		$url = $page_id > 0 ? get_permalink($page_id) : '';

		return '' !== $url ? $url : home_url('/');
	}

	public static function menu(): void {
		add_menu_page(
			'Kuulamised',
			'Kuulamised',
			'manage_options',
			self::MENU_SLUG,
			[__CLASS__, 'render'],
			'dashicons-headphones',
			26
		);
	}

	public static function render(): void {
		if (!current_user_can('manage_options')) {
			wp_die('Ligipääs keelatud.');
		}

		$user_id = isset($_GET['user']) ? absint(wp_unslash($_GET['user'])) : 0;

		if ($user_id > 0 && get_userdata($user_id)) {
			self::render_user($user_id);
			return;
		}

		self::render_overview();
	}

	private static function user_rows(): array {
		$users = get_users([
			'meta_key' => ABS_Listen::META_LOG,
			'number' => 500,
			'fields' => ['ID', 'display_name', 'user_email'],
		]);

		$rows = [];

		foreach ($users as $user) {
			$sessions = get_user_meta($user->ID, ABS_Listen::META_LOG, true);

			if (!is_array($sessions) || [] === $sessions) {
				continue;
			}

			$percents = 0;
			$stories = [];
			$last = null;

			foreach ($sessions as $session) {
				$percents += max(0, min(100, (int) ($session['percent'] ?? 0)));
				$stories[(int) ($session['post'] ?? 0)] = true;

				if (null === $last || (int) ($session['time'] ?? 0) > (int) $last['time']) {
					$last = $session;
				}
			}

			$rows[] = [
				'user' => $user,
				'stories' => count($stories),
				'total' => count($sessions),
				'avg_percent' => count($sessions) > 0 ? (int) round($percents / count($sessions)) : 0,
				'last' => $last,
			];
		}

		usort($rows, function ($a, $b) {
			return (int) ($b['last']['time'] ?? 0) <=> (int) ($a['last']['time'] ?? 0);
		});

		return $rows;
	}

	private static function render_overview(): void {
		$rows = self::user_rows();

		echo '<div class="wrap">';
		echo '<h1>Kuulamised</h1>';

		if ([] === $rows) {
			echo '<p>Ühtegi kuulamist pole veel salvestatud.</p>';
		} else {
			echo '<table class="widefat striped">';
			echo '<thead><tr>';
			echo '<th>Kasutaja</th><th>Kuulatud lugu</th><th>Kuulamisi kokku</th><th>Keskmine kuulamise pikkus</th><th>Viimane kuulamine</th>';
			echo '</tr></thead><tbody>';

			foreach ($rows as $row) {
				$user = $row['user'];
				$detail = admin_url('admin.php?page=' . self::MENU_SLUG . '&user=' . (int) $user->ID);

				echo '<tr>';
				echo '<td><a href="' . esc_url($detail) . '"><strong>' . esc_html($user->display_name) . '</strong></a><br /><span class="description">' . esc_html($user->user_email) . '</span></td>';
				echo '<td>' . esc_html((string) $row['stories']) . '</td>';
				echo '<td>' . esc_html((string) $row['total']) . '</td>';
				echo '<td>' . esc_html((string) $row['avg_percent']) . '%</td>';

				$post_id = (int) ($row['last']['post'] ?? 0);
				$title = $post_id > 0 ? get_the_title($post_id) : '';

				echo '<td>' . esc_html(wp_date('j.m.Y H:i', (int) ($row['last']['time'] ?? 0)));
				if ('' !== $title) {
					echo '<br /><span class="description">' . esc_html($title) . '</span>';
				}
				echo '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		self::render_quiz_table();

		echo '</div>';
	}

	public static function interest_label(string $interest): string {
		if ('yes' === $interest) {
			return 'Jah';
		}

		if ('no' === $interest) {
			return 'Ei';
		}

		return 'Pole veel vastanud';
	}

	private static function quiz_rows(): array {
		$users = get_users([
			'meta_key' => ABS_Dashboard::META_QUIZ,
			'number' => 500,
			'fields' => ['ID', 'display_name', 'user_email'],
		]);

		$rows = [];

		foreach ($users as $user) {
			$interest = (string) get_user_meta($user->ID, ABS_Dashboard::META_QUIZ, true);

			if ('' === $interest) {
				continue;
			}

			$rows[] = [
				'user' => $user,
				'interest' => $interest,
				'price' => (string) get_user_meta($user->ID, ABS_Dashboard::META_PRICE, true),
				'time' => (int) get_user_meta($user->ID, ABS_Dashboard::META_QUIZ_TIME, true),
			];
		}

		usort($rows, function ($a, $b) {
			return $b['time'] <=> $a['time'];
		});

		return $rows;
	}

	private static function render_quiz_table(): void {
		$rows = self::quiz_rows();

		echo '<h2>Küsitlus — isiklikud jutud</h2>';

		if ([] === $rows) {
			echo '<p>Keegi pole veel küsitlusele vastanud.</p>';

			return;
		}

		echo '<table class="widefat striped">';
		echo '<thead><tr>';
		echo '<th>Kasutaja</th><th>Huvitatud</th><th>Soovitud hind</th><th>Viimati muudetud</th>';
		echo '</tr></thead><tbody>';

		foreach ($rows as $row) {
			$user = $row['user'];
			$detail = admin_url('admin.php?page=' . self::MENU_SLUG . '&user=' . (int) $user->ID);

			echo '<tr>';
			echo '<td><a href="' . esc_url($detail) . '"><strong>' . esc_html($user->display_name) . '</strong></a><br /><span class="description">' . esc_html($user->user_email) . '</span></td>';
			echo '<td>' . esc_html(self::interest_label($row['interest'])) . '</td>';
			echo '<td>' . ('' !== $row['price'] ? esc_html($row['price']) : '&mdash;') . '</td>';
			echo '<td>' . ($row['time'] > 0 ? esc_html(wp_date('j.m.Y H:i', $row['time'])) : '&mdash;') . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private static function render_user(int $user_id): void {
		$user = get_userdata($user_id);
		$stats = ABS_Dashboard::stats($user_id);
		$back = admin_url('admin.php?page=' . self::MENU_SLUG);

		echo '<div class="wrap">';
		echo '<a href="' . esc_url($back) . '">&larr; Tagasi ülevaate juurde</a>';
		echo '<h1>' . esc_html($user->display_name) . ' — kuulamised</h1>';
		echo '<p><span class="description">' . esc_html($user->user_email) . '</span></p>';

		echo '<table class="widefat striped" style="max-width:640px">';
		echo '<tbody>';
		echo '<tr><td><strong>Kuulatud lugu</strong></td><td>' . esc_html((string) $stats['stories']) . '</td></tr>';
		echo '<tr><td><strong>Kuulamisi kokku</strong></td><td>' . esc_html((string) count(get_user_meta($user_id, ABS_Listen::META_LOG, true) ?: [])) . '</td></tr>';
		echo '<tr><td><strong>Keskmine kuulamise pikkus</strong></td><td>' . esc_html((string) $stats['avg_percent']) . '%</td></tr>';
		echo '<tr><td><strong>Kuulamisi päevas (aktiivsed päevad)</strong></td><td>' . esc_html((string) $stats['per_day']) . '</td></tr>';
		echo '</tbody></table>';

		echo '<h2>Kuulamised kuude lõikes</h2>';

		if ([] === $stats['months']) {
			echo '<p>Andmed puuduvad.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:420px"><tbody>';

			foreach ($stats['months'] as $month) {
				echo '<tr><td>' . esc_html($month['label']) . '</td><td>' . esc_html((string) $month['count']) . '</td></tr>';
			}

			echo '</tbody></table>';
		}

		echo '<h2>Viimased 10 lugu</h2>';

		if ([] === $stats['recent']) {
			echo '<p>Andmed puuduvad.</p>';
		} else {
			echo '<table class="widefat striped" style="max-width:720px">';
			echo '<thead><tr><th>Lugu</th><th>Kuulatud</th><th>Kokku</th></tr></thead><tbody>';

			foreach ($stats['recent'] as $item) {
				echo '<tr>';
				echo '<td><a href="' . esc_url($item['link']) . '" target="_blank">' . esc_html($item['title']) . '</a></td>';
				echo '<td>' . esc_html((string) $item['percent']) . '%</td>';
				echo '<td>' . esc_html((string) $item['plays']) . '×</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}

		$interest = (string) get_user_meta($user_id, ABS_Dashboard::META_QUIZ, true);
		$price    = (string) get_user_meta($user_id, ABS_Dashboard::META_PRICE, true);
		$quiz_time = (int) get_user_meta($user_id, ABS_Dashboard::META_QUIZ_TIME, true);

		echo '<h2>Küsitlus — isiklikud jutud</h2>';
		echo '<table class="widefat striped" style="max-width:420px"><tbody>';
		echo '<tr><td><strong>Huvitatud</strong></td><td>' . esc_html(self::interest_label($interest)) . '</td></tr>';
		echo '<tr><td><strong>Soovitud hind</strong></td><td>' . ('' !== $price ? esc_html($price) : '&mdash;') . '</td></tr>';
		echo '<tr><td><strong>Viimati muudetud</strong></td><td>' . ($quiz_time > 0 ? esc_html(wp_date('j.m.Y H:i', $quiz_time)) : '&mdash;') . '</td></tr>';
		echo '</tbody></table>';

		echo '</div>';
	}
}
