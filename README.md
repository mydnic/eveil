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

## Connecting a YouTube channel

Eveil needs its own Google OAuth credentials to read your channel's videos
and update thumbnails. This is a one-time setup on
[Google Cloud Console](https://console.cloud.google.com/):

1. Create a new project (or pick an existing one).
2. Go to **APIs & Services → Library**, search for **YouTube Data API v3**,
   and enable it.
3. Go to **APIs & Services → OAuth consent screen**. Choose **External**,
   fill in the required fields, and add your own Google account under
   **Test users** (no Google review needed for personal use).
4. Go to **APIs & Services → Credentials → Create Credentials → OAuth client
   ID**. Choose **Web application**, and under **Authorized redirect URIs**
   add the value of `GOOGLE_REDIRECT_URI` from your `.env`
   (`http://localhost:8000/youtube/callback` by default — update the host/port
   if you changed `APP_PORT` or are deploying behind a domain).
5. Copy the generated **Client ID** and **Client secret** into `.env`:

   ```bash
   GOOGLE_CLIENT_ID=...
   GOOGLE_CLIENT_SECRET=...
   ```

6. Restart the app (`docker compose up -d`) and click **Connect YouTube**.
