<?php

namespace Luminous\Http\Queries;

use Illuminate\Http\Request;
use Luminous\Bridge\WP;
use Luminous\Bridge\Post\DateArchive;
use Luminous\Bridge\Exceptions\EntityNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PostsQuery extends Query
{
    /**
     * The post collection.
     *
     * @var \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected $posts;

    /**
     * The term.
     *
     * @var \Luminous\Bridge\Term\Entity|null
     */
    protected $term;

    /**
     * The date.
     *
     * @var \Luminous\Bridge\Post\DateArchive|null
     */
    protected $date;

    /**
     * {@inheritdoc}
     */
    protected $througs = ['posts', 'term', 'date'];

    /**
     * {@inheritdoc}
     */
    public function __construct(WP $wp, Request $request)
    {
        parent::__construct($wp, $request);

        $query = $this->wp->posts($this->postType);

        if ($order = $this->route('order')) {
            call_user_func_array([$query, 'orderBy'], $order);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($this->term = $this->getTerm()) {
            $query->whereTerm($this->term);
        }

        if ($this->date = $this->getDate()) {
            $query->whereDate('created_at', $this->date);
        }

        $this->posts = $query->paginate($this->route('limit') ?: 10);
        $this->page = $this->posts->currentPage();

        if ($this->posts->isEmpty() && $this->page > 1) {
            throw new NotFoundHttpException;
        }
    }

    /**
     * Get the term parameter.
     *
     * @return \Luminous\Bridge\Term\Entity|null
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function getTerm()
    {
        if (! $type = $this->route('term_type')) {
            return null;
        }

        try {
            if ($id = $this->route('term__id')) {
                return $this->wp->term((int) $id, $type);
            } elseif ($slug = $this->route('term__slug')) {
                return $this->wp->term($slug, $type);
            } elseif ($path = $this->route('term__path')) {
                return $this->wp->term($path, $type);
            } else {
                throw new NotFoundHttpException;
            }
        } catch (EntityNotFoundException $e) {
            throw new NotFoundHttpException(null, $e);
        }
    }

    /**
     * Get the date parameter.
     *
     * @return \Luminous\Bridge\Post\DateArchive|null
     */
    protected function getDate()
    {
        if (! $path = $this->route('date__path')) {
            return null;
        }

        return DateArchive::createFromPath($path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTree()
    {
        $tree = parent::getTree();

        if ($this->term) {
            if ($this->term->type->hierarchical) {
                foreach ($this->term->ancestors->reverse() as $ancestor) {
                    $tree[] = new Node($ancestor, $this->postType);
                }
            }

            $tree[] = new Node($this->term, $this->postType);
        }

        if ($this->date) {
            $tree[] = new Node($this->date, $this->postType);
        }

        return $tree;
    }

    /**
     * {@inheritdoc}
     */
    public function canonicalUrl()
    {
        return $this->posts->url($this->posts->currentPage());
    }

    /**
     * {@inheritdoc}
     */
    public function prev()
    {
        return $this->posts->previousPageUrl();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        return $this->posts->nextPageUrl();
    }
}
