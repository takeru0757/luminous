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
     * @return \Illuminate\View\View
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function index(Request $request)
    {
        $wp = app('wp');

        $postType = $wp->postType($request->route('post_type'));
        $postQuery = $wp->posts($postType);

        $tree = (new Tree())->setPostType($postType);

        if ($order = $request->route('order')) {
            $postQuery->orderBy($order['column'], $order['direction']);
        } else {
            $postQuery->orderBy('created_at', 'desc');
        }

        if ($date = $this->getDateQuery($request)) {
            $postQuery->whereDate('created_at', $date);
            $tree->setDate($date);
        }

        if ($termType = $request->route('term_type')) {
            if ($termId = $request->route('term__id')) {
                $term = $wp->term((int) $termId, $termType);
            } elseif ($termSlug = $request->route('term__slug')) {
                $term = $wp->term($termSlug, $termType);
            } elseif ($termPath = $request->route('term__path')) {
                $term = $wp->term($termPath, $termType);
            } else {
                abort(404);
            }

            $postQuery->whereTerm($term);
            $tree->setTerm($term);
        }

        $posts = $postQuery->paginate($request->route('limit') ?: 10);
        $tree->setPage($page = $posts->currentPage());

        if ($posts->isEmpty() && $page > 1) {
            abort(404);
        }

        return view($this->getTemplateName($postType), compact('tree', 'posts'));
    }

    /**
     * Handle requests to the post.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function show(Request $request)
    {
        $wp = app('wp');

        $postType = $wp->postType($request->route('post_type'));
        $postQuery = $wp->posts($postType);

        if ($postId = $request->route('post__id')) {
            $postQuery->wherePost('id', (int) $postId);
        } elseif ($postSlug = $request->route('post__slug')) {
            $postQuery->wherePost('slug', $postSlug);
        } elseif ($postPath = $request->route('post__path')) {
            $postQuery->wherePost('path', $postPath);
        } else {
            abort(404);
        }

        if (! $post = $postQuery->first()) {
            abort(404);
        }

        $tree = (new Tree())->setPostType($postType)->setPost($post);

        return view($this->getTemplateName($postType, $post), compact('tree', 'post'));
    }

    /**
     * Get the date parameter from the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|null
     */
    protected function getDateQuery(Request $request)
    {
        if (! $parameter = $request->route('archive__path')) {
            return null;
        }

        $parts = explode('/', $parameter);
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
