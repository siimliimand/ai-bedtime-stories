<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Dashboard {

	const SHORTCODE  = 'abs_listen_dashboard';
	const PAGE_OPTION = 'abs_dashboard_page_id';
	const NONCE      = 'abs_quiz';
	const META_QUIZ  = '_abs_quiz_interest';
	const META_PRICE = '_abs_quiz_price';
	const META_QUIZ_TIME = '_abs_quiz_time';

	public static function init(): void {
		add_shortcode(self::SHORTCODE, [self::class, 'render']);
		add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
		add_action('wp_ajax_abs_quiz', [self::class, 'ajax_quiz']);
	}

	public static function register_assets(): void {
		wp_register_style('abs-dashboard', ABS_PLUGIN_URL . 'assets/dashboard.css', [], ABS_VERSION);
		wp_register_script('abs-dashboard', ABS_PLUGIN_URL . 'assets/dashboard.js', [], ABS_VERSION, true);
		wp_localize_script('abs-dashboard', 'absQuiz', [
			'ajax'  => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(self::NONCE),
		]);
	}

	public static function ensure_page(): void {
		$page_id = (int) get_option(self::PAGE_OPTION);

		if ($page_id > 0 && get_post($page_id) instanceof WP_Post && 'page' === get_post_type($page_id)) {
			return;
		}

		$page_id = wp_insert_post([
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Minu kuulamised',
			'post_name'    => 'minu-kuulamised',
			'post_content' => '[' . self::SHORTCODE . ']',
		], true);

		if (is_wp_error($page_id) || $page_id < 1) {
			return;
		}

		update_option(self::PAGE_OPTION, (int) $page_id);
	}

	public static function render(): string {
		wp_enqueue_style('abs-dashboard');

		if (!is_user_logged_in()) {
			return ABS_Frontend::login_banner('Siin näed oma kuulamise ülevaadet — logi sisse ja vaata, mitu lugu oled kuulanud.', (string) get_permalink());
		}

		wp_enqueue_script('abs-dashboard');

		$data = self::stats((int) get_current_user_id());
		$quiz = self::render_quiz((int) get_current_user_id());

		ob_start();
		?>
		<div class="abs-dash">
			<div class="abs-dash-top">
				<a class="abs-dash-logout" href="<?php echo esc_url(wp_logout_url((string) get_permalink())); ?>">
					<svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
					Logi välja
				</a>
			</div>
			<div class="abs-dash-stats">
				<div class="abs-dash-stat">
					<span class="abs-dash-value"><?php echo esc_html((string) $data['stories']); ?></span>
					<span class="abs-dash-label"><?php echo esc_html('Kuulatud lugu'); ?></span>
				</div>
				<div class="abs-dash-stat">
					<span class="abs-dash-value"><?php echo esc_html($data['avg_percent'] . '%'); ?></span>
					<span class="abs-dash-label"><?php echo esc_html('Keskmine kuulamise pikkus'); ?></span>
				</div>
				<div class="abs-dash-stat">
					<span class="abs-dash-value"><?php echo esc_html((string) $data['per_day']); ?></span>
					<span class="abs-dash-label"><?php echo esc_html('Kuulamisi päevas'); ?></span>
				</div>
			</div>

			<h3 class="abs-dash-heading"><?php echo esc_html('Kuulamised kuude lõikes'); ?></h3>
			<?php if ([] === $data['months']) : ?>
				<p class="abs-dash-empty"><?php echo esc_html('Siin veel kuulamisi ei ole.'); ?></p>
			<?php else : ?>
				<ul class="abs-dash-months">
					<?php foreach ($data['months'] as $month) : ?>
						<li><span><?php echo esc_html($month['label']); ?></span><strong><?php echo esc_html((string) $month['count']); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<h3 class="abs-dash-heading"><?php echo esc_html('Viimased 10 lugu'); ?></h3>
			<?php if ([] === $data['recent']) : ?>
				<p class="abs-dash-empty"><?php echo esc_html('Siin veel kuulamisi ei ole.'); ?></p>
			<?php else : ?>
				<div class="abs-dash-recent">
					<?php foreach ($data['recent'] as $row) : ?>
						<div class="abs-dash-row">
							<a class="abs-dash-title" href="<?php echo esc_url($row['link']); ?>"><?php echo esc_html($row['title']); ?></a>
							<span class="abs-dash-pct"><?php echo esc_html($row['percent'] . '%'); ?></span>
							<span class="abs-dash-count"><?php echo esc_html((string) $row['plays']); ?>×</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php echo $quiz; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_quiz(int $user_id): string {
		$state    = self::quiz_state($user_id);
		$interest = $state['interest'];
		$price    = $state['price'];
		$step     = 'intro';

		if ('no' === $interest || ('yes' === $interest && '' !== $price)) {
			$step = 'done';
		} elseif ('yes' === $interest) {
			$step = 'price';
		}

		$answer = 'no' === $interest ? 'Ei' : ('' !== $price ? 'Jah — ' . $price : 'Jah');

		ob_start();
		?>
		<section class="abs-quiz" data-interest="<?php echo esc_attr($interest); ?>" data-price="<?php echo esc_attr($price); ?>">
			<div class="abs-quiz-head">
				<span class="abs-quiz-icon" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
				</span>
				<h2 class="abs-quiz-title"><?php echo esc_html('Isiklikud helijutud'); ?></h2>
				<span class="abs-quiz-badge"><?php echo esc_html('Küsitlus'); ?></span>
			</div>
			<p class="abs-quiz-sub"><?php echo esc_html('Ainult sinule kirjutatud ja salvestatud lood, mida keegi teine ei näe ega kuule.'); ?></p>
			<div class="abs-quiz-step abs-quiz-step-intro"<?php echo 'intro' === $step ? '' : ' hidden'; ?>>
				<p><?php echo esc_html('Kas oled huvitatud isiklikest helijuttudest? Näiteks jutt sinu lapsest — kirjeldaksid oma last ja millist juttu soovid.'); ?></p>
				<div class="abs-quiz-actions">
					<button type="button" class="abs-quiz-btn abs-quiz-yes"><?php echo esc_html('Jah'); ?></button>
					<button type="button" class="abs-quiz-btn abs-quiz-no"><?php echo esc_html('Ei'); ?></button>
				</div>
			</div>
			<div class="abs-quiz-step abs-quiz-step-price"<?php echo 'price' === $step ? '' : ' hidden'; ?>>
				<p><?php echo esc_html('Kui palju oleksid nõus sellise isikliku jutu eest maksma?'); ?></p>
				<div class="abs-quiz-price-row">
					<input type="number" class="abs-quiz-price-input" inputmode="decimal" min="0" step="any" value="<?php echo esc_attr($price); ?>">
					<button type="button" class="abs-quiz-btn abs-quiz-send"><?php echo esc_html('Saada'); ?></button>
					<button type="button" class="abs-quiz-btn abs-quiz-back"><?php echo esc_html('Tagasi'); ?></button>
				</div>
			</div>
			<div class="abs-quiz-step abs-quiz-step-done"<?php echo 'done' === $step ? '' : ' hidden'; ?>>
				<p class="abs-quiz-summary">
					<svg class="abs-quiz-check" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
					<?php echo esc_html('Sinu vastus:'); ?> <strong class="abs-quiz-answer"><?php echo esc_html('done' === $step ? $answer : ''); ?></strong>
				</p>
				<button type="button" class="abs-quiz-btn abs-quiz-change"><?php echo esc_html('Muuda vastust'); ?></button>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function quiz_state(int $user_id): array {
		return [
			'interest' => (string) get_user_meta($user_id, self::META_QUIZ, true),
			'price'    => (string) get_user_meta($user_id, self::META_PRICE, true),
		];
	}

	public static function save_quiz(int $user_id, string $field, string $value) {
		if ($user_id < 1) {
			return new WP_Error('abs_quiz_user', 'invalid_user');
		}

		if ('interest' === $field) {
			if (!in_array($value, ['yes', 'no'], true)) {
				return new WP_Error('abs_quiz_value', 'invalid_value');
			}

			update_user_meta($user_id, self::META_QUIZ, $value);
			update_user_meta($user_id, self::META_QUIZ_TIME, time());

			return self::quiz_state($user_id);
		}

		if ('price' === $field) {
			$value = trim($value);

			if ('' === $value || mb_strlen($value) > 100) {
				return new WP_Error('abs_quiz_value', 'invalid_value');
			}

			update_user_meta($user_id, self::META_QUIZ, 'yes');
			update_user_meta($user_id, self::META_PRICE, $value);
			update_user_meta($user_id, self::META_QUIZ_TIME, time());

			return self::quiz_state($user_id);
		}

		return new WP_Error('abs_quiz_field', 'invalid_field');
	}

	public static function ajax_quiz(): void {
		if (!is_user_logged_in()) {
			wp_send_json_error(['message' => 'not_logged_in'], 401);
		}

		check_ajax_referer(self::NONCE, 'nonce');

		$field  = isset($_POST['field']) ? sanitize_key(wp_unslash($_POST['field'])) : '';
		$value  = isset($_POST['value']) ? sanitize_text_field(wp_unslash($_POST['value'])) : '';
		$saved  = self::save_quiz((int) get_current_user_id(), $field, $value);

		if (is_wp_error($saved)) {
			wp_send_json_error(['message' => $saved->get_error_message()], 400);
		}

		wp_send_json_success($saved);
	}

	public static function stats(int $user_id): array {
		$listens   = get_user_meta($user_id, ABS_Listen::META_USER, true);
		$listens   = is_array($listens) ? $listens : [];
		$log       = get_user_meta($user_id, ABS_Listen::META_LOG, true);
		$log       = is_array($log) ? array_values($log) : [];
		$sessions  = [];

		foreach ($log as $entry) {
			if (!is_array($entry) || (int) ($entry['post'] ?? 0) < 1) {
				continue;
			}

			$sessions[] = [
				'post'    => (int) $entry['post'],
				'time'    => (int) ($entry['time'] ?? 0),
				'percent' => (int) ($entry['percent'] ?? 0),
			];
		}

		$stories = [];

		foreach ($listens as $post_id => $entry) {
			if (!is_array($entry) || (int) ($entry['plays'] ?? 0) < 1) {
				continue;
			}

			$stories[(string) $post_id] = [
				'plays'   => (int) $entry['plays'],
				'percent' => (int) ($entry['percent'] ?? 0),
			];
		}

		$total       = count($sessions);
		$avg_percent = 0;

		foreach ($sessions as $session) {
			$last_session[$session['post']] = $session['percent'];
		}

		if ($total > 0) {
			$sum = 0;

			foreach ($sessions as $session) {
				$sum += $session['percent'];
			}

			$avg_percent = (int) round($sum / $total);
		}

		$per_day = 0;

		if ($total > 0) {
			$days = [];

			foreach ($sessions as $session) {
				if ($session['time'] > 0) {
					$days[wp_date('Y-m-d', $session['time'])] = true;
				}
			}

			$per_day = round($total / max(1, count($days)), 1);
		}

		$months = [];

		foreach ($sessions as $session) {
			$key = wp_date('Y-m', $session['time']);

			if (!isset($months[$key])) {
				$months[$key] = [
					'label' => wp_date('F Y', $session['time']),
					'count' => 0,
				];
			}

			$months[$key]['count']++;
		}

		krsort($months);
		$months = array_values($months);

		$recent = [];
		$seen   = [];

		for ($i = count($sessions) - 1; $i >= 0 && count($recent) < 10; $i--) {
			$post_id = $sessions[$i]['post'];
			$key     = (string) $post_id;

			if (isset($seen[$key]) || !isset($stories[$key])) {
				continue;
			}

			$seen[$key] = true;

			$post = get_post($post_id);

			if (!($post instanceof WP_Post) || 'post' !== $post->post_type || 'publish' !== $post->post_status) {
				continue;
			}

			$recent[] = [
				'title'   => (string) get_the_title($post),
				'link'    => (string) get_permalink($post),
				'percent' => isset($last_session[$post_id]) ? $last_session[$post_id] : $stories[$key]['percent'],
				'plays'   => $stories[$key]['plays'],
			];
		}

		return [
			'stories'     => count($stories),
			'avg_percent' => $avg_percent,
			'per_day'     => $per_day,
			'months'      => $months,
			'recent'      => $recent,
		];
	}
}
