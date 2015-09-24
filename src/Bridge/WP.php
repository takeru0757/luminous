<?php

namespace Luminous\Bridge;

use DateTimeZone;
use WP_Post;
use Illuminate\Support\Collection;
use Luminous\Bridge\Post\Builder as Post;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Term\Builder as Term;
use Luminous\Bridge\Term\Type as TermType;

class WP
{
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
     * @return \Illuminate\Support\Collection
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
