# Full-Stack Assessment (WordPress + Laravel)

Monorepo for the Full-Stack Developer Assessment.

| Folder | Description |
|--------|-------------|
| `wordpress-voting/wp-content/plugins/post-votes-api/` | WordPress voting plugin (backend API) |
| `laravel-voting/` | Laravel frontend (auth, posts UI, AJAX voting) |

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
- MySQL/SQLite (default: SQLite)
- WordPress installation

## WordPress setup

1. Install WordPress on your server.
2. Copy `wordpress-voting/wp-content/plugins/post-votes-api/` into your WordPress `wp-content/plugins/` folder.
3. Activate **Post Votes API** in WP Admin → Plugins.
4. (Recommended) Add to `wp-config.php` before `/* That's all, stop editing! */`:

```php
define('PVA_SECRET_TOKEN', 'your_secret_token_here');
```

5. Create a few published posts in WordPress.
6. Verify the API:
   - Posts: `GET /wp-json/wp/v2/posts` — each post should include a `votes` object.
   - Vote API: `POST /wp-json/post-votes/v1/vote` with header `Authorization: Bearer your_secret_token_here`.

## Laravel setup

```bash
cd laravel-voting
composer install
cp .env.example .env
php artisan key:generate
```

Configure `laravel-voting/.env`:

```env
APP_URL=http://localhost:8000

WORDPRESS_API_URL=http://localhost/wordpress-voting/wp-json/wp/v2/posts
WORDPRESS_VOTE_API_URL=http://localhost/wordpress-voting/wp-json/post-votes/v1/vote
WORDPRESS_SECRET_TOKEN=your_secret_token_here
```

Update `WORDPRESS_API_URL` and `WORDPRESS_VOTE_API_URL` to match your WordPress site URL.

`WORDPRESS_SECRET_TOKEN` must match `PVA_SECRET_TOKEN` in WordPress.

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `http://localhost:8000`.

## Authentication

User authentication is implemented with [Laravel Breeze](https://laravel.com/docs/starter-kits#laravel-breeze) (Blade stack). Login, registration, and logout routes live in `laravel-voting/routes/auth.php`.

Breeze was used during development and is already scaffolded in this repository. **No extra install step is required** beyond `composer install` and `php artisan migrate` above.

To verify auth locally, visit:

- `/register` — create an account
- `/login` — sign in
- Logout — available from the navigation when authenticated

### Development notes (optional)

If you are rebuilding the Laravel app from scratch, Breeze can be installed with:

```bash
cd laravel-voting
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

## Features

- Public blog listing (title, content, vote counts from WordPress)
- Registration, login, logout (Laravel Breeze)
- Logged-out users: Upvote/Downvote links redirect to login
- Logged-in users: AJAX voting through Laravel → WordPress API
- Vote states: cast, remove (same button), or switch (up ↔ down)
- WordPress admin dashboard page for post vote statistics
- WordPress object cache invalidation after successful vote

## Project structure

| Path | Purpose |
|------|---------|
| `wordpress-voting/wp-content/plugins/post-votes-api/post-votes-api.php` | WordPress plugin (votes, REST API, admin page) |
| `laravel-voting/app/Http/Controllers/PostController.php` | Fetches posts from WordPress |
| `laravel-voting/app/Http/Controllers/VoteController.php` | Auth-only vote proxy to WordPress |
| `laravel-voting/app/Models/PostVote.php` | Per-user vote state in Laravel DB |
| `laravel-voting/resources/views/post/posts.blade.php` | Posts UI and vote JavaScript |
| `laravel-voting/config/services.php` | WordPress URL and token config |

## License

MIT
