<?php

namespace Tests\Feature\Uploads;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KycDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_with_both_id_front_and_id_back_images_returns_200_with_both_urls(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_front' => UploadedFile::fake()->image('id_front.jpg'),
            'id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['id_front_url', 'id_back_url']);
    }

    public function test_upload_with_pdf_for_id_front_returns_200(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_front' => UploadedFile::fake()->create('id_front.pdf', 1000, 'application/pdf'),
            'id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['id_front_url', 'id_back_url']);
    }

    public function test_upload_with_missing_id_front_returns_422(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_front']);
    }

    public function test_upload_with_missing_id_back_returns_422(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_front' => UploadedFile::fake()->image('id_front.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_back']);
    }

    public function test_upload_with_file_exceeding_10mb_returns_422(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_front' => UploadedFile::fake()->image('id_front.jpg')->size(11000),
            'id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_front']);
    }

    public function test_upload_with_invalid_file_type_returns_422(): void
    {
        $response = $this->postJson('/api/uploads/kyc-documents', [
            'id_front' => UploadedFile::fake()->create('document.txt', 100, 'text/plain'),
            'id_back' => UploadedFile::fake()->image('id_back.jpg'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_front']);
    }

    public function test_service_provider_profile_has_kyc_status_column(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('service_provider_profiles', 'kyc_status')
        );
    }

    public function test_service_provider_profile_has_id_number_column(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumn('service_provider_profiles', 'id_number')
        );
    }
}