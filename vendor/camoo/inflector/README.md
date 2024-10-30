# Camoo Inflector Wrapper

## Installation

```shell
composer require camoo/inflector
```

## Usage
```php
use Camoo\Inflector\Inflector;

echo Inflector::camelize('foo bar');
echo Inflector::singularize('users');
echo Inflector::pluralize('domain');
echo Inflector::urlize('foo bar');
// ...
```
