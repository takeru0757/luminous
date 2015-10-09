<?php

namespace Luminous\Bridge\Post;

use Carbon\Carbon;
use Luminous\Bridge\UrlPathTrait;
use Luminous\Bridge\UrlResource;

/**
 * @property-read string $type
 * @property-read \Carbon\Carbon $date
 * @property-read int|null $count
 */
class Archive implements UrlResource
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
     * Get the URL apth.
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
        return ['archive' => $this];
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
