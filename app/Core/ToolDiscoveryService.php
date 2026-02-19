<?php

namespace App\Core;

use League\Container\Container;

class ToolDiscoveryService {
    protected Container $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    /**
     * @return ToolInterface[]
     */
    public function discover(): array {
        $tools = [];
        $toolsDir = __DIR__ . '/../Controllers/Tools';

        if (!is_dir($toolsDir)) {
            return [];
        }

        $files = scandir($toolsDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            if (str_ends_with($file, 'Controller.php')) {
                $className = 'App\\Controllers\\Tools\\' . str_replace('.php', '', $file);

                if (class_exists($className)) {
                    $tool = $this->container->get($className);

                    if ($tool instanceof ToolInterface) {
                        $tools[] = $tool;
                    }
                }
            }
        }

        return $tools;
    }
}
