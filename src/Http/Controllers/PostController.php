<?php

namespace Luminous\Http\Controllers;

use Illuminate\Http\Request;
use Luminous\Bridge\Post\Type;
use Luminous\Bridge\Post\Entity;
use Luminous\Http\RequestTree\Generator as Tree;
use Luminous\Routing\Controller as BaseController;

class PostController extends BaseController
{
    /**
     * Handle requests to posts.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $query
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function index(Request $request, $query)
    {
        $wp = app('wp');

        $postType = $wp->postType($query['post_type']);
        $postQuery = $wp->posts($postType);

        $tree = (new Tree())->setPostType($postType);

        if (isset($query['order'])) {
            $postQuery->orderBy($query['order']['column'], $query['order']['direction']);
        } else {
            $postQuery->orderBy('created_at', 'desc');
        }

        if ($date = $this->getDateQuery($query)) {
            $postQuery->whereDate('created_at', $date);
            $tree->setDate($date);
        }

        if (isset($query['term_type'])) {
            switch (true) {
                case isset($query['term__id']):
                    $term = $wp->term((int) $query['term__id'], $query['term_type']);
                    break;
                case isset($query['term__path']):
                    $term = $wp->term($query['term__path'], $query['term_type']);
                    break;
                case isset($query['term__slug']):
                    $term = $wp->term($query['term__slug'], $query['term_type']);
                    break;
                default:
                    abort(404);
            }

            $postQuery->whereTerm($term);
            $tree->setTerm($term);
        }

        $posts = $postQuery->paginate(isset($query['limit']) ? $query['limit'] : 10);
        $tree->setPage($page = $posts->currentPage());

        if ($posts->isEmpty() && $page > 1) {
            abort(404);
        }

        $view = view($this->getTemplateName($postType), compact('tree', 'posts'));

        return $this->createResponse($request, $view);
    }

    /**
     * Handle requests to the post.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $query
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function show(Request $request, $query)
    {
        $wp = app('wp');

        $postType = $wp->postType($query['post_type']);
        $postQuery = $wp->posts($postType);

        switch (true) {
            case isset($query['post__id']):
                $postQuery->wherePost('id', (int) $query['post__id']);
                break;
            case isset($query['post__path']):
                $postQuery->wherePost('path', $query['post__path']);
                break;
            case isset($query['post__slug']):
                $postQuery->wherePost('slug', $query['post__slug']);
                break;
            default:
                abort(404);
        }

        if (! $post = $postQuery->first()) {
            abort(404);
        }

        $tree = (new Tree())->setPostType($postType)->setPost($post);
        $view = view($this->getTemplateName($postType, $post), compact('tree', 'post'));

        return $this->createResponse($request, $view);
    }

    /**
     * Get the date parameter from the query.
     *
     * @param array $query
     * @return array
     */
    protected function getDateQuery(array $query)
    {
        if (! isset($query['archive__path'])) {
            return;
        }

        $parts = explode('/', $query['archive__path']);
        $value = [];

        foreach (['year', 'month', 'day'] as $i => $key) {
            if (! isset($parts[$i])) {
                break;
            }
            $value[$key] = intval($parts[$i], 10);
        }

        return $value;
    }

    /**
     * Determine the template name.
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
        } else {
            $files[] = $post ? 'show' : 'index';
        }

        $files[] = 'base';

        foreach ($files as $file) {
            if ($factory->exists($name = "{$postType->name}.{$file}")) {
                return $name;
            }
        }

        return 'layout';
    }
}
