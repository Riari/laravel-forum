<?php

namespace TeamTeaTime\Forum\Http\Requests\Bulk;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use TeamTeaTime\Forum\Actions\Bulk\DeletePosts as Action;
use TeamTeaTime\Forum\Events\UserBulkDeletedPosts;
use TeamTeaTime\Forum\Http\Requests\Traits\AuthorizesAfterValidation;
use TeamTeaTime\Forum\Http\Requests\Traits\HandlesDeletion;
use TeamTeaTime\Forum\Interfaces\FulfillableRequest;
use TeamTeaTime\Forum\Models\Post;

class DeletePosts extends FormRequest implements FulfillableRequest
{
    use AuthorizesAfterValidation, HandlesDeletion;

    public function rules(): array
    {
        return [
            'posts' => ['required', 'array'],
            'permadelete' => ['boolean']
        ];
    }

    public function authorizeValidated(): bool
    {
        $query = Post::query();

        if ($this->user()->can('viewTrashedPosts'))
        {
            $query = $query->withTrashed();
        }

        $posts = $query->with(['thread', 'thread.category'])->whereIn('id', $this->validated()['posts']);

        foreach ($posts as $post)
        {
            if (! ($this->user()->can('view', $post->thread->category) || $this->user()->can('delete', $post)))
            {
                return false;
            }
        }

        return true;
    }

    public function fulfill()
    {
        $action = new Action(
            $this->validated()['posts'],
            $this->user()->can('viewTrashedPosts'),
            $this->isPermaDeleting()
        );
        $posts = $action->execute();

        if (! is_null($posts))
        {
            event(new UserBulkDeletedPosts($this->user(), $posts));
        }

        return $posts;
    }
}
