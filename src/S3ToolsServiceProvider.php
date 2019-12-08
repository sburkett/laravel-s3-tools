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
	protected $configPrefix;

	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	*/
	public function boot()
	{
		$this->diskName = env('S3_TOOLS_DISK_NAME', 's3-tools');
		$this->configPrefix = 'filesystems.disks.' . $this->diskName;

		Storage::extend($this->diskName, function ($app, $config) {

			$s3Config = [
				"driver" => $this->diskName,
				"key" => config($this->configPrefix . '.key'),
				"secret" => config($this->configPrefix . '.secret'),
				"region" => config($this->configPrefix . '.region'),
				"bucket" => config($this->configPrefix . '.bucket'),
				"url" => null,
				"version" => "latest",
				"credentials" => [
					"key" => config($this->configPrefix . '.key'),
					"secret" => config($this->configPrefix . '.secret')
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
