<?php

namespace Luminous\Bridge\Post\Entities;

use WP_Post;
use Carbon\Carbon;
use Luminous\Bridge\WP;
use Luminous\Bridge\Entity as BaseEntity;
use Luminous\Bridge\Post\Type;

abstract class Entity extends BaseEntity
{
    const PAGING_SEPALATOR = '/\n?<!--nextpage-->\n?/';
    const TEASER_SEPALATOR = '/<!--more(.*?)?-->/';
    const NO_TEASER_FLAG   = '/<!--noteaser-->/';

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [
        'id'            => 'ID',
        'slug'          => 'post_name',
        'title'         => 'post_title',
        'raw_content'   => 'post_content',
        'raw_excerpt'   => 'post_excerpt',
        'status'        => 'post_status',
        'order'         => 'menu_order',
        'created_at'    => 'post_date',
        'updated_at'    => 'post_modified',
    ];

    /**
     * The array of paged content.
     *
     * @var array
     */
    protected $pagedContent;

    /**
     * Create a new post entity instance.
     *
     * @param \WP_Post $original
     * @param \Luminous\Bridge\Post\Type $type
     * @return void
     */
    public function __construct(WP_Post $original, Type $type)
    {
        parent::__construct($original, $type);
    }

    /**
     * Get the path.
     *
     * @uses \get_page_uri()
     *
     * @return string
     */
    protected function getPathAttribute()
    {
        return get_page_uri($this->original);
    }

    /**
     * Get the content.
     *
     * @todo Support teaser & more link.
     * @todo Support 'noteaser' flag.
     * @todo Support preview.
     *
     * @link https://developer.wordpress.org/reference/functions/get_the_content/ get_the_content()
     * @link https://developer.wordpress.org/reference/functions/the_content/ the_content()
     *
     * @uses \apply_filters()
     *
     * @param int $page
     * @return string HTML
     */
    public function content($page = 0)
    {
        if ($page > 0) {
            $content = $this->pagedContent($page) ?: '';
        } else {
            $content = $this->raw_content;
        }

        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);

        return $content;
    }

    /**
     * Get the paged content.
     *
     * @link https://developer.wordpress.org/reference/classes/wp_query/setup_postdata/ setup_postdata()
     *
     * @param null|int $page
     * @return array|string|null
     */
    protected function pagedContent($page = null)
    {
        if (is_null($this->pagedContent)) {
            $this->pagedContent = preg_split(static::PAGING_SEPALATOR, $this->raw_content);
        }

        if (is_null($page)) {
            return $this->pagedContent;
        }

        return isset($this->pagedContent[$index = $page - 1]) ? $this->pagedContent[$index] : null;
    }

    /**
     * Get the content.
     *
     * @return string HTML
     */
    protected function getContentAttribute()
    {
        return $this->content();
    }

    /**
     * Get the number of paged content.
     *
     * @return int
     */
    protected function getPagesAttribute()
    {
        return count($this->pagedContent());
    }

    /**
     * Get the excerpt.
     *
     * @uses \strip_shortcodes()
     *
     * @param int $length
     * @return string
     */
    public function excerpt($length = 120)
    {
        $filter = function ($excerpt) {
            $excerpt = strip_shortcodes($excerpt);
            $excerpt = strip_tags($excerpt);
            $excerpt = preg_replace('/<!--[^>]*-->/', '', $excerpt);
            $excerpt = preg_replace('/[\s|\x{3000}]+/u', ' ', $excerpt);
            return trim($excerpt);
        };

        $excerpt = $filter($this->raw_excerpt);

        if ($excerpt === '') {
            $excerpt = $filter($this->raw_content);
        }

        if ($length && mb_strlen($excerpt) > $length) {
            $excerpt = mb_substr($excerpt, 0, $length - 1, 'utf8') . '…';
        }

        return $excerpt;
    }

    /**
     * Get the excerpt.
     *
     * @return string HTML
     */
    protected function getExcerptAttribute()
    {
        return $this->excerpt();
    }

    /**
     * Get the time when the post was created.
     *
     * @param string $value
     * @return \Carbon\Carbon
     */
    protected function getCreatedAtAttribute($value)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, WP::timezone());
    }

    /**
     * Get the time when the post was updated.
     *
     * @param string $value
     * @return \Carbon\Carbon
     */
    protected function getUpdatedAtAttribute($value)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, WP::timezone());
    }

    /**
     * The alias to `created_at`.
     *
     * @return \Carbon\Carbon
     */
    protected function getDateAttribute()
    {
        return $this->created_at;
    }

    /**
     * Get the formatted time.
     *
     * @param string $format
     * @param bool $updated
     * @return string
     */
    public function date($format, $updated = false)
    {
        return $this->{$updated ? 'updated_at' : 'created_at'}->format($format);
    }

    /**
     * Get the route type for this instance.
     *
     * @return string
     */
    public function getRouteType()
    {
        return $this->type->getRouteType();
    }

    /**
     * Get a URL parameter.
     *
     * @param string $key
     * @return string
     */
    public function urlParameter($key)
    {
        $dateFormats = [
            'year'  => 'Y',
            'month' => 'm',
            'day'   => 'd',
        ];

        if (array_key_exists($key, $dateFormats)) {
            return $this->date($dateFormats[$key]);
        }

        return $this->{$key};
    }
}
