<?php

namespace TeamTeaTime\Forum\Http\Requests\Bulk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use TeamTeaTime\Forum\Actions\Bulk\DeleteThreads as Action;
use TeamTeaTime\Forum\Events\UserBulkDeletedThreads;
use TeamTeaTime\Forum\Http\Requests\Traits\AuthorizesAfterValidation;
use TeamTeaTime\Forum\Http\Requests\Traits\HandlesDeletion;
use TeamTeaTime\Forum\Interfaces\FulfillableRequest;
use TeamTeaTime\Forum\Models\Thread;

class DeleteThreads extends FormRequest implements FulfillableRequest
{
    use AuthorizesAfterValidation, HandlesDeletion;

    public function rules(): array
    {
        return [
            'threads' => ['required', 'array'],
            'permadelete' => ['boolean']
        ];
    }

    public function authorizeValidated(): bool
    {
        // Eloquent is used here so that we get a collection of Thread instead of
        // stdClass in order for the gate to infer the policy to use.
        $threads = Thread::whereIn('id', $this->validated()['threads'])->with('category')->get();
        foreach ($threads as $thread)
        {
            if (! ($this->user()->can('view', $category) || $this->user()->can('delete', $thread)))
            {
                return false;
            }
        }

        return true;
    }

    public function fulfill()
    {
        $action = new Action(
            $this->validated()['threads'],
            $this->user()->can('viewTrashedPosts'),
            $this->isPermaDeleting()
        );
        $threads = $action->execute();

        if (! is_null($threads))
        {
            event(new UserBulkDeletedThreads($this->user(), $threads));
        }

        return $threads;
    }
}
