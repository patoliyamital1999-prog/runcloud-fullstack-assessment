<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $response = Http::timeout(10)->get(config('services.wordpress.posts_url'), [
            'per_page' => 100,
            'status' => 'publish',
        ]);

        if ($response->failed()) {
            abort(502, 'Unable to fetch posts from WordPress. Please check WORDPRESS_API_URL and that the plugin is active.');
        }

        $posts = $response->json();
        $totalPosts = count($posts);

        if (!is_array($posts)) {
            abort(502, 'Invalid response from WordPress posts API.');
        }

        return view('post.posts', compact('posts','totalPosts'));
    }
}
