<?php

namespace Luminous\Bridge;

use ArrayAccess;

abstract class Entity implements ArrayAccess, UrlResource
{
    use DecoratorTrait;
    use UrlPathTrait;

    /**
     * The type.
     *
     * @var \Luminous\Bridge\Type
     */
    protected $type;

    /**
     * The accessors map for original instance.
     *
     * @var array
     */
    protected $accessors = [];

    /**
     * Create a new entity instance.
     *
     * @param \Luminous\Bridge\Type $type
     * @param object $original
     * @return void
     */
    public function __construct(Type $type, $original)
    {
        $this->type = $type;
        $this->original = $original;
        $this->accessorsForOriginal = $this->accessors;
    }

    /**
     * Get the type.
     *
     * @return \Luminous\Bridge\Type
     */
    protected function getTypeAttribute()
    {
        return $this->type;
    }

    /**
     * Get the array to build URL.
     *
     * @return array
     */
    public function forUrl()
    {
        $type = $this->type->type();

        return array_merge($this->type->forUrl(), [$type => $this]);
    }

    /**
     * Strip all HTML tags and shortcode tags.
     *
     * @uses \strip_shortcodes()
     *
     * @param string $content
     * @return string
     */
    protected function stripTags($content)
    {
        $content = strip_shortcodes($content);
        $content = strip_tags($content);
        $content = preg_replace('/<!--[^>]*-->/', '', $content);
        $content = preg_replace('/[\s|\x{3000}]+/u', ' ', $content);

        return trim($content);
    }
}
