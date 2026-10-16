<?php

namespace Tests\Unit;

use App\Http\Requests\MyClass\ClassCreate;
use PHPUnit\Framework\TestCase;

class ClassCreateValidationTest extends TestCase
{
    public function test_class_creation_requires_a_code()
    {
        $request = new ClassCreate();
        $rules = $request->rules();

        $this->assertArrayHasKey('code', $rules);
        $this->assertStringContainsString('required', $rules['code']);
    }
}
