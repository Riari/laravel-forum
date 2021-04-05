<?php

namespace TeamTeaTime\Forum\Database\Factories;

use TeamTeaTime\Forum\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'title' => $this->faker->name,
            'description' => $this->faker->text,
            'accepts_threads' => 1,
            'thread_count' => 0,
            'post_count' => 0,
            'is_private' => 1,
            'color' => '#007BFF'
        ];
    }
}