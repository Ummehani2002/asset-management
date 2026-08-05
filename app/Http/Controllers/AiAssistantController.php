<?php

namespace App\Http\Controllers;

use App\Services\AiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiAssistantController extends Controller
{
    public function status(AiAssistantService $assistant): JsonResponse
    {
        $configured = $assistant->isConfigured();

        return response()->json([
            'enabled' => $configured,
            'message' => $configured ? null : $assistant->disabledReason(),
        ]);
    }

    public function chat(Request $request, AiAssistantService $assistant): JsonResponse
    {
        if (!$assistant->isConfigured()) {
            return response()->json([
                'reply' => $assistant->disabledReason(),
            ], 503);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'history' => 'nullable|array|max:12',
            'history.*.role' => 'nullable|string|in:user,assistant',
            'history.*.content' => 'nullable|string|max:2000',
        ]);

        try {
            $result = $assistant->chat(
                $request->user(),
                $validated['message'],
                $validated['history'] ?? []
            );

            return response()->json($result);
        } catch (Throwable $e) {
            Log::error('AI assistant chat error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'reply' => 'Sorry, the assistant hit an error. Please try again in a moment.',
            ], 500);
        }
    }
}
