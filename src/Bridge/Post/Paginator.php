<?php

namespace Luminous\Bridge\Post;

use Illuminate\Pagination\LengthAwarePaginator;

class Paginator extends LengthAwarePaginator
{
    /**
     * {@inheritdoc}
     */
    public function url($page)
    {
        return rtrim($this->path, '/').$this->buildQuery($page).$this->buildFragment();
    }

    /**
     * Build the query of a URL.
     *
     * @param int $page
     * @return string
     */
    protected function buildQuery($page)
    {
        $query = $page > 1 ? array_merge($this->query, [$this->pageName => $page]) : $this->query;

        return $query ? '?'.urldecode(http_build_query($query, null, '&')) : '';
    }
}
