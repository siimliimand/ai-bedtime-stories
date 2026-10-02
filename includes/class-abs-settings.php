<?php

if (!defined('ABSPATH')) {
	exit;
}

class ABS_Settings {

	const OPTION       = 'abs_settings';
	const NONCE_ACTION = 'abs_admin';

	const NEURO_OPTION = 'abs_neurodifferences';
	const CHAR_OPTION  = 'abs_characters';
	const VOICE_OPTION = 'abs_audio_voices';

	private const OLD_IDEAS_PROMPT = "Sa oled kogenud lastekirjanik. Genereeri {count} uue lastele mõeldud unejutu ideed, olles inspireeritud vendade Grimmide muinasjutude stiilist: klassikalised muinasjutuelemendid (mets, loss, loomad, võlu), keskaegne õhustik, kuid sisu peab olema lapsele sobiv ja lõpp rahumeelne. Iga idee peab olema ainulaadne ega tohi kattuda nende olemasolevate juttudega: {existing_titles}\n\nVasta AINULT kehtiva JSON-massiivina ilma markdowni koodiblokita:\n[{\"title\": \"Jutu pealkiri eesti keeles\", \"description\": \"1-2 lause pikk kirjeldus eesti keeles\"}]";

	private const OLD_STORY_PROMPT = "Kirjuta eestikeelne unejutt, olles inspireeritud vendade Grimmide muinasjutude stiilist ja pikkusest (800–1500 sõna). Kasuta klassikalisi muinasjutuelemente (mets, võlukunst, kuningriik, loomad, kordused ja laululised vormelid), kuid hoia sisu lastele sobivana, ära tee liigselt hirmu ja lõpeta rahumeelse, uinutava ja lohutava lõpuga. Jutt peab sobima ettelugemiseks lastele enne magamaminekut.\n\nPealkiri: {title}\nKirjeldus: {description}\n\nVasta AINULT kehtiva JSON-objektina ilma markdowni koodiblokita:\n{\"title\": \"Pealkiri eesti keeles\", \"story\": \"Täielik jutt eesti keeles\"}";

	private const PREV_IDEAS_PROMPT = "Sa oled kogenud lastekirjanik. Genereeri {count} uue lastele mõeldud unejutu ideed, olles inspireeritud vendade Grimmide muinasjutude stiilist: klassikalised muinasjutuelemendid (mets, loss, loomad, võlu), keskaegne õhustik, kuid sisu peab olema lapsele sobiv ja lõpp rahumeelne. Iga idee peab olema kohandatud lapsele, kellel on järgmine neuroerisus: {neurodifference}. Iga idee peab olema ainulaadne ega tohi kattuda nende olemasolevate juttudega: {existing_titles}\n\nVasta AINULT kehtiva JSON-massiivina ilma markdowni koodiblokita:\n[{\"title\": \"Jutu pealkiri eesti keeles\", \"description\": \"1-2 lause pikk kirjeldus eesti keeles\"}]";

	private const PREV_STORY_PROMPT = "Kirjuta eestikeelne unejutt, olles inspireeritud vendade Grimmide muinasjutude stiilist ja pikkusest (800–1500 sõna). Kasuta klassikalisi muinasjutuelemente (mets, võlukunst, kuningriik, loomad, kordused ja laululised vormelid), kuid hoia sisu lastele sobivana, ära tee liigselt hirmu ja lõpeta rahumeelse, uinutava ja lohutava lõpuga. Jutt peab sobima ettelugemiseks lastele enne magamaminekut. Kohanda lugu sensitiiivselt lapsega, kellel on järgmine neuroerisus: {neurodifference}.\n\nPealkiri: {title}\nKirjeldus: {description}\n\nVasta AINULT kehtiva JSON-objektina ilma markdowni koodiblokita:\n{\"title\": \"Pealkiri eesti keeles\", \"story\": \"Täielik jutt eesti keeles\"}";

	public static function defaults(): array {
		return [
			'api_key'              => '',
			'base_url'             => 'https://api.z.ai/api/coding/paas/v4',
			'model'                => 'glm-5.3-flash',
			'custom_model'         => '',
			'openrouter_api_key'   => '',
			'openrouter_base_url'  => 'https://openrouter.ai/api/v1',
			'ideas_provider'       => 'zai',
			'ideas_model'          => 'glm-5.3-flash',
			'ideas_model_custom'   => '',
			'story_provider'       => 'zai',
			'story_model'          => 'glm-5.3-flash',
			'story_model_custom'   => '',
			'image_provider'       => 'openrouter',
			'image_model'          => 'openai/gpt-image-2.5-sunburst',
			'image_model_custom'   => '',
			'audio_provider'       => 'zai',
			'audio_model'          => 'glm-5.3-flash',
			'audio_model_custom'   => '',
			'tts_api_key'          => '',
			'tts_stream'           => 1,
			'tts_stability'        => 0,
			'tts_similarity_boost' => 0,
			'tts_style'            => 0,
			'tts_speed'            => 0.8,
			'ideas_count'          => 5,
			'ideas_prompt'         => "Sa oled kogenud lastekirjanik. Genereeri {count} uue lastele mõeldud unejutu ideed, olles inspireeritud vendade Grimmide muinasjutude stiilist: klassikalised muinasjutuelemendid (mets, loss, loomad, võlu), keskaegne õhustik, kuid sisu peab olema lapsele sobiv ja lõpp rahumeelne. Iga idee peab olema kohandatud lapsele, kellel on järgmine neuroerisus: {neurodifference}. Iga idee peab hõlmama järgmist tegelast: {character}. Iga idee peab olema ainulaadne ega tohi kattuda nende olemasolevate juttudega: {existing_titles}\n\nVasta AINULT kehtiva JSON-massiivina ilma markdowni koodiblokita:\n[{\"title\": \"Jutu pealkiri eesti keeles\", \"description\": \"1-2 lause pikk kirjeldus eesti keeles\"}]",
			'story_prompt'         => "Kirjuta eestikeelne unejutt, olles inspireeritud vendade Grimmide muinasjutude stiilist ja pikkusest (800–1500 sõna). Kasuta klassikalisi muinasjutuelemente (mets, võlukunst, kuningriik, loomad, kordused ja laululised vormelid), kuid hoia sisu lastele sobivana, ära tee liigselt hirmu ja lõpeta rahumeelse, uinutava ja lohutava lõpuga. Jutt peab sobima ettelugemiseks lastele enne magamaminekut. Kohanda lugu sensitiiivselt lapsega, kellel on järgmine neuroerisus: {neurodifference}. Loo peategelaseks on järgmine tegelane: {character}. Kõik loo sündmused peavad sobima selle tegelase iseloomu ja eripäraga.\n\nPealkiri: {title}\nKirjeldus: {description}\n\nVasta AINULT kehtiva JSON-objektina ilma markdowni koodiblokita:\n{\"title\": \"Pealkiri eesti keeles\", \"story\": \"Täielik jutt eesti keeles\"}",
			'image_prompt'         => 'Laste muinasjutu kaanepilt jutule, mille pealkiri on "{TITLE}" ja kirjanik on "unejutud.ee" ja lugu: {STORY}',
			'audio_prompt'         => 'Analüüsi järgmist eestikeelset lastejuttu ja koosta sellest helisalvestuse skript struktureeritud JSON-i kujul. Jaga jutt loogilisteks kõneridadeks. Iga rida defineerib ühe kõnefragmendi. Ühel real tohib olla AINULT ühe kõneleja katkematu kõne. Kui lauses on tegelase kõne koos jutustaja selgitusega (nt „ütles kuninganna“, „küsis kuningas“, „vastas Joonas“), siis jaga see ALATI eraldi ridadeks: tegelase kõne oma reale tegelase nimega speaker-iks, jutustaja selgitus oma eraldi reale speaker-iga „Jutustaja“. Ära kunagi pane ühele reale nii tegelase kõnet kui ka jutustaja selgitust. Näide — sisend: „Meil on vaja abi,“ ütles kuninganna. „Mitte uut loitsu, vaid head mõtet.“ → tulemus: [{"speaker": "Kuninganna", "tone": "murelik", "pause_after_ms": 200, "text": "Meil on vaja abi,"}, {"speaker": "Jutustaja", "tone": "rahulik", "pause_after_ms": 200, "text": "ütles kuninganna."}, {"speaker": "Kuninganna", "tone": "jäik", "pause_after_ms": 400, "text": "Mitte uut loitsu, vaid head mõtet."}]. Määra igale reale: "speaker" — kõneleja: jutustuse puhul alati "Jutustaja", dialoogi puhul tegelase nimi; "tone" — kõneviisi/emotsiooni lühikirjeldus eesti keeles (nt rahulik, soe, põnev, üllatunud, õnnelik, uinuv); "pause_after_ms" — paus selle rea järel millisekundites (0–5000, tavaliselt 400; pikem paus lõiguvahel, lühem dialoogi vahel); "text" — kõneldav tekst sõna-sõnalt sisendjutust, midagi ära ära lisa ja ära muuda. Vasta AINULT kehtiva JSON-objektina ilma markdowni koodiblokita: {"lines": [{"speaker": "Jutustaja", "tone": "rahulik", "pause_after_ms": 400, "text": "..."}]}. Jutt: {story}',
			'post_category'        => 0,
		];
	}

	public static function seed_defaults(): void {
		if (false === get_option(self::OPTION)) {
			add_option(self::OPTION, self::defaults());
		}

		if (false === get_option(self::NEURO_OPTION)) {
			add_option(self::NEURO_OPTION, self::neuro_defaults());
		}

		if (false === get_option(self::VOICE_OPTION)) {
			add_option(self::VOICE_OPTION, self::voice_defaults());
		}
	}

	private static function neuro_defaults(): array {
		return [
			[
				'name'        => 'Autismispekter (ASD)',
				'description' => 'Eripärad sotsiaalses suhtluses ja mitteverbaalses kommunikatsioonis, sensoorne üli- või alatundlikkus (valgus, helid, tekstuurid), vajadus rutiini ja prognoositavuse järele ning sageli sügav, detailne pühendumine kindlatele erihuvidele.',
			],
			[
				'name'        => 'Aktiivsus- ja tähelepanuhäire (ATH / ADHD)',
				'description' => 'Eripärad dopamiinisüsteemis ja täitevfunktsioonides. Avaldub raskustena tähelepanu juhtimisel, hüperfookusena huvipakkuvatel teemadel, impulsiivsuses ning sageli füüsilises või vaimses rahutuses.',
			],
			[
				'name'        => 'Düsleksia (lugemiserinevus)',
				'description' => 'Raskused kirjaliku teksti dekodeerimisel, häälimisel, lugemiskiiruses ja õigekirjas, mis ei ole seotud inimese intellektuaalse võimekusega. Tihti kaasneb sellega tugev ruumiline ja tervikpildipõhine mõtlemine.',
			],
			[
				'name'        => 'Düskalkuulia (matemaatikaerinevus)',
				'description' => 'Spetsiifiline eripära arvulise info mõistmisel. Raskused koguste tajumisel, peast arvutamisel, matemaatiliste sümbolite tõlgendamisel ja aja või eelarve arvestamisel.',
			],
			[
				'name'        => 'Düsgraafia (kirjutamiserinevus)',
				'description' => 'Raskused käelise kirjutamise koordinatsioonis (peenmotoorika), sõnade paigutamisel paberile ja mõtete struktureeritud kirjapanekul.',
			],
			[
				'name'        => 'Düspraksia ehk arenguline koordinatsioonihäire (DCD)',
				'description' => 'Mõjutab motoorsete liigutuste planeerimist, tasakaalu ja füüsilist koordinatsiooni. Võib väljenduda kohmakuses, raskustes spordis, jalgrattasõidul või tööriistade ja söögiriistade käsitsemisel.',
			],
			[
				'name'        => 'Tourette\'i sündroom',
				'description' => 'Närvisüsteemi eripära, mida iseloomustavad tahtmatud, korduvad liigutused või häälitsused (motoorsed ja vokaalsed tikid), mille allasurumine tekitab tugevat sisemist pinget.',
			],
			[
				'name'        => 'Sensoorse töötlemise tundlikkus / häire (SPD)',
				'description' => 'Raskused keskkonnast (helid, lõhnad, puudutus, visuaalne müra) tuleva sensoorse info filtreerimisel ja reguleerimisel, mis viib kiiresti sensoorse ülekoormuseni.',
			],
			[
				'name'        => 'Afantaasia',
				'description' => 'Suutmatus luua vaimusilmas visuaalseid kujutluspilte. Inimene mõtleb faktide, kontseptsioonide või sõnade kaudu, mitte piltides.',
			],
		];
	}

	public static function audio_voices(): array {
		$items = get_option(self::VOICE_OPTION);

		if (!is_array($items) || [] === $items) {
			$items = self::voice_defaults();
			update_option(self::VOICE_OPTION, $items);
		}

		return $items;
	}

	private static function voice_defaults(): array {
		return [
			[
				'name'        => 'jaan',
				'description' => 'jutustaja',
			],
			[
				'name'        => 'maarja',
				'description' => 'ema',
			],
			[
				'name'        => 'margus',
				'description' => 'isa',
			],
			[
				'name'        => 'anneli',
				'description' => 'tüdruk 1',
			],
			[
				'name'        => 'triin',
				'description' => 'tüdruk 2',
			],
			[
				'name'        => 'henri',
				'description' => 'poiss',
			],
			[
				'name'        => 'kalev',
				'description' => 'poiss 2',
			],
			[
				'name'        => 'tarmo',
				'description' => 'loom 1',
			],
			[
				'name'        => 'linda',
				'description' => 'loom 2',
			],
			[
				'name'        => 'tuuli',
				'description' => 'loom 3',
			],
		];
	}

	public static function neurodifferences(): array {
		$items = get_option(self::NEURO_OPTION);

		if (!is_array($items) || [] === $items) {
			$items = self::neuro_defaults();
			update_option(self::NEURO_OPTION, $items);
		}

		return $items;
	}

	public static function characters(): array {
		$items = get_option(self::CHAR_OPTION);

		return is_array($items) ? $items : [];
	}

	public static function all(): array {
		return wp_parse_args(get_option(self::OPTION, []), self::defaults());
	}

	public static function get(string $key): mixed {
		$all   = self::all();
		$value = $all[$key] ?? null;

		if ('model' === $key) {
			if ('custom' === $value) {
				$custom = trim((string) ($all['custom_model'] ?? ''));
				return '' !== $custom ? $custom : 'glm-5.3-flash';
			}
		}

		return $value;
	}

	private static function providers(): array {
		return [
			'zai' => [
				'label'    => 'z.ai (GLM Coding Plan)',
				'base_url' => self::defaults()['base_url'],
				'models'   => ['glm-5.3-flash', 'glm-5.3'],
			],
			'openrouter' => [
				'label'    => 'OpenRouter',
				'base_url' => 'https://openrouter.ai/api/v1',
				'models'   => [
					'google/gemini-2.5-flash',
					'google/gemini-2.5-pro',
					'anthropic/claude-sonnet-4.5',
					'anthropic/claude-3.5-haiku',
					'openai/gpt-5-mini',
					'deepseek/deepseek-chat-v3.1',
					'meta-llama/llama-3.3-70b-instruct',
				],
				'image_models' => [
					'openai/gpt-image-2.5-sunburst',
				],
			],
		];
	}

	public static function provider_label(string $provider): string {
		$providers = self::providers();

		return $providers[$provider]['label'] ?? $providers['zai']['label'];
	}

	public static function task_config(string $task): array {
		$task      = in_array($task, ['ideas', 'story', 'audio', 'image'], true) ? $task : 'story';
		$settings  = self::all();
		$providers = self::providers();

		$provider = (string) ($settings[$task . '_provider'] ?? '');
		$provider = isset($providers[$provider]) ? $provider : 'zai';

		$model_sel = trim((string) ($settings[$task . '_model'] ?? ''));
		$model     = 'custom' === $model_sel ? trim((string) ($settings[$task . '_model_custom'] ?? '')) : $model_sel;

		if ('' === $model) {
			$model = 'image' === $task
				? (string) ($providers[$provider]['image_models'][0] ?? 'openai/gpt-image-2.5-sunburst')
				: 'glm-5.3-flash';
		}

		if ('openrouter' === $provider) {
			$api_key  = (string) ($settings['openrouter_api_key'] ?? '');
			$base_url = trim((string) ($settings['openrouter_base_url'] ?? ''));
			$base_url = '' !== $base_url ? $base_url : $providers['openrouter']['base_url'];
		} else {
			$api_key  = (string) ($settings['api_key'] ?? '');
			$base_url = trim((string) ($settings['base_url'] ?? ''));
			$base_url = '' !== $base_url ? $base_url : $providers['zai']['base_url'];
		}

		return [
			'provider' => $provider,
			'model'    => $model,
			'api_key'  => $api_key,
			'base_url' => $base_url,
		];
	}

	public static function init(): void {
		register_setting(
			'abs_settings_group',
			self::OPTION,
			[
				'sanitize_callback' => [self::class, 'sanitize'],
				'default'           => self::defaults(),
			]
		);

		register_setting(
			'abs_settings_group',
			self::NEURO_OPTION,
			[
				'sanitize_callback' => [self::class, 'sanitize_neurodifferences'],
				'default'           => [],
			]
		);

		register_setting(
			'abs_settings_group',
			self::CHAR_OPTION,
			[
				'sanitize_callback' => [self::class, 'sanitize_characters'],
				'default'           => [],
			]
		);

		register_setting(
			'abs_settings_group',
			self::VOICE_OPTION,
			[
				'sanitize_callback' => [self::class, 'sanitize_audio_voices'],
				'default'           => [],
			]
		);

		add_action('admin_init', [self::class, 'maybe_migrate']);

		add_action('admin_menu', static function (): void {
			add_menu_page(
				'AI Bedtime Stories',
				'AI Stories',
				'manage_options',
				'abs-generate',
				class_exists('ABS_Ideas') ? [ABS_Ideas::class, 'render_page'] : [self::class, 'render_page'],
				'dashicons-book',
				26
			);
		});

		add_action('wp_ajax_abs_test_connection', [self::class, 'ajax_test_connection']);
	}

	private static function sanitize_tts_float(mixed $value, float $default, float $min, float $max): float
	{
		if (!is_numeric($value)) {
			return $default;
		}

		return max($min, min($max, (float) $value));
	}

	public static function sanitize(array $input): array {
		$defaults = self::defaults();
		$clean    = [];

		$clean['api_key'] = isset($input['api_key'])
			? sanitize_text_field((string) $input['api_key'])
			: $defaults['api_key'];

		$base_url           = isset($input['base_url']) ? esc_url_raw((string) $input['base_url']) : '';
		$clean['base_url']  = '' !== $base_url ? $base_url : $defaults['base_url'];

		$clean['model'] = isset($input['model'])
			? sanitize_text_field((string) $input['model'])
			: $defaults['model'];

		$clean['custom_model'] = isset($input['custom_model'])
			? sanitize_text_field((string) $input['custom_model'])
			: $defaults['custom_model'];

		$clean['openrouter_api_key'] = isset($input['openrouter_api_key'])
			? sanitize_text_field((string) $input['openrouter_api_key'])
			: $defaults['openrouter_api_key'];

		$openrouter_base_url          = isset($input['openrouter_base_url']) ? esc_url_raw((string) $input['openrouter_base_url']) : '';
		$clean['openrouter_base_url'] = '' !== $openrouter_base_url ? $openrouter_base_url : $defaults['openrouter_base_url'];

		foreach (['ideas', 'story', 'audio'] as $task) {
			$provider = isset($input[$task . '_provider']) ? (string) $input[$task . '_provider'] : '';

			$clean[$task . '_provider'] = in_array($provider, ['zai', 'openrouter'], true)
				? $provider
				: $defaults[$task . '_provider'];

			$clean[$task . '_model'] = isset($input[$task . '_model'])
				? sanitize_text_field((string) $input[$task . '_model'])
				: $defaults[$task . '_model'];

			$clean[$task . '_model_custom'] = isset($input[$task . '_model_custom'])
				? sanitize_text_field((string) $input[$task . '_model_custom'])
				: $defaults[$task . '_model_custom'];
		}

		$count                = isset($input['ideas_count']) ? absint($input['ideas_count']) : $defaults['ideas_count'];
		$clean['ideas_count'] = min(10, max(1, $count));

		$clean['ideas_prompt'] = isset($input['ideas_prompt'])
			? sanitize_textarea_field((string) $input['ideas_prompt'])
			: $defaults['ideas_prompt'];

		$clean['story_prompt'] = isset($input['story_prompt'])
			? sanitize_textarea_field((string) $input['story_prompt'])
			: $defaults['story_prompt'];

		$clean['audio_prompt'] = isset($input['audio_prompt'])
			? sanitize_textarea_field((string) $input['audio_prompt'])
			: $defaults['audio_prompt'];

		$clean['image_prompt'] = isset($input['image_prompt'])
			? sanitize_textarea_field((string) $input['image_prompt'])
			: $defaults['image_prompt'];

		$clean['tts_api_key'] = isset($input['tts_api_key'])
			? sanitize_text_field((string) $input['tts_api_key'])
			: $defaults['tts_api_key'];

		$clean['tts_stream'] = empty($input['tts_stream']) ? 0 : 1;
		$clean['tts_stability']        = self::sanitize_tts_float($input['tts_stability'] ?? null, (float) $defaults['tts_stability'], 0.0, 1.0);
		$clean['tts_similarity_boost'] = self::sanitize_tts_float($input['tts_similarity_boost'] ?? null, (float) $defaults['tts_similarity_boost'], 0.0, 1.0);
		$clean['tts_style']            = self::sanitize_tts_float($input['tts_style'] ?? null, (float) $defaults['tts_style'], 0.0, 1.0);
		$clean['tts_speed']            = self::sanitize_tts_float($input['tts_speed'] ?? null, (float) $defaults['tts_speed'], 0.5, 2.0);

		$clean['post_category'] = isset($input['post_category'])
			? absint($input['post_category'])
			: $defaults['post_category'];

		return $clean;
	}

	public static function sanitize_neurodifferences($value): array {
		if (is_array($value) && ([] === $value || isset($value[0]['name']))) {
			$items = [];

			foreach (array_slice(array_values($value), 0, 50) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$name = sanitize_text_field((string) ($row['name'] ?? ''));

				if ('' === $name) {
					continue;
				}

				$items[] = [
					'name'        => $name,
					'description' => sanitize_textarea_field((string) ($row['description'] ?? '')),
				];
			}

			return $items;
		}

		$names = isset($_POST['abs_neuro_names']) && is_array($_POST['abs_neuro_names']) ? wp_unslash($_POST['abs_neuro_names']) : [];
		$descs = isset($_POST['abs_neuro_descs']) && is_array($_POST['abs_neuro_descs']) ? wp_unslash($_POST['abs_neuro_descs']) : [];

		$items = [];
		$total = max(count($names), count($descs));

		for ($i = 0; $i < $total; $i++) {
			$name = isset($names[$i]) ? sanitize_text_field((string) $names[$i]) : '';

			if ('' === $name) {
				continue;
			}

			$items[] = [
				'name'        => $name,
				'description' => isset($descs[$i]) ? sanitize_textarea_field((string) $descs[$i]) : '',
			];

			if (count($items) >= 50) {
				break;
			}
		}

		return $items;
	}

	public static function sanitize_characters($value): array {
		if (is_array($value) && ([] === $value || isset($value[0]['name']))) {
			$items = [];

			foreach (array_slice(array_values($value), 0, 50) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$name = mb_substr(sanitize_text_field((string) ($row['name'] ?? '')), 0, 120);

				if ('' === $name) {
					continue;
				}

				$items[] = [
					'name'        => $name,
					'character'   => mb_substr(sanitize_text_field((string) ($row['character'] ?? '')), 0, 120),
					'description' => mb_substr(sanitize_textarea_field((string) ($row['description'] ?? '')), 0, 2000),
					'neuro'       => mb_substr(sanitize_text_field((string) ($row['neuro'] ?? '')), 0, 120),
				];
			}

			return $items;
		}

		$names  = isset($_POST['abs_char_names']) && is_array($_POST['abs_char_names']) ? wp_unslash($_POST['abs_char_names']) : [];
		$roles  = isset($_POST['abs_char_roles']) && is_array($_POST['abs_char_roles']) ? wp_unslash($_POST['abs_char_roles']) : [];
		$descs  = isset($_POST['abs_char_descs']) && is_array($_POST['abs_char_descs']) ? wp_unslash($_POST['abs_char_descs']) : [];
		$neuros = isset($_POST['abs_char_neuros']) && is_array($_POST['abs_char_neuros']) ? wp_unslash($_POST['abs_char_neuros']) : [];

		$items = [];
		$total = max(count($names), count($roles), count($descs), count($neuros));

		for ($i = 0; $i < $total; $i++) {
			$name = isset($names[$i]) ? mb_substr(sanitize_text_field((string) $names[$i]), 0, 120) : '';

			if ('' === $name) {
				continue;
			}

			$items[] = [
				'name'        => $name,
				'character'   => isset($roles[$i]) ? mb_substr(sanitize_text_field((string) $roles[$i]), 0, 120) : '',
				'description' => isset($descs[$i]) ? mb_substr(sanitize_textarea_field((string) $descs[$i]), 0, 2000) : '',
				'neuro'       => isset($neuros[$i]) ? mb_substr(sanitize_text_field((string) $neuros[$i]), 0, 120) : '',
			];

			if (count($items) >= 50) {
				break;
			}
		}

		return $items;
	}

	public static function sanitize_voice_volume($value): int {
		$vol = '' === trim((string) $value) ? 100 : (int) $value;

		return min(200, max(10, $vol));
	}

	public static function sanitize_audio_voices($value): array {
		if (is_array($value) && ([] === $value || isset($value[0]['name']))) {
			$items = [];

			foreach (array_slice(array_values($value), 0, 50) as $row) {
				if (!is_array($row)) {
					continue;
				}

				$name = mb_substr(sanitize_text_field((string) ($row['name'] ?? '')), 0, 120);

				if ('' === $name) {
					continue;
				}

				$items[] = [
					'name'        => $name,
					'description' => mb_substr(sanitize_text_field((string) ($row['description'] ?? '')), 0, 200),
					'volume'      => self::sanitize_voice_volume((string) ($row['volume'] ?? '')),
				];

				if (count($items) >= 50) {
					break;
				}
			}

			return $items;
		}

		$names = isset($_POST['abs_voice_names']) && is_array($_POST['abs_voice_names']) ? wp_unslash($_POST['abs_voice_names']) : [];
		$descs = isset($_POST['abs_voice_descs']) && is_array($_POST['abs_voice_descs']) ? wp_unslash($_POST['abs_voice_descs']) : [];
		$vols  = isset($_POST['abs_voice_vols']) && is_array($_POST['abs_voice_vols']) ? wp_unslash($_POST['abs_voice_vols']) : [];

		$items = [];
		$total = max(count($names), count($descs));

		for ($i = 0; $i < $total; $i++) {
			$name = isset($names[$i]) ? mb_substr(sanitize_text_field((string) $names[$i]), 0, 120) : '';

			if ('' === $name) {
				continue;
			}

			$items[] = [
				'name'        => $name,
				'description' => isset($descs[$i]) ? mb_substr(sanitize_text_field((string) $descs[$i]), 0, 200) : '',
				'volume'      => self::sanitize_voice_volume((string) ($vols[$i] ?? '')),
			];

			if (count($items) >= 50) {
				break;
			}
		}

		return $items;
	}

	public static function neurodifference_context($index): string {
		$index = (int) $index;

		if ($index < 0) {
			return 'puudub (ära arvesta ühegi konkreetse neuroerisusega)';
		}

		$items = self::neurodifferences();
		$item  = $items[absint($index)] ?? null;

		if (!is_array($item) || '' === trim((string) ($item['name'] ?? ''))) {
			return 'puudub (ära arvesta ühegi konkreetse neuroerisusega)';
		}

		$name        = trim((string) $item['name']);
		$description = trim((string) ($item['description'] ?? ''));

		return '' === $description ? $name : $name . ' — ' . $description;
	}

	public static function character_context($index): string {
		$index = (int) $index;

		if ($index < 0) {
			return 'puudub (ära arvesta ühegi konkreetse tegelasega)';
		}

		$items = self::characters();
		$item  = $items[absint($index)] ?? null;

		if (!is_array($item) || '' === trim((string) ($item['name'] ?? ''))) {
			return 'puudub (ära arvesta ühegi konkreetse tegelasega)';
		}

		$name        = trim((string) $item['name']);
		$role        = trim((string) ($item['character'] ?? ''));
		$description = trim((string) ($item['description'] ?? ''));

		if ('' !== $role && '' !== $description) {
			return $name . ' (' . $role . ') — ' . $description;
		}

		if ('' !== $role) {
			return $name . ' (' . $role . ')';
		}

		return '' === $description ? $name : $name . ' — ' . $description;
	}

	public static function character_neuro(string $neuro_name): ?array {
		$neuro_name = trim($neuro_name);

		if ('' === $neuro_name) {
			return null;
		}

		foreach (self::neurodifferences() as $item) {
			if (trim((string) ($item['name'] ?? '')) === $neuro_name) {
				return [
					'name'        => $neuro_name,
					'description' => trim((string) ($item['description'] ?? '')),
				];
			}
		}

		return null;
	}

	public static function maybe_migrate(): void {
		if (get_option('abs_version') === ABS_VERSION) {
			return;
		}

		$settings = get_option(self::OPTION, []);
		$defaults = self::defaults();

		if (is_array($settings)) {
			$changed = false;

			$stored_ideas = isset($settings['ideas_prompt']) ? str_replace("\r\n", "\n", (string) $settings['ideas_prompt']) : null;

			if (self::PREV_IDEAS_PROMPT === $stored_ideas || self::OLD_IDEAS_PROMPT === $stored_ideas) {
				$settings['ideas_prompt'] = $defaults['ideas_prompt'];
				$changed = true;
			}

			$stored_story = isset($settings['story_prompt']) ? str_replace("\r\n", "\n", (string) $settings['story_prompt']) : null;

			if (self::PREV_STORY_PROMPT === $stored_story || self::OLD_STORY_PROMPT === $stored_story) {
				$settings['story_prompt'] = $defaults['story_prompt'];
				$changed = true;
			}

			if (!isset($settings['audio_prompt']) || '' === trim(str_replace("\r\n", "\n", (string) $settings['audio_prompt']))) {
				$settings['audio_prompt'] = $defaults['audio_prompt'];
				$changed = true;
			}

			$stored_audio = isset($settings['audio_prompt']) ? str_replace("\r\n", "\n", (string) $settings['audio_prompt']) : null;

			if (null !== $stored_audio && '' !== trim($stored_audio) && false === strpos($stored_audio, 'AINULT ühe kõneleja katkematu kõne') && 0 === strpos($stored_audio, 'Analüüsi järgmist eestikeelset lastejuttu')) {
				$settings['audio_prompt'] = $defaults['audio_prompt'];
				$changed = true;
			}

		foreach (['ideas', 'story', 'audio', 'image'] as $task) {
				if (!isset($settings[$task . '_provider']) || '' === trim((string) $settings[$task . '_provider'])) {
					$settings[$task . '_provider'] = 'zai';
					$changed = true;
				}

				if (!isset($settings[$task . '_model']) || '' === trim((string) $settings[$task . '_model'])) {
					$legacy = ('custom' === ($settings['model'] ?? null))
						? (string) ($settings['custom_model'] ?? '')
						: (string) ($settings['model'] ?? '');

					$settings[$task . '_model'] = '' !== trim($legacy) ? trim($legacy) : 'glm-5.3-flash';
					$changed = true;
				}
			}

			if (!isset($settings['audio_model_custom']) || '' === trim((string) $settings['audio_model_custom'])) {
				$settings['audio_model_custom'] = $defaults['audio_model_custom'];
				$changed = true;
			}

			if (!isset($settings['image_provider']) || '' === trim((string) $settings['image_provider'])) {
				$settings['image_provider'] = 'openrouter';
				$changed = true;
			}

			if (!isset($settings['image_model']) || '' === trim((string) $settings['image_model'])) {
				$settings['image_model'] = $defaults['image_model'];
				$changed = true;
			}

			if (!isset($settings['image_prompt']) || '' === trim(str_replace("\r\n", "\n", (string) $settings['image_prompt']))) {
				$settings['image_prompt'] = $defaults['image_prompt'];
				$changed = true;
			}

			if ($changed) {
				update_option(self::OPTION, $settings);
			}
		}

		if (false === get_option(self::VOICE_OPTION)) {
			add_option(self::VOICE_OPTION, self::voice_defaults());
		}

		if (class_exists(ABS_Library::class) && method_exists(ABS_Library::class, 'ensure_page')) {
			ABS_Library::ensure_page();
		}

		if (class_exists(ABS_Dashboard::class) && method_exists(ABS_Dashboard::class, 'ensure_page')) {
			ABS_Dashboard::ensure_page();
		}

		update_option('abs_version', ABS_VERSION);
	}

	private static function render_task_provider_rows(string $task, array $settings, ?array $allowed_providers = null): void {
		$providers = self::providers();

		if (null !== $allowed_providers) {
			$providers = array_intersect_key($providers, array_fill_keys($allowed_providers, true));
		}

		$provider     = (string) ($settings[$task . '_provider'] ?? 'zai');
		$provider     = isset($providers[$provider]) ? $provider : (array_key_first($providers) ?? 'zai');
		$stored_model = trim((string) ($settings[$task . '_model'] ?? ''));
		$custom_value = (string) ($settings[$task . '_model_custom'] ?? '');
		$show_custom  = false;
		?>
		<tr class="abs-provider-row">
			<th scope="row"><label for="abs-<?php echo esc_attr($task); ?>-provider"><?php echo esc_html('Pakkuja'); ?></label></th>
			<td>
				<select id="abs-<?php echo esc_attr($task); ?>-provider" name="abs_settings[<?php echo esc_attr($task); ?>_provider]">
					<?php foreach ($providers as $slug => $config) : ?>
						<option value="<?php echo esc_attr($slug); ?>" <?php selected($provider, $slug); ?>><?php echo esc_html($config['label']); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr class="abs-provider-row">
			<th scope="row"><label for="abs-<?php echo esc_attr($task); ?>-model-<?php echo esc_attr($provider); ?>"><?php echo esc_html('Mudel'); ?></label></th>
			<td>
				<?php foreach ($providers as $slug => $config) :
					$active = $slug === $provider;
					$task_models = 'image' === $task && !empty($config['image_models']) ? $config['image_models'] : $config['models'];
					$sel    = $active ? $stored_model : '';

					if ('' === $sel) {
						$sel = $task_models[0];
					} elseif ('custom' !== $sel && !in_array($sel, $task_models, true)) {
						$sel = 'custom';
					}

					if ('custom' === $sel) {
						if ('' === trim($custom_value) && '' !== $stored_model && 'custom' !== $stored_model) {
							$custom_value = $stored_model;
						}

						if ($active) {
							$show_custom = true;
						}
					}
					?>
					<select
						class="abs-model-select<?php echo $active ? '' : ' abs-hidden'; ?>"
						data-task="<?php echo esc_attr($task); ?>"
						data-provider="<?php echo esc_attr($slug); ?>"
						name="abs_settings[<?php echo esc_attr($task); ?>_model]"
						id="abs-<?php echo esc_attr($task); ?>-model-<?php echo esc_attr($slug); ?>"
						<?php disabled(!$active); ?>
					>
						<?php foreach ($task_models as $model) : ?>
							<option value="<?php echo esc_attr($model); ?>" <?php selected($sel, $model); ?>><?php echo esc_html($model); ?></option>
						<?php endforeach; ?>
						<option value="custom" <?php selected($sel, 'custom'); ?>><?php echo esc_html('Muu (custom)'); ?></option>
					</select>
				<?php endforeach; ?>
				<input type="text" id="abs-<?php echo esc_attr($task); ?>-model-custom" name="abs_settings[<?php echo esc_attr($task); ?>_model_custom]" value="<?php echo esc_attr($custom_value); ?>" class="regular-text<?php echo $show_custom ? '' : ' abs-hidden'; ?>" placeholder="<?php echo esc_attr('mudeli nimi'); ?>" />
			</td>
		</tr>
		<?php
	}

	public static function render_page(): void {
		if (!current_user_can('manage_options')) {
			wp_die('You do not have permission to access this page.');
		}

		$settings  = self::all();
		$known     = ['glm-5.3-flash', 'glm-5.3', 'custom'];
		$is_known  = in_array($settings['model'], $known, true);
		$model_sel = $is_known ? $settings['model'] : 'custom';
		$custom    = $is_known ? $settings['custom_model'] : $settings['model'];
		$categories = get_categories(['hide_empty' => false]);
		?>
		<div class="wrap">
			<h1><?php echo esc_html('AI Bedtime Stories'); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields('abs_settings_group'); ?>

				<h2 class="title"><?php echo esc_html('AI pakkujad'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="abs_api_key"><?php echo esc_html('z.ai API võti'); ?></label></th>
						<td>
							<input type="password" id="abs_api_key" name="abs_settings[api_key]" value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text" autocomplete="new-password" />
							<p class="description"><?php echo esc_html('Your z.ai API key (get it at https://z.ai/manage-apikey/apikey-list).'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_base_url"><?php echo esc_html('z.ai baas-URL'); ?></label></th>
						<td>
							<input type="text" id="abs_base_url" name="abs_settings[base_url]" value="<?php echo esc_attr($settings['base_url']); ?>" class="regular-text" />
							<p class="description"><?php echo esc_html('Default is the GLM Coding Plan endpoint. For standard pay-as-you-go API use https://api.z.ai/api/paas/v4'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_model"><?php echo esc_html('z.ai baas-mudel'); ?></label></th>
						<td>
							<select id="abs_model" name="abs_settings[model]">
								<option value="glm-5.3-flash" <?php selected($model_sel, 'glm-5.3-flash'); ?>><?php echo esc_html('GLM-5.3-Flash (fast, recommended)'); ?></option>
								<option value="glm-5.3" <?php selected($model_sel, 'glm-5.3'); ?>><?php echo esc_html('GLM-5.3 (highest quality)'); ?></option>
								<option value="custom" <?php selected($model_sel, 'custom'); ?>><?php echo esc_html('Custom…'); ?></option>
							</select>
							<p class="description"><?php echo esc_html('Legacy base z.ai model — per-task provider and model below take precedence.'); ?></p>
						</td>
					</tr>
					<tr id="abs-custom-model-row" style="<?php echo 'custom' === $model_sel ? '' : 'display:none;'; ?>">
						<th scope="row"><label for="abs_custom_model"><?php echo esc_html('Custom model name'); ?></label></th>
						<td>
							<input type="text" id="abs_custom_model" name="abs_settings[custom_model]" value="<?php echo esc_attr($custom); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_openrouter_api_key"><?php echo esc_html('OpenRouter API võti'); ?></label></th>
						<td>
							<input type="password" id="abs_openrouter_api_key" name="abs_settings[openrouter_api_key]" value="<?php echo esc_attr($settings['openrouter_api_key']); ?>" class="regular-text" autocomplete="new-password" />
							<p class="description"><?php echo esc_html('Your OpenRouter API key (create one at https://openrouter.ai/keys).'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_openrouter_base_url"><?php echo esc_html('OpenRouter baas-URL'); ?></label></th>
						<td>
							<input type="text" id="abs_openrouter_base_url" name="abs_settings[openrouter_base_url]" value="<?php echo esc_attr($settings['openrouter_base_url']); ?>" class="regular-text" />
							<p class="description"><?php echo esc_html('Default is https://openrouter.ai/api/v1.'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Idea Generation'); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_task_provider_rows('ideas', $settings); ?>
					<tr>
						<th scope="row"><label for="abs_ideas_count"><?php echo esc_html('Ideas per click'); ?></label></th>
						<td>
							<input type="number" id="abs_ideas_count" name="abs_settings[ideas_count]" value="<?php echo esc_attr($settings['ideas_count']); ?>" min="1" max="10" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_ideas_prompt"><?php echo esc_html('Ideas prompt'); ?></label></th>
						<td>
							<textarea id="abs_ideas_prompt" name="abs_settings[ideas_prompt]" rows="9" class="large-text code"><?php echo esc_textarea($settings['ideas_prompt']); ?></textarea>
							<p class="description"><?php echo esc_html('Placeholders: {count} = number of ideas, {existing_titles} = recent story titles to avoid duplicates, {neurodifference} = the neurodifference of the selected character, {character} = the character selected on the Generate page, {topic} = the optional topic from the Teema field (empty if not set).'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Story Generation'); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_task_provider_rows('story', $settings); ?>
					<tr>
						<th scope="row"><label for="abs_story_prompt"><?php echo esc_html('Story prompt'); ?></label></th>
						<td>
							<textarea id="abs_story_prompt" name="abs_settings[story_prompt]" rows="11" class="large-text code"><?php echo esc_textarea($settings['story_prompt']); ?></textarea>
							<p class="description"><?php echo esc_html('Placeholders {title} and {description} are replaced with the chosen idea, {neurodifference} with the neurodifference of the selected character and {character} with the character selected on the Generate page. The AI must return JSON {"title": "...", "story": "..."}.'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Jutu kaanepilt'); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_task_provider_rows('image', $settings, ['openrouter']); ?>
					<tr>
						<th scope="row"><label for="abs_image_prompt"><?php echo esc_html('Kaanepildi prompt'); ?></label></th>
						<td>
							<textarea id="abs_image_prompt" name="abs_settings[image_prompt]" rows="9" class="large-text code"><?php echo esc_textarea($settings['image_prompt']); ?></textarea>
							<p class="description"><?php echo esc_html('Placeholderid: {TITLE} = jutu pealkiri, {STORY} = jutu tekst. Pilt genereeritakse OpenRouter Images API-ga (16:9) ja kasutatakse avaldatud blogiposti pildina (post thumbnail). Genereerimise nupp on jutude loendis veerus "Pilt".'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Audio skript'); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::render_task_provider_rows('audio', $settings); ?>
					<tr>
						<th scope="row"><label for="abs_audio_prompt"><?php echo esc_html('Audio skripti prompt'); ?></label></th>
						<td>
							<textarea id="abs_audio_prompt" name="abs_settings[audio_prompt]" rows="11" class="large-text code"><?php echo esc_textarea($settings['audio_prompt']); ?></textarea>
							<p class="description"><?php echo esc_html('Koht {story} asendatakse jutu tekstiga. AI peab tagastama kehtiva JSON-objekti kujul {"lines": [...]}. Audio skripti saab genereerida jutude loendis veerust "Audio".'); ?></p>
							<p class="description"><strong><?php echo esc_html('Genereerimisel lisatakse selle teksti lõppu automaatselt lubatud häälte loend (jaotisest "Audio hääled"), et AI saaks speaker-i väärtused sealt valida.'); ?></strong></p>
							<?php $abs_preview_voices = self::audio_voices(); ?>
							<?php if ([] !== $abs_preview_voices) : ?>
								<p class="description"><?php echo esc_html('Praegu lisatav loend:'); ?></p>
								<p class="description code"><?php echo esc_html(implode("\n", array_map(static function ($abs_voice): string {
									return '- ' . (string) $abs_voice['name'] . ('' !== (string) $abs_voice['description'] ? ' — ' . (string) $abs_voice['description'] : '');
								}, $abs_preview_voices))); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_api_key"><?php echo esc_html('Jutusta API võti'); ?></label></th>
						<td>
							<input type="password" id="abs_tts_api_key" name="abs_settings[tts_api_key]" value="<?php echo esc_attr($settings['tts_api_key']); ?>" class="regular-text" autocomplete="new-password" />
							<p class="description"><?php echo esc_html('Jutusta.ee API võti (xi-api-key). Vajalik audio skripti üksikute ridade heligeneratsiooniks — ilma võtmeta ei saa ridu helistada.'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_stream"><?php echo esc_html('Striimi režiim (stream)'); ?></label></th>
						<td>
							<label for="abs_tts_stream">
								<input type="checkbox" id="abs_tts_stream" name="abs_settings[tts_stream]" value="1" <?php checked(1, (int) $settings['tts_stream']); ?> />
								<?php echo esc_html('Kasuta jutusta.ee /stream otsapunkti'); ?>
							</label>
							<p class="description"><?php echo esc_html('Soovitatav: kiirem ja töökindlam (ei anna HTTP 502 vigu), kuid ignoreerib kõne tempo seadet (kasutab vaiketempot). Lülita välja ainult siis, kui vajad aeglast kõnet ja jutusta on selle parandanud.'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_speed"><?php echo esc_html('Kõne tempo (speed)'); ?></label></th>
						<td>
							<input type="number" id="abs_tts_speed" name="abs_settings[tts_speed]" value="<?php echo esc_attr($settings['tts_speed']); ?>" min="0.5" max="2" step="0.1" class="small-text" />
							<p class="description"><?php echo esc_html('1 = tavaline kiirus, väiksem = aeglasem kõne. Ettevaatust: jutusta.ee tagastab hetkel vea (HTTP 502) tempodel alla 0.7.'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_stability"><?php echo esc_html('Stabiilsus (stability)'); ?></label></th>
						<td>
							<input type="number" id="abs_tts_stability" name="abs_settings[tts_stability]" value="<?php echo esc_attr($settings['tts_stability']); ?>" min="0" max="1" step="0.1" class="small-text" />
							<p class="description"><?php echo esc_html('0 = kõnekooline ja elav, 1 = ühtlane ja monotonne.'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_similarity_boost"><?php echo esc_html('Hääle sarnasus (similarity_boost)'); ?></label></th>
						<td>
							<input type="number" id="abs_tts_similarity_boost" name="abs_settings[tts_similarity_boost]" value="<?php echo esc_attr($settings['tts_similarity_boost']); ?>" min="0" max="1" step="0.1" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="abs_tts_style"><?php echo esc_html('Stiil (style)'); ?></label></th>
						<td>
							<input type="number" id="abs_tts_style" name="abs_settings[tts_style]" value="<?php echo esc_attr($settings['tts_style']); ?>" min="0" max="1" step="0.1" class="small-text" />
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Audio hääled'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html('Hääled'); ?></th>
						<td>
							<div id="abs-voice-rows">
								<?php foreach (self::audio_voices() as $voice_item) : ?>
									<?php $voice_name = (string) ($voice_item['name'] ?? ''); ?>
									<div class="abs-char-item">
										<button type="button" class="abs-char-toggle" aria-expanded="false">
											<span class="abs-char-title"><?php echo esc_html('' !== trim($voice_name) ? $voice_name : 'Nimetu hääl'); ?></span>
											<span class="abs-char-arrow" aria-hidden="true">▾</span>
										</button>
										<div class="abs-char-panel" hidden>
											<p class="abs-char-field">
												<label><?php echo esc_html('Nimi'); ?></label>
												<input type="text" class="abs-voice-name" name="abs_voice_names[]" value="<?php echo esc_attr($voice_name); ?>" maxlength="120" />
											</p>
											<p class="abs-char-field">
												<label><?php echo esc_html('Kirjeldus'); ?></label>
												<input type="text" class="abs-voice-desc" name="abs_voice_descs[]" value="<?php echo esc_attr((string) ($voice_item['description'] ?? '')); ?>" maxlength="200" />
											</p>
											<p class="abs-char-field">
												<label><?php echo esc_html('Helitugevus (%)'); ?></label>
												<input type="number" class="abs-voice-vol" name="abs_voice_vols[]" value="<?php echo esc_attr((string) (int) ($voice_item['volume'] ?? 100)); ?>" min="10" max="200" step="5" />
											</p>
											<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="abs-char-item abs-hidden" data-abs-voice-template>
								<button type="button" class="abs-char-toggle" aria-expanded="false">
									<span class="abs-char-title"><?php echo esc_html('Nimetu hääl'); ?></span>
									<span class="abs-char-arrow" aria-hidden="true">▾</span>
								</button>
								<div class="abs-char-panel" hidden>
									<p class="abs-char-field">
										<label><?php echo esc_html('Nimi'); ?></label>
										<input type="text" class="abs-voice-name" name="abs_voice_names[]" value="" maxlength="120" />
									</p>
									<p class="abs-char-field">
										<label><?php echo esc_html('Kirjeldus'); ?></label>
										<input type="text" class="abs-voice-desc" name="abs_voice_descs[]" value="" maxlength="200" />
									</p>
									<p class="abs-char-field">
										<label><?php echo esc_html('Helitugevus (%)'); ?></label>
										<input type="number" class="abs-voice-vol" name="abs_voice_vols[]" value="100" min="10" max="200" step="5" />
									</p>
									<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
								</div>
							</div>
							<button type="button" id="abs-add-voice" class="button button-secondary"><?php echo esc_html('Lisa hääl'); ?></button>
							<p class="description"><?php echo esc_html('Lubatud speaker-väärtused audio skripti genereerimisel. Nimed salvestatakse sellisena, nagu sisestatud; tundmatu speaker asendatakse esimese loendis oleva häälega.'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Neuroerisused'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html('Neurotypes'); ?></th>
						<td>
							<div id="abs-neuro-rows">
								<?php foreach (self::neurodifferences() as $neuro_item) : ?>
									<?php $neuro_name = (string) ($neuro_item['name'] ?? ''); ?>
									<div class="abs-char-item">
										<button type="button" class="abs-char-toggle" aria-expanded="false">
											<span class="abs-char-title"><?php echo esc_html('' !== trim($neuro_name) ? $neuro_name : 'Nimetu erisus'); ?></span>
											<span class="abs-char-arrow" aria-hidden="true">▾</span>
										</button>
										<div class="abs-char-panel" hidden>
											<p class="abs-char-field">
												<label><?php echo esc_html('Nimi'); ?></label>
												<input type="text" class="abs-neuro-name" name="abs_neuro_names[]" value="<?php echo esc_attr($neuro_name); ?>" maxlength="120" />
											</p>
											<p class="abs-char-field">
												<label><?php echo esc_html('Kirjeldus'); ?></label>
												<textarea class="abs-neuro-desc" name="abs_neuro_descs[]" rows="5" maxlength="2000"><?php echo esc_textarea((string) ($neuro_item['description'] ?? '')); ?></textarea>
											</p>
											<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<div class="abs-char-item abs-hidden" data-abs-neuro-template>
								<button type="button" class="abs-char-toggle" aria-expanded="false">
									<span class="abs-char-title"><?php echo esc_html('Nimetu erisus'); ?></span>
									<span class="abs-char-arrow" aria-hidden="true">▾</span>
								</button>
								<div class="abs-char-panel" hidden>
									<p class="abs-char-field">
										<label><?php echo esc_html('Nimi'); ?></label>
										<input type="text" class="abs-neuro-name" name="abs_neuro_names[]" value="" maxlength="120" />
									</p>
									<p class="abs-char-field">
										<label><?php echo esc_html('Kirjeldus'); ?></label>
										<textarea class="abs-neuro-desc" name="abs_neuro_descs[]" rows="5" maxlength="2000"></textarea>
									</p>
									<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
								</div>
							</div>
							<button type="button" id="abs-add-neuro" class="button button-secondary"><?php echo esc_html('Lisa neuroerisus'); ?></button>
							<p class="description"><?php echo esc_html('These neurodifferences can be attached to characters below and are injected into the prompts via the {neurodifference} placeholder.'); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Tegelased'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html('Characters'); ?></th>
					<td>
						<div id="abs-char-rows">
							<?php foreach (self::characters() as $char_item) : ?>
								<?php
								$stored_neuro = trim((string) ($char_item['neuro'] ?? ''));
								$char_name    = (string) ($char_item['name'] ?? '');
								$char_role    = (string) ($char_item['character'] ?? '');
								?>
								<div class="abs-char-item">
									<button type="button" class="abs-char-toggle" aria-expanded="false">
										<span class="abs-char-title"><?php echo esc_html('' !== trim($char_name) ? $char_name : 'Nimetu tegelane'); ?></span>
										<span class="abs-char-sub"<?php echo '' !== trim($char_role) ? '' : ' hidden'; ?>><?php echo esc_html($char_role); ?></span>
										<span class="abs-char-arrow" aria-hidden="true">▾</span>
									</button>
									<div class="abs-char-panel" hidden>
										<p class="abs-char-field">
											<label><?php echo esc_html('Nimi'); ?></label>
											<input type="text" class="abs-char-name" name="abs_char_names[]" value="<?php echo esc_attr($char_name); ?>" maxlength="120" />
										</p>
										<p class="abs-char-field">
											<label><?php echo esc_html('Tegelane'); ?></label>
											<input type="text" class="abs-char-role" name="abs_char_roles[]" value="<?php echo esc_attr($char_role); ?>" maxlength="120" />
										</p>
										<p class="abs-char-field">
											<label><?php echo esc_html('Kirjeldus'); ?></label>
											<textarea class="abs-char-desc" name="abs_char_descs[]" rows="5" maxlength="2000"><?php echo esc_textarea((string) ($char_item['description'] ?? '')); ?></textarea>
										</p>
										<p class="abs-char-field">
											<label><?php echo esc_html('Neuroerisus'); ?></label>
											<select class="abs-char-neuro" name="abs_char_neuros[]">
												<option value="" <?php selected($stored_neuro, ''); ?>><?php echo esc_html('— Puudub —'); ?></option>
												<?php foreach (self::neurodifferences() as $neuro_option) : ?>
													<?php $neuro_option_name = trim((string) ($neuro_option['name'] ?? '')); ?>
													<option value="<?php echo esc_attr($neuro_option_name); ?>" <?php selected($stored_neuro, $neuro_option_name); ?>><?php echo esc_html($neuro_option_name); ?></option>
												<?php endforeach; ?>
											</select>
										</p>
										<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
						<div class="abs-char-item abs-hidden" data-abs-char-template>
							<button type="button" class="abs-char-toggle" aria-expanded="false">
								<span class="abs-char-title"><?php echo esc_html('Nimetu tegelane'); ?></span>
								<span class="abs-char-sub" hidden></span>
								<span class="abs-char-arrow" aria-hidden="true">▾</span>
							</button>
							<div class="abs-char-panel" hidden>
								<p class="abs-char-field">
									<label><?php echo esc_html('Nimi'); ?></label>
									<input type="text" class="abs-char-name" name="abs_char_names[]" value="" maxlength="120" />
								</p>
								<p class="abs-char-field">
									<label><?php echo esc_html('Tegelane'); ?></label>
									<input type="text" class="abs-char-role" name="abs_char_roles[]" value="" maxlength="120" />
								</p>
								<p class="abs-char-field">
									<label><?php echo esc_html('Kirjeldus'); ?></label>
									<textarea class="abs-char-desc" name="abs_char_descs[]" rows="5" maxlength="2000"></textarea>
								</p>
								<p class="abs-char-field">
									<label><?php echo esc_html('Neuroerisus'); ?></label>
									<select class="abs-char-neuro" name="abs_char_neuros[]">
										<option value=""><?php echo esc_html('— Puudub —'); ?></option>
										<?php foreach (self::neurodifferences() as $neuro_option) : ?>
											<option value="<?php echo esc_attr((string) ($neuro_option['name'] ?? '')); ?>"><?php echo esc_html((string) ($neuro_option['name'] ?? '')); ?></option>
										<?php endforeach; ?>
									</select>
								</p>
								<button type="button" class="button-link abs-char-remove"><?php echo esc_html('Eemalda'); ?></button>
							</div>
						</div>
						<button type="button" id="abs-add-char" class="button button-secondary"><?php echo esc_html('Lisa tegelane'); ?></button>
						<p class="description"><?php echo esc_html('These characters are offered on the Generate page and injected into the prompts via the {character} placeholder. The selected neurodifference is injected via the {neurodifference} placeholder.'); ?></p>
					</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Publishing'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="abs_post_category"><?php echo esc_html('Blog category'); ?></label></th>
						<td>
							<select id="abs_post_category" name="abs_settings[post_category]">
								<option value="0" <?php selected($settings['post_category'], 0); ?>><?php echo esc_html('— No category —'); ?></option>
								<?php if (!is_wp_error($categories) && !empty($categories)) : ?>
									<?php foreach ($categories as $category) : ?>
										<option value="<?php echo esc_attr($category->term_id); ?>" <?php selected($settings['post_category'], (int) $category->term_id); ?>><?php echo esc_html($category->name); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
							<p class="description"><?php echo esc_html("Published stories are assigned to this blog category. Create one (e.g. 'Unejutud') under Posts → Categories if needed."); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html('Test connection'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="abs-test-connection"><?php echo esc_html('API status'); ?></label></th>
						<td>
							<select id="abs-test-provider">
								<?php foreach (self::providers() as $slug => $config) : ?>
									<option value="<?php echo esc_attr($slug); ?>" <?php selected('zai', $slug); ?>><?php echo esc_html($config['label']); ?></option>
								<?php endforeach; ?>
							</select>
							<button type="button" id="abs-test-connection" class="button button-secondary"><?php echo esc_html('Test API connection'); ?></button>
							<span id="abs-test-result"></span>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
			<script>
				(function () {
					var select = document.getElementById('abs_model');
					var row = document.getElementById('abs-custom-model-row');
					if (!select || !row) {
						return;
					}
					var toggle = function () {
						row.style.display = 'custom' === select.value ? '' : 'none';
					};
					select.addEventListener('change', toggle);
					toggle();
				})();
			</script>
		</div>
		<?php
	}

	public static function ajax_test_connection(): void {
		if (!check_ajax_referer(self::NONCE_ACTION, 'nonce', false)) {
			wp_send_json_error(['message' => 'Invalid or expired security token.'], 403);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
		}

		if (!class_exists('ABS_API')) {
			wp_send_json_error(['message' => 'API client class is not available.']);
		}

		$provider  = isset($_POST['provider']) ? (string) $_POST['provider'] : 'zai';
		$providers = self::providers();

		if (!isset($providers[$provider])) {
			$provider = 'zai';
		}

		$label   = $providers[$provider]['label'];
		$api_key = 'openrouter' === $provider ? (string) self::get('openrouter_api_key') : (string) self::get('api_key');

		if (!is_string($api_key) || '' === trim($api_key)) {
			wp_send_json_error(['message' => $label . ': API key is not set.']);
		}

		$config = self::task_config('ideas');
		if ($config['provider'] !== $provider) {
			$config = self::task_config('story');
		}

		if ($config['provider'] !== $provider) {
			$config = [
				'provider' => $provider,
				'model'    => $providers[$provider]['models'][0],
				'api_key'  => $api_key,
				'base_url' => $providers[$provider]['base_url'],
			];
		}

		$response = ABS_API::chat(
			[['role' => 'user', 'content' => 'Sõna "OK".']],
			[
				'max_tokens' => 16,
				'timeout'    => 30,
			],
			$config
		);

		if (is_wp_error($response)) {
			wp_send_json_error(['message' => $response->get_error_message()]);
		}

		if (is_array($response) && isset($response['content'])) {
			wp_send_json_success([
				'message' => 'Connected (' . $label . '). Model replied: ' . trim((string) $response['content']),
			]);
		}

		wp_send_json_error(['message' => 'Unexpected API response.']);
	}
}
