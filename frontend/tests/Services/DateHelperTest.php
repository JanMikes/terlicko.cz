<?php

declare(strict_types=1);

namespace Terlicko\Web\Tests\Services;

use PHPUnit\Framework\TestCase;
use Terlicko\Web\Services\DateHelper;

final class DateHelperTest extends TestCase
{
    public function testFormatActionDateWithNullDatum(): void
    {
        $result = DateHelper::formatActionDate(null, null);

        $this->assertNull($result);
    }

    public function testFormatActionDateWithOnlyStartDate(): void
    {
        $datum = new \DateTimeImmutable('2023-10-15 00:00:00');

        $result = DateHelper::formatActionDate($datum, null);

        $this->assertSame('neděle 15.10.2023', $result);
    }

    public function testFormatActionDateWithStartDateAndTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 18:30:00'); // úterý

        $result = DateHelper::formatActionDate($datum, null);

        $this->assertSame('úterý 17.10.2023 od 18:30', $result);
    }

    public function testFormatActionDateSameDayWithEndTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 18:30:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-17 23:00:00'); // same day

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023 od 18:30 do 23:00', $result);
    }

    public function testFormatActionDateSameDayWithoutTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 00:00:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-17 00:00:00'); // same day

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023', $result);
    }

    public function testFormatActionDateMultiDayWithEndTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 18:30:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-18 23:00:00'); // středa

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023 od 18:30 do středa 18.10.2023 23:00', $result);
    }

    public function testFormatActionDateMultiDayWithoutEndTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 18:30:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-18 00:00:00'); // středa

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023 od 18:30 do středa 18.10.2023', $result);
    }

    public function testFormatActionDateMultiDayWithoutStartTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 00:00:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-18 23:00:00'); // středa

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023 do středa 18.10.2023 23:00', $result);
    }

    public function testFormatActionDateMultiDayWithoutAnyTime(): void
    {
        $datum = new \DateTimeImmutable('2023-10-17 00:00:00'); // úterý
        $datumDo = new \DateTimeImmutable('2023-10-18 00:00:00'); // středa

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('úterý 17.10.2023 do středa 18.10.2023', $result);
    }

    public function testCzechDaysOfWeek(): void
    {
        // Monday
        $datum = new \DateTimeImmutable('2023-10-16');
        $result = DateHelper::formatActionDate($datum, null);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('pondělí', $result);

        // Tuesday
        $datum = new \DateTimeImmutable('2023-10-17');
        $result = DateHelper::formatActionDate($datum, null);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('úterý', $result);

        // Wednesday
        $datum = new \DateTimeImmutable('2023-10-18');
        $result = DateHelper::formatActionDate($datum, null);
        $this->assertNotNull($result);
        $this->assertStringStartsWith('středa', $result);
    }

    public function testFormatActionDateEdgeCaseNewYear(): void
    {
        $datum = new \DateTimeImmutable('2023-12-31 23:30:00');
        $datumDo = new \DateTimeImmutable('2024-01-01 01:00:00');

        $result = DateHelper::formatActionDate($datum, $datumDo);

        $this->assertSame('neděle 31.12.2023 od 23:30 do pondělí 1.1.2024 01:00', $result);
    }
}