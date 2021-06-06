<?php

namespace TeamTeaTime\Forum\Policies;

use Illuminate\Support\Facades\Gate;
use TeamTeaTime\Forum\Models\Thread;
use TeamTeaTime\Forum\Policies\Traits\ChecksCategoryVisibility;

class ThreadPolicy
{
    use ChecksCategoryVisibility;

    public function view($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category);
    }

    public function deletePosts($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category);
    }

    public function restorePosts($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category);
    }

    public function rename($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category) && $user->getKey() === $thread->author_id;
    }

    public function reply($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category) && ! $thread->locked;
    }

    public function delete($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category)
            && (Gate::forUser($user)->allows('deleteThreads', $thread->category) || $user->getKey() === $thread->author_id);
    }

    public function restore($user, Thread $thread): bool
    {
        return $this->canUserViewCategory($user, $thread->category)
            && (Gate::forUser($user)->allows('restoreThreads', $thread->category) || $user->getKey() === $thread->author_id);
    }
}
