<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\BuildsFeatureTestUsers;

abstract class TestCase extends BaseTestCase
{
    use BuildsFeatureTestUsers;
}
