<?php

namespace Luminous\Bridge;

use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\Post\Builder as PostBuilder;
use Luminous\Bridge\Term\Builder as TermBuilder;

class WP
{
    /**
     * The option key for the time when posts were modified at.
     *
     * @var string
     */
    const OPTION_LAST_MODIFIED = 'luminous_last_modified';

    /**
     * The time when the application was modified at.
     *
     * @var int UNIX time
     */
    protected static $appLastModified = 0;

    /**
     * The post builder.
     *
     * @var Luminous\Bridge\Post\Builder
     */
    protected static $post;

    /**
     * The term builder.
     *
     * @var Luminous\Bridge\Term\Builder
     */
    protected static $term;

    /**
     * Set the time when the application was modified at.
     *
     * @param int $time UNIX time
     * @return void
     */
    public static function setAppLastModified($time)
    {
        static::$appLastModified = (int) $time;
    }

    /**
     * Set the post builder.
     *
     * @param Luminous\Bridge\Post\Builder $builder
     * @return void
     */
    public static function setPostBuilder(PostBuilder $builder)
    {
        static::$post = $builder;
    }

    /**
     * Set the term builder.
     *
     * @param Luminous\Bridge\Term\Builder $builder
     * @return void
     */
    public static function setTermBuilder(TermBuilder $builder)
    {
        static::$term = $builder;
    }

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
     * Get the time when the site was modified at.
     *
     * @return \Carbon\Carbon
     */
    public static function lastModified()
    {
        static $value = null;

        if (is_null($value)) {
            $lastModified = static::option(static::OPTION_LAST_MODIFIED, time());
            $time = max($lastModified, static::$appLastModified);
            $value = Carbon::createFromTimeStamp($time, static::timezone());
        }

        return $value;
    }

    /**
     * Get the timezone for display.
     *
     * @return \DateTimeZone
     */
    public static function timezone()
    {
        static $value = null;
        return ! is_null($value) ? $value : ($value = new DateTimeZone(static::option('timezone_string')));
    }

    /**
     * Whether this site is public (`blog_public`).
     *
     * @return bool
     */
    public static function isPublic()
    {
        static $value = null;
        return ! is_null($value) ? $value : ($value = (bool) static::option('blog_public'));
    }

    /**
     * Get all post type instances.
     *
     * @uses \get_post_types()
     *
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Post\Type[]
     */
    public static function postTypes()
    {
        $types = get_post_types(['public' => true, '_builtin' => false]);
        $types = array_merge(['page', 'post'], $types);
        return new Collection(array_map(get_called_class().'::postType', $types));
    }

    /**
     * Get the post type instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Post\Type
     */
    public static function postType($name)
    {
        return static::$post->getType($name);
    }

    /**
     * Get the post query instance.
     *
     * @param \Luminous\Bridge\Post\Type|string|array $type
     * @return \Luminous\Bridge\Post\Query\Builder
     */
    public static function posts($type = null)
    {
        $query = static::$post->query();
        return $type ? $query->type($type) : $query;
    }

    /**
     * Get the post entity instance.
     *
     * @param int|\WP_Post $id
     * @return \Luminous\Bridge\Post\Entity
     */
    public static function post($id)
    {
        return static::$post->get($id);
    }

    /**
     * Get the term type (taxonomy) instance.
     *
     * @param string $name
     * @return \Luminous\Bridge\Term\Type
     */
    public static function termType($name)
    {
        return static::$term->getType($name);
    }

    /**
     * Get the term entity instance.
     *
     * @param int|\stdClass $id
     * @param string $type
     * @return \Luminous\Bridge\Term\Entity
     */
    public static function term($id, $type = null)
    {
        return static::$term->get($id, $type);
    }
}
