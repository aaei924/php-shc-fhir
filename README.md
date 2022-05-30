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
