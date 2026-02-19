<?php

namespace App\Core;

class View {
    protected string $template;
    protected array $data;

    public function __construct(string $template, array $data = []) {
        $this->template = $template;
        $this->data = $data;
    }

    public function render(): string {
        $templatePath = __DIR__ . '/../Views/' . $this->template . '.php';

        if (!file_exists($templatePath)) {
            // Fallback for namespaced templates or absolute paths
            if (file_exists($this->template)) {
                $templatePath = $this->template;
            } else {
                throw new \Exception("View template not found: " . $this->template);
            }
        }

        extract($this->data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
