<?php

namespace Luminous\Http\Controllers;

use Illuminate\Http\Request;
use Luminous\Routing\Controller as BaseController;
use Luminous\Bridge\Post\Type;
use Luminous\Bridge\Post\Entity;
use Luminous\Http\RequestTree\Generator as Tree;

class PostController extends BaseController
{
    /**
     * Handle requests to posts.
     *
     * @uses \app()
     * @uses \abort()
     * @uses \view()
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
                case isset($query['term_id']):
                    $term = $wp->term((int) $query['term_id'], $query['term_type']);
                    break;
                case isset($query['term_path']):
                    $term = $wp->term($query['term_path'], $query['term_type']);
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
     * @uses \app()
     * @uses \abort()
     * @uses \view()
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
            case isset($query['post_id']):
                $postQuery->wherePost('id', $query['post_id']);
                break;
            case isset($query['post_path']):
                $postQuery->wherePost('path', $query['post_path']);
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
        $value = [];

        foreach (['year', 'month', 'day'] as $key) {
            if (isset($query["date_{$key}"])) {
                $value[$key] = intval($query["date_{$key}"], 10);
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
