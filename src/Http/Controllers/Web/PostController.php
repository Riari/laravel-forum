<?php

namespace TeamTeaTime\Forum\Http\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use TeamTeaTime\Forum\Events\UserCreatingPost;
use TeamTeaTime\Forum\Events\UserEditingPost;
use TeamTeaTime\Forum\Events\UserViewingPost;
use TeamTeaTime\Forum\Http\Requests\CreatePost;
use TeamTeaTime\Forum\Http\Requests\DeletePost;
use TeamTeaTime\Forum\Http\Requests\RestorePost;
use TeamTeaTime\Forum\Http\Requests\UpdatePost;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Support\Web\Forum;

class PostController extends BaseController
{
    public function show(Request $request, Thread $thread, string $postSlug, Post $post): View
    {
        $this->authorize('view', $post);

        event(new UserViewingPost($request->user(), $post));

        return view('forum::post.show', compact('category', 'thread', 'post'));
    }

    public function create(Request $request, Thread $thread): View
    {
        $this->authorize('reply', $thread);

        event(new UserCreatingPost($request->user(), $thread));

        $post = $request->has('post') ? $thread->posts->find($request->input('post')) : null;

        return view('forum::post.create', compact('thread', 'post'));
    }

    public function store(CreatePost $request, Thread $thread): RedirectResponse
    {
        $this->authorize('reply', $thread);

        $post = $request->fulfill();

        Forum::alert('success', 'general.reply_added');

        return redirect(Forum::route('thread.show', $post));
    }

    public function edit(Request $request, Thread $thread, $threadSlug, Post $post): View
    {
        if ($post->trashed()) return abort(404);

        $this->authorize('edit', $post);

        event(new UserEditingPost($request->user(), $post));

        $thread = $post->thread;
        $category = $post->thread->category;

        return view('forum::post.edit', compact('category', 'thread', 'post'));
    }

    public function update(UpdatePost $request, Thread $thread, $threadSlug, Post $post): RedirectResponse
    {
        $this->authorize('edit', $post);

        $post = $request->fulfill();

        Forum::alert('success', 'posts.updated');

        return redirect(Forum::route('thread.show', $post));
    }

    public function confirmDelete(Request $request, Thread $thread, $threadSlug, Post $post): View
    {
        return view('forum::post.confirm-delete', ['category' => $thread->category, 'thread' => $thread, 'post' => $post]);
    }

    public function confirmRestore(Request $request, Thread $thread, $threadSlug, Post $post): View
    {
        return view('forum::post.confirm-restore', ['category' => $thread->category, 'thread' => $thread, 'post' => $post]);
    }

    public function destroy(DeletePost $request): RedirectResponse
    {
        $post = $request->fulfill();

        if (is_null($post)) return $this->invalidSelectionResponse();

        Forum::alert('success', 'posts.deleted', 1);

        return redirect(Forum::route('thread.show', $post->thread));
    }

    public function restore(RestorePost $request): RedirectResponse
    {
        $post = $request->fulfill();

        if (is_null($post)) return $this->invalidSelectionResponse();

        Forum::alert('success', 'posts.updated', 1);

        return redirect(Forum::route('thread.show', $post));
    }
}
