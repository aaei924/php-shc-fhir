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

You should set or check some values before using.

Check directory of vendor/autoload.php
```php
require './vendor/autoload.php';
```

Set d, x, y, kid values.
```php
// ... line 119
        $this->key = [
            'alg' => 'ES256',
            'crv' => 'P-256',
            'd' => 'your D',
            'kty' => 'EC',
            'use' => 'sig',
            'x' => 'your X',
            'y' => 'your Y'
            'kid' => 'your kid'
        ];
```

```php
require 'path/to/shc-fhir.php';

// example of Pfizer-BioNTech vaccine. 
$first = [
    'type' => 'Immunization',
    'date' => '2021-12-01',
    'cvx' => '208',
    'lot' => 'FK1234'
];

// $second is same format as $first

// middle name is not required
$SHC = new SHC('Appleseed', 'John A.', '1970-01-01', [$first, $second]);
echo '<img src="'.$SHC->genshc().'">';
```
