<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\ApiResponse;
use App\Services\ChunkUploaderService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class UploadController extends Controller {
    protected ChunkUploaderService $uploader;

    public function __construct(ChunkUploaderService $uploader) {
        $this->uploader = $uploader;
    }

    public function initialize(): Response {
        $data = json_decode($this->request->getContent(), true);
        $fileName = $data['fileName'] ?? 'unknown';

        $uploadId = $this->uploader->initializeUpload($fileName);
        return ApiResponse::success(['uploadId' => $uploadId], 201);
    }

    public function chunk(): Response {
        $uploadId = $this->request->request->get('uploadId');
        $chunkIndex = (int)$this->request->request->get('chunkIndex');
        $totalChunks = (int)$this->request->request->get('totalChunks');
        $file = $this->request->files->get('chunkData');

        if (!$uploadId || !$file) {
            return ApiResponse::error('Missing fields', 400);
        }

        try {
            $result = $this->uploader->handleChunk($uploadId, $chunkIndex, $file->getPathname(), $totalChunks);
            return ApiResponse::success($result);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function download(string $fileId): Response {
        $filePath = $this->uploader->getFilePath($fileId);

        // Also check in processed uploads
        if (!$filePath) {
            $storagePath = $_ENV['APP_STORAGE_PATH'] ?? __DIR__ . '/../../storage';
            $files = glob($storagePath . '/uploads/processed_' . $fileId . '.epub');
            $filePath = $files[0] ?? null;
        }

        // If fileId is the full path (for processed files)
        if (!$filePath && file_exists($fileId)) {
            $filePath = $fileId;
        }

        if (!$filePath || !file_exists($filePath)) {
            return ApiResponse::error('File not found', 404);
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($filePath)
        );
        return $response;
    }
}
