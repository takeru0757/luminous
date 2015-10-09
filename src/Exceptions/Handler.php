<?php

namespace Luminous\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception Handler Class
 *
 * This class is based on Laravel Lumen:
 *
 * - Copyright (c) Taylor Otwell
 * - Licensed under the MIT license
 * - {@link https://github.com/laravel/lumen-framework/blob/5.1/src/Exceptions/Handler.php}
 */
class Handler implements ExceptionHandler
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
     * @param \Exception $e
     * @return void
     */
    public function report(Exception $e)
    {
        if ($this->shouldntReport($e)) {
            return;
        }

        app('Psr\Log\LoggerInterface')->error((string) $e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @param \Exception $e
     * @return bool
     */
    public function shouldReport(Exception $e)
    {
        return ! $this->shouldntReport($e);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param \Exception $e
     * @return bool
     */
    protected function shouldntReport(Exception $e)
    {
        foreach ($this->dontReport as $type) {
            if ($e instanceof $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Render an exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return \Illuminate\Http\Response
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception  $e
     * @return void
     */
    public function renderForConsole($output, Exception $e)
    {
        (new ConsoleApplication)->renderException($e, $output);
    }

    /**
     * Get the error view if exists.
     *
     * @param \Exception $e
     * @return \Illuminate\View\View
     */
    protected function getErrorView(Exception $e)
    {
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $file = "error.{$status}";
            if (view()->exists($file)) {
                return view($file, ['status' => $status, 'exception' => $e]);
            }
        }

        if (! env('APP_DEBUG', false)) {
            $status = 500;
            $file = "error.{$status}";
            if (view()->exists($file)) {
                return view($file, ['status' => $status, 'exception' => $e]);
            }
        }

        return null;
    }
}
