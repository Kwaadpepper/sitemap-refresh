<?php

namespace Kwaadpepper\SitemapRefresh\Lib;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapException;
use Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Utils
{
    /**
     * Get url mimetype
     *
     * @param string $url
     * @return string
     */
    public static function getUrlMimeType(string $url): string
    {
        $options = [];
        if (\config('app.debug')) {
            $options['verify'] = false;
        }

        /** @var string */
        $headMimeType = Str::of(
            Http::withOptions($options)->head($url)
                ->header('Content-Type')
        )->explode(';')->first();

        return $headMimeType;
    }

    /**
     * Fetch the concerned model for the route
     *
     * (Should be the last Model in order from left to right)
     *
     * @param \Illuminate\Routing\Route $route
     * @return null|\Illuminate\Database\Eloquent\Model
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapException If model is not found.
     */
    public static function fetchConcernedModel(Route $route): ?Model
    {
        $routeName = $route->getName();

        if (!self::routeHasAnyModelParameter($route)) {
            return null;
        }

        try {
            /** @var \Illuminate\Routing\Router $router */
            $router = app('router');
            $router->substituteBindings($route);
            $router->substituteImplicitBindings($route);

            return \collect($route->parameters())->last();
        } catch (NotFoundHttpException $e) {
            throw new SitemapException("Not found for route {$routeName}" . $e->getMessage());
        } //end try
    }

    /**
     * Resolve url to route
     *
     * @param string $url
     *
     * @return \Illuminate\Routing\Route
     * @throws \Kwaadpepper\SitemapRefresh\Exceptions\SitemapResolveUrlException If the method of the route
     *                                                                     is not GET or not found.
     */
    public static function resolveUrl(string $url): Route
    {
        // * Create request
        $request = Request::create($url);

        try {
            /** @var \Illuminate\Routing\Router $router */
            $router = app('router');

            return $router->getRoutes()->match($request);
        } catch (NotFoundHttpException $e) {
            throw new SitemapResolveUrlException("Not found {$url}" . $e->getMessage());
        } catch (MethodNotAllowedHttpException $e) {
            throw new SitemapResolveUrlException("Only GET is supported {$url}" . $e->getMessage());
        }
    }

    /**
     * Check if a route is in a pattern list
     *
     * @param \Illuminate\Routing\Route $route
     * @param string[]                  $routeNameList
     * @return boolean
     */
    public static function routeIsInList(Route $route, array $routeNameList): bool
    {
        return $route->named(...\collect($routeNameList)->sortDesc()->all());
    }

    /**
     * Get the last route model parameter if any
     *
     * @param \Illuminate\Routing\Route $route
     * @return boolean
     */
    private static function routeHasAnyModelParameter(Route $route): bool
    {
        /** @var array<\ReflectionParameter> */
        $signatureParameters = $route->signatureParameters();

        return \collect($signatureParameters)
            ->filter(function (\ReflectionParameter $param) {
                $reflexionType = $param->getType();
                if (!($reflexionType instanceof \ReflectionNamedType)) {
                    return false;
                }
                return !$reflexionType->isBuiltin() and
                    in_array(Model::class, \class_parents($reflexionType->getName()));
            })->count() !== 0;
    }
}
