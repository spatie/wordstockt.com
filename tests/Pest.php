<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class, RefreshDatabase::class);

expect()->extend('toBeRulePassed', fn () => $this->passed->toBeTrue());

expect()->extend('toBeRuleFailed', fn () => $this->passed->toBeFalse());

require_once __DIR__.'/Helpers/TestHelpers.php';
