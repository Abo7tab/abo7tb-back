<?php

namespace App\Application\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiVersion
{
    /**
     * Supported API versions
     */
    protected array $supportedVersions = ['v1'];

    /**
     * Handle an incoming request.
     * Validates that the requested API version is supported.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->route('version') ?? 'v1';

        if (!in_array($version, $this->supportedVersions, true)) {
            return response()->json([
                'success' => false,
                'message' => "API version '{$version}' is not supported.",
                'supported_versions' => $this->supportedVersions,
            ], Response::HTTP_BAD_REQUEST);
        }

        return $next($request);
    }
}
