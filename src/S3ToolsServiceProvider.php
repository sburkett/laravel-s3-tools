<?php

namespace Incursus\LaravelS3Tools;

use Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\AwsS3v3\AwsS3Adapter as S3Adapter;
use Aws\S3\S3Client;
use Incursus\LaravelS3Tools\S3ToolsAdapter;
use Incursus\LaravelS3Tools\S3ToolsFilesystemManager;
use Incursus\LaravelS3Tools\S3ToolsFilesystem as S3ToolsFilesystem;

use Illuminate\Support\ServiceProvider;

class S3ToolsServiceProvider extends ServiceProvider
{
		protected $diskName;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
			$this->diskName = env('S3_TOOLS_DISK_NAME', 's3-tools');

			Storage::extend($this->diskName, function ($app, $config) {
				// TODO - this config does not need to be hardcoded obviously lol
				$s3Config = [
					"driver" => "s3-tools",
					"key" => env('AWS_ACCESS_KEY_ID'),
					"secret" => env('AWS_SECRET_ACCESS_KEY'),
					"region" => env('AWS_DEFAULT_REGION'),
					"bucket" => env('AWS_BUCKET'),
					"url" => null,
					"version" => "latest",
					"credentials" => [
						"key" => env('AWS_ACCESS_KEY_ID'),
						"secret" => env('AWS_SECRET_ACCESS_KEY')
					]
				];

				$root = $s3Config['root'] ?? null;
				$options = $config['options'] ?? [];

				$client = new S3Client( $s3Config, $s3Config['bucket'], $root, $options );

				$adapter = new S3ToolsAdapter($client, $s3Config['bucket']);

				$filesystem = new S3ToolsFilesystem($adapter);
				$filesystem->diskName = $this->diskName;

				return $filesystem;
			});

    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
			//
    }

}
