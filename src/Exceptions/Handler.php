<?php

namespace Luminous\Exceptions;

use Error;
use ErrorException;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Luminous\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler implements ExceptionHandler
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * The log implementation.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $log;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        HttpExceptionInterface::class,
    ];

    /**
     * Create a new exception handler instance.
     *
     * @param \Luminous\Application $app
     * @param \Psr\Log\LoggerInterface $log
     * @return void
     */
    public function __construct(Application $app, LoggerInterface $log)
    {
        $this->app = $app;
        $this->log = $log;
    }

    /**
     * Set the error handling.
     *
     * @return void
     */
    public function register()
    {
        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });

        register_shutdown_function(function () {
            if (! is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
                $e = new FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
                $this->handleUncaughtException($e);
            }
        });
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     * @return bool
     */
    protected function isFatalError($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }

    /**
     * Handle an uncaught exception instance.
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function handleUncaughtException($exception)
    {
        if ($exception instanceof Error) {
            $exception = new FatalThrowableError($exception);
        }

        $this->report($exception);

        if ($this->app->runningInConsole()) {
            $this->renderForConsole(new ConsoleOutput, $exception);
        } else {
            $this->render($this->app['request'], $exception)->send();
        }
    }

    /**
     * Report or log an exception.
     *
     * @param \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        foreach ($this->dontReport as $type) {
            if ($exception instanceof $type) {
                return;
            }
        }

        $this->log->error((string) $exception);
    }

    /**
     * Render an exception into a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $headers = $exception->getHeaders();
        } else {
            $statusCode = 500;
            $headers = [];
        }

        if (! $content = $this->getContent($exception)) {
            $handler = new SymfonyExceptionHandler(env('APP_DEBUG', false));
            $content = $handler->createResponse($exception)->getContent();
        }

        return (new Response($content, $statusCode, $headers))->prepare($request);
    }

    /**
     * Render an exception to the console.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \Exception $exception
     * @return void
     */
    public function renderForConsole($output, Exception $exception)
    {
        (new ConsoleApplication)->renderException($exception, $output);
    }

    /**
     * Get the error view if exists.
     *
     * @param \Exception $exception
     * @return string|null
     */
    protected function getContent(Exception $exception)
    {
        $view = $this->app['view'];
        $statuses = [];

        if ($exception instanceof HttpExceptionInterface) {
            $statuses[] = $exception->getStatusCode();
        }

        if (! env('APP_DEBUG', false)) {
            $statuses[] = 500;
        }

        foreach ($statuses as $status) {
            if ($view->exists($file = "error.{$status}")) {
                return $view->make($file, compact('status', 'exception'))->render();
            }
        }

        return null;
    }
}
