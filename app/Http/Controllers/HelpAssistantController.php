<?php

namespace App\Http\Controllers;

use App\Services\OpenAIHelpAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HelpAssistantController extends Controller
{
    public function __construct(
        protected OpenAIHelpAssistantService $assistant,
    ) {
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'page.component' => ['nullable', 'string', 'max:255'],
            'page.url' => ['nullable', 'string', 'max:500'],
            'page.module' => ['nullable', 'string', 'max:100'],
            'page.module_permissions' => ['nullable', 'array'],
            'page.module_permissions.*' => ['string', 'max:150'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string'],
            'history.*.text' => ['required_with:history', 'string', 'max:4000'],
        ]);

        try {
            $result = $this->assistant->chat(
                $request->user(),
                (array) ($validated['page'] ?? []),
                (array) ($validated['history'] ?? []),
                (string) $validated['message'],
            );

            return response()->json([
                'answer' => $result['answer'],
                'model' => $result['model'] ?? null,
                'provider' => 'openai',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }
    }
}
