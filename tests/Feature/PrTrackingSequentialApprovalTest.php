<?php

use App\Mail\PrTrackingApprovalRequestMail;
use App\Models\PrTracking;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    Schema::dropIfExists('pr_trackings');
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

    Schema::create('pr_trackings', function (Blueprint $table) {
        $table->id();
        $table->date('requisition_date');
        $table->string('requisition_number')->unique();
        $table->string('item_requested', 255);
        $table->date('requisition_received_date')->nullable();
        $table->string('requisition_status', 100)->nullable();
        $table->string('approved_request_status', 100)->nullable();
        $table->date('forwarded_to_purchase_date')->nullable();
        $table->text('comments')->nullable();
        $table->string('approval_status', 30)->default('draft');
        $table->string('approver_one_email', 255)->nullable();
        $table->string('approver_one_status', 30)->default('pending');
        $table->timestamp('approver_one_action_at')->nullable();
        $table->string('approver_two_email', 255)->nullable();
        $table->string('approver_two_status', 30)->default('pending');
        $table->timestamp('approver_two_action_at')->nullable();
        $table->string('approver_three_email', 255)->nullable();
        $table->string('approver_three_status', 30)->default('pending');
        $table->timestamp('approver_three_action_at')->nullable();
        $table->timestamp('approval_requested_at')->nullable();
        $table->timestamps();
    });
});

function makePrUser(): User
{
    return User::create([
        'name' => 'PR Tester',
        'email' => 'pr.tester@tanseeqinvestment.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]);
}

function signedApproveUrl(int $id, string $approver): string
{
    return URL::temporarySignedRoute(
        'pr-tracking.approve-signed',
        now()->addDays(7),
        ['id' => $id, 'approver' => $approver]
    );
}

function signedRejectUrl(int $id, string $approver): string
{
    return URL::temporarySignedRoute(
        'pr-tracking.reject-signed',
        now()->addDays(7),
        ['id' => $id, 'approver' => $approver]
    );
}

test('send for approval emails only umme first', function () {
    Mail::fake();
    $user = makePrUser();

    $this->actingAs($user)->post(route('pr-tracking.store'), [
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-001',
        'item_requested' => 'Network switch',
        'submit_action' => 'send_for_approval',
    ])->assertRedirect(route('pr-tracking.index'));

    Mail::assertSent(PrTrackingApprovalRequestMail::class, function ($mail) {
        return $mail->hasTo('umme.hani@tanseeqinvestment.com')
            && $mail->approverKey === 'one';
    });
    Mail::assertNotSent(PrTrackingApprovalRequestMail::class, function ($mail) {
        return $mail->approverKey === 'two' || $mail->approverKey === 'three';
    });

    $pr = PrTracking::where('requisition_number', 'PR-SEQ-001')->first();
    expect($pr)->not->toBeNull()
        ->and($pr->approval_status)->toBe('pending_approval')
        ->and($pr->approver_one_email)->toBe('umme.hani@tanseeqinvestment.com')
        ->and($pr->approver_two_email)->toBe('rumanmohammed@tanseeqinvestment.com')
        ->and($pr->approver_three_email)->toBe('badruddin@tanseeqinvestment.com');
});

test('umme approval forwards email to ruman', function () {
    Mail::fake();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-002',
        'item_requested' => 'Laptop',
        'approval_status' => 'pending_approval',
        'approved_request_status' => 'Pending Approval',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_one_status' => 'pending',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_two_status' => 'pending',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
        'approver_three_status' => 'pending',
    ]);

    $this->get(signedApproveUrl($pr->id, 'one'))->assertRedirect(route('login'));

    $pr->refresh();
    expect($pr->approver_one_status)->toBe('approved')
        ->and($pr->approval_status)->toBe('partially_approved');

    Mail::assertSent(PrTrackingApprovalRequestMail::class, function ($mail) {
        return $mail->hasTo('rumanmohammed@tanseeqinvestment.com')
            && $mail->approverKey === 'two';
    });
});

test('ruman approval forwards email to badr', function () {
    Mail::fake();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-003',
        'item_requested' => 'Monitor',
        'approval_status' => 'partially_approved',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_one_status' => 'approved',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_two_status' => 'pending',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
        'approver_three_status' => 'pending',
    ]);

    $this->get(signedApproveUrl($pr->id, 'two'))->assertRedirect(route('login'));

    $pr->refresh();
    expect($pr->approver_two_status)->toBe('approved')
        ->and($pr->approval_status)->toBe('partially_approved');

    Mail::assertSent(PrTrackingApprovalRequestMail::class, function ($mail) {
        return $mail->hasTo('badruddin@tanseeqinvestment.com')
            && $mail->approverKey === 'three';
    });
});

test('badr approval marks pr fully approved', function () {
    Mail::fake();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-004',
        'item_requested' => 'Dock',
        'approval_status' => 'partially_approved',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_one_status' => 'approved',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_two_status' => 'approved',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
        'approver_three_status' => 'pending',
    ]);

    $this->get(signedApproveUrl($pr->id, 'three'))->assertRedirect(route('login'));

    $pr->refresh();
    expect($pr->approver_three_status)->toBe('approved')
        ->and($pr->approval_status)->toBe('approved')
        ->and($pr->approved_request_status)->toBe('Approved');

    Mail::assertNothingSent();
});

test('out of order approval link is rejected', function () {
    Mail::fake();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-005',
        'item_requested' => 'Cable',
        'approval_status' => 'pending_approval',
        'approver_one_status' => 'pending',
        'approver_two_status' => 'pending',
        'approver_three_status' => 'pending',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
    ]);

    $this->get(signedApproveUrl($pr->id, 'three'))
        ->assertRedirect(route('login'));

    $pr->refresh();
    expect($pr->approver_three_status)->toBe('pending')
        ->and($pr->approval_status)->toBe('pending_approval');

    Mail::assertNothingSent();
});

test('rejection by current approver stops the chain', function () {
    Mail::fake();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-006',
        'item_requested' => 'Printer',
        'approval_status' => 'pending_approval',
        'approver_one_status' => 'pending',
        'approver_two_status' => 'pending',
        'approver_three_status' => 'pending',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
    ]);

    $this->get(signedRejectUrl($pr->id, 'one'))->assertRedirect(route('login'));

    $pr->refresh();
    expect($pr->approver_one_status)->toBe('rejected')
        ->and($pr->approval_status)->toBe('rejected')
        ->and($pr->approved_request_status)->toBe('Rejected');

    Mail::assertNothingSent();
});

test('resend emails only the current pending approver', function () {
    Mail::fake();
    $user = makePrUser();
    $pr = PrTracking::create([
        'requisition_date' => '2026-07-15',
        'requisition_number' => 'PR-SEQ-007',
        'item_requested' => 'Headset',
        'approval_status' => 'partially_approved',
        'approver_one_email' => 'umme.hani@tanseeqinvestment.com',
        'approver_one_status' => 'approved',
        'approver_two_email' => 'rumanmohammed@tanseeqinvestment.com',
        'approver_two_status' => 'pending',
        'approver_three_email' => 'badruddin@tanseeqinvestment.com',
        'approver_three_status' => 'pending',
    ]);

    $this->actingAs($user)
        ->post(route('pr-tracking.request-approval', $pr->id))
        ->assertRedirect(route('pr-tracking.index'));

    Mail::assertSent(PrTrackingApprovalRequestMail::class, 1);
    Mail::assertSent(PrTrackingApprovalRequestMail::class, function ($mail) {
        return $mail->hasTo('rumanmohammed@tanseeqinvestment.com')
            && $mail->approverKey === 'two';
    });

    $pr->refresh();
    expect($pr->approver_one_status)->toBe('approved');
});
