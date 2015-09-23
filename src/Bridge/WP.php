<?php

namespace Luminous\Bridge;

use DateTimeZone;
use WP_Post;
use Luminous\Bridge\Post\Builder as Post;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Term\Builder as Term;
use Luminous\Bridge\Term\Type as TermType;

class WP
{
    /**
     * The timezone for display.
     *
     * @var \DateTimeZone
     */
    protected static $timezone;

    /**
     * Get the value from the options database table.
     *
     * @uses \get_option()
     *
     * @param string $name
     * @param bool $default
     * @return mixed
     */
    public static function option($name, $default = false)
    {
        return get_option($name, $default);
    }

    /**
     * Get the timezone for display.
     *
     * @return \DateTimeZone
     */
    public static function timezone()
    {
        if (static::$timezone === null) {
            $string = static::option('timezone_string');
            static::$timezone = new DateTimeZone($string);
        }

        return static::$timezone;
    }

    /**
     * Get the post type instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Post\Type
     */
    public static function postType($name)
    {
        return PostType::get($name);
    }

    /**
     * Get the post query instance.
     *
     * @param \Luminous\Bridge\Post\Type|string|array $type
     * @return \Luminous\Bridge\Post\Query
     */
    public static function posts($type = null)
    {
        $query = Post::query();
        return $type ? $query->type($type) : $query;
    }

    /**
     * Get the post entity instance.
     *
     * @param int|\WP_Post $id
     * @return \Luminous\Bridge\Post\Entities\Entity
     */
    public static function post($id)
    {
        return Post::get($id);
    }

    /**
     * Get the term type (taxonomy) instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Term\Type
     */
    public static function termType($name)
    {
        return TermType::get($name);
    }

    /**
     * Get the term entity instance.
     *
     * @param int|\stdClass $id
     * @param string $type
     * @return \Luminous\Bridge\Term\Entities\Entity
     */
    public static function term($id, $type = null)
    {
        return Term::get($id, $type);
    }
}
