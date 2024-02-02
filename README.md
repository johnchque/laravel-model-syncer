# Laravel Model Syncer
Laravel Model Syncer is a Laravel package that provides an easy way to export and import Eloquent models as ZIP files. This package is particularly useful for transferring data between different instances of a Laravel application or for backing up and restoring model data.

## Requirements
- Laravel 6.x or higher.
- Each model that will be exported or imported must have a uuid field. This is crucial for the package to correctly identify and handle model instances.

## Features
- Export Eloquent models and their relationships as ZIP files.
- Import model data from ZIP files with support for dependencies between models.
- Customize which model attributes are exported and imported.
- Supports Laravel 6.x and above.

## Installation
Install the package via Composer:

```bash
composer require johnchque/laravel-model-syncer
```
bash

## Usage
### Exporting Models
To export a model, use the exportToZip method provided by the trait. Here is an example:

```php
use App\User;
use johnchque\LaravelModelSyncer\Concerns\ModelSyncer;

$user = User::find(1);
$modelSyncer = new ModelSyncer();
$zipPath = $modelSyncer->exportToZip($user->toArray());
```

### Importing Models
To import a model, use the importModel method:

```php
$modelSyncer = new ModelSyncer();
$modelSyncer->importModel($zipFilePath);
```

## Security Vulnerabilities
If you discover a security vulnerability within this package, please send an e-mail via hello@johnchque.com. All security vulnerabilities will be promptly addressed.

## License
The Laravel Model Syncer package is open-sourced software licensed under the MIT license.
