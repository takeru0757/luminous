<?php

namespace Luminous\Http\RequestTree;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Type as PostType;
use Luminous\Bridge\Post\Entities\Entity as PostEntity;
use Luminous\Bridge\Term\Entities\Entity as TermEntity;

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
     * The current date type.
     *
     * @var string
     */
    public $dateType;

    /**
     * The current date.
     *
     * @var \Carbon\Carbon
     */
    public $date;

    /**
     * The current term entity.
     *
     * @var \Luminous\Bridge\Term\Entities\Entity
     */
    public $term;

    /**
     * The current post entity.
     *
     * @var \Luminous\Bridge\Post\Entities\Entity
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
     * @param \Luminous\Bridge\Post\Type $postType
     * @return $this
     */
    public function setPostType(PostType $postType)
    {
        $this->postType = $postType;

        if ($this->postType->hasArchive()) {
            $this->add($this->postType->label, archive_url($this->postType), $this->postType);
        }

        return $this;
    }

    /**
     * Set the current date.
     *
     * @param array $date
     * @return $this
     */
    public function setDate(array $date)
    {
        if (is_null($this->postType) || ! $this->postType->hasArchive()) {
            return $this;
        }

        $result = array_reduce(array_keys($this->dateTypes), function ($result, $key) use ($date) {
            if (isset($date[$key])) {
                $result['type'] = $this->dateTypes[$key];
                $result['params'][$key] = sprintf($key === 'year' ? '%04d' : '%02d', $date[$key]);
            }
            return $result;
        }, ['type' => null, 'params' => []]);

        if ($result['type']) {
            $this->dateType = $result['type'];
            $this->date = $this->createDate($result['params']);

            $dateUrl = archive_url($this->postType, $this->dateType, $result['params']);
            $this->add($this->date->format('F, Y'), $dateUrl, $this->date);
        }

        return $this;
    }

    /**
     * Create a new DateTime from array.
     *
     * @param array $params
     * @return \Carbon\Carbon
     */
    protected function createDate(array $params)
    {
        $params = array_merge(['year' => '0000', 'month' => '01', 'day' => '01'], $params);
        $string = implode('-', array_values($params));

        return Carbon::createFromFormat('Y-m-d', $string, WP::timezone())->startOfDay();
    }

    /**
     * Set the current term entity.
     *
     * @param \Luminous\Bridge\Term\Entities\Entity $term
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
     * @param \Luminous\Bridge\Post\Entities\Entity $post
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
     * @param bool $withPage
     * @return \Illuminate\Support\Collection|Luminous\Http\RequestTree\Node[]
     */
    public function all($withPage = true)
    {
        $tree = new Collection($this->tree->all());

        if ($withPage && $this->page > 1) {
            $tree->push(new Node(sprintf("Page: %d", $this->page), null));
        }

        return $tree;
    }

    /**
     * Get the parent nodes.
     *
     * @return \Illuminate\Support\Collection|Luminous\Http\RequestTree\Node[]
     */
    public function parents()
    {
        ($tree = $this->all()) && $tree->pop();

        return $tree;
    }

    /**
     * Get the current node.
     *
     * @return Luminous\Http\RequestTree\Node
     */
    public function active()
    {
        return $this->all()->last();
    }

    /**
     * Get the title.
     *
     * @param bool $reverse
     * @return string
     */
    public function title($sepalator = ' - ', $reverse = true)
    {
        if ($reverse) {
            $tree = $this->all()->reverse();
        } else {
            $tree = $this->all();
        }

        return $tree->implode('label', $sepalator);
    }
}
