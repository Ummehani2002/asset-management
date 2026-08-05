<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiAssistantService
{
    public function __construct(
        private readonly AiDataTools $dataTools
    ) {
    }

    public function isConfigured(): bool
    {
        if (!config('services.ai_assistant.enabled', true)) {
            return false;
        }

        return filled(config('services.openai.api_key'));
    }

    public function disabledReason(): string
    {
        if (!config('services.ai_assistant.enabled', true)) {
            return 'AI Assistant is disabled.';
        }

        if (!filled(config('services.openai.api_key'))) {
            return 'AI Assistant is not configured. Set OPENAI_API_KEY in the environment.';
        }

        return 'AI Assistant is unavailable.';
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{reply: string}
     */
    public function chat(User $user, string $message, array $history = []): array
    {
        if (!$this->isConfigured()) {
            return ['reply' => $this->disabledReason()];
        }

        $message = trim($message);
        if ($message === '') {
            return ['reply' => 'Please type a question.'];
        }

        $isAdmin = $user->isAdmin();
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($user, $isAdmin)],
        ];

        foreach (array_slice($history, -8) as $item) {
            $role = ($item['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) ($item['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $messages[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
        }

        $messages[] = ['role' => 'user', 'content' => mb_substr($message, 0, 2000)];

        $tools = $this->dataTools->definitions($isAdmin);
        $apiMessages = $messages;

        for ($round = 0; $round < 4; $round++) {
            $payload = [
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => $apiMessages,
                'temperature' => 0.2,
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;
                $payload['tool_choice'] = 'auto';
            }

            $response = $this->callOpenAi($payload);
            $choice = $response['choices'][0]['message'] ?? null;
            if (!$choice) {
                throw new RuntimeException('Unexpected OpenAI response.');
            }

            $toolCalls = $choice['tool_calls'] ?? [];
            if (empty($toolCalls)) {
                $reply = trim((string) ($choice['content'] ?? ''));
                return ['reply' => $reply !== '' ? $reply : 'I could not generate a reply. Please try again.'];
            }

            $apiMessages[] = [
                'role' => 'assistant',
                'content' => $choice['content'] ?? null,
                'tool_calls' => $toolCalls,
            ];

            foreach ($toolCalls as $toolCall) {
                $name = (string) ($toolCall['function']['name'] ?? '');
                $rawArgs = (string) ($toolCall['function']['arguments'] ?? '{}');
                $args = json_decode($rawArgs, true);
                if (!is_array($args)) {
                    $args = [];
                }

                $result = $this->dataTools->call($name, $args, $user);
                $apiMessages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $toolCall['id'] ?? uniqid('tool_', true),
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return ['reply' => 'I gathered data but could not finish the answer. Please try a simpler question.'];
    }

    private function callOpenAi(array $payload): array
    {
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $apiKey = (string) config('services.openai.api_key');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(45)
            ->post($baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            Log::error('OpenAI chat failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);
            throw new RuntimeException('AI provider request failed. Please try again later.');
        }

        return $response->json();
    }

    private function systemPrompt(User $user, bool $isAdmin): string
    {
        $roleLabel = $isAdmin ? 'admin' : 'standard user';

        return <<<PROMPT
You are the in-app assistant for Tanseeq Asset Management System.
Help users with how-to questions and read-only inventory questions.

Current user: {$user->name} <{$user->email}>
Role: {$roleLabel}

Help topics you know:
- Dashboard: entity filter, asset category totals, download PDF/CSV, scrap assets.
- Asset Master: add/edit assets, serial number, brand/model, PO/vendor/value.
- Brand & Model: Add Brand & Model, Model Values; brands/models are per category.
- Asset Transactions: assign, return, maintenance request/approval.
- PR Tracking: sequential approval Ruman Mohammed then Badruddin; Send for Approval emails only the current approver; after Badr approves status is fully approved.
- Users / masters: Employees, Projects, Locations, Entities (admin-heavy areas).
- Time Management / Work Log: separate work-log flows for logging hours.

Data rules:
- Use tools for live counts/lookups. Never invent numbers.
- Never reveal API keys, passwords, tokens, or .env values.
- Never claim you can assign assets, approve PRs, send emails, or change data. You are read-only.
- Non-admins: only high-level totals; do not dump employee lists or full asset inventories. If they need detailed lookups, tell them to ask an admin.
- Admins may use lookup and entity summary tools.
- Keep answers concise and practical. Prefer short steps for how-to questions.
PROMPT;
    }
}
