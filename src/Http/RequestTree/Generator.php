<?php

namespace Luminous\Http\RequestTree;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Post\Archive;
use Luminous\Bridge\Post\Entity as PostEntity;
use Luminous\Bridge\Term\Entity as TermEntity;

class Generator
{
    /**
     * The original tree.
     *
     * @var \Illuminate\Support\Collection|Luminous\Http\RequestTree\Node[]
     */
    protected $tree;

    /**
     * The map of date types.
     *
     * @var array
     */
    protected $dateTypes = [
        'year'  => 'yearly',
        'month' => 'monthly',
        'day'   => 'daily',
    ];

    /**
     * The current post type.
     *
     * @var \Luminous\Bridge\Post\Type
     */
    public $postType;

    /**
     * The current archive.
     *
     * @var \Luminous\Bridge\Post\Archive
     */
    public $archive;

    /**
     * The current term entity.
     *
     * @var \Luminous\Bridge\Term\Entity
     */
    public $term;

    /**
     * The current post entity.
     *
     * @var \Luminous\Bridge\Post\Entity
     */
    public $post;

    /**
     * The page.
     *
     * @var int
     */
    public $page = 1;

    /**
     * Create a new tree instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->tree = new Collection();
    }

    /**
     * Add a node.
     *
     * @param string $label
     * @param string $url
     * @param mixed $original
     * @return $this
     */
    public function add($label, $url, $original = null)
    {
        $this->tree->push(new Node($label, $url, $original));

        return $this;
    }

    /**
     * Set the current page.
     *
     * @param int $page
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = (int) $page;

        return $this;
    }

    /**
     * Set the current post type.
     *
     * @uses \trans()
     * @uses \archive_url()
     *
     * @param \Luminous\Bridge\Post\Type $postType
     * @return $this
     */
    public function setPostType(PostType $postType)
    {
        $this->postType = $postType;

        if ($this->postType->hasArchive()) {
            $label = $this->postType->name === 'post' ? trans('labels.post') : $this->postType->label;
            $this->add($label, archive_url($this->postType), $this->postType);
        }

        return $this;
    }

    /**
     * Set the current date.
     *
     * @uses \trans()
     * @uses \archive_url()
     *
     * @param array $date
     * @return $this
     */
    public function setDate(array $date)
    {
        if (is_null($this->postType) || ! $this->postType->hasArchive()) {
            return $this;
        }

        $type = array_reduce(array_keys($this->dateTypes), function ($type, $key) use ($date) {
            if (isset($date[$key])) {
                $type = $this->dateTypes[$key];
            }
            return $type;
        }, null);

        if ($type) {
            $this->archive = new Archive($type, $this->createDate($date));
            $label = $this->archive->format(trans("labels.archive.{$this->archive->type}"));
            $this->add($label, archive_url($this->postType, ['date' => $this->archive]), $this->archive);
        }

        return $this;
    }

    /**
     * Create a new DateTime from array.
     *
     * @uses \app()
     *
     * @param array $date
     * @return \Carbon\Carbon
     */
    protected function createDate(array $date)
    {
        $date = array_merge(['year' => 1900, 'month' => 1, 'day' => 1], array_map('intval', $date));
        $date = Carbon::createFromDate($date['year'], $date['month'], $date['day'], app('wp')->timezone());

        return $date->startOfDay();
    }

    /**
     * Set the current term entity.
     *
     * @uses \term_url()
     *
     * @param \Luminous\Bridge\Term\Entity $term
     * @return $this
     */
    public function setTerm(TermEntity $term)
    {
        $this->term = $term;

        foreach ($this->term->ancestors->reverse() as $ancestor) {
            $this->add($ancestor->name, term_url($ancestor), $ancestor);
        }

        $this->add($this->term->name, term_url($this->term), $this->term);

        return $this;
    }

    /**
     * Set the current post entity.
     *
     * @uses \post_url()
     *
     * @param \Luminous\Bridge\Post\Entity $post
     * @return $this
     */
    public function setPost(PostEntity $post)
    {
        $this->post = $post;

        foreach ($this->post->ancestors->reverse() as $ancestor) {
            $this->add($ancestor->title, post_url($ancestor), $ancestor);
        }

        $this->add($this->post->title, post_url($this->post), $this->post);

        return $this;
    }

    /**
     * Get the tree.
     *
     * @uses \trans()
     *
     * @return \Illuminate\Support\Collection|Luminous\Http\RequestTree\Node[]
     */
    public function all()
    {
        $tree = new Collection($this->tree->all());

        if ($this->page > 1) {
            $tree->push(new Node(trans('labels.page', ['page' => $this->page]), null));
        }

        return $tree;
    }

    /**
     * Determine if the tree is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return $this->tree->isEmpty();
    }
}
