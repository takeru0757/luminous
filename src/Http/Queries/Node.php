<?php

namespace Luminous\Http\Queries;

use ArrayAccess;
use Exception;
use Luminous\Bridge\Post\DateArchive;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Post\Entity as PostEntity;
use Luminous\Bridge\Term\Entity as TermEntity;

class Node implements ArrayAccess
{
    /**
     * The original instance.
     *
     * @var mixed
     */
    protected $original;

    /**
     * The post type.
     *
     * @var \Luminous\Bridge\Post\Type
     */
    protected $postType;

    /**
     * Dynamically retrieve the value.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->offsetExists($key)) {
            return $this->{$key}();
        }
    }

    /**
     * Create a new node instance.
     *
     * @param mixed $original
     * @param \Luminous\Bridge\Post\Type $postType
     * @return void
     */
    public function __construct($original, PostType $postType = null)
    {
        $this->original = $original;
        $this->postType = $postType;
    }

    /**
     * Get the label.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function label()
    {
        switch (true) {
            case $this->original instanceof PostType:
                return $this->original->label;
            case $this->original instanceof DateArchive:
                return $this->original->format(trans("date.{$this->original->type}"));
            case $this->original instanceof TermEntity:
                return $this->original->name;
            case $this->original instanceof PostEntity:
                return $this->original->title;
        }

        throw new Exception("Could not determine the label.");
    }

    /**
     * Get the URL.
     *
     * @param array|bool $parameters
     * @param bool $full
     * @return string
     *
     * @throws \Exception
     */
    public function url($parameters = [], $full = false)
    {
        if (is_bool($parameters)) {
            list($parameters, $full) = [[], $parameters];
        } elseif (! is_array($parameters)) {
            $parameters = [$parameters];
        }

        switch (true) {
            case $this->original instanceof PostType:
                return posts_url($this->original, $parameters, $full);
            case $this->original instanceof DateArchive:
            case $this->original instanceof TermEntity:
                $parameters += $this->original->forUrl();
                return posts_url($this->postType, $parameters, $full);
            case $this->original instanceof PostEntity:
                return post_url($this->original, $parameters, $full);
        }

        throw new Exception("Could not determine the URL.");
    }

    /**
     * ArrayAccess::offsetExists()
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return in_array($key, ['label', 'url']);
    }

    /**
     * ArrayAccess::offsetGet()
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->{$key};
    }

    /**
     * ArrayAccess::offsetSet()
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function offsetSet($key, $value)
    {
        throw new Exception(__FUNCTION__.' is not implemented.');
    }

    /**
     * ArrayAccess::offsetUnset()
     *
     * @param mixed $key
     * @return void
     *
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function offsetUnset($key)
    {
        throw new Exception(__FUNCTION__.' is not implemented.');
    }
}
