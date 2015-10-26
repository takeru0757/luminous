<?php

namespace Luminous\Http\Queries;

use Illuminate\Http\Request;
use Luminous\Bridge\WP;
use Luminous\Bridge\Exceptions\EntityNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostQuery extends Query
{
    /**
     * The post.
     *
     * @var \Luminous\Bridge\Post\Entity
     */
    protected $post;

    /**
     * {@inheritdoc}
     */
    protected $througs = ['post'];

    /**
     * {@inheritdoc}
     */
    public function __construct(WP $wp, Request $request)
    {
        parent::__construct($wp, $request);

        try {
            if ($id = $this->route('post__id')) {
                $this->post = $this->wp->post((int) $id, $this->postType);
            } elseif ($slug = $this->route('post__slug')) {
                $this->post = $this->wp->post($slug, $this->postType);
            } elseif ($path = $this->route('post__path')) {
                $this->post = $this->wp->post($path, $this->postType);
            } else {
                throw new NotFoundHttpException;
            }
        } catch (EntityNotFoundException $e) {
            throw new NotFoundHttpException(null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getTree()
    {
        $tree = parent::getTree();

        if ($this->post->type->hierarchical) {
            foreach ($this->post->ancestors->reverse() as $ancestor) {
                $tree[] = new Node($ancestor);
            }
        }

        $tree[] = new Node($this->post);

        return $tree;
    }

    /**
     * {@inheritdoc}
     */
    public function canonicalUrl()
    {
        return post_url($this->post, true);
    }

    /**
     * {@inheritdoc}
     */
    public function prev()
    {
        if (! $this->post->type->hierarchical && $this->post->newer) {
            return post_url($this->post->newer, true);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        if (! $this->post->type->hierarchical && $this->post->older) {
            return post_url($this->post->older, true);
        }

        return null;
    }
}
