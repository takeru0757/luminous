<?php

namespace Luminous\Routing;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Luminous\Application;

class ClosureController extends Controller
{
    /**
     * The closure.
     *
     * @var \Closure
     */
    protected $closure;

    /**
     * The response builder callback.
     *
     * @var \Closure
     */
    protected static $responseBuilder;

    /**
     * The error formatter callback.
     *
     * @var \Closure
     */
    protected static $errorFormatter;

    /**
     * Set the response builder callback.
     *
     * @param \Closure $callback
     * @return void
     */
    public static function buildResponseUsing(Closure $callback)
    {
        static::$responseBuilder = $callback;
    }

    /**
     * Set the error formatter callback.
     *
     * @param \Closure $callback
     * @return void
     */
    public static function formatErrorsUsing(Closure $callback)
    {
        static::$errorFormatter = $callback;
    }

    /**
     * Create a new closure controller instance.
     *
     * @param \Closure $closure
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->closure = $closure->bindTo($this);
    }

    /**
     * The render action.
     *
     * @param \Luminous\Application $app
     * @return mixed
     */
    public function render(Application $app)
    {
        $parameters = func_get_args();
        $app = array_shift($parameters);

        return $app->call($this->closure, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if (isset(static::$responseBuilder)) {
            return call_user_func(static::$responseBuilder, $request, $errors);
        }

        return parent::buildFailedValidationResponse($request, $errors);
    }

    /**
     * {@inheritdoc}
     */
    protected function formatValidationErrors(Validator $validator)
    {
        if (isset(static::$errorFormatter)) {
            return call_user_func(static::$errorFormatter, $validator);
        }

        return parent::formatValidationErrors($validator);
    }
}
