<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with('user')->latest()->paginate(15);
        return Inertia::render('Admin/Posts/Index', ['posts' => $posts]);
    }

    public function create()
    {
        return Inertia::render('Admin/Posts/Edit', ['post' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:posts,slug',
            'category'     => 'required|string|max:100',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'cover_image'  => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ], [
            'title.required'    => 'El título es obligatorio.',
            'slug.required'     => 'El slug es obligatorio.',
            'slug.unique'       => 'Ya existe un post con ese slug.',
            'category.required' => 'La categoría es obligatoria.',
            'content.required'  => 'El contenido es obligatorio.',
        ]);

        try {
            $post = Post::create([...$validated, 'user_id' => auth()->id()]);
            return redirect()->route('admin.posts.index')
                ->with('success', "El post \"{$post->title}\" fue creado correctamente.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo crear el post. Intentá de nuevo.');
        }
    }

    public function edit(Post $post)
    {
        return Inertia::render('Admin/Posts/Edit', ['post' => $post]);
    }

    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'slug'         => 'required|string|max:255|unique:posts,slug,' . $post->id,
            'category'     => 'required|string|max:100',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'cover_image'  => 'nullable|string|max:500',
            'published_at' => 'nullable|date',
        ], [
            'title.required'    => 'El título es obligatorio.',
            'slug.required'     => 'El slug es obligatorio.',
            'slug.unique'       => 'Ya existe un post con ese slug.',
            'category.required' => 'La categoría es obligatoria.',
            'content.required'  => 'El contenido es obligatorio.',
        ]);

        try {
            $post->update($validated);
            return redirect()->route('admin.posts.index')
                ->with('success', "El post \"{$post->title}\" fue actualizado correctamente.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo actualizar el post.');
        }
    }

    public function destroy(Post $post)
    {
        try {
            $title = $post->title;
            $post->delete();
            return back()->with('success', "El post \"{$title}\" fue eliminado.");
        } catch (\Exception $e) {
            return back()->with('error', 'No se pudo eliminar el post.');
        }
    }
}
