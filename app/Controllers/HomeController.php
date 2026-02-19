<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ToolDiscoveryService;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller {
    protected ToolDiscoveryService $discovery;

    public function __construct(ToolDiscoveryService $discovery) {
        $this->discovery = $discovery;
    }

    public function index(): Response {
        $tools = $this->discovery->discover();
        return $this->render('pages/home', [
            'title' => 'GlyphShifter EPUB Tools',
            'tools' => $tools
        ]);
    }
}
