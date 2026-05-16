# Laravel Voting Frontend

Laravel application for the Full-Stack Developer Assessment. It displays WordPress posts from a custom voting plugin and lets authenticated users vote via AJAX.

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm
- MySQL/SQLite (default: SQLite)
- WordPress with the **Post Votes API** plugin active (`wordpress-voting/wp-content/plugins/post-votes-api`)

## WordPress setup

1. Install WordPress and activate the **Post Votes API** plugin.
2. (Recommended) Add to `wp-config.php` before `/* That's all, stop editing! */`:

```php
define('PVA_SECRET_TOKEN', 'your_secret_token_here');
```

3. Create a few published posts in WordPress.
4. Verify the API:
   - Posts: `GET /wp-json/wp/v2/posts` — each post should include a `votes` object.
   - Vote API: `POST /wp-json/post-votes/v1/vote` with header `Authorization: Bearer your_secret_token_here`.

## Laravel setup

```bash
cd laravel-voting
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env`:

```env
APP_URL=http://localhost:8000

WORDPRESS_API_URL=http://localhost/wordpress-voting/wp-json/wp/v2/posts
WORDPRESS_VOTE_API_URL=http://localhost/wordpress-voting/wp-json/post-votes/v1/vote
WORDPRESS_SECRET_TOKEN=your_secret_token_here
```

`WORDPRESS_SECRET_TOKEN` must match `PVA_SECRET_TOKEN` in WordPress.

```bash
php artisan migrate
npm install
npm run build
php artisan serve
```

Open `http://localhost:8000`.

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
| `app/Http/Controllers/PostController.php` | Fetches posts from WordPress |
| `app/Http/Controllers/VoteController.php` | Auth-only vote proxy to WordPress |
| `app/Models/PostVote.php` | Per-user vote state in Laravel DB |
| `resources/views/post/posts.blade.php` | Posts UI and vote JavaScript |
| `config/services.php` | WordPress URL and token config |

## License

MIT
