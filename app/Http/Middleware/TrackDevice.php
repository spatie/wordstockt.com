<?php

namespace App\Http\Middleware;

use App\Domain\User\Actions\TrackDeviceAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackDevice
{
    public function __construct(private TrackDeviceAction $trackDevice) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->trackDevice->execute($request);
    }
}
