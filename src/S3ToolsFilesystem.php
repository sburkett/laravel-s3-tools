<?php

namespace Incursus\LaravelS3Tools;

use Storage;
use League\Flysystem\Util;

class S3ToolsFilesystem extends \League\Flysystem\Filesystem 
{
		public $diskName;

		/**
		* Constructor.
		*
		* @param AdapterInterface $adapter
		* @param Config|array     $config
		*/
		public function __construct(\League\Flysystem\AdapterInterface $adapter, $config = null)
		{
			// Set the diskName to whatever is in the .env file, with a default value of "s3-tools"
			$this->diskName = env('S3_TOOLS_DISK_NAME', $this->diskName);

			// Call the original/parent constructor in \League\Flysystem\Filesystem
			parent::__construct( $adapter, $config );
		}

    /**
     * @inheritdoc
     */
    public function read($path)
    {
			$versionId = null;

			if(is_array($path))
			{
				$fileName = $path[0];
				$versionId = $path[1];
			}
			else
				$fileName = $path;

        $fileName = Util::normalizePath($fileName);
        $this->assertPresent($fileName);

        if ( ! ($object = $this->getAdapter()->read([$fileName, $versionId]))) {
            return false;
        }

        return $object['contents'];
    }

		public function getObjectVersions( $path )
		{
			$this->diskName = env('S3_TOOLS_DISK_NAME', $this->diskName);
			$path = Util::normalizePath($path);
			$this->assertPresent($path);

			$versions = $this->getAdapter()->getObjectVersions($path);
			return $versions;
		}

		public function getVersion( $versionId )
		{
			$this->getAdapter()->setVersion( $versionId );
			return Storage::disk($this->diskName);
		}

		public function setOptions( $options )
		{
			$this->getAdapter()->setOptions( $options );
			return Storage::disk($this->diskName);
		}

		public function setOption( $option, $value )
		{
			$this->getAdapter()->setOption( $option, $value );
			return Storage::disk($this->diskName);
		}

		public function clearOptions()
		{
			$this->getAdapter()->clearOptions();
			return Storage::disk($this->diskName);
		}

		public function clearOption( $option )
		{
			$this->getAdapter()->clearOption( $option );
			return Storage::disk($this->diskName);
		}

		public function command( $commandName, $params = [] )
		{
			$response = $this->getAdapter()->command( $commandName, $params );
			return $response;
			//return Storage::disk($this->diskName);
		}

}
