<?php

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\SmartSchedulerServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app){
        return [SmartSchedulerServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void{
        $app["config"]->set("database.default","testing");
        $app["config"]->set("database.connections.testing", [
        "driver"=> "sqlite",
        "database"=> ":memory:",
        "prefix"=> "",
        ]);
    }

    protected function defineDatabaseMigrations(): void{
        $this->loadMigrationsFrom(__DIR__ ."/../database/migrations");
    }
}
