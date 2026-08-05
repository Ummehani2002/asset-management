<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\User;
use App\Services\AiDataTools;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('assets');
    Schema::dropIfExists('asset_categories');
    Schema::dropIfExists('entities');
    Schema::dropIfExists('user_logs');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('username')->nullable()->unique();
        $table->string('email')->unique();
        $table->string('password');
        $table->string('role')->default('user');
        $table->unsignedBigInteger('employee_id')->nullable();
        $table->timestamps();
    });

    Schema::create('user_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('action')->nullable();
        $table->string('url')->nullable();
        $table->string('method')->nullable();
        $table->string('ip_address')->nullable();
        $table->timestamps();
    });

    Schema::create('asset_categories', function (Blueprint $table) {
        $table->id();
        $table->string('category_name');
        $table->timestamps();
    });

    Schema::create('entities', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    Schema::create('assets', function (Blueprint $table) {
        $table->id();
        $table->string('asset_id')->nullable();
        $table->unsignedBigInteger('asset_category_id')->nullable();
        $table->unsignedBigInteger('brand_id')->nullable();
        $table->unsignedBigInteger('entity_id')->nullable();
        $table->string('model_number')->nullable();
        $table->string('serial_number')->nullable();
        $table->string('status')->default('available');
        $table->timestamps();
    });

    config([
        'services.ai_assistant.enabled' => true,
        'services.openai.api_key' => 'test-key',
        'services.openai.model' => 'gpt-4o-mini',
        'services.openai.base_url' => 'https://api.openai.com/v1',
    ]);
});

function makeAiUser(string $role = 'user'): User
{
    return User::create([
        'name' => $role === 'admin' ? 'AI Admin' : 'AI User',
        'email' => $role . '.ai@tanseeqinvestment.com',
        'password' => Hash::make('password'),
        'role' => $role,
    ]);
}

test('ai status reports configured when enabled and key present', function () {
    $user = makeAiUser();

    $this->actingAs($user)
        ->getJson(route('ai.status'))
        ->assertOk()
        ->assertJson(['enabled' => true]);
});

test('ai status reports disabled when flag off', function () {
    config(['services.ai_assistant.enabled' => false]);
    $user = makeAiUser();

    $this->actingAs($user)
        ->getJson(route('ai.status'))
        ->assertOk()
        ->assertJson(['enabled' => false]);
});

test('ai chat returns 503 when not configured', function () {
    config(['services.openai.api_key' => null]);
    $user = makeAiUser();

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'Hello'])
        ->assertStatus(503);
});

test('ai chat returns assistant reply from openai', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Go to PR Tracking Master and click Send for Approval.',
                ],
            ]],
        ], 200),
    ]);

    $user = makeAiUser();

    $this->actingAs($user)
        ->postJson(route('ai.chat'), ['message' => 'How do I send a PR for approval?'])
        ->assertOk()
        ->assertJson([
            'reply' => 'Go to PR Tracking Master and click Send for Approval.',
        ]);
});

test('non admin cannot use find asset tool', function () {
    $user = makeAiUser('user');
    $tools = app(AiDataTools::class);

    $result = $tools->call('find_asset_by_serial_or_id', ['query' => 'ABC'], $user);

    expect($result)->toHaveKey('error');
});

test('admin can count assets by status', function () {
    $category = AssetCategory::create(['category_name' => 'Desktop']);
    Asset::create([
        'asset_id' => 'DST1',
        'asset_category_id' => $category->id,
        'serial_number' => 'S1',
        'status' => 'available',
    ]);
    Asset::create([
        'asset_id' => 'DST2',
        'asset_category_id' => $category->id,
        'serial_number' => 'S2',
        'status' => 'assigned',
    ]);

    $admin = makeAiUser('admin');
    $result = app(AiDataTools::class)->call('count_assets_by_status', [], $admin);

    expect($result['by_status']['available_including_returned'])->toBe(1)
        ->and($result['by_status']['assigned'])->toBe(1)
        ->and($result['by_status']['total'])->toBe(2);
});

test('guest cannot access ai endpoints', function () {
    $this->getJson(route('ai.status'))->assertUnauthorized();
    $this->postJson(route('ai.chat'), ['message' => 'Hi'])->assertUnauthorized();
});
