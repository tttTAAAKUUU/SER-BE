<?php

namespace Tests\Feature\Uploads;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilePhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_with_valid_jpg_image_returns_200_with_url(): void
    {
        $response = $this->postJson('/api/uploads/profile-photo', [
            'photo' => UploadedFile::fake()->image('avatar.jpg'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['url']);
    }

    public function test_upload_with_valid_png_image_returns_200_with_url(): void
    {
        $response = $this->postJson('/api/uploads/profile-photo', [
            'photo' => UploadedFile::fake()->image('avatar.png'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['url']);
    }

    public function test_upload_with_non_image_file_returns_422_validation_error(): void
    {
        $response = $this->postJson('/api/uploads/profile-photo', [
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_upload_with_file_exceeding_5mb_returns_422_validation_error(): void
    {
        $response = $this->postJson('/api/uploads/profile-photo', [
            'photo' => UploadedFile::fake()->image('large-avatar.jpg')->size(6000),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_upload_without_photo_field_returns_422_validation_error(): void
    {
        $response = $this->postJson('/api/uploads/profile-photo', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }
}
