<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RouteAccessSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feature_pages_are_accessible(): void
    {
        $routes = $this->publicRoutes();

        $this->assertNotEmpty($routes, 'No public feature routes were discovered.');

        $failures = [];

        foreach ($routes as $route) {
            $response = $this->get($route['uri']);
            $status = $response->getStatusCode();

            if (!in_array($status, [200, 302], true)) {
                $failures[] = "{$route['label']} returned HTTP {$status}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_auth_only_feature_pages_redirect_guests_and_load_for_authenticated_users(): void
    {
        $routes = $this->authOnlyRoutes();

        $this->assertNotEmpty($routes, 'No auth-only feature routes were discovered.');

        $guestFailures = [];
        foreach ($routes as $route) {
            $response = $this->get($route['uri']);
            if ($response->getStatusCode() !== 302) {
                $guestFailures[] = "{$route['label']} should redirect guests but returned HTTP {$response->getStatusCode()}";
            }
        }
        $this->assertSame([], $guestFailures, implode(PHP_EOL, $guestFailures));

        $user = $this->createUser();
        $this->actingAs($user);

        $authFailures = [];
        foreach ($routes as $route) {
            $response = $this->get($route['uri']);
            $status = $response->getStatusCode();

            if (!in_array($status, [200, 302], true)) {
                $authFailures[] = "{$route['label']} should load for authenticated users but returned HTTP {$status}";
            }
        }

        $this->assertSame([], $authFailures, implode(PHP_EOL, $authFailures));
    }

    public function test_module_protected_feature_pages_forbid_users_without_permission(): void
    {
        $routes = $this->moduleProtectedRoutes();

        $this->assertNotEmpty($routes, 'No module-protected feature routes were discovered.');

        $user = $this->createUser();
        $this->actingAs($user);

        $failures = [];
        foreach ($routes as $route) {
            $response = $this->get($route['uri']);
            if ($response->getStatusCode() !== 403) {
                $failures[] = "{$route['label']} should forbid missing permission {$route['module_key']} but returned HTTP {$response->getStatusCode()}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_module_protected_feature_pages_load_for_users_with_permissions(): void
    {
        $routes = $this->moduleProtectedRoutes();

        $user = $this->createUser([], $this->allModuleKeys($routes));
        $this->actingAs($user);

        $failures = [];
        foreach ($routes as $route) {
            $response = $this->get($route['uri']);
            $status = $response->getStatusCode();

            if (!in_array($status, [200, 302], true)) {
                $failures[] = "{$route['label']} should load with permission {$route['module_key']} but returned HTTP {$status}";
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    /**
     * @return array<int, array{label: string, uri: string, module_key?: string|null}>
     */
    private function publicRoutes(): array
    {
        return $this->collectRoutes(fn (Route $route) => !$this->hasAuthMiddleware($route));
    }

    /**
     * @return array<int, array{label: string, uri: string, module_key?: string|null}>
     */
    private function authOnlyRoutes(): array
    {
        return $this->collectRoutes(
            fn (Route $route) => $this->hasAuthMiddleware($route) && $this->resolveModuleKey($route) === null
        );
    }

    /**
     * @return array<int, array{label: string, uri: string, module_key: string}>
     */
    private function moduleProtectedRoutes(): array
    {
        return $this->collectRoutes(function (Route $route): bool {
            return $this->hasAuthMiddleware($route) && $this->resolveModuleKey($route) !== null;
        });
    }

    /**
     * @param  callable(Route): bool  $filter
     * @return array<int, array{label: string, uri: string, module_key?: string|null}>
     */
    private function collectRoutes(callable $filter): array
    {
        return collect(RouteFacade::getRoutes())
            ->filter(fn (Route $route) => $this->isSmokeTestCandidate($route))
            ->filter($filter)
            ->map(function (Route $route): array {
                return [
                    'label' => $route->getName() ?: $route->uri(),
                    'uri' => '/' . ltrim($route->uri(), '/'),
                    'module_key' => $this->resolveModuleKey($route),
                ];
            })
            ->values()
            ->all();
    }

    private function isSmokeTestCandidate(Route $route): bool
    {
        $methods = array_values(array_diff($route->methods(), ['HEAD']));
        if ($methods !== ['GET']) {
            return false;
        }

        $uri = $route->uri();

        if (str_contains($uri, '{')) {
            return false;
        }

        if (in_array($route->getName(), ['debug', 'session-timeout'], true)) {
            return false;
        }

        foreach (['/download', '/export', '/pdf', '/template'] as $fragment) {
            if (str_contains('/' . $uri, $fragment)) {
                return false;
            }
        }

        return true;
    }

    private function hasAuthMiddleware(Route $route): bool
    {
        return collect($route->gatherMiddleware())->contains(function (string $middleware): bool {
            return $middleware === 'auth' || str_contains($middleware, 'Authenticate');
        });
    }

    private function resolveModuleKey(Route $route): ?string
    {
        $middleware = collect($route->gatherMiddleware())
            ->first(fn (string $item): bool => str_contains($item, 'EnsureModulePermission'));

        if (!$middleware || !str_contains($middleware, ':')) {
            return null;
        }

        return trim((string) explode(':', $middleware, 2)[1]) ?: null;
    }

    /**
     * @param  array<int, array{module_key?: string|null}>  $routes
     * @return array<int, string>
     */
    private function allModuleKeys(array $routes): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $route): ?string => $route['module_key'] ?? null,
            $routes
        ))));
    }
}
