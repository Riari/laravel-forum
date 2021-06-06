<?php

// Categories
$r->group(['prefix' => 'categories', 'as' => 'category.'], function ($r)
{
    $r->get('/', ['as' => 'index', 'uses' => 'CategoryController@index']);
    $r->post('/', ['as' => 'store', 'uses' => 'CategoryController@store']);
    $r->get('{category}', ['as' => 'fetch', 'uses' => 'CategoryController@fetch']);
    $r->delete('{category}', ['as' => 'delete', 'uses' => 'CategoryController@destroy']);
    $r->patch('{category}', ['as' => 'update', 'uses' => 'CategoryController@update']);
});

// Threads
$r->group(['prefix' => 'threads', 'as' => 'thread.'], function ($r)
{
    $r->get('/', ['as' => 'index', 'uses' => 'ThreadController@index']);
    $r->get('new', ['as' => 'new.index', 'uses' => 'ThreadController@indexNew']);
    $r->patch('mark-as-read', ['as' => 'unread.mark-as-read', 'uses' => 'ThreadController@markAsRead']);
    $r->post('/', ['as' => 'store', 'uses' => 'ThreadController@store']);
    $r->get('{thread}', ['as' => 'fetch', 'uses' => 'ThreadController@fetch']);
    $r->delete('{thread}', ['as' => 'delete', 'uses' => 'ThreadController@destroy']);
    $r->patch('{thread}', ['as' => 'update', 'uses' => 'ThreadController@update']);
});

// Posts
$r->group(['prefix' => 'posts', 'as' => 'post.'], function ($r)
{
    $r->get('/', ['as' => 'index', 'uses' => 'PostController@index']);
    $r->post('/', ['as' => 'store', 'uses' => 'PostController@store']);
    $r->get('{post}', ['as' => 'fetch', 'uses' => 'PostController@fetch']);
    $r->patch('{post}', ['as' => 'update', 'uses' => 'PostController@update']);
    $r->delete('{post}', ['as' => 'delete', 'uses' => 'PostController@destroy']);
    $r->post('{post}/restore', ['as' => 'restore', 'uses' => 'PostController@restore']);
});

// Bulk actions
$r->group(['prefix' => 'bulk', 'as' => 'bulk.', 'namespace' => 'Bulk'], function ($r)
{
    // Categories
    $r->group(['prefix' => 'categories', 'as' => 'category.'], function ($r)
    {
        $r->post('manage', ['as' => 'manage', 'uses' => 'CategoryController@manage']);
    });

    // Threads
    $r->group(['prefix' => 'threads', 'as' => 'thread.'], function ($r)
    {
        $r->delete('/', ['as' => 'delete', 'uses' => 'ThreadController@bulkDestroy']);
        $r->patch('restore', ['as' => 'restore', 'uses' => 'ThreadController@bulkRestore']);
        $r->patch('move', ['as' => 'move', 'uses' => 'ThreadController@bulkMove']);
        $r->patch('lock', ['as' => 'lock', 'uses' => 'ThreadController@bulkLock']);
        $r->patch('unlock', ['as' => 'unlock', 'uses' => 'ThreadController@bulkUnlock']);
        $r->patch('pin', ['as' => 'pin', 'uses' => 'ThreadController@bulkPin']);
        $r->patch('unpin', ['as' => 'unpin', 'uses' => 'ThreadController@bulkUnpin']);
    });

    // Posts
    $r->group(['prefix' => 'posts', 'as' => 'post.'], function ($r)
    {
        $r->patch('/', ['as' => 'update', 'uses' => 'PostController@bulkUpdate']);
        $r->delete('/', ['as' => 'delete', 'uses' => 'PostController@bulkDestroy']);
        $r->patch('restore', ['as' => 'restore', 'uses' => 'PostController@bulkRestore']);
    });
});

$r->bind('thread', function ($value)
{
    $thread = \TeamTeaTime\Forum\Models\Thread::withTrashed()->with('category')->find($value);

    if ($thread->trashed() && ! Gate::allows('viewTrashedThreads')) return null;

    return $thread;
});

$r->bind('post', function ($value)
{
    $post = \TeamTeaTime\Forum\Models\Post::withTrashed()->with(['thread', 'thread.category'])->find($value);

    if ($post->trashed() && ! Gate::allows('viewTrashedPosts')) return null;

    return $post;
});