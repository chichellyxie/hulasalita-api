<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_student_can_upload_school_id_to_private_storage(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        Sanctum::actingAs($student);

        $this->post('/api/v1/verification', [
            'id_image' => UploadedFile::fake()->image('school-id.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $verification = Verification::firstOrFail();
        $this->assertSame('pending', $verification->status);
        Storage::disk('local')->assertExists($verification->id_image_path);
    }

    public function test_pending_student_cannot_play_games(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/puzzles/daily')
            ->assertForbidden()
            ->assertJsonPath('status', 'pending');
    }

    public function test_verified_student_cannot_upload_again(): void
    {
        Sanctum::actingAs(User::factory()->approved()->create());

        $this->post('/api/v1/verification', [
            'id_image' => UploadedFile::fake()->image('school-id.jpg'),
        ], ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_admin_approval_verifies_student_and_deletes_the_id_file(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $path = UploadedFile::fake()->image('id.jpg')->store("verifications/{$student->id}", 'local');
        $verification = $student->verifications()->create(['id_image_path' => $path, 'status' => 'pending']);

        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/verifications')
            ->assertOk()
            ->assertJsonPath('data.0.user.username', $student->username)
            ->assertJsonMissingPath('data.0.id_image_path');

        $this->get("/api/v1/admin/verifications/{$verification->id}/document")->assertOk();

        $this->patchJson("/api/v1/admin/verifications/{$verification->id}", ['decision' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.has_document', false);

        $this->assertSame('approved', $student->fresh()->status);
        $this->assertNull($verification->fresh()->id_image_path);
        $this->assertSame($admin->id, $verification->fresh()->reviewed_by);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_students_cannot_open_the_verification_queue(): void
    {
        Sanctum::actingAs(User::factory()->approved()->create());

        $this->getJson('/api/v1/admin/verifications')->assertForbidden();
    }
}
