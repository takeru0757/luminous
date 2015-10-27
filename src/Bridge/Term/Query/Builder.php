<?php

namespace Luminous\Bridge\Term\Query;

use WP_Query;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\QueryBuilder;
use Luminous\Bridge\WP;
use Luminous\Bridge\Term\Builder as TermBuilder;
use Luminous\Bridge\Term\Type;
use Luminous\Bridge\Term\Query\Parameters\OrderByParameter;
use Luminous\Bridge\Term\Query\Parameters\TermParameter;

class Builder extends QueryBuilder
{
    use OrderByParameter;
    use TermParameter;

    /**
     * The term type for the query.
     *
     * @var string|string[]
     */
    protected $type;

    /**
     * Create a new term query builder instance.
     *
     * @param \Luminous\Bridge\Term\Builder $entityBuilder
     * @return void
     */
    public function __construct(TermBuilder $entityBuilder)
    {
        parent::__construct($entityBuilder);
    }

    /**
     * Set the term type of the query.
     *
     * @param \Luminous\Bridge\Term\Type|string|array $value
     * @return $this
     */
    public function type($value)
    {
        if ($value instanceof Type) {
            $this->type = $value->name;
        } else {
            $this->type = $value;
        }

        return $this;
    }

    /**
     * Execute the query.
     *
     * @return \Illuminate\Support\Collection|\Luminous\Bridge\Term\Entity[]
     */
    public function get()
    {
        return $this->entityBuilder->makeMany($this->executeQuery());
    }

    /**
     * Implementation for Countable.
     *
     * @return int
     */
    public function count()
    {
        $args = $this->buildArgs();
        $args['fields'] = 'count';

        return (int) ($this->executeQuery($args) ?: 0);
    }

    /**
     * Execute the query.
     *
     * @uses \get_terms()
     * @uses \is_wp_error()
     *
     * @param array $args
     * @return array
     */
    protected function executeQuery(array $args = [])
    {
        $result = get_terms($this->type, $args ?: $this->buildArgs());

        return ! is_wp_error($result) ? $result : [];
    }

    /**
     * Build the argumants for `get_terms()`.
     *
     * @return array
     */
    protected function buildArgs()
    {
        $query = [
            'number' => $this->limit ?: null,
            'offset' => $this->offset,
        ];

        return array_merge(
            $query,
            $this->getOrderByQuery(),
            $this->getTermQuery()
        );
    }
}
