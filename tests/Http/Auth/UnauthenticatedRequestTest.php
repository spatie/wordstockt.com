<?php

it('returns a json 401 for unauthenticated api requests without an accept header', function (string $method, string $uri): void {
    $this->call($method, $uri)
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
})->with([
    'user' => ['GET', '/api/auth/user'],
    'games' => ['GET', '/api/games'],
    'broadcasting auth' => ['POST', '/api/broadcasting/auth'],
]);

it('returns a json 401 for unauthenticated api requests that accept json', function (): void {
    $this->getJson('/api/auth/user')
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'Unauthenticated.']);
});
