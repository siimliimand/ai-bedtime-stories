<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Publish {

	const NONCE_PREFIX = 'abs_publish_';

	public static function init(): void {
		add_action('admin_post_abs_publish_story', [self::class, 'handle_publish']);
		add_action('add_meta_boxes', [self::class, 'add_meta_box']);
		add_action('admin_notices', [self::class, 'admin_notices']);
		add_filter('post_row_actions', [self::class, 'filter_row_actions'], 10, 2);
		add_action('save_post_' . ABS_CPT::POST_TYPE, [self::class, 'sync_published_post'], 20, 3);
	}

	public static function sync_published_post(int $post_id, WP_Post $post, bool $update): void {
		if (!$update || wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
			return;
		}

		$published_id = (int) get_post_meta($post_id, '_abs_published_post_id', true);

		if ($published_id < 1) {
			return;
		}

		$published = get_post($published_id);

		if (!($published instanceof WP_Post) || 'post' !== $published->post_type) {
			return;
		}

		if ($published->post_content === $post->post_content && $published->post_title === $post->post_title) {
			return;
		}

		wp_update_post([
			'ID'           => $published_id,
			'post_title'   => $post->post_title,
			'post_content' => $post->post_content,
		]);
	}

	public static function filter_row_actions(array $actions, WP_Post $post): array {
		if (ABS_CPT::POST_TYPE !== get_post_type($post)) {
			return $actions;
		}

		$published_id = (int) get_post_meta($post->ID, '_abs_published_post_id', true);

		if ($published_id > 0 && get_post($published_id) instanceof WP_Post) {
			$permalink = get_permalink($published_id);

			if (is_string($permalink) && '' !== $permalink) {
				$actions['abs_view_blog_post'] = sprintf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
					esc_url($permalink),
					esc_html__('View blog post', 'ai-bedtime-stories')
				);
			}

			return $actions;
		}

		$actions['abs_publish_story'] = sprintf(
			'<a href="%1$s" onclick="%2$s">%3$s</a>',
			esc_url(self::build_publish_url($post->ID)),
			esc_attr('return confirm("' . esc_js(__('Publish this story as a blog post?', 'ai-bedtime-stories')) . '");'),
			esc_html__('Publish as blog post', 'ai-bedtime-stories')
		);

		return $actions;
	}

	public static function add_meta_box(): void {
		if (!class_exists(ABS_CPT::class)) {
			return;
		}

		add_meta_box(
			'abs_story_publish',
			__('Publishing', 'ai-bedtime-stories'),
			[self::class, 'render_meta_box'],
			ABS_CPT::POST_TYPE,
			'side',
			'high'
		);
	}

	public static function render_meta_box(WP_Post $post): void {
		$idea         = (string) get_post_meta($post->ID, '_abs_idea_description', true);
		$published_id = (int) get_post_meta($post->ID, '_abs_published_post_id', true);
		$is_published = $published_id > 0 && get_post($published_id) instanceof WP_Post;
		?>
		<div class="abs-publish-meta-box">
			<?php if ('' !== $idea) : ?>
				<p><strong><?php echo esc_html__('Idea:', 'ai-bedtime-stories'); ?></strong> <?php echo esc_html($idea); ?></p>
			<?php endif; ?>

			<?php if ($is_published) : ?>
				<?php $permalink = get_permalink($published_id); ?>
				<p>
					<strong><?php echo esc_html__('Published as blog post:', 'ai-bedtime-stories'); ?></strong>
					<?php if (is_string($permalink) && '' !== $permalink) : ?>
						<a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View post', 'ai-bedtime-stories'); ?></a>
					<?php endif; ?>
					<?php $edit_link = get_edit_post_link($published_id); ?>
					<?php if (is_string($edit_link) && '' !== $edit_link) : ?>
						<a href="<?php echo esc_url($edit_link); ?>"><?php echo esc_html__('Edit post', 'ai-bedtime-stories'); ?></a>
					<?php endif; ?>
				</p>
			<?php else : ?>
				<p>
					<a
						class="button button-primary"
						href="<?php echo esc_url(self::build_publish_url($post->ID)); ?>"
						onclick="<?php echo esc_attr('return confirm("' . esc_js(__('Publish this story as a blog post?', 'ai-bedtime-stories')) . '");'); ?>"
					><?php echo esc_html__('Publish as blog post', 'ai-bedtime-stories'); ?></a>
				</p>
				<p class="description">
					<?php echo esc_html__('Creates a regular blog post from this story and assigns the category from AI Stories → Settings.', 'ai-bedtime-stories'); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_publish(): void {
		if (!current_user_can('manage_options')) {
			wp_die(esc_html__('Permission denied.', 'ai-bedtime-stories'));
		}

		$story_id = absint($_GET['story_id'] ?? 0);
		check_admin_referer(self::NONCE_PREFIX . $story_id);

		$redirect_url = admin_url('edit.php?post_type=' . ABS_CPT::POST_TYPE);
		$story        = get_post($story_id);

		if (!($story instanceof WP_Post) || ABS_CPT::POST_TYPE !== $story->post_type) {
			wp_safe_redirect(add_query_arg(
				['abs_notice' => 'error_missing', 'story_id' => $story_id],
				$redirect_url
			));
			exit;
		}

		$existing = (int) get_post_meta($story_id, '_abs_published_post_id', true);

		if ($existing > 0 && get_post($existing) instanceof WP_Post) {
			wp_safe_redirect(add_query_arg(
				['abs_notice' => 'error_duplicate', 'story_id' => $story_id],
				$redirect_url
			));
			exit;
		}

		$cats   = [];
		$cat_id = class_exists(ABS_Settings::class) ? (int) ABS_Settings::get('post_category') : 0;
		$term   = $cat_id > 0 ? get_term($cat_id, 'category') : null;

		if ($term instanceof WP_Term && !is_wp_error($term)) {
			$cats = [$cat_id];
		}

		$post_id = wp_insert_post([
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_title'    => $story->post_title,
			'post_content'  => $story->post_content,
			'post_author'   => $story->post_author ?: get_current_user_id(),
			'post_category' => $cats,
		], true);

		if (is_wp_error($post_id) || ! $post_id) {
			wp_safe_redirect(add_query_arg(
				['abs_notice' => 'error_insert', 'story_id' => $story_id],
				$redirect_url
			));
			exit;
		}

		update_post_meta((int) $post_id, '_abs_source_story_id', $story_id);
		update_post_meta($story_id, '_abs_published_post_id', (int) $post_id);
		wp_update_post(['ID' => $story_id, 'post_status' => 'publish']);

		$cover_id = (int) get_post_meta($story_id, '_abs_cover_attachment', true);

		if ($cover_id > 0 && wp_get_attachment_image_url($cover_id, 'thumbnail')) {
			set_post_thumbnail((int) $post_id, $cover_id);
		}

		wp_safe_redirect(add_query_arg(
			['abs_notice' => 'published', 'ids' => (int) $post_id],
			$redirect_url
		));
		exit;
	}

	public static function admin_notices(): void {
		if (!function_exists('get_current_screen')) {
			return;
		}

		$screen     = get_current_screen();
		$query_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';

		$is_story_screen = ($screen instanceof WP_Screen && ABS_CPT::POST_TYPE === $screen->post_type)
			|| ABS_CPT::POST_TYPE === $query_type;

		if (!$is_story_screen) {
			return;
		}

		$notice = isset($_GET['abs_notice']) ? sanitize_key((string) $_GET['abs_notice']) : '';

		if ('' === $notice) {
			return;
		}

		if ('published' === $notice) {
			$post_id   = isset($_GET['ids']) ? absint($_GET['ids']) : 0;
			$permalink = $post_id > 0 ? get_permalink($post_id) : false;
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php echo esc_html__('Story published as a blog post.', 'ai-bedtime-stories'); ?>
					<?php if ($post_id > 0 && is_string($permalink) && '' !== $permalink) : ?>
						<a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View post', 'ai-bedtime-stories'); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<?php
			return;
		}

		if ('error_missing' === $notice) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html__('The story could not be found. It may have been deleted.', 'ai-bedtime-stories'); ?></p>
			</div>
			<?php
			return;
		}

		if ('error_duplicate' === $notice) {
			$story_id    = isset($_GET['story_id']) ? absint($_GET['story_id']) : 0;
			$existing_id = $story_id > 0 ? (int) get_post_meta($story_id, '_abs_published_post_id', true) : 0;
			$permalink   = ($existing_id > 0 && get_post($existing_id) instanceof WP_Post) ? get_permalink($existing_id) : false;
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<?php echo esc_html__('This story has already been published as a blog post.', 'ai-bedtime-stories'); ?>
					<?php if ($existing_id > 0 && is_string($permalink) && '' !== $permalink) : ?>
						<a href="<?php echo esc_url($permalink); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('View post', 'ai-bedtime-stories'); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<?php
			return;
		}

		if ('error_insert' === $notice) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html__('The blog post could not be created. Please try again.', 'ai-bedtime-stories'); ?></p>
			</div>
			<?php
		}
	}

	private static function build_publish_url(int $story_id): string {
		return wp_nonce_url(
			admin_url('admin-post.php?action=abs_publish_story&story_id=' . $story_id),
			self::NONCE_PREFIX . $story_id
		);
	}
}
