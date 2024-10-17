<?php

declare(strict_types=1);

namespace AutumnDev\JMS;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTime;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\XmlDeserializationVisitor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class CarbonHandlerTest extends TestCase
{
    #[DataProvider('carbonTestDataProvider')]
    public function testThatCarbonCanBeSerialized(
        CarbonInterface $date,
        string $expected,
        string $format,
    ): void {
        $carbonHandler = new CarbonHandler();

        $dateString = $carbonHandler->serializeCarbon(
            visitor: new JsonSerializationVisitor(),
            date: $date,
            type: [
                'params' => [
                    $format,
                ],
            ],
            context: new SerializationContext(),
        );

        self::assertEquals(expected: $expected, actual: $dateString);
    }

    #[DataProvider('carbonTestDataProvider')]
    public function testThatCarbonCanBeDeSerializedFromJson(
        CarbonInterface $expected,
        string $data,
        string $format,
    ): void {
        $carbonHandler = new CarbonHandler();

        $dateString = $carbonHandler->deserializeCarbonFromJson(
            visitor: new JsonDeserializationVisitor(),
            data: $data,
            type: [
                'params' => [
                    $format,
                ],
            ],
        );

        self::assertEquals(expected: $expected, actual: $dateString);
    }

    #[DataProvider('carbonTestDataProvider')]
    public function testThatCarbonCanBeDeSerializedFromXml(
        CarbonInterface $expected,
        string $data,
        string $format,
    ): void {
        $carbonHandler = new CarbonHandler();

        $dateString = $carbonHandler->deserializeCarbonFromXml(
            visitor: new XmlDeserializationVisitor(),
            data: new SimpleXMLElement(data: "<root>$data</root>"),
            type: [
                'params' => [
                    $format,
                ],
            ],
        );

        self::assertEquals(expected: $expected, actual: $dateString);
    }

    public function testThatSubscribingMethodsReturnAllMethods(
    ): void {
        $methods = CarbonHandler::getSubscribingMethods();

        self::assertCount(expectedCount: 24, haystack: $methods);
    }

    public static function carbonTestDataProvider(): array
    {
        return [
            [new Carbon(time: '2021-10-10 10:10:10', timezone: 'Europe/Berlin'), '2021-10-10T10:10:10+0200', DateTime::ISO8601],
            [new CarbonImmutable(time: '2021-10-10 10:10:10', timezone: 'Europe/Berlin'), '2021-10-10T10:10:10+0200', DateTime::ISO8601],
            [new CarbonImmutable(time: '2021-10-10 10:10:10', timezone: 'Europe/Berlin'), '1633853410', 'U'],
        ];
    }
}