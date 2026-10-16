<?php

namespace Tests\Feature;

use App\Http\Controllers\SupportTeam\MyClassController;
use App\Http\Requests\MyClass\ClassUpdate;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class MyClassControllerTest extends TestCase
{
    public function test_update_redirects_back_to_classes_page_after_success()
    {
        $repo = Mockery::mock(MyClassRepo::class);
        $repo->shouldReceive('update')->once()->with(7, ['name' => 'Science', 'code' => 'SCI'])->andReturn(true);

        $request = Mockery::mock(ClassUpdate::class);
        $request->shouldReceive('only')->once()->with(['name', 'code'])->andReturn(['name' => 'Science', 'code' => 'SCI']);

        $controller = new MyClassController($repo, Mockery::mock(UserRepo::class));
        $response = $controller->update($request, 7);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('classes.index'), $response->getTargetUrl());
    }

    public function test_delete_selected_deletes_each_class_and_redirects_to_classes_page()
    {
        $repo = Mockery::mock(MyClassRepo::class);
        $repo->shouldReceive('delete')->once()->with(7)->andReturn(1);
        $repo->shouldReceive('delete')->once()->with(9)->andReturn(1);

        $request = new class extends Request {
            public function validate(array $rules, ...$params)
            {
                return ['class_ids' => [7, 9]];
            }
        };

        $controller = new MyClassController($repo, Mockery::mock(UserRepo::class));
        $response = $controller->deleteSelected($request);

        $this->assertTrue($response->isRedirect());
        $this->assertEquals(route('classes.index'), $response->getTargetUrl());
    }
}
