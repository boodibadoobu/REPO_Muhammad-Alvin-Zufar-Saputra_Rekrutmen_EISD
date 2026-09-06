<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CloudStorageConfigurationTest extends TestCase
{
    public function test_public_disk_can_generate_cloud_photo_urls_without_network_access(): void
    {
        config(['filesystems.disks.public' => [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'ap-southeast-1',
            'bucket' => 'pleasefix',
            'endpoint' => 'https://storage.example.test/storage/v1/s3',
            'url' => 'https://example.test/storage/v1/object/public/pleasefix',
            'use_path_style_endpoint' => true,
        ]]);
        Storage::forgetDisk('public');

        $this->assertSame(
            'https://example.test/storage/v1/object/public/pleasefix/reports/photo.jpg',
            Storage::disk('public')->url('reports/photo.jpg'),
        );
    }
}
