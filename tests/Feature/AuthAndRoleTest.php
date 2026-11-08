<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    private function registrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Peserta Baru',
            'email' => 'baru@contoh.test',
            'phone' => '0812 3456 7890',
            'institution' => 'Universitas Contoh',
            'city' => 'Makassar',
            'province' => 'Sulawesi Selatan',
            'discipline' => 'Gizi',
            'participant_type' => 'student',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
            'consent' => '1',
        ], $overrides);
    }

    public function test_participant_can_register_and_is_redirected_to_team_page(): void
    {
        $this->post(route('register'), $this->registrationData())->assertRedirect(route('portal.team'));

        $user = User::query()->where('email', 'baru@contoh.test')->firstOrFail();
        $this->assertSame(UserRole::Participant, $user->role);
        $this->assertNotNull($user->consented_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_cannot_escalate_role(): void
    {
        $this->post(route('register'), $this->registrationData(['role' => 'admin', 'is_active' => '1']))->assertRedirect();

        $this->assertSame(UserRole::Participant, User::query()->where('email', 'baru@contoh.test')->first()->role);
    }

    public function test_registration_requires_consent(): void
    {
        $this->post(route('register'), $this->registrationData(['consent' => null]))->assertSessionHasErrors('consent');
        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.test']);
    }

    public function test_login_and_role_based_redirect(): void
    {
        $reviewer = $this->reviewer(['email' => 'juri@contoh.test']);

        $this->post(route('login'), ['email' => 'juri@contoh.test', 'password' => 'Rahasia123'])->assertRedirect(route('reviewer.dashboard'));
        $this->assertAuthenticatedAs($reviewer);
        $this->assertTrue(AuditLog::query()->where('action', 'auth.login')->exists());
    }

    public function test_invalid_login_is_rejected(): void
    {
        $this->participant(['email' => 'a@contoh.test']);
        $this->post(route('login'), ['email' => 'a@contoh.test', 'password' => 'salah123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_cannot_use_portal(): void
    {
        $user = $this->participant();
        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user)->get(route('portal.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_each_role_only_reaches_its_own_dashboard(): void
    {
        $participant = $this->participant();
        $reviewer = $this->reviewer();
        $admin = $this->admin();

        $this->actingAs($participant)->get(route('portal.dashboard'))->assertOk();
        $this->actingAs($participant)->get(route('reviewer.dashboard'))->assertForbidden();
        $this->actingAs($participant)->get(route('admin.dashboard'))->assertForbidden();

        $this->actingAs($reviewer)->get(route('reviewer.dashboard'))->assertOk();
        $this->actingAs($reviewer)->get(route('portal.dashboard'))->assertForbidden();
        $this->actingAs($reviewer)->get(route('admin.proposals.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('reviewer.assignments.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('portal.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_participant_cannot_change_role_via_profile(): void
    {
        $user = $this->participant();
        $this->actingAs($user)->put(route('portal.profile.update'), [
            'name' => 'Nama', 'email' => $user->email, 'phone' => '081234567', 'institution' => 'X', 'city' => 'Y',
            'province' => 'Z', 'discipline' => 'D', 'participant_type' => 'student', 'role' => 'admin',
        ])->assertRedirect();

        $this->assertSame(UserRole::Participant, $user->fresh()->role);
    }

    public function test_admin_role_change_is_audited_and_self_demotion_blocked(): void
    {
        $admin = $this->admin();
        $user = $this->participant();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'role' => 'reviewer',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame(UserRole::Reviewer, $user->fresh()->role);
        $this->assertTrue(AuditLog::query()->where('action', 'user.role_changed')->exists());

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'participant',
        ])->assertSessionHas('error');
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    public function test_setup_wizard_only_available_with_token_and_no_admin(): void
    {
        $this->get(route('setup'))->assertNotFound();

        config(['deepnest.setup_token' => 'token-rahasia']);
        $this->get(route('setup'))->assertOk();
        $this->post(route('setup'), ['token' => 'salah', 'name' => 'A', 'email' => 'admin@contoh.test', 'password' => 'Rahasia12345', 'password_confirmation' => 'Rahasia12345'])->assertSessionHasErrors('token');
        $this->post(route('setup'), ['token' => 'token-rahasia', 'name' => 'A', 'email' => 'admin@contoh.test', 'password' => 'Rahasia12345', 'password_confirmation' => 'Rahasia12345'])->assertRedirect(route('admin.dashboard'));

        $this->assertSame(UserRole::Admin, User::query()->where('email', 'admin@contoh.test')->first()->role);
        auth()->logout();
        $this->get(route('setup'))->assertNotFound();
    }
}
