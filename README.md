# php-shc-fhir
SMART health card generation library in php

## usage
At first, you should install libraries with composer.
```bash
php composer.phar install
```
OR can use this command.
```bash
composer install
```

```php
require 'path/to/shc-fhir.php';

$first = [
    'type' => 'Immunization',
    'date' => '2021-12-01',
    'cvx' => '207',
    'lot' => 'FK1234'
];

// $second is same format as $first

// middle name is not required
$SHC = new SHC('lastname', 'middlename firstname', '1970-01-01', [$first, $second]);
echo '<img src="'.$SHC->genshc().'">';
```
