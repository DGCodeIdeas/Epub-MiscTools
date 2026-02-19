<?php

namespace App\Core;

use Symfony\Component\HttpFoundation\Response;

interface ToolInterface {
    public function getName(): string;
    public function getSlug(): string;
    public function getDescription(): string;
    public function getIcon(): string;
    public function index(): Response;
    public function process(): Response;
}
