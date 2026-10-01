# AI Bedtime Stories

**Version:** 1.1.48 · **Requires:** WordPress 6.0+, PHP 8.0+ · **Text domain:** `ai-bedtime-stories`

Generates bedtime story ideas and full Brothers Grimm-inspired bedtime stories in Estonian using the z.ai GLM API (Coding Plan endpoint), then turns them into audio: an AI-generated multi-voice script, per-line TTS via jutusta.ee, and a merged full-length WAV. Stories can be published as blog posts with AI-generated cover art, an audio player, and listen analytics for logged-in users.

## Features

- **Idea generation** — 1–10 unique story ideas per click, deduplicated against existing story titles, in Brothers Grimm style.
- **Full story generation** — 800–1500 word, child-safe, calm-ending bedtime stories from any pending idea.
- **Characters & neurodifferences** — define characters (name, role, description, linked neurodifference) and neurotypes (ASD, ADHD, dyslexia, etc.). Both are injected into prompts so stories are sensitively tailored to the selected child.
- **Per-task AI providers** — independently choose z.ai (GLM Coding Plan) or OpenRouter for ideas, story, and audio-script generation; images use the OpenRouter Images API.
- **Cover images** — 16:9 AI cover art per story, saved to the media library and set as the featured image of the published post.
- **Audio pipeline** — AI audio script (`{"lines": [{speaker, tone, pause_after_ms, text}]}`) → per-line TTS (jutusta.ee, WAV) → merged full audio with pauses and per-voice volume scaling. Lines can be edited and regenerated individually.
- **Publishing** — one-click publish as a regular blog post with optional category; edits to the story sync back to the post.
- **Frontend audio** — audio player prepended to published stories (logged-in users only; guests see a login banner).
- **Listen tracking & dashboards** — per-user listen stats (start/progress/end), audio library, personal dashboard with monthly breakdown, and an admin "Kuulamised" overview per user.
- **Subscriber experience** — non-admins are redirected away from wp-admin to their dashboard on login, and the admin bar is hidden for them.

## Installation

1. Copy the `ai-bedtime-stories` folder into `wp-content/plugins/`.
2. Activate **AI Bedtime Stories** in *Plugins*. Activation seeds default settings, neurotypes, and voices.
3. Go to **AI Stories → Settings** and configure API keys (see below).

## Configuration (AI Stories → Settings)

| Key | Purpose |
| --- | --- |
| **z.ai API key** | Required for ideas/story/audio generation (GLM Coding Plan endpoint `https://api.z.ai/api/coding/paas/v4` by default; standard API: `https://api.z.ai/api/paas/v4`). |
| **OpenRouter API key** | Required for cover images; optional alternative provider for ideas/story/audio. |
| **Jutusta API key** (`xi-api-key`) | Required for per-line TTS at `https://jutusta.ee/api/v1/text-to-speech/{voice}[/stream]?output_format=wav`. |
| **TTS options** | Stream mode (recommended), speed (note: jutusta.ee currently errors below 0.7), stability, similarity boost, style. |
| **Prompts** | Fully editable Estonian prompts with placeholders: `{count}`, `{existing_titles}`, `{neurodifference}`, `{character}`, `{title}`, `{description}`, `{story}`; image prompt: `{TITLE}`, `{STORY}`. |
| **Audio voices** | Allowed `speaker` names for the audio script (name, description, volume 10–200%). Narrator line = the voice described as "jutustaja". Unknown speakers fall back to the first voice. |
| **Post category** | Category assigned to published blog posts. |

Default models: `glm-5.3-flash` (fast) or `glm-5.3` (highest quality); OpenRouter models include Gemini, Claude, GPT, DeepSeek, and Llama; image model `openai/gpt-image-2.5-sunburst`.

## Usage

1. **Generate** — *AI Stories → Generate Stories*: optionally pick a character, click **Generate ideas**, then turn any idea into a full story. Ideas can be edited or discarded before writing.
2. **Manage** — *Stories* list: generate audio script (Audio column), edit/regenerate lines, generate TTS per line, merge full audio ("Koosta audio"), and generate/regenerate the cover image (Pilt column). Filter the list by character.
3. **Publish** — "Publish as blog post" row action or the *Publishing* meta box. Publishing links the story to the post (`_abs_published_post_id` / `_abs_source_story_id`); later story edits sync to the post.
4. **Frontend** — pages are auto-created on migration/activation:
   - *Helijutud* — `[abs_audio_library]` — audio library with sticky player and optional autoplay-next (logged-in users only).
   - *Minu kuulamised* — `[abs_listen_dashboard]` — personal stats, monthly breakdown, recent 10 stories, and a personalized-audio quiz.
5. **Analytics** — *Kuulamised* admin menu: listen overview across users and per-user detail.

## Architecture

```
ai-bedtime-stories.php        Plugin bootstrap, constants, activation hook
includes/
  class-abs-api.php           Chat/image HTTP clients (z.ai, OpenRouter)
  class-abs-settings.php      Settings, defaults, prompts, providers, migration
  class-abs-cpt.php           CPTs, list columns, audio/image AJAX, WAV merge
  class-abs-ideas.php         Generate Stories page, idea/story AJAX
  class-abs-publish.php       Blog-post publishing and content sync
  class-abs-frontend.php      Frontend audio player + login banner
  class-abs-listen.php        Listen tracking (AJAX) and stats
  class-abs-dashboard.php     [abs_listen_dashboard] shortcode + quiz
  class-abs-library.php       [abs_audio_library] shortcode
  class-abs-admin-listens.php Kuulamised admin page, admin access control
assets/                       admin, dashboard, library CSS/JS + listen.js
```

### Post types

- `ai_story` — internal story record (UI visible under *Stories*).
- `abs_idea` — pending idea (hidden UI).

### Key meta

| Meta | On | Meaning |
| --- | --- | --- |
| `_abs_published_post_id` | story | Linked blog post |
| `_abs_source_story_id` | post | Back-link to story |
| `_abs_idea_description`, `_abs_neurodifference`, `_abs_character_name`, `_abs_character_role` | story | Generation context |
| `_abs_audio_script`, `_abs_audio_files`, `_abs_audio_full` | story | Audio script, per-line WAVs, merged WAV (in `uploads/abs-audio/`) |
| `_abs_cover_attachment` | story | Cover image attachment |
| `_abs_listens`, `_abs_listen_log` | user | Per-user listen stats/log (log capped at 1000) |
| `_abs_listen_count` | post | Total play count |
| `_abs_quiz_interest`, `_abs_quiz_price`, `_abs_quiz_time` | user | Dashboard quiz answers |

### AJAX endpoints

`abs_generate_ideas`, `abs_generate_story`, `abs_discard_idea`, `abs_update_idea`, `abs_generate_audio`, `abs_save_audio`, `abs_generate_line_audio`, `abs_merge_audio`, `abs_generate_image`, `abs_test_connection`, `abs_track_listen`, `abs_quiz` — all nonce-checked (`abs_admin` / `abs_listen` / `abs_quiz`) and capability-checked (`manage_options` where admin-only).

## External services

- **z.ai** — LLM chat completions (stories, ideas, audio scripts).
- **OpenRouter** — LLM chat (optional) and image generation (cover art).
- **jutusta.ee** — Estonian TTS (per-line WAV generation).

API keys are stored in the `abs_settings` option and never exposed to the frontend. Audio files are written to `wp-content/uploads/abs-audio/` and are served directly (not access-restricted); the *player UI* is limited to logged-in users.

## Notes & troubleshooting

- **"API key is not configured"** — set the provider key in *AI Stories → Settings*.
- **HTTP 401 / 429** — invalid key or exhausted plan quota.
- **TTS speed < 0.7** — jutusta.ee currently returns HTTP 502; keep stream mode on and speed ≥ 0.7.
- **Line generation retries** — each TTS line retries up to 3 times; "Genereeri kõik" stops after 5 consecutive failures.
- **Migration** — on version change (`admin_init`), outdated default prompts/provider fields are upgraded in place and the library/dashboard pages are ensured.
