<?php

namespace Luminous\Bridge\Post;

use Carbon\Carbon;

/**
 * @property-read string $type
 * @property-read \Carbon\Carbon $date
 * @property-read int|null $count
 */
class Archive
{
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
    protected $date;

    /**
     * The number of posts.
     *
     * @var int|null
     */
    protected $count;

    /**
     * Create a new archive instance.
     *
     * @param string $type
     * @param \Carbon\Carbon $date
     * @param int $count
     * @return void
     */
    public function __construct($type, Carbon $date, $count = null)
    {
        $this->type = $type;
        $this->date = $date;
        $this->count = $count;
    }

    /**
     * Get the formatted date string.
     *
     * @param string $format
     * @return string
     */
    public function format($format)
    {
        return $this->date->format($format);
    }

    /**
     * Get the value of the archive route key.
     *
     * @param string $key
     * @return string
     */
    public function urlParameter($key)
    {
        $parameters = [
            'yearly'    => ['year' => 'Y'],
            'monthly'   => ['year' => 'Y', 'month' => 'm'],
            'daily'     => ['year' => 'Y', 'month' => 'm', 'day' => 'd'],
        ];

        $formats = $parameters[$this->type];

        return isset($formats[$key]) ? $this->format($formats[$key]) : null;
    }

    /**
     * Dynamically access this attributes.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if (in_array($key, ['type', 'date', 'count'])) {
            return $this->{$key};
        }

        return $this->date->{$key};
    }
}
