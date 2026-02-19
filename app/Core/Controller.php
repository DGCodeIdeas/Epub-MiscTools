<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller {
    protected Request $request;

    public function setRequest(Request $request): void {
        $this->request = $request;
    }

    protected function render(string $template, array $data = []): Response {
        $view = new View($template, $data);
        return new Response($view->render());
    }

    protected function json(array $data, int $status = 200): Response {
        return ApiResponse::success($data, $status);
    }
}
