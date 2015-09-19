<?php

namespace Luminous\Exceptions;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
    }

    /**
     * Render an exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        $responce = (new SymfonyExceptionHandler(env('APP_DEBUG', false)))->createResponse($e);

        if ($view = $this->getErrorView($e)) {
            $responce->setContent($view->render());
        }

        return $responce;
    }

    /**
     * Get the error view if exists.
     *
     * @param Exception $e
     * @return \Illuminate\View\View
     */
    protected function getErrorView(Exception $e)
    {
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $file = "errors.{$status}";
            if (view()->exists($file)) {
                return view($file, ['status' => $status, 'exception' => $e]);
            }
        }

        if (! env('APP_DEBUG', false)) {
            $status = 500;
            $file = "errors.{$status}";
            if (view()->exists($file)) {
                return view($file, ['status' => $status, 'exception' => $e]);
            }
        }

        return null;
    }
}
