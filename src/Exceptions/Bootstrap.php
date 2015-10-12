<?php

namespace Luminous\Exceptions;

use Error;
use ErrorException;
use Luminous\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Debug\Exception\FatalErrorException;
use Symfony\Component\Debug\Exception\FatalThrowableError;

class Bootstrap
{
    /**
     * The application instance.
     *
     * @var \Luminous\Application
     */
    protected $app;

    /**
     * Set the error handling for the application.
     *
     * @param \Luminous\Application $app
     * @return void
     */
    public function register(Application $app)
    {
        $this->app = $app;

        error_reporting(-1);

        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function ($e) {
            $this->handleException($e);
        });

        register_shutdown_function(function () {
            if (! is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
                $e = new FatalErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
                $this->handleException($e);
            }
        });
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * @param \Throwable $exception
     * @return void
     */
    protected function handleException($exception)
    {
        if ($exception instanceof Error) {
            $exception = new FatalThrowableError($exception);
        }

        $handler = $this->app->make('Illuminate\Contracts\Debug\ExceptionHandler');
        $handler->report($exception);

        if ($this->app->runningInConsole()) {
            $handler->renderForConsole(new ConsoleOutput, $exception);
        } else {
            $handler->render($this->app->make('request'), $exception)->send();
        }
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type
     * @return bool
     */
    protected function isFatal($type)
    {
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];

        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }

        return in_array($type, $errorCodes);
    }
}
