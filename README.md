<p align="center">
  <img src="https://github.com/sburkett/laravel-s3-tools/raw/master/doc/laravel-s3-tools-logo.png">
</p>

# Overview

This Laravel package contains additional functionality not currently in Laravel for interfacing with Amazon's S3 service. In particular, there are methods for dealing with versioned objects within S3. It simply extends the existing core classes to add support for versioning in S3, and is tied into the `Storage` facade for convenience. It was designed to be a drop-in replacement, and is backwards compatible with the core functionality, so there shouldn't be any conflicts. I developed this package originally for my own need to deal with versioned objects in S3 and wanted the convenience of Laravel's `Storage` facade.

With this package, you can easily:

- Manage versioned objects stored in S3
	- Get a list of versions for a given object stored in S3
	- Retrieve a specific version of an object stored in S3
	- Delete a specific version of an object stored in S3
- Set or clear Amazon S3/API option values
- Execute other Amazon S3/API commands against your objects

Other methods and conveniences may be added in the future, depending largely upon either my own needs, or suggestions from the community. Pull requests, bug reports, etc. are welcome! :)

NOTE: Yes, I know that you can make use of the underlying Amazon S3 API package to do these sorts of things. But I wanted the convenience of tying them into the `Storage` facade, as well as for some potential additional functionality down the road. So, if you'd rather do this:

```php
// Instantiate an Amazon S3 client.
$s3 = new S3Client([
	'version' => 'latest',
	'region'  => 'us-west-2'
]);

// Fetch the latest version of a file
try {
    $s3->putObject([
        'Bucket' => 'my-bucket',
        'Key'    => 'myfile.png',
				'VersionId' => 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz'
    ]);
} catch (Aws\S3\Exception\S3Exception $e) {
    echo "There was an error retrieving the file.\n";
}
```
... instead of this:

```php
$file = Storage::disk('s3-tools')->getVersion($versionId)->get('myfile.png');
```
... then that's on you. Have fun. :)

## Requirements

This package assumes you have already installed the following packages:

- [league/flysystem](https://github.com/thephpleague/flysystem)
- [league/flysystem-aws-s3-v3](https://github.com/thephpleague/flysystem-aws-s3-v3)
- [aws/aws-sdk-php](https://github.com/aws/aws-sdk-php)

Laravel should already have the `league/flysystem` package installed, but you may need to install the others. I've added them as dependencies to this package, so it should be all automatic for you anyway.

## Installation

You can install the package via composer:

```bash
composer require incursus/laravel-s3-tools
```

Once it is installed, you will need to add the service provider, as usual, to your `config/app.php` file (for pre 5.5 versions of Laravel that do not support auto-discovery):

```php
	...
	'providers' => [
		...
		Incursus\LaravelS3Tools\S3ToolsServiceProvider::class,
		...
	],
	...
```

## Configuration

### Environment Variables

#### AWS Environment Variables
The `laravel-s3-tools` package makes use of the existing AWS/S3 configuration within Laravel, so if you've already configured your app to use S3, you are good to go! Of course, provided you are using the most recent AWS/S3 config statements (these were changed not too long ago in Laravel). To make sure, check your `.env` file for the following:

```
AWS_ACCESS_KEY_ID=<YOUR KEY>
AWS_SECRET_ACCESS_KEY=<YOUR SECRET>
AWS_DEFAULT_REGION=<DEFAULT REGION>
AWS_BUCKET=<YOUR BUCKET NAME>
```

If you aren't sure what value to use in `AWS_DEFAULT_REGION`, [check this page](https://docs.aws.amazon.com/general/latest/gr/rande.html) for more information (use the value shown in the `Region` column in the table on that page.

#### S3 Tools Disk Name
By default, this package will use a disk name of `s3-tools`. If you'd like to rename it to something else, you can use the `S3_TOOLS_DISK_NAME` environment variable in your `.env` file, as show below.

```
S3_TOOLS_DISK_NAME="diskname"
```

### Disk Configuration
The `laravel-s3-tools` package requires that you setup a new disk configuration in your `config/filesystems.php` file. It's pretty simple, really. Just copy the entry below and paste it into your `config/filesystems.php` file. It will automatically look in your `.env` file for a custom disk name, and if not found, will fall back to the default value of simply `s3-tools`. This disk name will be the disk you use in the `Storage` facade whenever you want to utilize the functionality of this package. Th new disk configuration can also be used for normal, non-versioned S3 operations, or you can just use the original 's3' configuration for that. Up to you!

So, your `config/filesystems.php` file should look something like this:

```php

<?php

return [

    ...

    'disks' => [

				...

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

				// Add this entry
				env('S3_TOOLS_DISK_NAME', 's3-tools') => [
          'driver' => env('S3_TOOLS_DISK_NAME', 's3-tools'),
          'key' => env('AWS_ACCESS_KEY_ID'),
          'secret' => env('AWS_SECRET_ACCESS_KEY'),
          'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
          'bucket' => env('AWS_BUCKET'),
          'url' => env('AWS_URL'),
        ],
      ],
	...
```

## Usage

### Summary of Methods

This it the `TL;DR` section. The following are the methods available to you with the `laravel-s3-tools` package. Each is described in more detail, with examples, below:

| Method Name           | Arguments                 | Description                                                                                                                                 |   |   |
|-----------------------|---------------------------|---------------------------------------------------------------------------------------------------------------------------------------------|---|---|
| `setOption()`         | $optionName, $optionValue | Sets the value of a single AWS/S3 API option                                                                                                |   |   |
| `setOptions()`        | $optionArray              | Sets multiple AWS/S3 API option values                                                                                                      |   |   |
| `clearOption()`       | $optionName               | Resets/clears a single AWS/S3 API option                                                                                                    |   |   |
| `clearOptions()`      | N/A                       | Resets/clears all AWS/S3 API options that you've set through either `setOption()` or `setOptions()`                                         |   |   |
| `getObjectVersions()` | $objectPath               | Fetches a list of versions of the specified object stored in S3                                                                             |   |   |
| `getVersion()`        | $versionId                | Shortcut for `setOption('VersionId', $versionString)`                                                                                       |   |   |
| `has()`               | $objectPath               | Works the same as the normal `has()` method in Laravel, but provides support for checking for existence of a specific version of an object. |   |   |
| `delete()`            | $objectPath               | Works the same as the normal `delete()` method in Laravel, but provides support for deleting a specific version of an object.               |   |   |


### Get a list of versions for a given object

A list of available versions of an object (file) stored in S3 can be retrieved and processed as follows.  The returned list of versions will appear in reverse-chronological order based on the date last modified. The most recent version (the latest version) will always be the first element (0th) in the returned array.

```php
$versions = Storage::disk('s3-tools')->getObjectVersions('myfile.png');

foreach($versions as $v)
{
	echo '<li> Version ID: ' . $v['versionId'];
	echo '<li> File Size: ' . $v['fileSize'] . ' bytes';
	echo '<li> Is Latest Version?: ' . $v['isLatest']; // Will be true or false (boolean)
	echo '<li> Date Modified: ' . $v['dateModified'];
	echo '<li> ----------------------------------------------';
}
```

The output from the above code will appear similar to the following:

```
- Version ID: WX6q0O9qkcAcqld3DidZo2m5z68uGKnn
- File Size: 132645 bytes
- Is Latest Version?: 1
- Date Modified: 2019-03-21T19:35:29+00:00
----------------------------------------------
- Version ID: nMw5IAmOPdMK0MR3eXtkSPVQTd18Vucd
- File Size: 3631 bytes
- Is Latest Version?:
- Date Modified: 2019-03-21T19:16:26+00:00
----------------------------------------------
...
```

### Retrieve the latest version of an object

To retrieve the latest version of the a given object, simply use the `Storage` facade as usual. Here is an example of retrieving the latest version of an image from S3.

```php
// Fetch the latest version of the file from S3
$file = Storage::disk('s3-tools')->get('myfile.png');
	
// Show the image in the browser
return response($file)->header('Content-Type', 'image/png');
```

### Fetch a specific version of an object

However, unlike Laravel, it can also be used to specify a specific version of an object that you wish to retrieve. The `versionId` field returned by `getObjectVersions()` can be used to retrieve a specific version of an object from S3:

```php
// Fetch the image from S3
$versionId = 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz';
$file = Storage::disk('s3-tools')->getVersion($versionId)->get('myfile.png');
	
// Show the image in the browser
return response($file)->header('Content-Type', 'image/png');
```

### Delete the latest version of an Object

Without specifying a specific version, the latest version of an Object will be deleted:

```php
$result = Storage::disk('s3-tools')->delete('some/longer/S3/path/business-plan.pdf');
```

The above operation will actually not "delete" the file from S3 if versioning is enabled for the bucket. By default, S3 will place a `DeleteMarker` for that version of the file. However, you are charged a nominal fee by Amazon for `DeleteMarker` storage. To fully delete a file, and leave no `DeleteMarker` in its place, you need to delete the specific version of the file as demonstrated below.

Alternatively, you can do the following to help manage your `DeleteMarkers` in S3:

- Login to the S3 Console
- Select your Bucket
- Open Properties
- Click Lifecycle
- Create a rule set to Permanently Delete `n` days after the object's creation date

### Delete a specific version of an Object

If you specify a `versionId`, you can delete just that particular version of the object, assuming it exists. This operation will also not leave behind a `DeleteMarker` - think of it as a "hard delete" operation.

```php
$versionId = 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz';
$result = Storage::disk('s3-tools')->getVersion($versionId)->delete('some/longer/S3/path/business-plan.pdf');
```

### Setting AWS/S3 API Options

At times, you may need to provide additional options for a given request. The options for each API call are well-documented on [Amazon's API Reference site](https://docs.aws.amazon.com/aws-sdk-php/v3/api/index.html). As an example, consider this request which does the same thing as the built-in `getVersion()` method in this package:

```php
$result = Storage::disk('s3-tools')->setOption('VersionId', $versionString)->get('myfile.png');
```

You can also use the plural version called `setOptions()` to pass in an array of options:

```php
$options = [
	'VersionId' => 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz',
	'IfModifiedSince' => '2 days ago'
];

$result = Storage::disk('s3-tools')->setOptions($options)->delete('myfile.png');
```

The `clearOption()` method will reset a specific option, while the `clearOptions()` method will reset them all. If you experience any weirdness while doing complex operations into and out of S3, it may behoove you call `clearOptions()` to reset things prior to making certain API calls.

```php

// Retrieve a specific version of a file
$versionId = 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz';
$file = Storage::disk('s3-tools')->setOption('VersionId', $versionId)->get('myfile.png');

// Clear out ll of our options
$file = Storage::disk('s3-tools')->clearOptions();

// or alternatively, just clear the 'VersionId' option
//$file = Storage::disk('s3-tools')->clearOption('VersionId');

// Get the latest version of another file ...
$file = Storage::disk('s3-tools')->get('myfile.png');

```

### Execute Other Amazon S3 API Commands
Using the `command()` method, you can execute any other API call to S3 as well, and there are a great number of them. However, you will be responsible for not only passing in all of the appropriate options, but also parsing the response. All responses returned via this method are sent back to you in raw format. In some senses, this is a bit extraneous, since you could just use the offical S3 API to execute them, but I've included it here just to provide a method of consistency should you decide to use this package for other things.

Consider the following example which does the same thing as the built-in `getObjectVersions()` method of this package:

```php
$result = Storage::disk('s3-tools')->command('ListObjectVersions', [
	'Prefix' => 'some/longer/S3/path/business-plan.pdf'
]);
```

Here is the same command above, but using a different bucket name:

```
$result = Storage::disk('s3-tools')->command('ListObjectVersions', [
	'Bucket' => 'MyBucketName',
	'Prefix' => 'some/longer/S3/path/business-plan.pdf'
]);
```

Here is an example of creating a new S3 bucket. Remember, bucket names in S3 must conform to DNS naming conventions, so:

- Should not contain uppercase characters
- Should not contain underscores
- Should be between 3 and 63 characters long
- Should not end with a dash
- Cannot contain two, adjacent periods
- Cannot contain dashes next to periods (e.g., "my-.bucket.com" and "my.-bucket" are invalid)

```
$result = Storage::disk('s3-tools')->command('CreateBucket', [
	'Bucket' => 'my-terrific-bucket-name',
	'ACL' => 'private'
]);
```

Here is a final example for you. Removing multiple objects in a single API call. In this example, we delete the latest version of `myfile.png` and `business-plan.pdf`, as well as a specific version of a fictitious spreadsheet.

```
$result = Storage::disk('s3-tools')->command('DeleteObjects', [
	'Delete' => [
		[ 'Key' => 'myfile.png' ],
		[ 'Key' => 'some/longer/S3/path/business-plan.pdf' ],
		[ 
			'Key' => 'some/longer/S3/path/financial-planning-spreadsheet.xlsx', 
			'VersionId' => 'fiWFsPPFwbvlGh37rB9IaZYkO4pzOgWGz'
		]
	]
]);
```

#### Notes on `Storage::command` Usage

- When using the `Storage::command` method, the only "option" value that WILL actually default is `Bucket` ... it will default to the value of `AWS_BUCKET` from your `.env` file if a bucket name isn't passed in directly.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email scott@incurs.us instead of using the issue tracker.

## Credits

- [Scott Burkett](https://github.com/sburkett)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

