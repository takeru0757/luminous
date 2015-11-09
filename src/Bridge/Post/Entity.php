<?php

namespace Luminous\Bridge\Post;

use WP_Post;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\Entity as BaseEntity;
use Luminous\Bridge\WP;
use Luminous\Bridge\Term\Type as TermType;

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
        'modified_at'   => 'post_modified',
    ];

    /**
     * The array of paged content.
     *
     * @var array
     */
    protected $cachedPagedContent;

    /**
     * Create a new post entity instance.
     *
     * @param \Luminous\Bridge\Post\Type $type
     * @param \WP_Post $original
     * @return void
     */
    public function __construct(Type $type, WP_Post $original)
    {
        parent::__construct($type, $original);
    }

    /**
     * Determine if the post is public.
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->status === 'publish';
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
        if (is_null($this->cachedPagedContent)) {
            $this->cachedPagedContent = preg_split(static::PAGING_SEPALATOR, $this->raw_content);
        }

        if (is_null($page)) {
            return $this->cachedPagedContent;
        }

        return isset($this->cachedPagedContent[$index = $page - 1]) ? $this->cachedPagedContent[$index] : null;
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
     * @param int|false $length
     * @param bool $useContent
     * @param string $marker
     * @return string
     */
    public function excerpt($length = 120, $useContent = true, $marker = '…')
    {
        $excerpt = $this->excerpt;

        if ($useContent && $excerpt === '') {
            $excerpt = $this->stripTags($this->raw_content);
        }

        if ($length && mb_strlen($excerpt) > $length) {
            $excerpt = mb_substr($excerpt, 0, $length - 1, 'utf8').$marker;
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
        return $this->stripTags($this->raw_excerpt);
    }

    /**
     * Get the time when the post was created at.
     *
     * @param string $value
     * @return \Carbon\Carbon
     */
    protected function getCreatedAtAttribute($value)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $value, WP::timezone());
    }

    /**
     * Get the time when the post was modified at.
     *
     * @param string $value
     * @return \Carbon\Carbon
     */
    protected function getModifiedAtAttribute($value)
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
     * @param bool $modified
     * @return string
     */
    public function date($format, $modified = false)
    {
        return $this->{$modified ? 'modified_at' : 'created_at'}->format($format);
    }

    /**
     * Get the terms.
     *
     * @uses \get_the_terms()
     * @uses \is_wp_error()
     *
     * @param string|\Luminous\Bridge\Term\Type $termType
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Term\Entity[]
     */
    public function terms($type)
    {
        $type = WP::termType($type);

        $originals = get_the_terms($this->id, $type->name);
        $originals = $originals && ! is_wp_error($originals) ? $originals : [];

        $terms = array_map(function ($original) {
            return WP::term($original);
        }, $originals);

        return new Collection($terms);
    }

    /**
     * Get the meta data.
     *
     * @uses \get_post_meta()
     *
     * @param string $key
     * @param bool $single
     * @return mixed
     */
    public function meta($key, $single = true)
    {
        return get_post_meta($this->id, $key, $single);
    }

    /**
     * Get the thumbnail.
     *
     * @uses \get_post_thumbnail_id()
     * @return \Luminous\Bridge\Post\Entities\AttachmentEntity|null
     */
    protected function getThumbnailAttribute()
    {
        if ($id = get_post_thumbnail_id($this->id)) {
            return WP::post((int) $id);
        }

        return null;
    }

    /**
     * Get the thumbnail URL.
     *
     * @param string|null $size
     * @param string $default
     * @return string
     */
    public function thumbnailSrc($size = null, $default = null)
    {
        if ($thumbnail = $this->thumbnail) {
            return $thumbnail->src($size);
        }

        return $default;
    }

    /**
     * Get the URL path.
     *
     * @param string $key
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function urlPath($key)
    {
        $dateFormats = [
            'date_year'     => 'Y',
            'date_month'    => 'm',
            'date_day'      => 'd',
        ];

        if (array_key_exists($key, $dateFormats)) {
            return $this->date($dateFormats[$key]);
        }

        return parent::urlPath($key);
    }
}
