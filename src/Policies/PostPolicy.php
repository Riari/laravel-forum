<?php

namespace TeamTeaTime\Forum\Policies;

use Illuminate\Support\Facades\Gate;
use TeamTeaTime\Forum\Models\Post;
use TeamTeaTime\Forum\Policies\Traits\ChecksCategoryVisibility;

class PostPolicy
{
    use ChecksCategoryVisibility;

    public function view($user, Post $post): bool
    {
        return $this->canUserViewCategory($user, $post->thread->category);
    }

    public function edit($user, Post $post): bool
    {
        return $this->canUserViewCategory($user, $post->thread->category) && $user->getKey() === $post->author_id;
    }

    public function delete($user, Post $post): bool
    {
        return $this->canUserViewCategory($user, $post->thread->category)
            && Gate::forUser($user)->allows('deletePosts', $post->thread) || $user->getKey() === $post->author_id;
    }

    public function restore($user, Post $post): bool
    {
        return $this->canUserViewCategory($user, $post->thread->category)
            && Gate::forUser($user)->allows('restorePosts', $post->thread) || $user->getKey() === $post->author_id;
    }
}
