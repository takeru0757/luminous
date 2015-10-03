<?php

namespace Luminous\Bridge\Post\Query;

use WP_Query;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Luminous\Bridge\QueryBuilder;
use Luminous\Bridge\Post\Archive;
use Luminous\Bridge\Post\Builder as PostBuilder;
use Luminous\Bridge\Post\Query\Parameters\DateParameter;
use Luminous\Bridge\Post\Query\Parameters\OrderByParameter;
use Luminous\Bridge\Post\Query\Parameters\PostParameter;
use Luminous\Bridge\Post\Query\Parameters\TermParameter;

class Builder extends QueryBuilder
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
     * Execute the query.
     *
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Post\Entity[]
     */
    public function get()
    {
        return $this->retrievePosts($this->buildQuery());
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

        $query = $this->forPage($page, $perPage)->buildQuery();
        $posts = $this->retrievePosts($query);
        $total = $this->retrieveTotal($query);

        return new LengthAwarePaginator($posts, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the archives.
     *
     * @link https://developer.wordpress.org/reference/functions/wp_get_archives/
     *
     * @uses \add_filter()
     * @uses \remove_filter()
     *
     * @param string $type
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Post\Archive[]
     */
    public function archives($type)
    {
        $groupbyFilter = function () use ($type) {
            $formats = [
                'yearly'    => 'YEAR(`%1$s`)',
                'monthly'   => 'YEAR(`%1$s`), MONTH(`%1$s`)',
                'daily'     => 'YEAR(`%1$s`), MONTH(`%1$s`), DAYOFMONTH(`%1$s`)',
            ];
            return sprintf($formats[$type], 'post_date');
        };

        $fieldsFilter = function () use ($type) {
            $formats = [
                'yearly'    => 'DATE_FORMAT(`%1$s`, \'%%Y-01-01\') as `_date`, count(`%2$s`) as `_count`',
                'monthly'   => 'DATE_FORMAT(`%1$s`, \'%%Y-%%m-01\') as `_date`, count(`%2$s`) as `_count`',
                'daily'     => 'DATE_FORMAT(`%1$s`, \'%%Y-%%m-%%d\') as `_date`, count(`%2$s`) as `_count`',
            ];
            return sprintf($formats[$type], 'post_date', 'ID');
        };

        add_filter('posts_groupby', $groupbyFilter);
        add_filter('posts_fields', $fieldsFilter);

        $query = $this->orderBy('created_at', 'desc')->buildQuery();

        remove_filter('posts_groupby', $groupbyFilter);
        remove_filter('posts_fields', $fieldsFilter);

        return new Collection(array_map(function ($object) use ($type) {
            $date = Carbon::createFromFormat('Y-m-d', $object->_date, $this->postBuilder->timezone());
            return new Archive($type, $date->startOfDay(), (int) $object->_count);
        }, $query->posts));
    }

    /**
     * Retrieve posts from WP_Query.
     *
     * @param \WP_Query $query
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Post\Entity[]
     */
    protected function retrievePosts(WP_Query $query)
    {
        return $this->postBuilder->makeMany($query->posts);
    }

    /**
     * Retrieve number of posts from WP_Query.
     *
     * @param \WP_Query $query
     * @return int
     */
    protected function retrieveTotal(WP_Query $query)
    {
        return (int) $query->found_posts;
    }

    /**
     * Build the original query instance.
     *
     * @return \WP_Query
     */
    protected function buildQuery()
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

        return new WP_Query($query);
    }
}
