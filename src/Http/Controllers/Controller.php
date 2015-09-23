<?php

namespace Luminous\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Type;

class Controller extends BaseController
{
    /**
     * Handle requests for home.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function home()
    {
        return $this->createResponse(view('home'));
    }

    /**
     * Handle requests for archive.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param array $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function archive(WP $wp, $query)
    {
        $postType = $wp->postType($query['postType']);
        $postQuery = $wp->posts($postType);

        if (isset($query['order'])) {
            $postQuery->orderBy($query['order']['column'], $query['order']['direction']);
        } else {
            $postQuery->orderBy('created_at', 'desc');
        }

        if ($date = $this->getDate($query)) {
            $postQuery->whereDateAt('created_at', $date);
        }

        if ($term = $this->getTermQuery($query)) {
            $postQuery->whereTerm($term['column'], $term['operator'], $term['value']);
        }

        $posts = $postQuery->paginate(isset($query['limit']) ? $query['limit'] : 10);

        if ($posts->isEmpty()) {
            abort(404);
        }

        return $this->createResponse(
            view($this->getTemplateName($postType), compact('posts')),
            $posts->first()->updated_at
        );
    }

    /**
     * Handle requests for singular.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param array $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function show(WP $wp, $query)
    {
        $postType = $wp->postType($query['postType']);
        $postQuery = $wp->posts($postType);

        $path = null;

        foreach (['id', 'slug', 'path'] as $key) {
            if (isset($query[$key])) {
                $postQuery->wherePost($key, $query[$key]);
                $path = $query[$key];
                break;
            }
        }

        $post = $postQuery->first();

        if (! $post) {
            abort(404);
        }

        return $this->createResponse(
            view($this->getTemplateName($postType, $path), compact('post')),
            $post->updated_at
        );
    }

    /**
     * Get the term parameter from the query.
     *
     * @param array $query
     * @return array
     */
    protected function getTermQuery(array $query)
    {
        if (!isset($query['termType'])) {
            return [];
        }

        $value = isset($query['path']) ? $query['path'] : $query['slug'];
        $operator = 'in';

        if (strpos($value, '/') !== false) {
            $paths = explode('/', $value);
            $value = end($paths);
        } elseif (strpos($value, ',') !== false) {
            $value = explode(',', $value);
            $operator = 'in';
        } elseif (strpos($value, '+') !== false) {
            $value = explode('+', $value);
            $operator = 'and';
        }

        return array_merge(
            ['column' => $query['termType']],
            compact('operator', 'value')
        );
    }

    /**
     * Get the date parameter from the query.
     *
     * @param array $query
     * @return array
     */
    protected function getDate(array $query)
    {
        $value = [];

        foreach (['year', 'month', 'week', 'day', 'hour', 'minute', 'second'] as $key) {
            if (isset($query[$key])) {
                $value[$key] = intval($query[$key], 10);
            }
        }

        return $value;
    }

    /**
     * Determine the template name.
     *
     * @param \Luminous\Bridge\Post\Type $postType
     * @param array $query
     * @return string
     */
    protected function getTemplateName(Type $postType, $path = null)
    {
        $parts = [];

        if ($postType->hierarchical) {
            $parts = array_merge($parts, array_filter(explode('/', $path), 'strlen'));
        } else {
            $parts[] = $path === null ? 'archive' : 'show';
        }

        while ($parts) {
            $name = implode('.', $parts);
            $file = "{$postType->name}.{$name}";
            if (view()->exists($file)) {
                return $file;
            }
            array_pop($parts);
        }

        $base = "{$postType->name}.base";

        if (view()->exists($base)) {
            return $base;
        }

        return 'layout';
    }

    /**
     * Create the response with headers.
     *
     * @param \Illuminate\Contracts\View\View $view
     * @param \DateTime $modified
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function createResponse(View $view, DateTime $modified = null)
    {
        $response = response($view->render(), 200);
        $expires = $this->expires();

        $response->header('Cache-Control', "private,max-age={$expires}");
        $response->header('Expires', Carbon::now()->addSeconds($expires)->format(DateTime::RFC1123));

        if ($modified) {
            $response->header('Last-Modified', $modified->format(DateTime::RFC1123));
        }

        return $response;
    }

    /**
     * Get expires in seconds.
     *
     * @return int 600
     */
    protected function expires()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
