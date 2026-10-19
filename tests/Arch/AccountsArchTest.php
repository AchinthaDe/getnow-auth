<?php

declare(strict_types=1);

arch('app uses strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('domain does not import http')
    ->expect('App\Domain')
    ->not->toUse(['Illuminate\Http', 'App\Http']);

arch('controllers do not use repositories directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('App\Domain\Accounts\Repositories');

$basePath = dirname(__DIR__, 2);
$servicesPath = $basePath.'/app/Domain/Accounts/Services';
if (is_dir($servicesPath)) {
    arch('services do not use eloquent repositories directly')
        ->expect('App\Domain\Accounts\Services')
        ->not->toUse(['App\Domain\Accounts\Repositories\UserRepository', 'Illuminate\Http']);
}

$usersTrackNamespaces = array_filter([
    'App\Domain\Accounts\Services',
    'App\Domain\Accounts\Repositories',
    'App\Domain\Accounts\Policies',
    'App\Http\Controllers\Api\V1\Users',
], fn (string $ns): bool => is_dir($basePath.'/'.str_replace(['App\\', '\\'], ['app/', '/'], $ns)));

if (class_exists('App\Http\Controllers\Api\V1\Auth\AuthController')) {
    $usersTrackNamespaces[] = 'App\Http\Controllers\Api\V1\Auth\AuthController';
}

if (! empty($usersTrackNamespaces)) {
    arch('users track does not import passport')
        ->expect($usersTrackNamespaces)
        ->not->toUse('Laravel\Passport');
}
