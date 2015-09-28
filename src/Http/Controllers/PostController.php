<?php

namespace Luminous\Http\Controllers;

use DateTime;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Laravel\Lumen\Routing\Controller as BaseController;
use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Type;
use Luminous\Bridge\Post\Entities\Entity;

class PostController extends BaseController
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

        if (isset($query['termType'], $query['term'])) {
            $term = $wp->term($query['term'], $query['termType']);
            $postQuery->whereTerm($term);
        } else {
            $term = null;
        }

        if ($date = $this->getDateQuery($query)) {
            $postQuery->whereDateAt('created_at', $date);
        }

        $posts = $postQuery->paginate(isset($query['limit']) ? $query['limit'] : 10);

        if ($posts->isEmpty()) {
            abort(404);
        }

        return $this->createResponse(
            view($this->getTemplateName($postType), compact('posts', 'term', 'date')),
            $posts->first()->updated_at
        );
    }

    /**
     * Handle requests for the post.
     *
     * @param \Luminous\Bridge\WP $wp
     * @param array $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post(WP $wp, $query)
    {
        $postType = $wp->postType($query['postType']);
        $postQuery = $wp->posts($postType);

        foreach (['id', 'path'] as $key) {
            if (isset($query[$key])) {
                $postQuery->wherePost($key, $query[$key]);
                break;
            }
        }

        $post = $postQuery->first();

        if (! $post) {
            abort(404);
        }

        return $this->createResponse(
            view($this->getTemplateName($postType, $post), compact('post')),
            $post->updated_at
        );
    }

    /**
     * Get the date parameter from the query.
     *
     * @param array $query
     * @return array
     */
    protected function getDateQuery(array $query)
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
     * @param \Luminous\Bridge\Post\Entities\Entity $post
     * @return string
     */
    protected function getTemplateName(Type $postType, Entity $post = null)
    {
        $factory = view();
        $files = [];

        if ($post && $postType->hierarchical) {
            $files[] = implode('.', $paths = explode('/', $post->path)).'.index';
            while ($paths) {
                $file = implode('.', $paths);
                array_push($files, "{$file}.base", $file);
                array_pop($paths);
            }
        }

        $files[] = $post ? 'post' : 'archive';
        $files[] = 'base';

        foreach ($files as $file) {
            if ($factory->exists($name = "{$postType->name}.{$file}")) {
                return $name;
            }
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
     * @return int 600 or 0 (debug)
     */
    protected function expires()
    {
        return env('APP_DEBUG', false) ? 0 : 600;
    }
}
