<?php

namespace Incursus\LaravelS3Tools;

use Storage;
//use Aws\Result;
//use Aws\S3\Exception\DeleteMultipleObjectsException;
//use Aws\S3\Exception\S3Exception;
//use Aws\S3\Exception\S3MultipartUploadException;
//use Aws\S3\S3Client;
//use League\Flysystem\Adapter\AbstractAdapter;
//use League\Flysystem\Adapter\CanOverwriteFiles;
//use League\Flysystem\AdapterInterface;
//use League\Flysystem\Config;
//use League\Flysystem\Util;

class S3ToolsAdapter extends \League\Flysystem\AwsS3v3\AwsS3Adapter // AbstractAdapter implements CanOverwriteFiles
{
		public $versionId;

    /**
     * Read an object and normalize the response.
     *
     * @param $path
     *
     * @return array|bool
     */
    protected function readObject($path)
    {
			$versionId = '';

			if(is_array($path)) {
				$fileName = $path[0];
				$versionId = $path[1];
			}
			else
				$fileName = $path;

			$options = [
				'Bucket' => $this->bucket,
				'Key'    => $this->applyPathPrefix($fileName),
			];

			//if (isset($versionId))
				//$options['VersionId'] = $versionId;
			//elseif (isset($this->versionId))
				//$options['VersionId'] = $this->versionId;

			$options += $this->options;

			if (isset($this->options['@http'])) {
				$options['@http'] = $this->options['@http'];
			}

			$command = $this->s3Client->getCommand('getObject', $options);

			$response = $this->s3Client->execute($command);

			$result = $this->normalizeResponse($response->toArray(), $fileName);

			return $result;
    }

    /**
     * Get a list of available versions of an object stored in S3
     *
     * @param $path
     *
     * @return array
     */
		public function getObjectVersions( $path )
		{
			$options = [
				'Bucket' => $this->bucket,
				'Key'    => $this->applyPathPrefix($path),
			];

			//if (isset($versionId))
				//$options['VersionId'] = $versionId;
			//elseif (isset($this->versionId))
				//$options['VersionId'] = $this->versionId;


			if (isset($this->options['@http'])) {
				$options['@http'] = $this->options['@http'];
			}

			$response = $this->s3Client->listObjectVersions($options);

			// A little trick for accessing private properties ... AWS\Result has a private property that we want to see called "data", so let's do it :)
			$privatePropertyAccessor = function($prop) { return $this->$prop; };

			$dataObject = $privatePropertyAccessor->call($response, 'data');

			if(is_array($dataObject) && isset($dataObject['Versions']))
			{
				$versionList = $dataObject['Versions'];

      	if( count($versionList))
      	{
        	$versions = [];
	
        	foreach($response['Versions'] as $r)
          	$versions[] = [
            	'versionId' => $r['VersionId'],
            	'fileSize' => $r['Size'],
            	'isLatest' => $r['IsLatest'],
            	'dateModified' => $r['LastModified']
          	];
	
        	return $versions;
      	}
			}

			return [];
    }

    /**
     * 
     *
     * @param $path
     *
     * @return array
     */
		public function delete( $path )
		{
			$options = [
				'Bucket' => $this->bucket,
				'Key'    => $this->applyPathPrefix($path),
			];

			if (isset($versionId))
				$options['VersionId'] = $versionId;
			elseif (isset($this->versionId))
				$options['VersionId'] = $this->versionId;

			if (isset($this->options['@http'])) {
				$options['@http'] = $this->options['@http'];
			}

			$location = $this->applyPathPrefix($path);

			$command = $this->s3Client->getCommand( 'deleteObject', $options );

			$this->s3Client->execute($command);

			return ! $this->has($path);
		}

		/**
		* Check whether a file exists.
		*
		* @param string $path
		*
		* @return bool
		*/
		public function has($path)
		{
			$options = [
				'Bucket' => $this->bucket,
				'Key'    => $this->applyPathPrefix($path),
			];

			if (isset($versionId))
				$options['VersionId'] = $versionId;
			elseif (isset($this->versionId))
				$options['VersionId'] = $this->versionId;

			$location = $this->applyPathPrefix($path);

			if ($this->s3Client->doesObjectExist($this->bucket, $location, $options)) {
				return true;
			}

			return $this->doesDirectoryExist($location);
		}

    /**
     * Set the versionId of the object to be retrieved (i.e. Storage::disk('s3-tools')->getVersion($versionId)->get($fileName);)
     *
     * @param $versionId
     *
     * @return void
     */
		public function setVersion( $versionId )
		{
			$this->versionId = $versionId;
		}

    /**
     * Set global options: i.e. Storage::disk('s3-tools')->setOptions($options)-delete($fileName);
     *
     * @param array $options
     *
     * @return void
     */
		public function setOptions( $options )
		{
			$this->options += $options;
		}

		public function setOption( $option, $value )
		{
			$this->options[ $option ] = $value;
		}

    /**
     * Clear all options: i.e. Storage::disk('s3-tools')->clearOptions()->setOptions($options)-delete($fileName);
     *
     * @param void
     *
     * @return void
     */
		public function clearOptions()
		{
			$this->versionId = null;;
			$this->options = [];;
		}

    /**
     * Clear a single option: i.e. Storage::disk('s3-tools')->clearOption('VersionId')-get($fileName);
     *
     * @param void
     *
     * @return void
     */
		public function clearOption( $option )
		{
			$this->options[ $option ] = null;
		}

}
