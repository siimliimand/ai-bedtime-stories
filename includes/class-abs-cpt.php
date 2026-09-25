<?php

if (!defined('ABSPATH')) {
	exit;
}

final class ABS_CPT
{
	public const POST_TYPE = 'ai_story';
	public const IDEA_POST_TYPE = 'abs_idea';

	public static function init(): void
	{
		add_action('init', [self::class, 'register_post_type']);
		add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'filter_columns']);
		add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'render_column'], 10, 2);
		add_action('restrict_manage_posts', [self::class, 'render_character_filter']);
		add_action('pre_get_posts', [self::class, 'filter_character_query']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
		add_action('wp_ajax_abs_generate_audio', [self::class, 'ajax_generate_audio']);
		add_action('wp_ajax_abs_save_audio', [self::class, 'ajax_save_audio']);
		add_action('wp_ajax_abs_generate_line_audio', [self::class, 'ajax_generate_line_audio']);
		add_action('wp_ajax_abs_merge_audio', [self::class, 'ajax_merge_audio']);
		add_action('wp_ajax_abs_generate_image', [self::class, 'ajax_generate_image']);
	}

	public static function register_post_type(): void
	{
		$labels = [
			'name'               => __('Stories', 'ai-bedtime-stories'),
			'singular_name'      => __('Story', 'ai-bedtime-stories'),
			'add_new'            => __('Add New', 'ai-bedtime-stories'),
			'add_new_item'       => __('Add New Story', 'ai-bedtime-stories'),
			'edit_item'          => __('Edit Story', 'ai-bedtime-stories'),
			'new_item'           => __('New Story', 'ai-bedtime-stories'),
			'view_item'          => __('View Story', 'ai-bedtime-stories'),
			'view_items'         => __('View Stories', 'ai-bedtime-stories'),
			'search_items'       => __('Search Stories', 'ai-bedtime-stories'),
			'not_found'          => __('No stories found.', 'ai-bedtime-stories'),
			'not_found_in_trash' => __('No stories found in Trash.', 'ai-bedtime-stories'),
			'all_items'          => __('All Stories', 'ai-bedtime-stories'),
			'menu_name'          => __('Stories', 'ai-bedtime-stories'),
		];

		register_post_type(self::POST_TYPE, [
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_icon'           => 'dashicons-book-alt',
			'supports'            => ['title', 'editor'],
			'has_archive'         => false,
			'rewrite'             => false,
			'map_meta_cap'        => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		]);

		$idea_labels = [
			'name'          => __('Ideas', 'ai-bedtime-stories'),
			'singular_name' => __('Idea', 'ai-bedtime-stories'),
		];

		register_post_type(self::IDEA_POST_TYPE, [
			'labels'              => $idea_labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'supports'            => ['title'],
			'has_archive'         => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'show_in_rest'        => false,
		]);
	}

	public static function filter_columns(array $columns): array
	{
		$date = $columns['date'] ?? __('Date', 'ai-bedtime-stories');

		unset($columns['date']);

		$columns['story_status']     = __('Status', 'ai-bedtime-stories');
		$columns['story_character']  = __('Tegelane', 'ai-bedtime-stories');
		$columns['story_audio']      = __('Audio', 'ai-bedtime-stories');
		$columns['story_image']      = __('Pilt', 'ai-bedtime-stories');
		$columns['story_listens']    = __('Kuulamised', 'ai-bedtime-stories');
		$columns['idea_description'] = __('Idea', 'ai-bedtime-stories');
		$columns['date']             = $date;

		return $columns;
	}

	public static function render_column(string $column, int $post_id): void
	{
		if ('story_status' === $column) {
			$published_id = (int) get_post_meta($post_id, '_abs_published_post_id', true);

			if ($published_id > 0 && get_post($published_id) instanceof WP_Post) {
				$permalink = get_permalink($published_id);

				if (is_string($permalink) && '' !== $permalink) {
					printf(
						'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url($permalink),
						esc_html__('Published', 'ai-bedtime-stories')
					);

					return;
				}

			$edit_link = get_edit_post_link($published_id);

			if (is_string($edit_link) && '' !== $edit_link) {
				printf(
					'<a href="%1$s">%2$s</a>',
					esc_url($edit_link),
					esc_html__('Published', 'ai-bedtime-stories')
				);
			} else {
				echo esc_html__('Published', 'ai-bedtime-stories');
			}

			return;
			}

			printf(
				'<span style="color:#8c8f94;">%s</span>',
				esc_html__('Draft', 'ai-bedtime-stories')
			);

			return;
		}

		if ('idea_description' === $column) {
			$idea = (string) get_post_meta($post_id, '_abs_idea_description', true);

			if ('' === $idea) {
				echo '&mdash;';

				return;
			}

			echo esc_html(wp_trim_words($idea, 20));
		}

		if ('story_character' === $column) {
			$name = (string) get_post_meta($post_id, '_abs_character_name', true);

			if ('' === $name) {
				return;
			}

			$role = (string) get_post_meta($post_id, '_abs_character_role', true);

			echo esc_html('' !== $role ? $name . ' (' . $role . ')' : $name);

			return;
		}

		if ('story_audio' === $column) {
			self::render_audio_column($post_id);
		}

		if ('story_image' === $column) {
			self::render_image_column($post_id);
		}

		if ('story_listens' === $column) {
			self::render_listens_column($post_id);
		}
	}

	private static function render_image_column(int $post_id): void
	{
		$attachment_id = (int) get_post_meta($post_id, '_abs_cover_attachment', true);
		$has_image     = $attachment_id > 0 && (bool) wp_get_attachment_image_url($attachment_id, 'thumbnail');

		echo '<div class="abs-image-cell">';

		if ($has_image) {
			echo wp_get_attachment_image(
				$attachment_id,
				[60, 60],
				false,
				[
					'style' => 'display:block;width:60px;height:60px;object-fit:cover;border-radius:4px;margin-bottom:6px;',
					'alt'   => get_the_title($post_id),
				]
			);
		}

		printf(
			'<button type="button" class="button button-small" data-abs-image="1" data-story-id="%1$s">%2$s</button>',
			esc_attr((string) $post_id),
			esc_html($has_image ? __('Genereeri uuesti', 'ai-bedtime-stories') : __('Genereeri pilt', 'ai-bedtime-stories'))
		);

		echo '</div>';
	}

	private static function render_listens_column(int $post_id): void
	{
		$stats = class_exists(ABS_Listen::class) ? ABS_Listen::story_listen_stats() : [];
		$row   = $stats[$post_id] ?? null;

		if (!is_array($row) || $row['count'] < 1) {
			echo '0';

			return;
		}

		$avg = $row['n'] > 0 ? round($row['sum'] / $row['n']) . '%' : '-';

		printf(
			'<span class="abs-listens-cell">%1$s / %2$s</span>',
			esc_html((string) $row['count']),
			esc_html($avg)
		);
	}

	private static function render_audio_column(int $post_id): void
	{
		$meta  = (string) get_post_meta($post_id, '_abs_audio_script', true);
		$lines = self::parse_audio_script($meta);

		echo '<div class="abs-audio-cell">';

		if ([] === $lines) {
			printf(
				'<button type="button" class="button button-small" data-abs-audio="1" data-story-id="%1$s">%2$s</button>',
				esc_attr((string) $post_id),
				esc_html__('Genereeri audio', 'ai-bedtime-stories')
			);

			echo '</div>';

			return;
		}

		printf(
			'<span class="abs-audio-status">%s</span>',
			esc_html(sprintf(__('✓ Valmis (%d rida)', 'ai-bedtime-stories'), count($lines)))
		);

		printf(
			'<button type="button" class="button button-small abs-audio-edit-open" data-story-id="%1$s">%2$s</button>',
			esc_attr((string) $post_id),
			esc_html__('Muuda', 'ai-bedtime-stories')
		);

		$full = get_post_meta($post_id, '_abs_audio_full', true);

		if (is_array($full) && !empty($full['url'])) {
			printf(
				'<button type="button" class="button button-small abs-audio-full-play" data-src="%1$s" aria-label="%2$s">▶ %2$s</button>',
				esc_url((string) $full['url']),
				esc_html__('Kuula', 'ai-bedtime-stories')
			);
		}

		$pretty = wp_json_encode(json_decode($meta, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		printf(
			'<textarea class="abs-audio-editor" rows="10" hidden>%s</textarea>',
			esc_textarea(is_string($pretty) ? $pretty : $meta)
		);

		$files = get_post_meta($post_id, '_abs_audio_files', true);
		$files = is_array($files) ? $files : [];

		printf(
			'<input type="hidden" class="abs-audio-files" value="%1$s" />',
			esc_attr((string) wp_json_encode($files, JSON_FORCE_OBJECT))
		);

		$full = get_post_meta($post_id, '_abs_audio_full', true);
		$full = is_array($full) ? $full : [];

		printf(
			'<input type="hidden" class="abs-audio-full" value="%1$s" />',
			esc_attr((string) wp_json_encode($full))
		);

		printf(
			'<button type="button" class="button button-small" data-abs-audio="1" data-story-id="%1$s">%2$s</button>',
			esc_attr((string) $post_id),
			esc_html__('Genereeri uuesti', 'ai-bedtime-stories')
		);

		echo '</div>';
	}

	private static function parse_audio_script(mixed $script): array
	{
		if (!is_string($script) || '' === $script) {
			return [];
		}

		$decoded = json_decode($script, true);

		if (!is_array($decoded) || !isset($decoded['lines']) || !is_array($decoded['lines'])) {
			return [];
		}

		return self::normalize_audio_lines($decoded['lines']);
	}

	private static function normalize_audio_lines(mixed $raw): array
	{
		if (!is_array($raw)) {
			return [];
		}

		$allowed   = [];
		$narrator  = '';

		foreach (ABS_Settings::audio_voices() as $voice) {
			$voice_name = trim((string) ($voice['name'] ?? ''));

			if ('' === $voice_name) {
				continue;
			}

			$allowed[] = $voice_name;

			if ('' === $narrator && 'jutustaja' === mb_strtolower(trim((string) ($voice['description'] ?? '')))) {
				$narrator = $voice_name;
			}
		}

		$lines = [];

		foreach ($raw as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			$text = trim((string) ($entry['text'] ?? ''));

			if ('' === $text) {
				continue;
			}

			$speaker = trim((string) ($entry['speaker'] ?? ''));
			$tone    = trim((string) ($entry['tone'] ?? ''));
			$pause   = (int) ($entry['pause_after_ms'] ?? 400);
			$pause   = min(5000, max(0, $pause));

			if ([] !== $allowed) {
				if ('jutustaja' === mb_strtolower($speaker) && '' !== $narrator) {
					$speaker = $narrator;
				} else {
					$matched = '';

					foreach ($allowed as $allowed_name) {
						if (mb_strtolower($allowed_name) === mb_strtolower($speaker)) {
							$matched = $allowed_name;
							break;
						}
					}

					$speaker = '' !== $matched ? $matched : $allowed[0];
				}
			} else {
				$speaker = '' !== $speaker ? $speaker : 'Jutustaja';
			}

			$lines[] = [
				'speaker'        => $speaker,
				'tone'           => $tone,
				'pause_after_ms' => $pause,
				'text'           => $text,
			];
		}

		return $lines;
	}

	private static function delete_audio_file_entry(array $entry): void
	{
		$file = trim((string) ($entry['file'] ?? ''));

		if ('' === $file) {
			return;
		}

		$uploads = wp_upload_dir();
		$basedir = wp_normalize_path(trailingslashit((string) $uploads['basedir']));
		$path    = wp_normalize_path(trailingslashit((string) $uploads['basedir']) . ltrim($file, '/'));

		if ('' !== $basedir && 0 === strpos($path, $basedir) && is_file($path)) {
			wp_delete_file($path);
		}
	}

	public static function enqueue_assets(string $hook): void
	{
		if ('edit.php' !== $hook) {
			return;
		}

		$post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

		if (self::POST_TYPE !== $post_type) {
			return;
		}

		wp_enqueue_style(
			'abs-admin',
			ABS_PLUGIN_URL . 'assets/admin.css',
			[],
			ABS_VERSION
		);

		wp_enqueue_script(
			'abs-admin',
			ABS_PLUGIN_URL . 'assets/admin.js',
			[],
			ABS_VERSION,
			true
		);

		wp_localize_script('abs-admin', 'absAdmin', [
			'ajaxUrl'          => admin_url('admin-ajax.php'),
			'nonce'            => wp_create_nonce(ABS_Settings::NONCE_ACTION),
			'audioNonce'       => wp_create_nonce(ABS_Settings::NONCE_ACTION),
			'audioButtonLabel' => 'Genereeri audio',
			'audioRetryLabel'  => 'Genereeri uuesti',
			'audioBusyLabel'   => 'Genereerin…',
			'audioDoneLabel'   => 'Valmis',
			'audioEditLabel'   => 'Muuda',
			'audioSaveLabel'   => 'Salvesta',
			'audioCancelLabel' => 'Katkesta',
			'audioModalTitleLabel' => 'Muuda audio skripti',
			'audioCloseLabel'  => 'Sulge',
			'audioRowsLabel'   => 'rida',
			'audioAddLineLabel'    => 'Lisa rida',
			'audioRemoveLineLabel' => 'Eemalda',
			'audioSpeakerLabel'    => 'Kõneleja',
			'audioToneLabel'       => 'Toon',
			'audioPauseLabel'      => 'Paus (ms)',
			'audioTextLabel'       => 'Tekst',
			'audioInvalidJsonLabel' => 'Skript ei ole kehtiv JSON.',
			'audioInvalidLineLabel' => 'Skriptis ei ole ühtegi kehtivat rida.',
			'audioLineGenLabel'        => 'Genereeri',
			'audioLineRegenLabel'      => 'Genereeri uuesti',
			'audioLineBusyLabel'       => 'Genereerin…',
			'audioLinePlayLabel'       => 'Esita',
			'audioLinePauseLabel'      => 'Peata',
			'audioLineKeyMissingLabel' => 'Jutusta API võti on seadistamata.',
			'audioLineEmptyLabel'      => 'Rea tekst on tühi.',
			'audioMergeLabel'      => 'Koosta audio',
			'audioMergeBusyLabel'  => 'Koostan…',
			'audioFullPlayLabel'   => 'Kuula',
			'genAllLabel'      => 'Genereeri kõik',
			'genAllBusyLabel'  => 'Genereerin',
			'genAllDoneLabel'  => 'Kõik read genereeritud.',
			'genAllStopLabel'  => 'Peatatud — 5 järjestikust ebaõnnestumist.',
			'genAllWaitLabel'  => 'Ebaõnnestus, proovin 5 sekundi pärast uuesti…',
			'genAllAbortLabel' => 'Katkestatud.',
			'audioTtsEnabled'      => '' !== trim((string) ABS_Settings::get('tts_api_key')),
			'audioVoices'      => array_values(array_map(static function (array $voice): array {
				return [
					'name'        => (string) ($voice['name'] ?? ''),
					'description' => (string) ($voice['description'] ?? ''),
					'volume'      => (int) ($voice['volume'] ?? 100),
				];
			}, ABS_Settings::audio_voices())),
			'failureLabel'     => 'Request failed',
			'imageBusyLabel'   => 'Genereerin…',
			'serverErrorLabel' => 'Server error — check PHP error log.',
		]);
	}

	public static function ajax_generate_audio(): void
	{
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$story_id = isset($_POST['story_id']) ? absint($_POST['story_id']) : 0;
		$story    = $story_id > 0 ? get_post($story_id) : null;

		if (!($story instanceof WP_Post) || self::POST_TYPE !== $story->post_type) {
			wp_send_json_error(['message' => 'Story not found.'], 404);
		}

		$content = trim((string) $story->post_content);

		if ('' === $content) {
			wp_send_json_error(['message' => 'Story content is empty.'], 400);
		}

		$prompt = (string) ABS_Settings::get('audio_prompt');
		$prompt = str_replace('{story}', $content, $prompt);

		$voice_lines = [];

		foreach (ABS_Settings::audio_voices() as $voice) {
			$voice_name = trim((string) ($voice['name'] ?? ''));

			if ('' === $voice_name) {
				continue;
			}

			$voice_desc    = trim((string) ($voice['description'] ?? ''));
			$voice_lines[] = '- ' . $voice_name . ('' !== $voice_desc ? ' — ' . $voice_desc : '');
		}

		if ([] !== $voice_lines) {
			$prompt .= "\n\nLubatud hääled (speaker peab olema täpselt üks neist):\n" . implode("\n", $voice_lines);
		}

		if (function_exists('set_time_limit')) {
			@set_time_limit(180);
		}

		$config = ABS_Settings::task_config('audio');

		$result = ABS_API::json(
			[['role' => 'user', 'content' => $prompt]],
			[
				'max_tokens'  => 8192,
				'temperature' => 0.3,
				'timeout'     => 150,
			],
			$config
		);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		$lines = self::normalize_audio_lines($result['lines'] ?? null);

		if ([] === $lines) {
			wp_send_json_error(['message' => 'AI ei tagastanud kehtivat audio skripti.']);
		}

		update_post_meta($story_id, '_abs_audio_script', wp_slash(wp_json_encode(['lines' => $lines], JSON_UNESCAPED_UNICODE)));

		$full_old = get_post_meta($story_id, '_abs_audio_full', true);

		if (is_array($full_old)) {
			self::delete_audio_file_entry($full_old);
		}

		delete_post_meta($story_id, '_abs_audio_full');

		wp_send_json_success(
			[
				'story_id'   => $story_id,
				'lines'      => count($lines),
				'lines_data' => $lines,
				'full_url'   => '',
			]
		);
	}

	public static function ajax_generate_image(): void
	{
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$story_id = isset($_POST['story_id']) ? absint($_POST['story_id']) : 0;
		$story    = $story_id > 0 ? get_post($story_id) : null;

		if (!($story instanceof WP_Post) || self::POST_TYPE !== $story->post_type) {
			wp_send_json_error(['message' => 'Story not found.'], 404);
		}

		$content = trim((string) $story->post_content);

		if ('' === $content) {
			wp_send_json_error(['message' => 'Story content is empty.'], 400);
		}

		$prompt = (string) ABS_Settings::get('image_prompt');
		$prompt = str_replace(['{TITLE}', '{STORY}'], [(string) $story->post_title, $content], $prompt);

		if (function_exists('set_time_limit')) {
			@set_time_limit(300);
		}

		$result = ABS_API::image($prompt);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()], 500);
		}

		$binary = base64_decode((string) ($result['b64'] ?? ''), true);

		if (false === $binary || '' === $binary) {
			wp_send_json_error(['message' => 'Could not decode the image data.'], 500);
		}

		$ext_map    = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
		$media_type = (string) ($result['media_type'] ?? 'image/png');
		$ext        = $ext_map[$media_type] ?? 'png';

		$upload = wp_upload_bits(
			sprintf('abs-cover-%d-%d.%s', $story_id, time(), $ext),
			null,
			$binary
		);

		if (!empty($upload['error'])) {
			wp_send_json_error(['message' => (string) $upload['error']], 500);
		}

		if (!function_exists('wp_generate_attachment_metadata')) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$attachment_id = wp_insert_attachment([
			'post_mime_type' => (string) ($upload['type'] ?? $media_type),
			'post_title'     => (string) $story->post_title,
			'post_status'    => 'inherit',
		], (string) $upload['file']);

		if (is_wp_error($attachment_id) || ! $attachment_id) {
			wp_send_json_error(['message' => 'Could not save the image to the media library.'], 500);
		}

		$attachment_id = (int) $attachment_id;

		update_post_meta($attachment_id, '_abs_source_story_id', $story_id);
		wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, (string) $upload['file']));

		update_post_meta($story_id, '_abs_cover_attachment', $attachment_id);

		$published_id = (int) get_post_meta($story_id, '_abs_published_post_id', true);
		$post_image   = false;

		if ($published_id > 0 && get_post($published_id) instanceof WP_Post) {
			set_post_thumbnail($published_id, $attachment_id);
			$post_image = true;
		}

		ob_start();
		self::render_image_column($story_id);
		$html = (string) ob_get_clean();

		wp_send_json_success([
			'html'       => $html,
			'thumbnail'  => (string) wp_get_attachment_image_url($attachment_id, 'thumbnail'),
			'post_image' => $post_image,
		]);
	}

	public static function ajax_save_audio(): void
	{
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$story_id = isset($_POST['story_id']) ? absint($_POST['story_id']) : 0;
		$story    = $story_id > 0 ? get_post($story_id) : null;

		if (!($story instanceof WP_Post) || self::POST_TYPE !== $story->post_type) {
			wp_send_json_error(['message' => 'Story not found.'], 404);
		}

		$script = isset($_POST['script']) ? trim((string) wp_unslash((string) $_POST['script'])) : '';
		$decoded = '' !== $script ? json_decode($script, true) : null;

		if (!is_array($decoded)) {
			wp_send_json_error(['message' => 'Skript ei ole kehtiv JSON.']);
		}

		$lines = self::normalize_audio_lines($decoded['lines'] ?? null);

		if ([] === $lines) {
			wp_send_json_error(['message' => 'Skriptis ei ole ühtegi kehtivat rida.']);
		}

		update_post_meta($story_id, '_abs_audio_script', wp_slash(wp_json_encode(['lines' => $lines], JSON_UNESCAPED_UNICODE)));

		$full = get_post_meta($story_id, '_abs_audio_full', true);

		if (is_array($full)) {
			self::delete_audio_file_entry($full);
		}

		delete_post_meta($story_id, '_abs_audio_full');

		$files  = get_post_meta($story_id, '_abs_audio_files', true);
		$files  = is_array($files) ? $files : [];
		$pruned = false;

		foreach (array_keys($files) as $index) {
			if ((int) $index >= count($lines)) {
				if (is_array($files[$index])) {
					self::delete_audio_file_entry($files[$index]);
				}

				unset($files[$index]);
				$pruned = true;
			}
		}

		if ($pruned) {
			if ([] === $files) {
				delete_post_meta($story_id, '_abs_audio_files');
			} else {
				update_post_meta($story_id, '_abs_audio_files', $files);
			}
		}

		wp_send_json_success(
			[
				'story_id'   => $story_id,
				'lines'      => count($lines),
				'lines_data' => $lines,
				'full_url'   => '',
			]
		);
	}

	public static function ajax_generate_line_audio(): void
	{
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$story_id = isset($_POST['story_id']) ? absint($_POST['story_id']) : 0;
		$line     = isset($_POST['line']) ? absint($_POST['line']) : 0;
		$story    = $story_id > 0 ? get_post($story_id) : null;

		if (!($story instanceof WP_Post) || self::POST_TYPE !== $story->post_type) {
			wp_send_json_error(['message' => 'Story not found.'], 404);
		}

		$tts_key = trim((string) ABS_Settings::get('tts_api_key'));

		if ('' === $tts_key) {
			wp_send_json_error(['message' => 'Jutusta API võti on seadistamata.']);
		}

		$lines = self::parse_audio_script((string) get_post_meta($story_id, '_abs_audio_script', true));

		if ([] === $lines) {
			wp_send_json_error(['message' => 'Audio skript ei ole salvestatud ega kehtiv.']);
		}

		if (!isset($lines[$line])) {
			wp_send_json_error(['message' => 'Rea indeks on väljaspool skripti vahemikku.']);
		}

		$voice_slug = strtolower(trim((string) $lines[$line]['speaker']));
		$voice_slug = (string) preg_replace('/[^a-z0-9_-]/', '', $voice_slug);

		if ('' === $voice_slug) {
			wp_send_json_error(['message' => 'Hääle nimi ei ole kehtiv.']);
		}

		$body = wp_json_encode([
			'text'              => (string) $lines[$line]['text'],
			'model_id'          => 'jutusta_v1',
			'language_code'     => 'et',
			'seed'              => 0,
			'voice_settings'    => [
				'stability'         => (float) ABS_Settings::get('tts_stability'),
				'similarity_boost'  => (float) ABS_Settings::get('tts_similarity_boost'),
				'style'             => (float) ABS_Settings::get('tts_style'),
				'use_speaker_boost' => true,
				'speed'             => (float) ABS_Settings::get('tts_speed'),
			],
			'previous_text'     => '',
			'next_text'         => '',
			'sentence_chunking' => true,
		]);

		if (!is_string($body) || '' === $body) {
			wp_send_json_error(['message' => 'Päringu koostamine ebaõnnestus.']);
		}

		if (function_exists('set_time_limit')) {
			@set_time_limit(360);
		}

		$response = null;
		$code     = 0;
		$wav      = '';
		$wp_error = '';

		$tts_stream = 1 === (int) ABS_Settings::get('tts_stream');
		$tts_url    = 'https://jutusta.ee/api/v1/text-to-speech/' . $voice_slug . ($tts_stream ? '/stream' : '') . '?output_format=wav';

		for ($attempt = 1; $attempt <= 3; $attempt++) {
			$response = wp_remote_post(
				$tts_url,
				[
					'timeout' => 100,
					'headers' => [
						'Content-Type' => 'application/json',
						'Accept'       => 'audio/wav',
						'xi-api-key'   => $tts_key,
					],
					'body'    => $body,
				]
			);

			if (is_wp_error($response)) {
				$wp_error = $response->get_error_message();
			} else {
				$code = (int) wp_remote_retrieve_response_code($response);
				$wav  = (string) wp_remote_retrieve_body($response);

				if (200 === $code && strlen($wav) >= 44 && 'RIFF' === substr($wav, 0, 4)) {
					break;
				}
			}

			if ($attempt < 3) {
				sleep(2);
			}
		}

		if ('' !== $wp_error) {
			wp_send_json_error(['message' => 'Heli genereerimine ebaõnnestus: ' . $wp_error]);
		}

		if (200 !== $code || '' === $wav) {
			wp_send_json_error(['message' => 'Heli genereerimine ebaõnnestus (HTTP ' . $code . ') pärast 3 katset. Proovi mõne hetke pärast uuesti.']);
		}

		$uploads = wp_upload_dir();

		if (!empty($uploads['error'])) {
			wp_send_json_error(['message' => 'Üleslaadimiste kataloog ei ole saadaval.']);
		}

		$dir = trailingslashit((string) $uploads['basedir']) . 'abs-audio';

		if (!is_dir($dir) && !wp_mkdir_p($dir)) {
			wp_send_json_error(['message' => 'Helifailide kataloogi loomine ebaõnnestus.']);
		}

		$existing = get_post_meta($story_id, '_abs_audio_files', true);
		$files    = is_array($existing) ? $existing : [];

		if (isset($files[$line]) && is_array($files[$line])) {
			self::delete_audio_file_entry($files[$line]);
		}

		$filename = 'story-' . $story_id . '-line-' . $line . '-' . time() . '.wav';
		$path     = trailingslashit($dir) . $filename;
		$written  = file_put_contents($path, $wav);

		if (false === $written || $written <= 0) {
			wp_send_json_error(['message' => 'Helifaili salvestamine ebaõnnestus.']);
		}

		$relative = 'abs-audio/' . $filename;
		$file_url = esc_url_raw(trailingslashit((string) $uploads['baseurl']) . $relative);

		$files[$line] = [
			'file'    => $relative,
			'url'     => $file_url,
			'voice'   => $voice_slug,
			'updated' => time(),
		];

		update_post_meta($story_id, '_abs_audio_files', $files);

		wp_send_json_success(
			[
				'story_id' => $story_id,
				'line'     => $line,
				'url'      => $file_url,
				'voice'    => $voice_slug,
			]
		);
	}

	public static function ajax_merge_audio(): void
	{
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$story_id = isset($_POST['story_id']) ? absint($_POST['story_id']) : 0;
		$story    = $story_id > 0 ? get_post($story_id) : null;

		if (!($story instanceof WP_Post) || self::POST_TYPE !== $story->post_type) {
			wp_send_json_error(['message' => 'Story not found.'], 404);
		}

		$lines = self::parse_audio_script((string) get_post_meta($story_id, '_abs_audio_script', true));

		if ([] === $lines) {
			wp_send_json_error(['message' => 'Skript puudub.'], 404);
		}

		if (function_exists('set_time_limit')) {
			@set_time_limit(300);
		}

		$files   = get_post_meta($story_id, '_abs_audio_files', true);
		$files   = is_array($files) ? $files : [];
		$uploads = wp_upload_dir();

		if (!empty($uploads['error'])) {
			wp_send_json_error(['message' => 'Üleslaadimiste kataloog ei ole saadaval.']);
		}

		$basedir = trailingslashit((string) $uploads['basedir']);
		$paths   = [];
		$format  = null;

		foreach ($lines as $index => $line) {
			$entry = $files[$index] ?? $files[(string) $index] ?? null;
			$file  = is_array($entry) ? trim((string) ($entry['file'] ?? '')) : '';

			if ('' === $file || 0 !== strpos(wp_normalize_path($basedir . ltrim($file, '/')), wp_normalize_path($basedir))) {
				wp_send_json_error(['message' => 'Kõikidel ridadel peab olema heli enne kokkukitkemist. Puudub rida ' . ($index + 1) . '.']);
			}

			$path = $basedir . ltrim($file, '/');

			if (!is_file($path)) {
				wp_send_json_error(['message' => 'Helifail puudub real ' . ($index + 1) . '. Genereeri see uuesti.']);
			}

			$info = self::parse_wav_header((string) file_get_contents($path));

			if (null === $info) {
				wp_send_json_error(['message' => 'Helifail real ' . ($index + 1) . ' ei ole kehtiv WAV.']);
			}

			if (null === $format) {
				$format = $info;
			} elseif ($info['rate'] !== $format['rate'] || $info['channels'] !== $format['channels'] || $info['bits'] !== $format['bits']) {
				wp_send_json_error(['message' => 'Helifailid ei ole ühilduvad (erinev kvaliteet). Genereeri read uuesti.']);
			}

			$paths[$index] = ['path' => $path, 'offset' => $info['offset'], 'size' => $info['size'], 'pause' => isset($line['pause_after_ms']) ? (int) $line['pause_after_ms'] : 0, 'speaker' => mb_strtolower(trim((string) ($line['speaker'] ?? '')))];
		}

		$volumes = [];

		foreach (ABS_Settings::audio_voices() as $voice) {
			$voice_name = mb_strtolower(trim((string) ($voice['name'] ?? '')));

			if ('' !== $voice_name) {
				$volumes[$voice_name] = max(0.1, min(2.0, (int) ($voice['volume'] ?? 100) / 100));
			}
		}

		$dir = $basedir . 'abs-audio';

		if (!is_dir($dir) && !wp_mkdir_p($dir)) {
			wp_send_json_error(['message' => 'Helifaili ei õnnestunud salvestada.']);
		}

		$filename = 'story-' . $story_id . '-full-' . time() . '.wav';
		$target   = $basedir . 'abs-audio/' . $filename;
		$fh       = fopen($target, 'wb');

		if (false === $fh) {
			wp_send_json_error(['message' => 'Helifaili ei õnnestunud salvestada.']);
		}

		fwrite($fh, str_repeat("\0", 44));

		$channels = (int) $format['channels'];
		$rate     = (int) $format['rate'];
		$total    = 0;
		$prev_gap = 0;

		foreach ($paths as $clip) {
			if ($total > 0 && $prev_gap > 0) {
				$silence = str_repeat("\0", intdiv($rate * $channels * 2 * $prev_gap, 1000));
				fwrite($fh, $silence);
				$total += strlen($silence);
			}

			$chunk = (string) file_get_contents($clip['path'], false, null, $clip['offset'], $clip['size']);

			if ('' !== $chunk) {
				$chunk = self::scale_pcm($chunk, $volumes[$clip['speaker']] ?? 1.0);
				fwrite($fh, $chunk);
				$total += strlen($chunk);
			}

			$prev_gap = max(0, min(5000, $clip['pause']));
		}

		$header = 'RIFF'
			. pack('V', $total + 36)
			. 'WAVE'
			. 'fmt '
			. pack('V', 16)
			. pack('v', 1)
			. pack('v', $channels)
			. pack('V', $rate)
			. pack('V', $rate * $channels * 2)
			. pack('v', $channels * 2)
			. pack('v', 16)
			. 'data'
			. pack('V', $total);

		fseek($fh, 0);
		fwrite($fh, $header);
		fclose($fh);

		if ($total <= 0) {
			wp_delete_file($target);
			wp_send_json_error(['message' => 'Helifailide kokkukitkamine ebaõnnestus.']);
		}

		$old = get_post_meta($story_id, '_abs_audio_full', true);

		if (is_array($old)) {
			self::delete_audio_file_entry($old);
		}

		$relative = 'abs-audio/' . $filename;
		$url      = esc_url_raw(trailingslashit((string) $uploads['baseurl']) . $relative);

		update_post_meta($story_id, '_abs_audio_full', [
			'file'    => $relative,
			'url'     => $url,
			'updated' => time(),
			'lines'   => count($lines),
		]);

		wp_send_json_success(
			[
				'story_id' => $story_id,
				'url'      => $url,
				'lines'    => count($lines),
				'full_url' => $url,
			]
		);
	}

	private static function scale_pcm(string $pcm, float $gain): string
	{
		if (1.0 === $gain || '' === $pcm) {
			return $pcm;
		}

		$samples = unpack('v*', $pcm);
		$out     = '';

		foreach ($samples as $sample) {
			$signed = $sample >= 32768 ? $sample - 65536 : $sample;
			$signed = (int) round($signed * $gain);

			if ($signed > 32767) {
				$signed = 32767;
			}

			if ($signed < -32768) {
				$signed = -32768;
			}

			$out .= pack('v', $signed);
		}

		return $out;
	}

	private static function parse_wav_header(string $bytes): ?array
	{
		if (strlen($bytes) < 44 || 'RIFF' !== substr($bytes, 0, 4) || 'WAVE' !== substr($bytes, 8, 4)) {
			return null;
		}

		$pos  = 12;
		$fmt  = null;
		$data = null;
		$len  = strlen($bytes);

		while ($pos + 8 <= $len) {
			$id   = substr($bytes, $pos, 4);
			$size = (int) unpack('V', substr($bytes, $pos + 4, 4))[1];
			$body = min($size, $len - $pos - 8);

			if ('fmt ' === $id) {
				$fmt = substr($bytes, $pos + 8, min(16, $body));
			} elseif ('data' === $id) {
				$data = ['offset' => $pos + 8, 'size' => $body];
				break;
			}

			$pos += 8 + $size + ($size % 2);
		}

		if (null === $fmt || null === $data || strlen($fmt) < 16) {
			return null;
		}

		$head = unpack('vaudio_format/vchannels/Vsample_rate/Vbyte_rate/vblock_align/vbits', $fmt);

		if (1 !== (int) $head['audio_format'] || 16 !== (int) $head['bits'] || (int) $head['channels'] < 1 || (int) $head['sample_rate'] < 1) {
			return null;
		}

		return [
			'channels' => (int) $head['channels'],
			'rate'     => (int) $head['sample_rate'],
			'bits'     => (int) $head['bits'],
			'offset'   => $data['offset'],
			'size'     => $data['size'],
		];
	}

	public static function render_character_filter(string $post_type): void
	{
		if (self::POST_TYPE !== $post_type) {
			return;
		}

		global $wpdb;

		$names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} AS pm INNER JOIN {$wpdb->posts} AS p ON (p.ID = pm.post_id) WHERE pm.meta_key = %s AND p.post_type = %s ORDER BY meta_value ASC",
				'_abs_character_name',
				self::POST_TYPE
			)
		);

		if (!is_array($names) || [] === $names) {
			return;
		}

		$current = isset($_GET['abs_character']) ? sanitize_text_field(wp_unslash((string) $_GET['abs_character'])) : '';

		echo '<select name="abs_character" id="abs-character-filter">';
		echo '<option value="">' . esc_html__('Kuva kõik tegelased', 'ai-bedtime-stories') . '</option>';

		foreach ($names as $name) {
			$name = (string) $name;

			if ('' === $name) {
				continue;
			}

			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr($name),
				selected($current, $name, false),
				esc_html($name)
			);
		}

		echo '</select>';
	}

	public static function filter_character_query(WP_Query $query): void
	{
		if (!is_admin() || !$query->is_main_query()) {
			return;
		}

		$screen = function_exists('get_current_screen') ? get_current_screen() : null;

		if (!$screen instanceof WP_Screen || self::POST_TYPE !== $screen->post_type) {
			return;
		}

		$character = isset($_GET['abs_character']) ? sanitize_text_field(wp_unslash((string) $_GET['abs_character'])) : '';

		if ('' !== $character) {
			$query->set('meta_query', [
				[
					'key'   => '_abs_character_name',
					'value' => $character,
				],
			]);
		}
	}
}
