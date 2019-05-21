# CSV Parser Library

This library allows you to define your CSV parsing in OOP style, allowing you to easily filter and test your imports. 

## Installation

Install this package using composer:
``` bash
composer require kaliop/csv-parser
```

## Basic Usage

You'll need to describe the mapping between CSV columns and entity's properties.
To do so, you should start by creating a *Parser*, but first, let's take a look at our CSV:

```csv
date;order_id;client_name;address;postal_code;country;amount
2019-04-15;678945;"Laurent Doe";"12 avenue PhpUnit";34000;France;12
2019-03-12;987564;"Ruh Doe";"15 rue du test";31001;France;50,53
2019-05-01;123456;"Julien Doe";"125 rue PHP";34440;France;69,12
2019-02-09;456123;"Gérard Doe";"15 blvd Bouchard";76000;France;789,10
2019-01-01;965478;"Jean-Luc Doe";"15 rue du test";34000;France;5,00
2019-05-01;126578;"Bernard Doe";"15 rue Symfony";75000;France;33,53
2019-05-01;216543;"Maël Doe";"Disneyland Paris";77000;France;1250,53
2019-05-01;987521;"Gros Doe";"15 rue de Behat";98520;France;50,98
```

our Entity looks like this:

```php
<?php

namespace App\Entity;

class Order
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var string
     */
    public $clientName;

    /**
     * @var string
     */
    public $address;

    /**
     * @var int
     */
    public $postalCode;

    /**
     * @var string
     */
    public $country;

    /**
     * @var float
     */
    public $amount;
    
    /**
     * @var null|\DateTime
     */
    public $shipDate = null;
}
```

Creating the *Parser*:

```php
<?php

namespace App\CSV;

use App\Entity\Order;
use Kaliop\CsvParser\Parser\AbstractParser;
use Kaliop\CsvParser\Parser\ParserInterface;
use Kaliop\CsvParser\ColumnHelper;

class OrderParser extends AbstractParser implements ParserInterface
{
    public function getMappingDefinition()
    {
        return [
            ColumnHelper::index('A') => [
                'property'  => 'entity.date',
                'filter'    => function($value) { return \DateTime::createFromFormat('Y-m-d', $value); }
            ],
            ColumnHelper::index('B') => [
                'property'  => 'entity.id',
                'filter'    => function($value) { return \intval($value); }
            ],
            ColumnHelper::index('C') => [
                'property'  => 'entity.clientName',
                'filter'    => function($value) { return \trim($value); }
            ],
            ColumnHelper::index('D') => [
                'property'  => 'entity.address',
                'filter'    => function($value) { return \trim($value); }
            ],
            ColumnHelper::index('E') => [
                'property'  => 'entity.postalCode',
                'filter'    => function($value) { return \intval($value); }
            ],
            ColumnHelper::index('F') => [
                'property'  => 'entity.country',
                'filter'    => function($value) { return \trim($value); }
            ],
            ColumnHelper::index('G') => [
                'property'  => 'entity.amount',
                'filter'    => function($value) { return \floatval($value); }
            ]
        ];
    }

    public function getEntityClassName()
    {
        return Order::class;
    }
}
```

And now do the magic! In this example we persist imported entities in database. 

```php
<?php

namespace App\Core;

use App\CSV\OrderParser;

class ImportOrdersFromCSV
{
    protected $em;
    
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }
    
    public function import($csvFilePath)
    {
        $parser = new OrderParser($csvFilePath, ";");
        $results = $parser->execute();
        
        foreach ($results as $result) {
            if ($result->isValid()) {
                $this->em->persist($result->getEntity());
                continue;
            }
            
            // log or do something with invalid entities
        }
        
        $this->em->flush();
    }
}
```

## Advanced Usage

You can decide to change the default ```ParserResult``` class to do you own logic once the Entity has been parsed.
In the following example we will add a ```$shipDate``` which will be set by our custom result class, in the ```finalize()``` method:


```php
<?php

namespace App\CSV;

use Kaliop\CsvParser\Result\ParserResult;
use App\Entity\Order;

class OrderParserResult extends ParserResult
{
    public function finalize()
    {
        if (!$this->entity instanceof Order || !$this->entity->date) {
            // do nothing on invalid entities
            return;
        }

        $shipDate = clone $this->entity->date;
        $shipDate->modify('+3 days');

        $this->entity->shipDate = $shipDate;
    }
}
```

Now just tell your Parser to use this Result class and you're ready:

```php
<?php
// ... ImportOrdersFromCSV

$parser->setResultClassName(App\CSV\OrderParserResult::class);
```

## Run PHPUnit Tests

```bash
./vendor/bin/phpunit 
```