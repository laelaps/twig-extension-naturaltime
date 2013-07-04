<?php

namespace Laelaps\Twig\Naturaltime;

use DateInterval;
use DateTime;
use DomainException;
use InvalidArgumentException;

/**
 * @author Mateusz Charytoniuk <mateusz.charytoniuk@gmail.com>
 */
class Naturaltime
{
    const NATURALTIME_DIFF_DAYS_LIMIT = 14;

    /**
     * @param int $n
     * @return int
     */
    protected function getInflectForm($n)
    {
        $n = intval($n);
        if ($n === 1) {
            return 0;
        }
        if (($n % 10 >= 2) && ($n % 10 <= 4) && ($n % 100 < 10 || $n % 100 >= 20)) {
            return 1;
        }

        return 2;
    }

    /**
     * @param int $n
     * @param string $f1
     * @param string $f2
     * @param string $f3
     * @return string
     */
    protected function inflect($n, $f1, $f2, $f3)
    {
        $n = intval($n);
        switch ($this->getInflectForm($n)) {
            case 0: return $f1;
            case 1: return $f2;
            case 2: return $f3;
        }
    }

    /**
     * @param int $n
     * @return string
     * @throws DomainException If unknown month is used as function parameter.
     */
    public function getMonthName($n)
    {
        switch ($n) {
            case 1: return 'stycznia';
            case 2: return 'lutego';
            case 3: return 'marca';
            case 4: return 'kwietnia';
            case 5: return 'maja';
            case 6: return 'czerwca';
            case 7: return 'lipca';
            case 8: return 'sierpnia';
            case 9: return 'września';
            case 10: return 'października';
            case 11: return 'listopada';
            case 12: return 'grudnia';
        }
        throw new DomainException('No such month: ' . $n);
    }

    /**
     * @param DateInterval $datediff
     * @return boolean
     */
    public function isFuture(DateInterval $datediff)
    {
        return !$this->isNow($datediff) && !$this->isPast($datediff);
    }

    /**
     * @param DateInterval $datediff
     * @return boolean
     */
    public function isNow(DateInterval $datediff)
    {
        return intval($datediff->format('%r%a%h%i%s')) === 0;
    }

    /**
     * @param DateInterval $datediff
     * @return boolean
     */
    public function isPast(DateInterval $datediff)
    {
        return !$this->isNow($datediff) && ($datediff->format('%R') === '-');
    }

    /**
     * @param mixed $timestamp
     * @return DateTime
     */
    public function normalizeDateTime($timestamp)
    {
        if ($timestamp instanceof DateTime) {
            return $timestamp;
        }
        $timestamp = $this->normalizeTimestamp($timestamp);

        $dateTime = new DateTime;
        $dateTime->setTimestamp($timestamp);

        return $dateTime;
    }

    /**
     * @param mixed $timestamp
     * @return int
     */
    public function normalizeTimestamp($timestamp)
    {
        if (is_int($timestamp)) {
            return $timestamp;
        }
        if ($timestamp instanceof DateTime) {
            return $timestamp->getTimestamp();
        }
        if (is_string($timestamp)) {
            $timestamp = trim($timestamp);
            if (is_numeric($timestamp) && strpos($timestamp, '.') === false) {
                return intval($timestamp);
            }
        }

        return strtotime($timestamp);
    }

    /**
     * @param int $then
     * @param int $now
     * @return string
     */
    public function renderTimestamp($then, $now)
    {
        $then = $this->normalizeDateTime($then);
        $now = $this->normalizeDateTime($now);

        $dateInterval = $now->diff($then);

        return $this->renderDateInterval($dateInterval, $now, $then);
    }

    /**
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateInterval(DateInterval $di, DateTime $now, DateTime $then)
    {
        if ($this->isNow($di)) {
            $response = $this->renderDateIntervalNow($di, $now, $then);
        } elseif ($this->isPast($di)) {
            $response = $this->renderDateIntervalStub('temu', $di, $now, $then);
        } else {
            $response = $this->renderDateIntervalStub('od teraz', $di, $now, $then);
        }

        return $this->sanitizeResponse($response);
    }

    /**
     * @param DateInterval $di
     * @param DateTime $now
     * @return string
     */
    public function renderDateIntervalNow(DateInterval $di, DateTime $now)
    {
        return 'teraz';
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStub($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $a = intval($di->format('%a'));
        if ($a > self::NATURALTIME_DIFF_DAYS_LIMIT) {
            return $this->renderDateIntervalStubDateTime($suffix, $di, $now, $then);
        } elseif ($a > 0) {
            return $this->renderDateIntervalStubDay($suffix, $di, $now, $then);
        }
        $h = intval($di->format('%h'));
        if ($h > 0) {
            return $this->renderDateIntervalStubHour($suffix, $di, $now, $then);
        }
        $i = intval($di->format('%i'));
        if ($i > 0) {
            return $this->renderDateIntervalStubMinute($suffix, $di, $now, $then);
        }
        $s = intval($di->format('%s'));
        if ($s > 0) {
            return $this->renderDateIntervalStubSecond($suffix, $di, $now, $then);
        }

        return $this->renderDateIntervalNow($di, $now, $then);
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStubDateTime($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $d = intval($then->format('d'));
        $m = intval($then->format('m'));

        return $d . ' ' . $this->getMonthName($m);
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStubDay($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $n = intval($di->format('%d'));
        if ($n === 1) {
            return $this->isPast($di) ? 'dzień temu' : 'za jeden dzień';
        }
        $date = $this->inflect($n, 'dzień', 'dni', 'dni');

        return $this->renderDateStubChunk($n, $date, $suffix);
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStubHour($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $n = intval($di->format('%h'));
        if ($n === 1 && $this->isPast($di)) {
            return $this->renderDateStubChunk(null, 'godzinę', $suffix);
        }
        $date = $this->inflect($n, 'godzina', 'godziny', 'godzin');

        return $this->renderDateStubChunk($n, $date, $suffix);
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStubMinute($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $n = intval($di->format('%i'));
        if ($n === 1 && $this->isPast($di)) {
            return $this->renderDateStubChunk(null, 'minutę', $suffix);
        }
        $date = $this->inflect($n, 'minuta', 'minuty', 'minut');

        return $this->renderDateStubChunk($n, $date, $suffix);
    }

    /**
     * @param string $suffix
     * @param DateInterval $di
     * @param DateTime $now
     * @param DateTime $then
     * @return string
     */
    public function renderDateIntervalStubSecond($suffix, DateInterval $di, DateTime $now, DateTime $then)
    {
        $n = intval($di->format('%s'));
        if ($n === 1 && $this->isPast($di)) {
            return $this->renderDateStubChunk(null, 'sekundę', $suffix);
        }
        $date = $this->inflect($n, 'sekunda', 'sekundy', 'sekund');

        return $this->renderDateStubChunk($n, $date, $suffix);
    }

    /**
     * @param int $n
     * @param string $body
     * @param string $suffix
     * @return string
     */
    public function renderDateStubChunk($n, $body, $suffix)
    {
        return $this->sanitizeResponse($n . ' ' . $body . ' ' . $suffix);
    }

    /**
     * @param string $response
     * @return string
     */
    public function sanitizeResponse($response)
    {
        if (!is_string($response)) {
            throw new InvalidArgumentException('Response is not a string.');
        }

        return trim($response);
    }
}
