<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Library {

	const SHORTCODE  = 'abs_audio_library';
	const PAGE_OPTION = 'abs_library_page_id';

	public static function init(): void {
		add_shortcode(self::SHORTCODE, [self::class, 'render']);
		add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
	}

	public static function register_assets(): void {
		wp_register_style('abs-library', ABS_PLUGIN_URL . 'assets/library.css', [], ABS_VERSION);
		wp_register_script('abs-library', ABS_PLUGIN_URL . 'assets/library.js', [], ABS_VERSION, true);
		wp_localize_script('abs-library', 'absListen', ABS_Listen::listen_config());
	}

	public static function ensure_page(): void {
		$page_id = (int) get_option(self::PAGE_OPTION);

		if ($page_id > 0 && get_post($page_id) instanceof WP_Post && 'page' === get_post_type($page_id)) {
			return;
		}

		$page_id = wp_insert_post([
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Helijutud',
			'post_name'    => 'helijutud',
			'post_content' => '[' . self::SHORTCODE . ']',
		], true);

		if (is_wp_error($page_id) || $page_id < 1) {
			return;
		}

		update_option(self::PAGE_OPTION, (int) $page_id);
	}

	public static function tracks(): array {
		$stories = get_posts([
			'post_type'        => ABS_CPT::POST_TYPE,
			'post_status'      => 'publish',
			'numberposts'      => 200,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
			'meta_query'       => [
				[
					'key'     => '_abs_audio_full',
					'compare' => 'EXISTS',
				],
			],
		]);

		$tracks = [];

		foreach ($stories as $story) {
			$full = get_post_meta($story->ID, '_abs_audio_full', true);
			$url  = is_array($full) ? (string) ($full['url'] ?? '') : '';

			if ('' === $url) {
				continue;
			}

			$published_id = (int) get_post_meta($story->ID, '_abs_published_post_id', true);
			$published    = $published_id > 0 ? get_post($published_id) : null;

			if (!($published instanceof WP_Post) || 'publish' !== $published->post_status) {
				continue;
			}

			$image = get_the_post_thumbnail_url($published_id, 'medium_large');
			$intro = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $story->post_content)));

			$tracks[] = [
				'title'  => (string) get_the_title($story),
				'intro'  => '' !== $intro ? wp_trim_words($intro, 22, '…') : '',
				'url'    => $url,
				'image'  => is_string($image) && '' !== $image ? $image : '',
				'story'  => $story->ID,
				'post'   => $published_id,
				'link'   => (string) get_permalink($published_id),
			];
		}

		return $tracks;
	}

	public static function render(): string {
		if (!is_user_logged_in()) {
			return ABS_Frontend::login_banner('Helijutte saavad kuulata sisseloginud kasutajad — logi sisse ja vali endale meelepärane lugu.', (string) get_permalink());
		}

		wp_enqueue_style('abs-library');
		wp_enqueue_script('abs-library');

		$tracks = self::tracks();

		if ([] === $tracks) {
			return '<p class="abs-lib-empty">' . esc_html('Siin veel helijutte ei ole.') . '</p>';
		}

		ob_start();
		?>
		<div class="abs-lib-grid">
			<?php foreach ($tracks as $track) : ?>
				<div class="abs-lib-card<?php echo '' === $track['image'] ? ' abs-lib-noimg' : ''; ?>" data-src="<?php echo esc_url($track['url']); ?>" data-post="<?php echo esc_attr((string) $track['post']); ?>" data-title="<?php echo esc_attr($track['title']); ?>" data-image="<?php echo esc_url($track['image']); ?>">
					<div class="abs-lib-cover">
						<?php if ('' !== $track['image']) : ?>
							<img src="<?php echo esc_url($track['image']); ?>" alt="<?php echo esc_attr($track['title']); ?>" loading="lazy" />
						<?php else : ?>
							<span class="abs-lib-note" aria-hidden="true">♪</span>
						<?php endif; ?>
						<button type="button" class="abs-lib-play" aria-label="<?php echo esc_attr('Esita: ' . $track['title']); ?>">▶</button>
					</div>
					<div class="abs-lib-meta">
						<span class="abs-lib-title"><?php echo esc_html($track['title']); ?></span>
						<?php if ('' !== $track['intro']) : ?>
							<span class="abs-lib-intro"><?php echo esc_html($track['intro']); ?></span>
						<?php endif; ?>
						<a class="abs-lib-link" href="<?php echo esc_url($track['link']); ?>"><?php echo esc_html('Loe lugu'); ?></a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="abs-lib-player" id="absLibPlayer" hidden>
			<span class="abs-lib-player-cover" aria-hidden="true"></span>
			<span class="abs-lib-player-title"></span>
			<span class="abs-lib-time abs-lib-cur">0:00</span>
			<input type="range" class="abs-lib-seek" min="0" max="1000" value="0" step="1" aria-label="<?php echo esc_attr('Helipositsioon'); ?>" />
			<span class="abs-lib-time abs-lib-dur">0:00</span>
			<button type="button" class="abs-lib-auto" aria-pressed="false" aria-label="<?php echo esc_attr('Järgmine automaatselt'); ?>" title="<?php echo esc_attr('Järgmine automaatselt'); ?>">↻</button>
			<button type="button" class="abs-lib-toggle" aria-label="<?php echo esc_attr('Peata'); ?>">❚❚</button>
			<button type="button" class="abs-lib-close" aria-label="<?php echo esc_attr('Sulge'); ?>">✕</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
