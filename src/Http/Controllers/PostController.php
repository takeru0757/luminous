<?php

namespace Luminous\Http\Controllers;

use Illuminate\Http\Request;
use Luminous\Routing\Controller as BaseController;
use Luminous\Bridge\WP;
use Luminous\Bridge\Post\Type;
use Luminous\Bridge\Post\Entity;
use Luminous\Http\RequestTree\Generator as Tree;

class PostController extends BaseController
{
    /**
     * Handle requests for archive.
     *
     * @uses \abort()
     * @uses \view()
     *
     * @param \Illuminate\Http\Request $request
     * @param \Luminous\Bridge\WP $wp
     * @param array $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function archive(Request $request, WP $wp, $query)
    {
        $postType = $wp->postType($query['postType']);
        $postQuery = $wp->posts($postType);

        $tree = (new Tree())->setPostType($postType);
        $tree->setPage($page = $request->query('page', 1));

        if (isset($query['order'])) {
            $postQuery->orderBy($query['order']['column'], $query['order']['direction']);
        } else {
            $postQuery->orderBy('created_at', 'desc');
        }

        if ($date = $this->getDateQuery($query)) {
            $postQuery->whereDateAt('created_at', $date);
            $tree->setDate($date);
        }

        if (isset($query['termType'], $query['term'])) {
            $postQuery->whereTerm($term = $wp->term($query['term'], $query['termType']));
            $tree->setTerm($term);
        }

        $posts = $postQuery->paginate(isset($query['limit']) ? $query['limit'] : 10);

        if ($posts->isEmpty() && $page > 1) {
            abort(404);
        }

        return $this->createResponse(
            view($this->getTemplateName($postType), compact('tree', 'posts')),
            ($first = $posts->first()) ? $first->modified_at : null
        );
    }

    /**
     * Handle requests for the post.
     *
     * @uses \abort()
     * @uses \view()
     *
     * @param \Luminous\Bridge\WP $wp
     * @param array $query
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post(WP $wp, $query)
    {
        $postType = $wp->postType($query['postType']);
        $postQuery = $wp->posts($postType);

        $tree = (new Tree())->setPostType($postType);

        foreach (['id', 'path'] as $key) {
            if (isset($query[$key])) {
                $postQuery->wherePost($key, $query[$key]);
                break;
            }
        }

        if (! $post = $postQuery->first()) {
            abort(404);
        }

        $tree->setPost($post);

        return $this->createResponse(
            view($this->getTemplateName($postType, $post), compact('tree', 'post')),
            $post->modified_at
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
     * @uses \view()
     *
     * @param \Luminous\Bridge\Post\Type $postType
     * @param \Luminous\Bridge\Post\Entity $post
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
}
