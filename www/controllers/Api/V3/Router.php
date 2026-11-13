<?php

namespace Controllers\Api\V3;

use Controllers\Api\HttpException;

/**
 *  Match a request with the routes table (see Routes)
 *  Route patterns are made of literal segments and parameters:
 *  - {name}            any segment
 *  - {name:int}        digits only (Ids)
 *  - {name:a|b|c}      one of the listed values
 */
class Router
{
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     *  Return the route matching the method and path segments, with the parameters extracted from the path
     *  Throw a 404 error if no route matches the path, a 405 error if routes match the path but not the method
     */
    public function match(string $method, array $segments): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            [$routeMethod, $pattern, $handler, $action, $auth] = $route;

            $params = $this->matchPath($pattern, $segments);

            if ($params === null) {
                continue;
            }

            if ($routeMethod != $method) {
                $allowedMethods[] = $routeMethod;
                continue;
            }

            return [
                'handler' => $handler,
                'action'  => $action,
                'auth'    => $auth,
                'params'  => $params
            ];
        }

        if (!empty($allowedMethods)) {
            $allowedMethods = array_values(array_unique($allowedMethods));

            header('Allow: ' . implode(', ', $allowedMethods));

            throw new HttpException(405, 'Method ' . $method . ' is not allowed on this route (allowed: ' . implode(', ', $allowedMethods) . ')');
        }

        throw new HttpException(404, 'No matching route');
    }

    /**
     *  Return the parameters extracted from the path if it matches the pattern, null otherwise
     */
    private function matchPath(string $pattern, array $segments): array|null
    {
        $parts = array_values(array_filter(explode('/', $pattern), function ($part) {
            return $part !== '';
        }));

        if (count($parts) != count($segments)) {
            return null;
        }

        $params = [];

        foreach ($parts as $i => $part) {
            // Literal segment
            if (!preg_match('/^\{([a-z_]+)(?::(.+))?\}$/', $part, $matches)) {
                if ($part !== $segments[$i]) {
                    return null;
                }

                continue;
            }

            // Parameter segment, with an optional constraint
            $name = $matches[1];
            $constraint = $matches[2] ?? null;

            if ($constraint == 'int' and !ctype_digit($segments[$i])) {
                return null;
            }

            if ($constraint !== null and $constraint != 'int' and !in_array($segments[$i], explode('|', $constraint), true)) {
                return null;
            }

            $params[$name] = $segments[$i];
        }

        return $params;
    }
}
