<?php

namespace App\Http\Controllers;

use App\Models\HelpAssistantMessage;
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

    public function history(Request $request): JsonResponse
    {
        $messages = HelpAssistantMessage::query()
            ->where('user_id', (int) $request->user()->id)
            ->orderBy('created_at')
            ->limit(60)
            ->get([
                'id',
                'role',
                'message',
                'module',
                'page_component',
                'page_url',
                'provider',
                'created_at',
            ])
            ->map(fn (HelpAssistantMessage $message) => [
                'id' => "db-{$message->id}",
                'role' => (string) $message->role,
                'text' => (string) $message->message,
                'provider' => (string) ($message->provider ?: 'database'),
            ])
            ->values();

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function storeMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:user,assistant'],
            'text' => ['required', 'string', 'max:4000'],
            'provider' => ['nullable', 'string', 'max:50'],
            'page.component' => ['nullable', 'string', 'max:255'],
            'page.url' => ['nullable', 'string', 'max:500'],
            'page.module' => ['nullable', 'string', 'max:100'],
            'meta' => ['nullable', 'array'],
        ]);

        $message = HelpAssistantMessage::create([
            'user_id' => (int) $request->user()->id,
            'role' => (string) $validated['role'],
            'message' => (string) $validated['text'],
            'module' => (string) data_get($validated, 'page.module', ''),
            'page_component' => data_get($validated, 'page.component'),
            'page_url' => data_get($validated, 'page.url'),
            'provider' => $validated['provider'] ?? null,
            'meta' => $validated['meta'] ?? null,
        ]);

        return response()->json([
            'id' => $message->id,
        ]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        HelpAssistantMessage::query()
            ->where('user_id', (int) $request->user()->id)
            ->delete();

        return response()->json([
            'cleared' => true,
        ]);
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
