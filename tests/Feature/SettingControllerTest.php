<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\SettingController;
use App\Repositories\MyClassRepo;
use App\Repositories\SettingRepo;
use Illuminate\View\View;
use Mockery;
use Tests\TestCase;

class SettingControllerTest extends TestCase
{
    public function test_index_sets_empty_value_for_missing_next_term_fee_setting()
    {
        $settingRepo = Mockery::mock(SettingRepo::class);
        $settingRepo->shouldReceive('all')->once()->andReturn(collect([
            (object) ['type' => 'system_name', 'description' => 'Demo School'],
            (object) ['type' => 'lock_exam', 'description' => 0],
        ]));

        $myClassRepo = Mockery::mock(MyClassRepo::class);
        $myClassRepo->shouldReceive('getTypes')->once()->andReturn(collect([
            (object) ['name' => 'Primary', 'code' => 'E'],
        ]));

        $controller = new SettingController($settingRepo, $myClassRepo);
        $response = $controller->index();

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('Demo School', $response->getData()['s']['system_name']);
        $this->assertSame('', $response->getData()['s']['next_term_fees_e']);
    }
}
