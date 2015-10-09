<?php

namespace Luminous\Bridge\Term\Query;

use WP_Query;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Luminous\Bridge\QueryBuilder;
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
     * Build the original objects.
     *
     * @uses \get_terms()
     *
     * @return array
     */
    protected function executeQuery()
    {
        $query = array_merge(
            $this->getOrderByQuery(),
            $this->getTermQuery()
        );

        return get_terms($this->type, $query);
    }
}
