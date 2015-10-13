<?php

namespace Luminous\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Handler implements ExceptionHandler
{
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
     * @param \Psr\Log\LoggerInterface $log
     * @return void
     */
    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
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
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render($request, Exception $exception)
    {
        $response = (new SymfonyExceptionHandler(env('APP_DEBUG', false)))->createResponse($exception);
        $response = new Response($response->getContent(), $response->getStatusCode(), $response->headers->all());
        $response->exception = $exception;

        return $response;
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
}
