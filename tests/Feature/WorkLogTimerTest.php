<?php

use App\Models\TimeManagement;
use App\Models\User;
use App\Models\WorkTicket;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('time_managements');
    Schema::dropIfExists('work_tickets');
    Schema::dropIfExists('employees');
    Schema::dropIfExists('users');
    Schema::dropIfExists('user_logs');

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

    Schema::create('employees', function (Blueprint $table) {
        $table->id();
        $table->string('employee_id')->nullable();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('entity_name')->nullable();
        $table->string('department_name')->nullable();
        $table->string('designation')->nullable();
        $table->boolean('is_active')->default(true);
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

    Schema::create('work_tickets', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number', 50);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('employee_id')->nullable();
        $table->string('employee_name')->nullable();
        $table->string('category')->default('End User Support');
        $table->string('task_description', 255);
        $table->string('site_location', 255);
        $table->string('status')->default('pending');
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('time_managements', function (Blueprint $table) {
        $table->id();
        $table->string('ticket_number')->nullable();
        $table->unsignedBigInteger('work_ticket_id')->nullable();
        $table->string('category')->default('End User Support');
        $table->text('task_description')->nullable();
        $table->string('site_location')->nullable();
        $table->unsignedBigInteger('employee_id')->nullable();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('employee_name')->nullable();
        $table->string('project_name')->nullable();
        $table->date('job_card_date')->nullable();
        $table->decimal('standard_man_hours', 8, 2)->nullable();
        $table->datetime('start_time')->nullable();
        $table->datetime('end_time')->nullable();
        $table->decimal('duration_hours', 8, 2)->nullable();
        $table->decimal('overtime_hours', 8, 2)->default(0);
        $table->text('action_taken')->nullable();
        $table->text('remarks')->nullable();
        $table->string('status')->nullable();
        $table->integer('delayed_days')->nullable();
        $table->text('delay_reason')->nullable();
        $table->decimal('performance_percent', 5, 2)->nullable();
        $table->timestamps();
    });
});

function makeWorkLogUser(array $overrides = []): User
{
    return User::create(array_merge([
        'name' => 'Field Worker',
        'username' => 'field.worker.'.uniqid(),
        'email' => 'worker.'.uniqid().'@tanseeqinvestment.com',
        'password' => Hash::make('password'),
        'role' => 'user',
    ], $overrides));
}

it('starts a work log with a manual ticket and automatic timer fields', function () {
    $user = makeWorkLogUser();

    $response = $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0001',
        'category' => 'Hardware',
        'site_location' => 'Head Office',
        'task_description' => 'Fix printer issue',
        'action_taken' => 'Checked cable',
    ]);

    $response->assertRedirect(route('time.index'));

    $log = TimeManagement::first();
    expect($log)->not->toBeNull()
        ->and($log->ticket_number)->toBe('INC-0001')
        ->and($log->employee_name)->toBe($user->name)
        ->and($log->category)->toBe('Hardware')
        ->and($log->site_location)->toBe('Head Office')
        ->and($log->task_description)->toBe('Fix printer issue')
        ->and($log->status)->toBe('pending')
        ->and($log->end_time)->toBeNull()
        ->and((float) $log->duration_hours)->toBe(0.0)
        ->and($log->user_id)->toBe($user->id)
        ->and($log->start_time)->not->toBeNull()
        ->and($log->job_card_date->format('Y-m-d'))->toBe(now()->toDateString());

    expect(WorkTicket::count())->toBe(1);
    expect(WorkTicket::first()->status)->toBe('pending');
});

it('allows starting a second running work log while another is pending', function () {
    $user = makeWorkLogUser();

    $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0002',
        'category' => 'Network',
        'site_location' => 'Site A',
        'task_description' => 'First job here',
    ])->assertRedirect(route('time.index'));

    $response = $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0003',
        'category' => 'Software',
        'site_location' => 'Site B',
        'task_description' => 'Second job here',
    ]);

    $response->assertRedirect(route('time.index'));
    expect(TimeManagement::count())->toBe(2);
    expect(TimeManagement::whereNull('end_time')->count())->toBe(2);
    expect(WorkTicket::where('status', 'pending')->count())->toBe(2);
});

it('stops a running work log and calculates duration', function () {
    $user = makeWorkLogUser();

    $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0004',
        'category' => 'Network',
        'site_location' => 'Warehouse',
        'task_description' => 'Network check',
    ]);

    $log = TimeManagement::first();
    $log->start_time = now()->subHours(2);
    $log->save();

    $response = $this->actingAs($user)->post(route('time.stop', $log->id), [
        'complete_ticket' => 1,
    ]);
    $response->assertRedirect(route('time.index'));

    $log->refresh();
    expect($log->end_time)->not->toBeNull()
        ->and($log->status)->toBe('completed')
        ->and((float) $log->duration_hours)->toBeGreaterThan(1.9)
        ->and((float) $log->duration_hours)->toBeLessThan(2.2);

    expect($log->workTicket->fresh()->status)->toBe('completed');
});

it('prevents other users from stopping a work log', function () {
    $owner = makeWorkLogUser(['name' => 'Owner']);
    $other = makeWorkLogUser(['name' => 'Other']);

    $this->actingAs($owner)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0005',
        'category' => 'End User Support',
        'site_location' => 'Office',
        'task_description' => 'Owned job',
    ]);

    $log = TimeManagement::first();

    $this->actingAs($other)
        ->post(route('time.stop', $log->id))
        ->assertForbidden();

    expect($log->fresh()->end_time)->toBeNull();
});

it('keeps a ticket open after stopping a visit and allows a continued visit', function () {
    $user = makeWorkLogUser();

    $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0100',
        'category' => 'Software',
        'site_location' => 'Branch Office',
        'task_description' => 'Investigate application issue',
    ]);

    $firstVisit = TimeManagement::first();
    $ticket = $firstVisit->workTicket;

    $this->actingAs($user)->post(route('time.stop', $firstVisit->id), [
        'complete_ticket' => 0,
    ]);

    expect($ticket->fresh()->status)->toBe('pending')
        ->and($firstVisit->fresh()->status)->toBe('completed');

    $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'continue',
        'work_ticket_id' => $ticket->id,
    ])->assertRedirect(route('time.index'));

    $secondVisit = TimeManagement::orderByDesc('id')->first();
    expect(TimeManagement::count())->toBe(2)
        ->and($secondVisit->work_ticket_id)->toBe($ticket->id)
        ->and($secondVisit->ticket_number)->toBe('INC-0100')
        ->and($secondVisit->category)->toBe('Software')
        ->and($secondVisit->site_location)->toBe('Branch Office')
        ->and($secondVisit->isRunning())->toBeTrue();
});

it('assigns overtime after more than eight completed hours in a day', function () {
    $user = makeWorkLogUser();

    TimeManagement::create([
        'ticket_number' => 'TCKT0001',
        'category' => TimeManagement::DEFAULT_CATEGORY,
        'task_description' => 'Morning work',
        'site_location' => 'Office',
        'user_id' => $user->id,
        'employee_name' => $user->name,
        'job_card_date' => now()->toDateString(),
        'standard_man_hours' => 8,
        'start_time' => now()->startOfDay()->addHours(8),
        'end_time' => now()->startOfDay()->addHours(15),
        'duration_hours' => 7,
        'overtime_hours' => 0,
        'status' => 'completed',
    ]);

    $this->actingAs($user)->post(route('time.store'), [
        'log_type' => 'new',
        'ticket_number' => 'INC-0006',
        'category' => 'Infrastructure',
        'site_location' => 'Office',
        'task_description' => 'Afternoon work',
    ]);

    $running = TimeManagement::whereNull('end_time')->first();
    $running->start_time = now()->startOfDay()->addHours(15);
    $running->save();

    $this->travelTo(now()->startOfDay()->addHours(17));
    $this->actingAs($user)->post(route('time.stop', $running->id));

    $running->refresh();
    expect((float) $running->duration_hours)->toBe(2.0)
        ->and((float) $running->overtime_hours)->toBe(1.0);
});
