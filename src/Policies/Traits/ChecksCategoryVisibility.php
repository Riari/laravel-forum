<?php

namespace TeamTeaTime\Forum\Policies\Traits;

use Illuminate\Support\Facades\Gate;
use TeamTeaTime\Forum\Models\Category;

trait ChecksCategoryVisibility
{
    protected function canUserViewCategory($user, Category $category): bool
    {
        return ! $category->is_private || Gate::forUser($user)->allows('view', $category);
    }
}