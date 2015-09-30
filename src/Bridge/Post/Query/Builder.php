<?php

namespace Luminous\Bridge\Post\Query;

use WP_Query;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Luminous\Bridge\Post\Builder as PostBuilder;
use Luminous\Bridge\Post\Query\Parameters\DateParameter;
use Luminous\Bridge\Post\Query\Parameters\OrderByParameter;
use Luminous\Bridge\Post\Query\Parameters\PostParameter;
use Luminous\Bridge\Post\Query\Parameters\TermParameter;

class Builder
{
    use DateParameter;
    use OrderByParameter;
    use PostParameter;
    use TermParameter;

    /**
     * The post builder instance.
     *
     * @var \Luminous\Bridge\Post\Builder
     */
    protected $postBuilder;

    /**
     * The maximum number of records to return.
     *
     * @var int|null
     */
    public $limit;

    /**
     * The number of records to skip.
     *
     * @var int
     */
    public $offset = 0;

    /**
     * Create a new post query instance.
     *
     * @param \Luminous\Bridge\Post\Builder $builder
     * @return void
     */
    public function __construct(PostBuilder $postBuilder)
    {
        $this->postBuilder = $postBuilder;
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param int $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Alias to set the "offset" value of the query.
     *
     * @param int $value
     * @return \Luminous\Bridge\Post\Query\Builder|static
     */
    public function skip($value)
    {
        return $this->offset($value);
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param int|null $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value > 0) {
            $this->limit = $value;
        } else {
            $this->limit = null;
        }

        return $this;
    }

    /**
     * Alias to set the "limit" value of the query.
     *
     * @param int $value
     * @return \Luminous\Bridge\Post\Query\Builder|static
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param int $page
     * @param int $perPage
     * @return \Luminous\Bridge\Post\Query\Builder|static
     */
    public function forPage($page, $perPage = 10)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Support\Collection
     */
    public function get()
    {
        $result = $this->executeQuery();
        return $result['items'];
    }

    /**
     * Execute the query and get the first result.
     *
     * @return \Luminous\Bridge\Post\Entity|null
     */
    public function first()
    {
        return $this->take(1)->get()->first();
    }

    /**
     * Paginate the given query.
     *
     * @param int $perPage
     * @param int $page
     * @param string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $page = null, $pageName = 'page')
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $result = $this->forPage($page, $perPage)->executeQuery();

        return new LengthAwarePaginator($result['items'], $result['total'], $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Execute the query.
     *
     * @return array
     */
    protected function executeQuery()
    {
        $query = [
            'posts_per_page' => $this->limit ?: -1,
            'offset' => $this->offset,
        ];

        $query = array_merge(
            $query,
            $this->getDateQuery(),
            $this->getOrderByQuery(),
            $this->getPostQuery(),
            $this->getTermQuery()
        );

        $result = new WP_Query($query);

        return [
            'total' => intval($result->found_posts),
            'items' => $this->postBuilder->makeMany($result->get_posts()),
        ];
    }
}
