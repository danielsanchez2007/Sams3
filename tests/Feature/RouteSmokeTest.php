<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteSmokeTest extends TestCase
{
    public function test_auth_pages_are_reachable_for_guests(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/password/reset')->assertStatus(200);

        $registerResponse = $this->get('/register');
        if (config('sams.allow_registration', false)) {
            $registerResponse->assertStatus(200);
        } else {
            $registerResponse->assertStatus(404);
        }
    }

    public function test_get_routes_without_parameters_do_not_return_server_errors(): void
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            $methods = $route->methods();
            $hasGet = in_array('GET', $methods, true) || in_array('HEAD', $methods, true);
            $uri = $route->uri();

            return $hasGet && ! str_contains($uri, '{');
        });

        foreach ($routes as $route) {
            $uri = $route->uri();
            $path = $uri === '/' ? '/' : '/' . ltrim($uri, '/');
            $response = $this->get($path);

            $this->assertLessThan(
                500,
                $response->getStatusCode(),
                "Route [{$path}] returned {$response->getStatusCode()}."
            );
        }
    }
}
