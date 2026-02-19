<?php

namespace App\Core;

use League\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Kernel {
    protected Container $container;
    protected Router $router;
    protected Logger $logger;

    public function __construct() {
        $this->bootstrap();
    }

    protected function bootstrap(): void {
        // Load Environment Variables
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        }

        // Initialize Logger
        $this->logger = new Logger('glyphshifter');
        $logPath = ($_ENV['APP_STORAGE_PATH'] ?? __DIR__ . '/../../storage') . '/logs/app.log';
        $this->logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

        // Initialize Container
        $this->container = new Container();
        $this->container->delegate(new \League\Container\ReflectionContainer());
        $this->container->add(Logger::class, $this->logger);

        // Register Services
        $this->registerServices();

        // Initialize Router
        $this->router = new Router($this->container);
    }

    protected function registerServices(): void {
        // Register services in the container
        $this->container->add(\App\Services\ChunkUploaderService::class)
            ->addArgument(Logger::class);
        $this->container->add(\App\Services\EpubManager::class)
            ->addArgument(Logger::class);
        $this->container->add(ToolDiscoveryService::class)
            ->addArgument($this->container);
    }

    public function handle(Request $request): Response {
        try {
            return $this->router->dispatch($request);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            if (str_starts_with($request->getPathInfo(), '/api')) {
                return ApiResponse::error('Internal Server Error: ' . $e->getMessage(), 500);
            }

            return new Response('500 - Internal Server Error', 500);
        }
    }
}
