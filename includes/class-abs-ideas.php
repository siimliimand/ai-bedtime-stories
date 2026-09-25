<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Ideas {

	public static function init(): void {
		add_action('admin_menu', [self::class, 'register_menu']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue']);

		add_action('wp_ajax_abs_generate_ideas', [self::class, 'ajax_generate_ideas']);
		add_action('wp_ajax_abs_generate_story', [self::class, 'ajax_generate_story']);
		add_action('wp_ajax_abs_discard_idea', [self::class, 'ajax_discard_idea']);
		add_action('wp_ajax_abs_update_idea', [self::class, 'ajax_update_idea']);
	}

	public static function register_menu(): void {
		add_submenu_page(
			'abs-generate',
			'Generate Stories',
			'Generate Stories',
			'manage_options',
			'abs-generate',
			[self::class, 'render_page']
		);

		if (class_exists('ABS_Settings') && method_exists('ABS_Settings', 'render_page')) {
			add_submenu_page(
				'abs-generate',
				'Settings',
				'Settings',
				'manage_options',
				'abs-settings',
				[ABS_Settings::class, 'render_page']
			);
		}
	}

	public static function enqueue(string $hook): void {
		$page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';

		if (!in_array($page, ['abs-generate', 'abs-settings'], true)) {
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
			'generateLabel'    => 'Generate ideas',
			'generatingLabel'  => 'Generating…',
			'writingLabel'     => 'Writing story…',
			'testingLabel'     => 'Testing…',
			'testButtonLabel'  => 'Test API connection',
			'failureLabel'     => 'Request failed',
			'serverErrorLabel' => 'Server error — check PHP error log.',
			'storyCreated'     => 'Story created',
			'editStoryLabel'   => 'Edit story',
			'wordsLabel'       => 'words',
			'ideaEditLabel'     => 'Muuda',
			'ideaSaveLabel'     => 'Salvesta',
			'ideaSavingLabel'   => 'Salvestan…',
			'ideaCancelLabel'   => 'Katkesta',
			'ideaTitleLabel'    => 'Pealkiri',
			'ideaDescLabel'     => 'Kirjeldus',
			'ideaRequiredLabel' => 'Pealkiri ja kirjeldus on kohustuslikud.',
		]);
	}

	public static function render_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die('You do not have permission to access this page.');
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html('Generate Stories'); ?></h1>

			<p><?php echo esc_html('Generate bedtime story ideas with AI, then turn any idea into a full Brothers Grimm-inspired story. Ideas and stories are generated in Estonian.'); ?></p>

		<p>
			<label class="abs-neuro-label" for="abs-character-select"><?php echo esc_html('Tegelane'); ?></label>
			<select id="abs-character-select">
				<option value="-1"><?php echo esc_html('— Puudub —'); ?></option>
				<?php foreach (ABS_Settings::characters() as $char_index => $char_item) : ?>
					<?php
					$char_option_name  = trim((string) ($char_item['name'] ?? ''));
					$char_option_neuro = trim((string) ($char_item['neuro'] ?? ''));
					$char_option_label = '' !== $char_option_neuro ? $char_option_name . ' — ' . $char_option_neuro : $char_option_name;
					?>
					<option value="<?php echo esc_attr((string) $char_index); ?>" title="<?php echo esc_attr((string) ($char_item['description'] ?? '')); ?>"><?php echo esc_html($char_option_label); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

			<p>
				<button type="button" id="abs-generate-ideas" class="button button-primary button-hero"><?php echo esc_html('Generate ideas'); ?></button>
				<span id="abs-ideas-spinner" class="abs-spinner" hidden></span>
			</p>

			<div id="abs-ideas-feedback" class="notice inline" hidden></div>

			<div id="abs-ideas-grid" class="abs-ideas-grid">
				<?php
				$pending = get_posts(
					[
						'post_type'      => class_exists('ABS_CPT') ? ABS_CPT::IDEA_POST_TYPE : 'abs_idea',
						'posts_per_page' => 50,
						'post_status'    => 'publish',
						'orderby'        => 'date',
						'order'          => 'DESC',
					]
				);

				if ([] === $pending) {
					echo '<p class="abs-no-ideas">No pending ideas. Click "Generate ideas" to create some.</p>';
				} else {
					foreach ($pending as $pending_idea) {
						$pending_char_name = (string) get_post_meta($pending_idea->ID, '_abs_character_name', true);
						$pending_char_role = (string) get_post_meta($pending_idea->ID, '_abs_character_role', true);

						self::render_idea_card(
							(int) $pending_idea->ID,
							(string) get_the_title($pending_idea),
							(string) get_post_meta($pending_idea->ID, '_abs_idea_description', true),
							(string) get_post_meta($pending_idea->ID, '_abs_neurodifference', true),
							'' !== $pending_char_name && '' !== $pending_char_role ? $pending_char_name . ' (' . $pending_char_role . ')' : $pending_char_name
						);
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function render_idea_card(int $id, string $title, string $description, string $neuro = '', string $character = ''): void {
		?>
		<article class="abs-idea-card" data-id="<?php echo esc_attr((string) $id); ?>">
			<h3 class="abs-idea-title"><?php echo esc_html($title); ?></h3>
			<?php if ('' !== $neuro) : ?>
				<span class="abs-idea-neuro"><?php echo esc_html($neuro); ?></span>
			<?php endif; ?>
			<?php if ('' !== $character) : ?>
				<span class="abs-idea-character"><?php echo esc_html($character); ?></span>
			<?php endif; ?>
			<p class="abs-idea-desc"><?php echo esc_html($description); ?></p>
			<footer>
				<button type="button" class="button button-primary abs-generate-story" data-id="<?php echo esc_attr((string) $id); ?>" data-title="<?php echo esc_attr($title); ?>" data-description="<?php echo esc_attr($description); ?>"><?php echo esc_html('Generate full story'); ?></button>
				<button type="button" class="button-link abs-edit-idea" data-id="<?php echo esc_attr((string) $id); ?>"><?php echo esc_html('Muuda'); ?></button>
				<button type="button" class="button-link abs-discard-idea" data-id="<?php echo esc_attr((string) $id); ?>"><?php echo esc_html('Discard'); ?></button>
			</footer>
		</article>
		<?php
	}

	public static function recent_titles(): array {
		$ids = get_posts(
			[
				'post_type'      => [class_exists('ABS_CPT') ? ABS_CPT::POST_TYPE : 'ai_story', 'post'],
				'posts_per_page' => 30,
				'post_status'    => 'any',
				'fields'         => 'ids',
			]
		);

		if (!is_array($ids) || [] === $ids) {
			return [];
		}

		$titles = [];
		foreach ($ids as $id) {
			$title = get_the_title((int) $id);
			if (is_string($title) && '' !== trim($title)) {
				$titles[] = $title;
			}
		}

		return $titles;
	}

	public static function ajax_generate_ideas(): void {
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$count = max(1, min(10, (int) ABS_Settings::get('ideas_count')));

		$existing = self::recent_titles();
		$prompt   = (string) ABS_Settings::get('ideas_prompt');
		$prompt   = str_replace(
			['{count}', '{existing_titles}'],
			[(string) $count, [] === $existing ? '-' : implode('; ', $existing)],
			$prompt
		);

		$char       = isset($_POST['char']) ? (int) $_POST['char'] : -1;
		$char_items = ABS_Settings::characters();
		$char_item  = $char >= 0 ? ($char_items[absint($char)] ?? null) : null;
		$char_name  = is_array($char_item) && '' !== trim((string) ($char_item['name'] ?? '')) ? trim((string) $char_item['name']) : '';
		$char_role  = is_array($char_item) ? trim((string) ($char_item['character'] ?? '')) : '';
		$char_desc  = is_array($char_item) ? trim((string) ($char_item['description'] ?? '')) : '';

		$char_neuro_name = is_array($char_item) ? trim((string) ($char_item['neuro'] ?? '')) : '';
		$neuro_item      = '' !== $char_neuro_name ? ABS_Settings::character_neuro($char_neuro_name) : null;
		$neuro_name      = is_array($neuro_item) && '' !== trim((string) ($neuro_item['name'] ?? '')) ? trim((string) $neuro_item['name']) : '';
		$neuro_desc      = is_array($neuro_item) ? trim((string) ($neuro_item['description'] ?? '')) : '';

		$prompt = str_replace('{neurodifference}', '' !== $neuro_name ? ('' === $neuro_desc ? $neuro_name : $neuro_name . ' — ' . $neuro_desc) : ABS_Settings::neurodifference_context(-1), $prompt);
		$prompt = str_replace('{character}', ABS_Settings::character_context($char), $prompt);

		$config = ABS_Settings::task_config('ideas');

		$result = ABS_API::json(
			[['role' => 'user', 'content' => $prompt]],
			[
				'max_tokens'  => 2048,
				'temperature' => 1.1,
				'timeout'     => 90,
			],
			$config
		);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		$ideas = [];
		if (is_array($result)) {
			foreach ($result as $entry) {
				if (count($ideas) >= $count) {
					break;
				}

				if (!is_array($entry)) {
					continue;
				}

				$title = isset($entry['title']) ? trim((string) $entry['title']) : '';
				if ('' === $title) {
					continue;
				}

				$ideas[] = [
					'title'       => sanitize_text_field($title),
					'description' => sanitize_text_field(wp_strip_all_tags((string) ($entry['description'] ?? ''))),
				];
			}
		}

		if ([] === $ideas) {
			wp_send_json_error(['message' => 'The AI returned no usable ideas. Please try again.']);
		}

		$idea_post_type = class_exists('ABS_CPT') ? ABS_CPT::IDEA_POST_TYPE : 'abs_idea';

		foreach ($ideas as $index => $idea) {
			$idea_id = wp_insert_post(
				[
					'post_type'   => $idea_post_type,
					'post_status' => 'publish',
					'post_title'  => $idea['title'],
					'post_author' => get_current_user_id(),
				]
			);

			if (is_wp_error($idea_id) || !is_numeric($idea_id) || (int) $idea_id < 1) {
				continue;
			}

			$idea_id = (int) $idea_id;

			update_post_meta($idea_id, '_abs_idea_description', $idea['description']);

			if ('' !== $neuro_name) {
				update_post_meta($idea_id, '_abs_neurodifference', $neuro_name);
				update_post_meta($idea_id, '_abs_neurodifference_description', $neuro_desc);
			}

			if ('' !== $char_name) {
				update_post_meta($idea_id, '_abs_character_name', $char_name);
			}

			if ('' !== $char_role) {
				update_post_meta($idea_id, '_abs_character_role', $char_role);
			}

			if ('' !== $char_desc) {
				update_post_meta($idea_id, '_abs_character_description', $char_desc);
			}

			$ideas[$index]['neuro']     = $neuro_name;
			$ideas[$index]['character'] = $char_name;
			$ideas[$index]['id']        = $idea_id;
		}

		wp_send_json_success(['ideas' => $ideas]);
	}

	public static function ajax_generate_story(): void {
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$title       = isset($_POST['title']) ? sanitize_text_field((string) $_POST['title']) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field((string) $_POST['description']) : '';

		$idea_id = isset($_POST['idea_id']) ? absint($_POST['idea_id']) : 0;
		$idea    = $idea_id > 0 ? get_post($idea_id) : null;
		$is_idea = $idea instanceof WP_Post && $idea->post_type === (class_exists('ABS_CPT') ? ABS_CPT::IDEA_POST_TYPE : 'abs_idea');

		if ($is_idea && $idea) {
			$idea_title       = get_the_title($idea);
			$idea_description = (string) get_post_meta($idea->ID, '_abs_idea_description', true);

			if (is_string($idea_title) && '' !== trim($idea_title)) {
				$title = sanitize_text_field($idea_title);
			}

			if ('' !== trim($idea_description)) {
				$description = sanitize_textarea_field($idea_description);
			}
		}

		if ('' === $title || '' === $description) {
			wp_send_json_error(['message' => 'Missing idea title or description.'], 400);
		}

		$prompt = (string) ABS_Settings::get('story_prompt');
		$prompt = str_replace(
			['{title}', '{description}'],
			[$title, $description],
			$prompt
		);

		$neuro_context = '';

		if ($is_idea && $idea) {
			$neuro_name = trim((string) get_post_meta($idea->ID, '_abs_neurodifference', true));

			if ('' !== $neuro_name) {
				$neuro_description = trim((string) get_post_meta($idea->ID, '_abs_neurodifference_description', true));
				$neuro_context     = '' === $neuro_description ? $neuro_name : $neuro_name . ' — ' . $neuro_description;
			}
		}

		$char           = isset($_POST['char']) ? (int) $_POST['char'] : -1;
		$char_items     = ABS_Settings::characters();
		$live_char_item = $char >= 0 ? ($char_items[absint($char)] ?? null) : null;

		if ('' === $neuro_context) {
			$live_neuro_name = is_array($live_char_item) ? trim((string) ($live_char_item['neuro'] ?? '')) : '';
			$live_neuro_item = '' !== $live_neuro_name ? ABS_Settings::character_neuro($live_neuro_name) : null;

			if (is_array($live_neuro_item)) {
				$live_neuro_resolved_name = trim((string) ($live_neuro_item['name'] ?? ''));
				$live_neuro_resolved_desc = trim((string) ($live_neuro_item['description'] ?? ''));
				$neuro_context            = '' === $live_neuro_resolved_desc ? $live_neuro_resolved_name : $live_neuro_resolved_name . ' — ' . $live_neuro_resolved_desc;
			}
		}

		$prompt = str_replace('{neurodifference}', '' !== $neuro_context ? $neuro_context : ABS_Settings::neurodifference_context(-1), $prompt);

		$character_context = '';
		$char_name         = '';
		$char_role         = '';
		$char_description  = '';

		if ($is_idea && $idea) {
			$char_name = trim((string) get_post_meta($idea->ID, '_abs_character_name', true));

			if ('' !== $char_name) {
				$char_role        = trim((string) get_post_meta($idea->ID, '_abs_character_role', true));
				$char_description = trim((string) get_post_meta($idea->ID, '_abs_character_description', true));
			}
		}

		if ('' === $char_name) {
			if (is_array($live_char_item) && '' !== trim((string) ($live_char_item['name'] ?? ''))) {
				$char_name        = trim((string) $live_char_item['name']);
				$char_role        = trim((string) ($live_char_item['character'] ?? ''));
				$char_description = trim((string) ($live_char_item['description'] ?? ''));
			}
		}

		if ('' !== $char_name) {
			if ('' !== $char_role && '' !== $char_description) {
				$character_context = $char_name . ' (' . $char_role . ') — ' . $char_description;
			} elseif ('' !== $char_role) {
				$character_context = $char_name . ' (' . $char_role . ')';
			} elseif ('' !== $char_description) {
				$character_context = $char_name . ' — ' . $char_description;
			} else {
				$character_context = $char_name;
			}
		}

		$prompt = str_replace('{character}', '' !== $character_context ? $character_context : ABS_Settings::character_context($char), $prompt);

		if (function_exists('set_time_limit')) {
			@set_time_limit(180);
		}

		$config = ABS_Settings::task_config('story');

		$result = ABS_API::json(
			[['role' => 'user', 'content' => $prompt]],
			[
				'max_tokens'  => 4096,
				'temperature' => 0.8,
				'timeout'     => 150,
			],
			$config
		);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => $result->get_error_message()]);
		}

		$story = isset($result['story']) && is_string($result['story']) ? trim($result['story']) : '';

		if ('' === $story) {
			wp_send_json_error(['message' => 'The AI returned no story text. Please try again.']);
		}

		$returned_title = isset($result['title']) && is_string($result['title']) ? trim($result['title']) : '';
		$story_title    = '' !== $returned_title ? sanitize_text_field($returned_title) : $title;
		$story_text     = $story;

		$post_type = class_exists('ABS_CPT') ? ABS_CPT::POST_TYPE : 'ai_story';

		$id = wp_insert_post(
			[
				'post_type'    => $post_type,
				'post_status'  => 'draft',
				'post_title'   => $story_title,
				'post_content' => $story_text,
				'post_author'  => get_current_user_id(),
			],
			true
		);

		if (is_wp_error($id)) {
			wp_send_json_error(['message' => $id->get_error_message()]);
		}

		$id = (int) $id;

		update_post_meta($id, '_abs_idea_description', $description);

		if ('' !== $char_name) {
			update_post_meta($id, '_abs_character_name', $char_name);
		}

		if ('' !== $char_role) {
			update_post_meta($id, '_abs_character_role', $char_role);
		}

		if ('' !== $char_description) {
			update_post_meta($id, '_abs_character_description', $char_description);
		}

		if ($is_idea) {
			wp_delete_post($idea_id, true);
		}

		$edit_link = get_edit_post_link($id, 'raw');

		wp_send_json_success(
			[
				'story_id'  => $id,
				'edit_link' => is_string($edit_link) ? $edit_link : '',
				'title'     => $story_title,
				'words'     => str_word_count(wp_strip_all_tags($story_text)),
			]
		);
	}

	public static function ajax_discard_idea(): void {
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$id = isset($_POST['idea_id']) ? absint($_POST['idea_id']) : 0;

		$post = $id > 0 ? get_post($id) : null;

		if (!$post instanceof WP_Post || $post->post_type !== (class_exists('ABS_CPT') ? ABS_CPT::IDEA_POST_TYPE : 'abs_idea')) {
			wp_send_json_error(['message' => 'Idea not found.'], 404);
		}

		wp_delete_post($id, true);

		wp_send_json_success(['deleted' => $id]);
	}

	public static function ajax_update_idea(): void {
		if (!check_ajax_referer(ABS_Settings::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Security check failed.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		$id   = isset($_POST['idea_id']) ? absint($_POST['idea_id']) : 0;
		$post = $id > 0 ? get_post($id) : null;

		if (!$post instanceof WP_Post || $post->post_type !== (class_exists('ABS_CPT') ? ABS_CPT::IDEA_POST_TYPE : 'abs_idea')) {
			wp_send_json_error(['message' => 'Idea not found.'], 404);
		}

		$title       = isset($_POST['title']) ? sanitize_text_field((string) wp_unslash((string) $_POST['title'])) : '';
		$description = isset($_POST['description']) ? sanitize_textarea_field((string) wp_unslash((string) $_POST['description'])) : '';

		if ('' === $title || '' === $description) {
			wp_send_json_error(['message' => 'Missing idea title or description.'], 400);
		}

		$updated = wp_update_post(
			[
				'ID'         => $id,
				'post_title' => $title,
			],
			true
		);

		if (is_wp_error($updated)) {
			wp_send_json_error(['message' => $updated->get_error_message()]);
		}

		update_post_meta($id, '_abs_idea_description', $description);

		wp_send_json_success(
			[
				'id'          => $id,
				'title'       => $title,
				'description' => $description,
			]
		);
	}
}
