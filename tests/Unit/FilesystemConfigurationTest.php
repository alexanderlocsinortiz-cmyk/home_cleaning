<?php

namespace Tests\Unit;

use Tests\TestCase;

class FilesystemConfigurationTest extends TestCase
{
    public function test_s3_upload_disks_use_private_object_visibility_for_r2_compatible_public_media(): void
    {
        $privateDisk = config('filesystems.disks.s3');
        $publicDisk = config('filesystems.disks.s3_public');
        $proofDisk = config('filesystems.disks.s3_proof');

        $this->assertSame('s3', $privateDisk['driver']);
        $this->assertSame('private', $privateDisk['visibility']);
        $this->assertSame('s3', $proofDisk['driver']);
        $this->assertSame('private', $proofDisk['visibility']);
        $this->assertSame('s3', $publicDisk['driver']);
        $this->assertSame('private', $publicDisk['visibility']);
    }

    public function test_s3_upload_disks_use_separate_configurable_prefixes(): void
    {
        config([
            'filesystems.disks.s3.prefix' => 'private-uploads',
            'filesystems.disks.s3_public.prefix' => 'public-media',
        ]);

        $this->assertSame('private-uploads', config('filesystems.disks.s3.prefix'));
        $this->assertSame('public-media', config('filesystems.disks.s3_public.prefix'));
        $this->assertNotSame(
            config('filesystems.disks.s3.prefix'),
            config('filesystems.disks.s3_public.prefix')
        );
    }

    public function test_s3_upload_disks_can_use_separate_private_and_public_buckets(): void
    {
        config([
            'filesystems.disks.s3.bucket' => 'cleanflow-private',
            'filesystems.disks.s3_public.bucket' => 'cleanflow-public',
        ]);

        $this->assertSame('cleanflow-private', config('filesystems.disks.s3.bucket'));
        $this->assertSame('cleanflow-public', config('filesystems.disks.s3_public.bucket'));
        $this->assertNotSame(
            config('filesystems.disks.s3.bucket'),
            config('filesystems.disks.s3_public.bucket')
        );
    }
}
