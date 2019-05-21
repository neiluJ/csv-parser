<?php

namespace App;


use Kaliop\CsvParser\ColumnHelper;
use Kaliop\CsvParser\Result\ParserResult;
use Kaliop\CsvParser\Result\ParserResultInterface;
use PHPUnit\Framework\TestCase;
use Kaliop\CsvParser\Parser\AbstractParser;
use Kaliop\CsvParser\Parser\ParserInterface;

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

/**
 * Class ParserTest
 *
 * @package App
 */
class ParserTest extends TestCase
{
    public function testSimpleParsing()
    {
        $parser = new OrderParser(__DIR__ .'/fixtures/valid-csv-orders.csv', ';');
        $parser->setSkipFirstLine(true);
        $parser->setMinColumnCount(7);

        $results = $parser->execute();
        $this->assertInternalType('array', $results);
        $this->assertCount(8, $results);

        // rapidly test parsed results
        foreach ($results as $result) {
            $this->assertInstanceOf(ParserResultInterface::class, $result);
            /** @var ParserResultInterface $result */
            $this->assertInstanceOf(Order::class, $result->getEntity());
            /** @var Order $order */
            $order = $result->getEntity();
            $this->assertInstanceOf(\DateTime::class, $order->date);
            $this->assertNotEmpty($order->id);
            $this->assertNotEmpty($order->clientName);
            $this->assertNotEmpty($order->country);
            $this->assertNotEmpty($order->postalCode);
            $this->assertInternalType('integer', $order->postalCode);
            $this->assertInternalType('float', $order->amount);
            $this->assertNull($order->shipDate);
        }
    }


    public function testParsingWithCustomResult()
    {
        $parser = new OrderParser(__DIR__ .'/fixtures/valid-csv-orders.csv', ';');
        $parser->setSkipFirstLine(true);
        $parser->setMinColumnCount(7);
        $parser->setResultClassName(OrderParserResult::class);

        $results = $parser->execute();
        $this->assertInternalType('array', $results);
        $this->assertCount(8, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(OrderParserResult::class, $result);
            /** @var ParserResultInterface $result */
            $this->assertInstanceOf(Order::class, $result->getEntity());
            /** @var Order $order */
            $order = $result->getEntity();
            $this->assertNotNull($order->shipDate);
            $this->assertInstanceOf(\DateTime::class, $order->shipDate);
        }
    }
}

