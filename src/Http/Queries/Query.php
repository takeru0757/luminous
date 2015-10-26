<?php

namespace Luminous\Http\Queries;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Luminous\Bridge\WP;

abstract class Query
{
    /**
     * The wp instance.
     *
     * @var \Luminous\Bridge\WP
     */
    protected $wp;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * The post type.
     *
     * @var \Luminous\Bridge\Post\Type
     */
    protected $postType;

    /**
     * The cached tree items.
     *
     * @var \Luminous\Http\Queries\Node[]
     */
    protected $cachedTree;

    /**
     * The atttributes allowed to access.
     *
     * @var array
     */
    protected $througs = [];

    /**
     * Dynamically retrieve the value.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($key === 'postType' || in_array($key, $this->througs)) {
            return $this->{$key};
        }
    }

    /**
     * Create a new query instance.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param \Illuminate\Http\Request $request
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function __construct(WP $wp, Request $request)
    {
        $this->wp = $wp;
        $this->request = $request;
        $this->postType = $this->wp->postType($this->route('post_type'));
    }

    /**
     * Get the parameter from the request.
     *
     * @param string $key
     * @return mixed
     */
    protected function route($key)
    {
        return $this->request->route($key);
    }

    /**
     * Get the post type.
     *
     * @return \Luminous\Bridge\Post\Type
     */
    public function postType()
    {
        return $this->postType;
    }

    /**
     * Get the tree.
     *
     * @return \Illuminate\Support\Collection|\Luminous\Http\Queries\Node[]
     */
    public function tree()
    {
        if (is_null($this->cachedTree)) {
            $this->cachedTree = $this->getTree();
        }

        return new Collection($this->cachedTree);
    }

    /**
     * Get the tree items.
     *
     * @return \Luminous\Http\Queries\Node[]
     */
    protected function getTree()
    {
        return $this->postType->hasArchive() ? [new Node($this->postType)] : [];
    }

    /**
     * Get the canonical URL.
     *
     * @return string
     */
    abstract public function canonicalUrl();

    /**
     * Get the previous URL.
     *
     * @return string
     */
    abstract public function prev();

    /**
     * Get the next URL.
     *
     * @return string
     */
    abstract public function next();
}
