# Eveil

Eveil is a self-hosted assistant for managing YouTube video metadata: generate
thumbnails from a customizable template, draft descriptions, and chat with an
AI agent about your channel or a specific video — all backed by any AI
provider you configure, including local/self-hosted LLMs (LiteLLM, Ollama,
vLLM, ...).

Built with Laravel, Inertia, Vue, [Nuxt UI](https://ui.nuxt.com) and
[laravel/ai](https://laravel.com/docs/ai-sdk).

## Installation

Requires [Docker](https://docs.docker.com/get-docker/) and Docker Compose.

```bash
git clone https://github.com/mydnic/eveil.git
cd eveil
cp .env.example .env
```

Generate an application key and add it to `.env`:

```bash
docker compose run --rm eveil php artisan key:generate --show
```

Copy the printed value into `APP_KEY=` in `.env`, then edit `.env` to point
`OPENAI_URL` (and `OPENAI_API_KEY` if needed) at your AI provider — an
OpenAI-compatible local gateway by default, or any provider listed in
`config/ai.php`.

Start the app:

```bash
docker compose up -d
```

Eveil is now available at `http://localhost:8000` (configurable via
`APP_PORT` in `.env`).
