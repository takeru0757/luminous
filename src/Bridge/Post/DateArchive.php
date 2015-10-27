<?php

namespace Luminous\Bridge\Post;

use Closure;
use Carbon\Carbon;
use Luminous\Bridge\UrlPathTrait;
use Luminous\Bridge\UrlResource;
use Luminous\Bridge\WP;

/**
 * @property-read string $type
 * @property-read int|null $count
 */
class DateArchive implements UrlResource
{
    use UrlPathTrait {
        urlPath as protected originalUrlPath;
    }

    /**
     * The type name.
     *
     * @var string
     */
    protected $type;

    /**
     * The date.
     *
     * @var \Carbon\Carbon
     */
    protected $datetime;

    /**
     * The number of posts.
     *
     * @var int|null
     */
    protected $count;

    /**
     * The timezone resolver callback.
     *
     * @var \Closure
     */
    protected static $timezoneResolver;

    /**
     * Dynamically access this attributes.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, ['datetime', 'type', 'count'])) {
            return $this->{$key};
        }

        return $this->datetime->{$key};
    }

    /**
     * Create a new date archive instance.
     *
     * @param string $type
     * @param \Carbon\Carbon $datetime
     * @param int|null $count
     * @return void
     */
    public function __construct($type, Carbon $datetime, $count = null)
    {
        $this->type = $type;
        $this->datetime = $datetime;
        $this->count = $count;
    }

    /**
     * Create a new date archive instance from the path.
     *
     * @param string $value
     * @param bool $local
     * @return static
     */
    public static function createFromPath($value, $local = true)
    {
        $timezone = $local ? static::getTimezone() : null;
        $value = array_map('intval', explode('/', $value));

        $types = [
            1 => 'yearly',
            2 => 'monthly',
            3 => 'daily',
        ];

        $type = $types[count($value)];
        $date = array_pad($value, 3, 1);
        $datetime = Carbon::createFromDate($date[0], $date[1], $date[2], $timezone);

        return new static($type, $datetime->startOfDay());
    }

    /**
     * Create a new date archive instance from a specific format.
     *
     * @param string $type
     * @param string $format
     * @param string $value
     * @param bool $local
     * @return static
     */
    public static function createFromFormat($type, $format, $value, $local = true)
    {
        $timezone = $local ? static::getTimezone() : null;
        $datetime = Carbon::createFromFormat($format, $value, $timezone);

        return new static($type, $datetime->startOfDay());
    }

    /**
     * Get the timezone.
     *
     * @return \DateTimeZone|null
     */
    protected static function getTimezone()
    {
        return static::$timezoneResolver ? call_user_func(static::$timezoneResolver) : null;
    }

    /**
     * Set the count.
     *
     * @param int $value
     * @return $this
     */
    public function setCount($value)
    {
        $this->count = (int) $value;

        return $this;
    }

    /**
     * Get the formatted date string.
     *
     * @param string $format
     * @return string
     */
    public function format($format)
    {
        return $this->datetime->format($format);
    }

    /**
     * Get the URL path.
     *
     * @param string $key
     * @return string
     */
    public function urlPath($key)
    {
        if ($key === 'path') {
            $formats = [
                'yearly'    => 'Y',
                'monthly'   => 'Y/m',
                'daily'     => 'Y/m/d',
            ];

            return $this->format($formats[$this->type]);
        }

        return $this->originalUrlPath($key);
    }

    /**
     * Get the array to build URL.
     *
     * @return array
     */
    public function forUrl()
    {
        return ['date' => $this];
    }

    /**
     * Get the array to build query.
     *
     * @return array
     */
    public function forQuery()
    {
        $types = [
            'yearly'    => ['year'],
            'monthly'   => ['year', 'month'],
            'daily'     => ['year', 'month', 'day'],
        ];

        $keys = $types[$this->type];

        return array_combine($keys, array_map(function ($key) {
            return $this->datetime->{$key};
        }, $keys));
    }

    /**
     * Set the timezone resolver callback.
     *
     * @param \Closure $resolver
     * @return void
     */
    public static function timezoneResolver(Closure $resolver)
    {
        static::$timezoneResolver = $resolver;
    }
}
