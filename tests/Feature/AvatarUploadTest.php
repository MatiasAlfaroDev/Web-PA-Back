<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploading_an_avatar_stores_it_and_saves_the_url(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('me.jpg', 10, 'image/jpeg')])
            ->assertOk()
            ->assertJsonPath('avatar_url', fn ($url) => is_string($url) && $url !== '');

        $this->assertNotNull($user->fresh()->avatar_url);
        $this->assertNotEmpty(Storage::disk('s3')->allFiles('avatars'));
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('s3');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('doc.pdf', 10)])
            ->assertStatus(422);
    }
}
