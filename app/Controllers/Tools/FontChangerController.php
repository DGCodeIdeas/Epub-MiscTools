<?php

namespace App\Controllers\Tools;

use App\Core\Controller;
use App\Core\ToolInterface;
use App\Core\ApiResponse;
use App\Services\ChunkUploaderService;
use App\Services\EpubManager;
use Symfony\Component\HttpFoundation\Response;

class FontChangerController extends Controller implements ToolInterface {
    protected ChunkUploaderService $uploader;
    protected EpubManager $epubManager;

    public function __construct(ChunkUploaderService $uploader, EpubManager $epubManager) {
        $this->uploader = $uploader;
        $this->epubManager = $epubManager;
    }

    public function getName(): string {
        return 'EPUB Font Changer';
    }

    public function getSlug(): string {
        return 'font-changer';
    }

    public function getDescription(): string {
        return 'Embeds a user-provided font into an EPUB file, updates the CSS, and repackages it.';
    }

    public function getIcon(): string {
        return '🔤';
    }

    public function index(): Response {
        return $this->render('tools/font-changer', [
            'tool' => $this,
            'title' => $this->getName()
        ]);
    }

    public function process(): Response {
        $data = json_decode($this->request->getContent(), true);
        $epubUploadId = $data['epubUploadId'] ?? null;
        $fontUploadId = $data['fontUploadId'] ?? null;

        if (!$epubUploadId || !$fontUploadId) {
            return ApiResponse::error('EPUB and Font upload IDs are required', 400);
        }

        $epubPath = $this->uploader->getFilePath($epubUploadId);
        $fontPath = $this->uploader->getFilePath($fontUploadId);

        if (!$epubPath || !$fontPath) {
            return ApiResponse::error('Uploaded files not found', 404);
        }

        try {
            $tempDir = $this->epubManager->unzipToTemp($epubPath);
            $this->epubManager->injectFont($tempDir, $fontPath);
            $processedFile = $this->epubManager->rebuildFromTemp($tempDir);

            $fileId = str_replace('.epub', '', basename($processedFile));
            $fileId = str_replace('processed_', '', $fileId);

            return ApiResponse::success([
                'status' => 'success',
                'downloadUrl' => '/api/download/' . $fileId,
                'fileName' => basename($processedFile)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error('EPUB processing failed: ' . $e->getMessage(), 500);
        }
    }
}
