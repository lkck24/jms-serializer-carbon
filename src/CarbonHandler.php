<?php

declare(strict_types=1);

namespace AutumnDev\JMS;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTime;
use DateTimeZone;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlDeserializationVisitor;
use JMS\Serializer\XmlSerializationVisitor;
use RuntimeException;
use SimpleXMLElement;

class CarbonHandler implements SubscribingHandlerInterface
{
	private DateTimeZone $defaultTimezone;

	public function __construct(
        private $defaultFormat = DateTime::ISO8601,
        string $defaultTimezone = 'UTC',
        private $xmlCData = true,
    ) {
		$this->defaultTimezone = new DateTimeZone($defaultTimezone);
	}

	public static function getSubscribingMethods(): array
	{
        $types = ['Carbon', 'CarbonImmutable', Carbon::class, CarbonImmutable::class];

        $methods = [];

		foreach (['json', 'xml', 'yml'] as $format) {
			foreach ($types as $type) {
				$methods[] = [
					'type' => $type,
					'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
					'format' => $format,
				];
				$methods[] = [
					'type' => $type,
					'format' => $format,
					'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
					'method' => 'serializeCarbon',
				];
			}
		}

		return $methods;
	}

	public function serializeCarbon(
		SerializationVisitorInterface $visitor,
		CarbonInterface $date,
		array $type,
		Context $context,
	): mixed {
		if ($visitor instanceof XmlSerializationVisitor && false === $this->xmlCData) {
			return $visitor->visitSimpleString(
                data: $date->format(
                    format: $this->getFormat(type: $type),
                ),
                type: $type,
            );
		}

		$format = $this->getFormat($type);

		if ('U' === $format) {
			return (string)$visitor->visitInteger((int)$date->format($format), $type);
		}

		return $visitor->visitString($date->format($this->getFormat($type)), $type);
	}

	public function deserializeCarbonFromXml(
        XmlDeserializationVisitor $visitor,
        mixed $data,
        array $type,
    ): ?Carbon {
		if ($this->isDataXmlNull($data)) {
			return null;
		}

		$dateObj = $this->parseDateTime(
            data: $data,
            type: $type,
        );

		return Carbon::instance(date: $dateObj);
	}

	public function deserializeCarbonFromJson(
        JsonDeserializationVisitor $visitor,
        mixed $data,
        array $type
    ): ?Carbon {
		if (empty($data)) {
			return null;
		}

		$dateObj = $this->parseDateTime(
            data: $data,
            type: $type,
        );

		return Carbon::instance(date: $dateObj);
	}

	private function parseDateTime(
        mixed $data,
        array $type,
    ) {
        $timezone = isset($type['params'][1]) ? new DateTimeZone($type['params'][1]) : $this->defaultTimezone;
		$format = $this->getFormat($type);

        $datetime = DateTime::createFromFormat($format, (string)$data, $timezone);

		if (false === $datetime) {
			throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
		}

		return $datetime;
	}

	private function getFormat(
        array $type,
    ): string {
		return $type['params'][0] ?? $this->defaultFormat;
	}

	private function isDataXmlNull(
        SimpleXMLElement $data,
    ): bool {
		$attributes = $data->attributes(
            namespaceOrPrefix: 'xsi',
            isPrefix: true,
        );

		return isset($attributes['nil'][0]) && (string)$attributes['nil'][0] === 'true';
	}
}
