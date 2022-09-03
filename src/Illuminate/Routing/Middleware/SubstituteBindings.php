<?php

namespace Illuminate\Routing\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Routing\RouteAction;
use Illuminate\Support\Str;

class SubstituteBindings
{
    /**
     * The router instance.
     *
     * @var \Illuminate\Contracts\Routing\Registrar
     */
    protected $router;

    /**
     * Create a new bindings substitutor.
     *
     * @param  \Illuminate\Contracts\Routing\Registrar  $router
     * @return void
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $this->router->substituteBindings($route = $request->route());

            $this->router->substituteImplicitBindings($route);
        } catch (ModelNotFoundException $exception) {
            $missing = $route->getMissing();

            if ($missing instanceof Closure) {
                return $missing($request, $exception);
            }

            if (! is_null($missing)) {
                if (is_string($missing)) {
                    $missing = ['uses' => $missing];
                    $missing['controller'] = $missing['uses'];
                }

                [$class, $method] = Str::parseCallback(RouteAction::parse($route->uri(), $missing)['uses']);

                return $route->controllerDispatcher()->dispatch(
                    $route, app()->make(ltrim($class, '\\')), $method,
                );
            }

            throw $exception;
        }

        return $next($request);
    }
}
