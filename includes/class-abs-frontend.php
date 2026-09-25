<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Frontend {

	public static function init(): void {
		add_filter('the_content', [self::class, 'prepend_audio'], 20);
	}

	public static function prepend_audio($content) {
		if (!is_singular('post') || !is_main_query()) {
			return $content;
		}

		$post = get_post();

		if (!($post instanceof WP_Post) || get_queried_object_id() !== (int) $post->ID) {
			return $content;
		}

		$html = self::build_audio_html((int) $post->ID);

		if ('' === $html) {
			return $content;
		}

		if (!is_user_logged_in()) {
			return self::login_banner('Sellel jutul on ka heliversioon — kuulamine on saadaval sisseloginud kasutajatele.', get_permalink($post)) . "\n" . $content;
		}

		return $html . "\n" . $content;
	}

	public static function login_banner(string $text, string $redirect): string {
		$style  = 'display:flex;flex-wrap:wrap;align-items:center;gap:12px;justify-content:space-between;margin:0 0 1.5em;padding:16px 20px;border-radius:12px;background:#f4f1ff;border:1px solid #ddd6fe;color:#3730a3;font-size:15px;line-height:1.5';
		$span   = 'flex:1 1 220px';
		$btn    = 'display:inline-flex;align-items:center;padding:10px 18px;border-radius:999px;background:#6d5df6;color:#fff;text-decoration:none;font-weight:600;font-size:14px;white-space:nowrap';

		return '<div class="abs-audio-banner" style="' . esc_attr($style) . '"><span style="' . esc_attr($span) . '">' . esc_html($text) . '</span><a href="' . esc_url(wp_login_url($redirect)) . '" style="' . esc_attr($btn) . '">' . esc_html('Logi sisse') . '</a></div>';
	}

	public static function build_audio_html(int $post_id): string {
		$story_id = (int) get_post_meta($post_id, '_abs_source_story_id', true);

		if ($story_id < 1) {
			return '';
		}

		$full = get_post_meta($story_id, '_abs_audio_full', true);
		$url  = is_array($full) ? esc_url_raw((string) ($full['url'] ?? '')) : '';

		if ('' === $url) {
			return '';
		}

		return '<div class="abs-story-audio" style="margin:0 0 1.5em"><audio controls preload="none" style="width:100%" src="' . esc_url($url) . '"></audio></div>';
	}
}
