<?php

use App\Domain\Game\Support\Rules\RuleResult;

it('creates a passing result', function (): void {
    $result = RuleResult::pass('test.rule');

    expect($result->passed)->toBeTrue()
        ->and($result->message)->toBe('')
        ->and($result->ruleIdentifier)->toBe('test.rule');
});

it('creates a failing result with message', function (): void {
    $result = RuleResult::fail('test.rule', 'Something went wrong');

    expect($result->passed)->toBeFalse()
        ->and($result->message)->toBe('Something went wrong')
        ->and($result->ruleIdentifier)->toBe('test.rule');
});

it('returns true for failing result when calling failed()', function (): void {
    $result = RuleResult::fail('test.rule', 'Error');

    expect($result->failed())->toBeTrue();
});

it('returns false for passing result when calling failed()', function (): void {
    $result = RuleResult::pass('test.rule');

    expect($result->failed())->toBeFalse();
});
