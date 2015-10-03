<?php

namespace Luminous\Bridge\Post;

use Carbon\Carbon;

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
     * @var Carbon\Carbon
     */
    protected $date;

    /**
     * The number of posts.
     *
     * @var int
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
    public function __construct($type, Carbon $date, $count = 0)
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
     * Get a URL parameters.
     *
     * @return array
     */
    public function urlParameters()
    {
        $types = [
            'yearly'    => ['year'],
            'monthly'   => ['year', 'month'],
            'daily'     => ['year', 'month', 'day'],
        ];

        $keys = $types[$this->type];

        return array_combine($keys, array_map([$this->date, '__get'], $keys));
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
