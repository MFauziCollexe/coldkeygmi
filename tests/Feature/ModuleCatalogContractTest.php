<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\Support\ImplementedModuleCatalog;
use Tests\TestCase;

class ModuleCatalogContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_implemented_module_page_is_registered_in_backend_module_config(): void
    {
        $configuredLeafKeys = $this->configuredModuleLeafKeys();
        $missing = [];

        foreach (array_keys(ImplementedModuleCatalog::pages()) as $moduleKey) {
            if (!in_array($moduleKey, $configuredLeafKeys, true)) {
                $missing[] = $moduleKey;
            }
        }

        $this->assertSame([], $missing, 'Implemented module keys missing from config/modules.php: ' . implode(', ', $missing));
    }

    public function test_every_implemented_module_page_has_a_matching_protected_route(): void
    {
        $failures = [];

        foreach (ImplementedModuleCatalog::pages() as $moduleKey => $page) {
            $route = $this->findGetRouteByUri($page['uri']);

            if (!$route) {
                $failures[] = "{$moduleKey} is missing GET route {$page['uri']}";
                continue;
            }

            if ($this->resolveModuleKey($route) !== $moduleKey) {
                $failures[] = "{$moduleKey} route {$page['uri']} uses middleware key " . ($this->resolveModuleKey($route) ?? '[none]');
            }
        }

        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }

    public function test_every_implemented_module_page_requires_its_own_permission(): void
    {
        foreach (ImplementedModuleCatalog::pages() as $moduleKey => $page) {
            $guestResponse = $this->get($page['uri']);
            $guestResponse->assertRedirect();

            $unauthorizedUser = $this->createUser();
            $forbiddenResponse = $this->actingAs($unauthorizedUser)->get($page['uri']);
            $forbiddenResponse->assertForbidden();
            Auth::logout();

            $authorizedUser = $this->createUser([], $moduleKey);
            $allowedResponse = $this->actingAs($authorizedUser)->get($page['uri']);

            $this->assertContains(
                $allowedResponse->getStatusCode(),
                [200, 302],
                "{$moduleKey} should load for authorized users on {$page['uri']}"
            );

            Auth::logout();
        }
    }

    /**
     * @return array<int, string>
     */
    private function configuredModuleLeafKeys(): array
    {
        $keys = [];
        $walk = function (array $items) use (&$walk, &$keys): void {
            foreach ($items as $item) {
                $children = $item['children'] ?? [];
                if (is_array($children) && $children !== []) {
                    $walk($children);
                    continue;
                }

                if (!empty($item['key'])) {
                    $keys[] = (string) $item['key'];
                }
            }
        };

        $walk(config('modules', []));

        return array_values(array_unique($keys));
    }

    private function findGetRouteByUri(string $uri): ?Route
    {
        $normalized = ltrim($uri, '/');

        foreach (RouteFacade::getRoutes() as $route) {
            $methods = array_values(array_diff($route->methods(), ['HEAD']));
            if ($methods !== ['GET']) {
                continue;
            }

            if ($route->uri() === $normalized) {
                return $route;
            }
        }

        return null;
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
}
