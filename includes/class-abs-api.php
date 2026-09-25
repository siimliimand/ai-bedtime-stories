<?php
if (!defined('ABSPATH')) {
	exit;
}

class ABS_API {

	public static function chat(array $messages, array $args = [], ?array $config = null): array|WP_Error {
		if (null === $config) {
			$config = class_exists('ABS_Settings') && method_exists('ABS_Settings', 'task_config')
				? ABS_Settings::task_config('story')
				: [];
		}

		$provider = (string) ($config['provider'] ?? 'zai');
		$api_key  = trim((string) ($config['api_key'] ?? ''));
		$base_url = trim((string) ($config['base_url'] ?? ''));
		$model    = trim((string) ($config['model'] ?? ''));

		if ('' === $base_url) {
			$base_url = 'https://api.z.ai/api/coding/paas/v4';
			$provider = 'zai';
		}

		$label = class_exists('ABS_Settings') && method_exists('ABS_Settings', 'provider_label')
			? ABS_Settings::provider_label($provider)
			: $provider;

		if ('' === $api_key) {
			return new WP_Error('abs_no_api_key', $label . ' API key is not configured. Go to AI Stories → Settings.');
		}

		if ('' === $model) {
			$model = 'glm-5.3-flash';
		}

		$headers = [
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		];

		$payload = [
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => (float) ($args['temperature'] ?? 1.0),
			'max_tokens'  => (int) ($args['max_tokens'] ?? 4096),
		];

		if ('openrouter' === $provider) {
			$headers['HTTP-Referer'] = home_url('/');
			$headers['X-Title']      = 'AI Bedtime Stories';
		} else {
			$payload['thinking'] = $args['thinking'] ?? ['type' => 'disabled'];
		}

		$response = wp_remote_post(
			rtrim($base_url, '/') . '/chat/completions',
			[
				'headers'     => $headers,
				'body'        => (string) wp_json_encode($payload),
				'timeout'     => (int) ($args['timeout'] ?? 120),
				'data_format' => 'body',
			]
		);

		if (is_wp_error($response)) {
			return new WP_Error('abs_http', 'Could not reach the ' . $label . ' API: ' . $response->get_error_message());
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = (string) wp_remote_retrieve_body($response);

		if (401 === $code) {
			return new WP_Error('abs_auth', 'Invalid API key (401). Check the key in AI Stories → Settings.');
		}

		if (429 === $code) {
			return new WP_Error('abs_rate', 'Rate limit reached (429). Your plan quota may be exhausted — try again later.');
		}

		if (200 !== $code) {
			return new WP_Error('abs_api', $label . ' API error (code ' . $code . '): ' . self::error_detail($body));
		}

		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			return new WP_Error('abs_parse', 'Invalid JSON response from ' . $label . ' API.');
		}

		$content = $decoded['choices'][0]['message']['content'] ?? null;
		if (!is_string($content) || '' === trim($content)) {
			return new WP_Error('abs_empty', 'The API returned an empty response.');
		}

		return [
			'content' => $content,
			'usage' => (array) ($decoded['usage'] ?? []),
		];
	}

	public static function image(string $prompt, ?array $config = null): array|WP_Error {
		if (null === $config) {
			$config = class_exists('ABS_Settings') && method_exists('ABS_Settings', 'task_config')
				? ABS_Settings::task_config('image')
				: [];
		}

		$provider = (string) ($config['provider'] ?? 'openrouter');
		$api_key  = trim((string) ($config['api_key'] ?? ''));
		$base_url = trim((string) ($config['base_url'] ?? ''));
		$model    = trim((string) ($config['model'] ?? ''));

		if ('openrouter' !== $provider) {
			return new WP_Error('abs_image_provider', 'Kaanepildi genereerimine toetab ainult OpenRouter pakkujat. Vali AI Stories → Settings → "Jutu kaanepilt" pakkuja OpenRouter.');
		}

		if ('' === $base_url) {
			$base_url = 'https://openrouter.ai/api/v1';
		}

		if ('' === $api_key) {
			return new WP_Error('abs_no_api_key', 'OpenRouter API key is not configured. Go to AI Stories → Settings.');
		}

		if ('' === $model) {
			$model = 'openai/gpt-image-2.5-sunburst';
		}

		$response = wp_remote_post(
			rtrim($base_url, '/') . '/images',
			[
				'headers'     => [
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
					'HTTP-Referer'  => home_url('/'),
					'X-Title'       => 'AI Bedtime Stories',
				],
				'body'        => (string) wp_json_encode([
					'model'        => $model,
					'prompt'       => $prompt,
					'n'            => 1,
					'aspect_ratio' => '16:9',
				]),
				'timeout'     => 300,
				'data_format' => 'body',
			]
		);

		if (is_wp_error($response)) {
			return new WP_Error('abs_http', 'Could not reach the OpenRouter image API: ' . $response->get_error_message());
		}

		$code = (int) wp_remote_retrieve_response_code($response);
		$body = (string) wp_remote_retrieve_body($response);

		if (401 === $code) {
			return new WP_Error('abs_auth', 'Invalid API key (401). Check the OpenRouter key in AI Stories → Settings.');
		}

		if (402 === $code) {
			return new WP_Error('abs_credits', 'Insufficient OpenRouter credits (402). Top up your account and try again.');
		}

		if (429 === $code) {
			return new WP_Error('abs_rate', 'Rate limit reached (429). Try again later.');
		}

		if (200 !== $code) {
			return new WP_Error('abs_api', 'OpenRouter image API error (code ' . $code . '): ' . self::error_detail($body));
		}

		$decoded = json_decode($body, true);
		if (!is_array($decoded)) {
			return new WP_Error('abs_parse', 'Invalid JSON response from OpenRouter image API.');
		}

		$b64 = $decoded['data'][0]['b64_json'] ?? null;
		if (!is_string($b64) || '' === trim($b64)) {
			return new WP_Error('abs_empty', 'The image API returned no image data.');
		}

		return [
			'b64'        => $b64,
			'media_type' => (string) ($decoded['data'][0]['media_type'] ?? 'image/png'),
			'usage'      => (array) ($decoded['usage'] ?? []),
		];
	}

	public static function json(array $messages, array $args = [], ?array $config = null): array|WP_Error {
		if (!array_key_exists('max_tokens', $args)) {
			$args['max_tokens'] = 4096;
		}

		$result = self::chat($messages, $args, $config);
		if (is_wp_error($result)) {
			return $result;
		}

		return self::decode_json((string) $result['content']);
	}

	private static function decode_json(string $content): array|WP_Error {
		$plain = self::normalize($content);

		$decoded = self::try_decode($plain);
		if (null !== $decoded) {
			return $decoded;
		}

		$stripped = self::strip_fences($plain);
		if ($stripped !== $plain) {
			$decoded = self::try_decode($stripped);
			if (null !== $decoded) {
				return $decoded;
			}
		}

		foreach ([$plain, $stripped] as $candidate) {
			$sliced = self::slice_outermost($candidate);
			if (null === $sliced) {
				continue;
			}
			$decoded = self::try_decode($sliced);
			if (null !== $decoded) {
				return $decoded;
			}
		}

		return new WP_Error('abs_json', 'The AI response was not valid JSON. Raw response starts with: ' . self::snippet($content, 200));
	}

	private static function normalize(string $content): string {
		$str = trim($content);
		if (str_starts_with($str, "\xEF\xBB\xBF")) {
			$str = ltrim(substr($str, 3));
		}
		return $str;
	}

	private static function strip_fences(string $str): string {
		if (!str_contains($str, '```')) {
			return $str;
		}

		$str = (string) preg_replace('/^\s*```[a-zA-Z0-9_-]*\s*/i', '', $str);
		$str = (string) preg_replace('/\s*```\s*$/i', '', $str);

		return trim($str);
	}

	private static function slice_outermost(string $str): ?string {
		$open = array_filter(
			[strpos($str, '{'), strpos($str, '[')],
			static fn($pos): bool => false !== $pos
		);
		$close = array_filter(
			[strrpos($str, '}'), strrpos($str, ']')],
			static fn($pos): bool => false !== $pos
		);

		if ([] === $open || [] === $close) {
			return null;
		}

		$start = min($open);
		$end = max($close);

		if ($end <= $start) {
			return null;
		}

		return substr($str, $start, $end - $start + 1);
	}

	private static function try_decode(string $str): ?array {
		$decoded = json_decode($str, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		$cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str);
		if (!is_string($cleaned) || '' === $cleaned || $cleaned === $str) {
			return null;
		}

		$decoded = json_decode($cleaned, true, 512, JSON_INVALID_UTF8_SUBSTITUTE);

		return is_array($decoded) ? $decoded : null;
	}

	private static function error_detail(string $body): string {
		$decoded = json_decode($body, true);
		if (is_array($decoded)) {
			foreach ([$decoded['error']['message'] ?? null, $decoded['message'] ?? null] as $candidate) {
				if (is_string($candidate) && '' !== trim($candidate)) {
					return trim($candidate);
				}
			}
		}

		$raw = trim($body);
		if ('' === $raw) {
			return 'No response body.';
		}

		return self::snippet($raw, 300);
	}

	private static function snippet(string $text, int $length): string {
		return function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
	}
}
