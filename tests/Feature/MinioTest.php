<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MinioTest extends TestCase
{
    public function test_file_uploaded_to_minio()
    {
        // Fake the MinIO disk so no real network calls are made
        Storage::fake('minio');

        // Hit the route that uploads the file
        $response = $this->get('/minio-test');

        // Assert response and that the file was stored on the fake disk
        $response->assertStatus(200);
        $response->assertSee('File uploaded to MinIO!');

        Storage::disk('minio')->assertExists('hello.txt');
        $this->assertEquals(
            'Hello from Laravel + MinIO',
            Storage::disk('minio')->get('hello.txt')
        );
    }
}
