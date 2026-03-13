<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RemembersIndexUrl
{
    protected function rememberIndexUrl(Request $request, string $key): void
    {
        $request->session()->put($this->indexUrlSessionKey($key), $request->getRequestUri());
    }

    protected function redirectToRememberedIndex(Request $request, string $key, string $fallbackRoute): RedirectResponse
    {
        $url = $request->session()->get($this->indexUrlSessionKey($key));

        if (is_string($url) && $url !== '' && str_starts_with($url, '/')) {
            return redirect()->to($url);
        }

        return redirect()->route($fallbackRoute);
    }

    private function indexUrlSessionKey(string $key): string
    {
        return "index_url.{$key}";
    }
}

