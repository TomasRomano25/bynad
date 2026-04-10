<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Inertia\Inertia;

class LandingController extends Controller
{
    public function index()
    {
        $posts = Post::published()->with('user')->latest('published_at')->take(3)->get();

        return Inertia::render('Landing/Index', [
            'posts' => $posts,
        ]);
    }

    public function blog()
    {
        $posts = Post::published()->with('user')->latest('published_at')->paginate(9);

        return Inertia::render('Landing/Blog', [
            'posts' => $posts,
        ]);
    }

    public function post(Post $post)
    {
        if (!$post->published_at || $post->published_at->isFuture()) {
            abort(404);
        }

        return Inertia::render('Landing/Post', [
            'post' => $post->load('user'),
        ]);
    }
}
