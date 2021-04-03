<?php

namespace TeamTeaTime\Forum\Tests\Feature\Web;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\Factories\UserFactory;
use TeamTeaTime\Forum\Support\Web\Forum;
use TeamTeaTime\Forum\Tests\FeatureTestCase;

class CategoryStoreTest extends FeatureTestCase
{
    private string $route = 'category.store';

    private UserFactory $userFactory;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = UserFactory::new();
        $this->user = $this->userFactory->createOne();
    }

    /** @test */
    public function should_fail_validation_without_a_title()
    {
        $response = $this->actingAs($this->user)
            ->post(Forum::route($this->route), ['title' => ""]);

        $response->assertSessionHasErrors();
    }
}