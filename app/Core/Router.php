<?php

namespace App\Core;

use League\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Router {
    protected Container $container;
    protected array $routes = [];

    public function __construct(Container $container) {
        $this->container = $container;
        $this->loadRoutes();
    }

    protected function loadRoutes(): void {
        // Define core routes
        $this->addRoute('GET', '/', [\App\Controllers\HomeController::class, 'index']);
        $this->addRoute('POST', '/api/upload/init', [\App\Controllers\UploadController::class, 'initialize']);
        $this->addRoute('POST', '/api/upload/chunk', [\App\Controllers\UploadController::class, 'chunk']);
        $this->addRoute('GET', '/api/download/{fileId}', [\App\Controllers\UploadController::class, 'download']);

        // Discovery of tool-specific routes
        $discovery = $this->container->get(ToolDiscoveryService::class);
        $tools = $discovery->discover();
        foreach ($tools as $tool) {
            $this->addRoute('GET', '/tool/' . $tool->getSlug(), [$tool::class, 'index']);
            $this->addRoute('POST', '/api/tools/' . $tool->getSlug() . '/process', [$tool::class, 'process']);
        }
    }

    public function addRoute(string $method, string $path, array $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }

    public function dispatch(Request $request): Response {
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $path, $params)) {
                [$controllerClass, $action] = $route['handler'];
                $controller = $this->container->has($controllerClass)
                    ? $this->container->get($controllerClass)
                    : new $controllerClass();

                if (method_exists($controller, 'setRequest')) {
                    $controller->setRequest($request);
                }

                return call_user_func_array([$controller, $action], $params);
            }
        }

        return new Response('404 - Not Found', 404);
    }

    protected function matchPath(string $routePath, string $requestPath, &$params): bool {
        $params = [];
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestPath, $matches)) {
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }
}
