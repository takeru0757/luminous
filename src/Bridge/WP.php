<?php

namespace Luminous\Bridge;

use Exception;
use DateTimeZone;
use Carbon\Carbon;
use Illuminate\Contracts\Container\Container;
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
     * The IoC container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * The post builder.
     *
     * @var \Luminous\Bridge\Post\Builder
     */
    protected $post;

    /**
     * The term builder.
     *
     * @var \Luminous\Bridge\Term\Builder
     */
    protected $term;

    /**
     * The map of methods to get a option value.
     *
     * @var array
     */
    protected $optionMethods = [
        'last_modified' => 'lastModified',
        'timezone' => 'timezone',
    ];

    /**
     * The map of option aliases.
     *
     * @var array
     */
    protected $optionAliases = [
        'url'           => 'home',
        'name'          => 'blogname',
        'description'   => 'blogdescription',
    ];

    /**
     * Create a new wp instance.
     *
     * @param \Illuminate\Contracts\Container\Container $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->post = new PostBuilder($this->container, $this);
        $this->term = new TermBuilder($this->container, $this);
    }

    /**
     * Get the value from the options database table.
     *
     * @uses \get_option()
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function option($key, $default = null)
    {
        if (array_key_exists($key, $this->optionMethods)) {
            return $this->{$this->optionMethods[$key]}();
        }

        if (array_key_exists($key, $this->optionAliases)) {
            $key = $this->optionAliases[$key];
        }

        return get_option($key, $default);
    }

    /**
     * Get the time when the site was modified at.
     *
     * @return \Carbon\Carbon
     *
     * @throws \Exception
     */
    public function lastModified()
    {
        static $value = null;

        if (is_null($value)) {
            if (! $timestamp = $this->option(static::OPTION_LAST_MODIFIED)) {
                throw new Exception("Option [".static::OPTION_LAST_MODIFIED."] could not be found.");
            }
            $value = Carbon::createFromTimeStamp((int) $timestamp, $this->timezone());
        }

        return $value;
    }

    /**
     * Get the timezone for display.
     *
     * @return \DateTimeZone
     */
    public function timezone()
    {
        static $value = null;
        return ! is_null($value) ? $value : ($value = new DateTimeZone($this->option('timezone_string')));
    }

    /**
     * Whether this site is public (`blog_public`).
     *
     * @return bool
     */
    public function isPublic()
    {
        static $value = null;
        return ! is_null($value) ? $value : ($value = (bool) $this->option('blog_public'));
    }

    /**
     * Get all post type collection.
     *
     * @uses \get_post_types()
     *
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Post\Type[]
     */
    public function postTypes()
    {
        $types = array_merge(['page', 'post'], get_post_types(['public' => true, '_builtin' => false]));

        return new Collection(array_map([$this, 'postType'], $types));
    }

    /**
     * Get the post type instance.
     *
     * @param string|\Luminous\Bridge\Post\Type $name
     * @return \Luminous\Bridge\Post\Type
     */
    public function postType($name)
    {
        return $this->post->getType($name);
    }

    /**
     * Get the post query instance.
     *
     * @param \Luminous\Bridge\Post\Type|string|array $type
     * @return \Luminous\Bridge\Post\Query\Builder
     */
    public function posts($type = null)
    {
        $query = $this->post->query();

        return $type ? $query->type($type) : $query;
    }

    /**
     * Get the post entity instance.
     *
     * @param int|string|\WP_Post $id
     * @param \Luminous\Bridge\Post\Type|string $type
     * @return \Luminous\Bridge\Post\Entity
     */
    public function post($id, $type = null)
    {
        return $this->post->get($id, $type);
    }

    /**
     * Get the term type (taxonomy) instance.
     *
     * @param string|\Luminous\Bridge\Term\Type $name
     * @return \Luminous\Bridge\Term\Type
     */
    public function termType($name)
    {
        return $this->term->getType($name);
    }

    /**
     * Get the term entity instance.
     *
     * @param int|\stdClass $id
     * @param \Luminous\Bridge\Term\Type|string $type
     * @return \Luminous\Bridge\Term\Entity
     */
    public function term($id, $type = null)
    {
        return $this->term->get($id, $type);
    }

    /**
     * Get the term query instance.
     *
     * @param \Luminous\Bridge\Term\Type|string $type
     * @return \Luminous\Bridge\Term\Query\Builder
     */
    public function terms($type = null)
    {
        $query = $this->term->query();

        return $type ? $query->type($type) : $query;
    }
}
