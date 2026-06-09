<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        putenv('SESSION_DRIVER=array');
        $_ENV['SESSION_DRIVER'] = 'array';
        $_SERVER['SESSION_DRIVER'] = 'array';

        parent::setUp();

        config(['session.driver' => 'array']);

        $this->withServerVariables([
            'HTTP_HOST' => config('app.central_domain'),
        ]);
    }
}
