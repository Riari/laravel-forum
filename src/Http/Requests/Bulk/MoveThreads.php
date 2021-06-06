<?php

namespace TeamTeaTime\Forum\Http\Requests\Bulk;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use TeamTeaTime\Forum\Actions\MoveThreads as Action;
use TeamTeaTime\Forum\Events\UserBulkMovedThreads;
use TeamTeaTime\Forum\Http\Requests\Traits\AuthorizesAfterValidation;
use TeamTeaTime\Forum\Interfaces\FulfillableRequest;
use TeamTeaTime\Forum\Models\BaseModel;
use TeamTeaTime\Forum\Models\Category;
use TeamTeaTime\Forum\Models\Thread;

class MoveThreads extends FormRequest implements FulfillableRequest
{
    use AuthorizesAfterValidation;

    private Collection $sourceCategories;
    private Category $destinationCategory;

    public function rules(): array
    {
        return [
            'threads' => ['required', 'array'],
            'category_id' => ['required', 'int', 'exists:forum_categories,id']
        ];
    }

    public function authorizeValidated(): bool
    {
        $destinationCategory = $this->getDestinationCategory();

        if (! ($this->user()->can('view', $destinationCategory) || $this->user()->can('moveThreadsTo', $destinationCategory)))
        {
            return false;
        }

        foreach ($this->getSourceCategories() as $category)
        {
            if (! ($this->user()->can('view', $category) || $this->user()->can('moveThreadsFrom', $category)))
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
            $this->getDestinationCategory(),
            $this->user()->can('viewTrashedThreads')
        );
        $threads = $action->execute();

        if (! is_null($threads))
        {
            event(new UserBulkMovedThreads($this->user(), $threads, $this->getSourceCategories(), $this->getDestinationCategory()));
        }

        return $threads;
    }

    private function getSourceCategories()
    {
        if (! $this->sourceCategories)
        {
            $query = Thread::select('category_id')->distinct()->where('category_id', '!=', $this->validated()['category_id']);

            if (! $this->user()->can('viewTrashedThreads'))
            {
                $query = $query->whereNull(Thread::DELETED_AT);
            }

            $this->sourceCategories = Category::whereIn('id', $query->get()->pluck('category_id'));
        }
        
        return $this->sourceCategories;
    }

    private function getDestinationCategory()
    {
        if (! $this->destinationCategory)
        {
            $this->destinationCategory = Category::find($this->validated()['category_id']);
        }
        
        return $this->destinationCategory;
    }
}
