<?php

$authMiddleware = config('forum.web.router.auth_middleware');
$prefix = config('forum.web.route_prefixes');

$r->get('/', ['as' => 'index', 'uses' => 'CategoryController@index']);

$r->get('recent', ['as' => 'recent', 'uses' => 'ThreadController@recent']);

$r->get('unread', ['as' => 'unread', 'uses' => 'ThreadController@unread']);
$r->patch('unread/mark-as-read', ['as' => 'unread.mark-as-read', 'uses' => 'ThreadController@markAsRead'])->middleware($authMiddleware);

$r->get('manage', ['as' => 'category.manage', 'uses' => 'CategoryController@manage'])->middleware($authMiddleware);

$r->post($prefix['category'] . '/create', ['as' => 'category.store', 'uses' => 'CategoryController@store']);
$r->group(['prefix' => $prefix['category'] . '/{category}-{category_slug}'], function ($r) use ($prefix, $authMiddleware)
{
    $r->get('/', ['as' => 'category.show', 'uses' => 'CategoryController@show']);
    $r->patch('/', ['as' => 'category.update', 'uses' => 'CategoryController@update'])->middleware($authMiddleware);
    $r->delete('/', ['as' => 'category.delete', 'uses' => 'CategoryController@destroy'])->middleware($authMiddleware);

    $r->get($prefix['thread'] . '/create', ['as' => 'thread.create', 'uses' => 'ThreadController@create']);
    $r->post($prefix['thread'] . '/create', ['as' => 'thread.store', 'uses' => 'ThreadController@store'])->middleware($authMiddleware);
});

$r->group(['prefix' => $prefix['thread'] . '/{thread}-{thread_slug}'], function ($r) use ($prefix, $authMiddleware)
{
    $r->get('/', ['as' => 'thread.show', 'uses' => 'ThreadController@show']);
    $r->get($prefix['post'] . '/{post}', ['as' => 'post.show', 'uses' => 'PostController@show']);

    $r->group(['middleware' => $authMiddleware], function ($r) use ($prefix, $authMiddleware) {
        $r->patch('/', ['as' => 'thread.update', 'uses' => 'ThreadController@update']);
        $r->post('lock', ['as' => 'thread.lock', 'uses' => 'ThreadController@lock']);
        $r->post('unlock', ['as' => 'thread.unlock', 'uses' => 'ThreadController@unlock']);
        $r->post('pin', ['as' => 'thread.pin', 'uses' => 'ThreadController@pin']);
        $r->post('unpin', ['as' => 'thread.unpin', 'uses' => 'ThreadController@unpin']);
        $r->post('move', ['as' => 'thread.move', 'uses' => 'ThreadController@move']);
        $r->post('restore', ['as' => 'thread.restore', 'uses' => 'ThreadController@restore']);
        $r->post('rename', ['as' => 'thread.rename', 'uses' => 'ThreadController@rename']);
        $r->delete('/', ['as' => 'thread.delete', 'uses' => 'ThreadController@destroy']);

        $r->get('reply', ['as' => 'post.create', 'uses' => 'PostController@create']);
        $r->post('reply', ['as' => 'post.store', 'uses' => 'PostController@store']);
        $r->get($prefix['post'] . '/{post}/edit', ['as' => 'post.edit', 'uses' => 'PostController@edit']);
        $r->patch($prefix['post'] . '/{post}', ['as' => 'post.update', 'uses' => 'PostController@update']);
        $r->get($prefix['post'] . '/{post}/delete', ['as' => 'post.confirm-delete', 'uses' => 'PostController@confirmDelete']);
        $r->get($prefix['post'] . '/{post}/restore', ['as' => 'post.confirm-restore', 'uses' => 'PostController@confirmRestore']);
        $r->delete($prefix['post'] . '/{post}', ['as' => 'post.delete', 'uses' => 'PostController@destroy']);
        $r->post($prefix['post'] . '/{post}/restore', ['as' => 'post.restore', 'uses' => 'PostController@restore']);
    });
});

$r->group(['prefix' => 'bulk', 'as' => 'bulk.', 'namespace' => 'Bulk', 'middleware' => $authMiddleware], function ($r)
{
    $r->post('thread/move', ['as' => 'thread.move', 'uses' => 'ThreadController@move']);
    $r->post('thread/lock', ['as' => 'thread.lock', 'uses' => 'ThreadController@lock']);
    $r->post('thread/unlock', ['as' => 'thread.unlock', 'uses' => 'ThreadController@unlock']);
    $r->post('thread/pin', ['as' => 'thread.pin', 'uses' => 'ThreadController@pin']);
    $r->post('thread/unpin', ['as' => 'thread.unpin', 'uses' => 'ThreadController@unpin']);
    $r->delete('thread', ['as' => 'thread.delete', 'uses' => 'ThreadController@destroy']);
    $r->post('thread/restore', ['as' => 'thread.restore', 'uses' => 'ThreadController@restore']);

    $r->delete('post', ['as' => 'post.delete', 'uses' => 'PostController@destroy']);
    $r->post('post/restore', ['as' => 'post.restore', 'uses' => 'PostController@restore']);

});

$r->bind('category', function ($value)
{
    return \TeamTeaTime\Forum\Models\Category::findOrFail($value);
});

$r->bind('thread', function ($value)
{
    $thread = \TeamTeaTime\Forum\Models\Thread::withTrashed()->with('category')->find($value);

    if (is_null($thread)) abort(404);

    if ($thread->trashed() && ! Gate::allows('viewTrashedThreads')) return null;

    return $thread;
});

$r->bind('post', function ($value)
{
    $post = \TeamTeaTime\Forum\Models\Post::withTrashed()->with(['thread', 'thread.category'])->find($value);

    if (is_null($post)) abort(404);

    if ($post->trashed() && ! Gate::allows('viewTrashedPosts')) return null;

    return $post;
});