<?php

namespace App\Services;

use Monolog\Logger;

class ChunkUploaderService {
    protected Logger $logger;
    protected string $storagePath;
    protected string $chunksDir;
    protected string $uploadsDir;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->storagePath = $_ENV['APP_STORAGE_PATH'] ?? __DIR__ . '/../../storage';
        $this->chunksDir = $this->storagePath . '/' . ($_ENV['CHUNK_UPLOAD_DIR'] ?? 'chunks');
        $this->uploadsDir = $this->storagePath . '/' . ($_ENV['FINAL_UPLOAD_DIR'] ?? 'uploads');

        if (!is_dir($this->chunksDir)) mkdir($this->chunksDir, 0777, true);
        if (!is_dir($this->uploadsDir)) mkdir($this->uploadsDir, 0777, true);
    }

    public function initializeUpload(string $fileName): string {
        $uploadId = uniqid('upload_', true);
        $path = $this->chunksDir . '/' . $uploadId;
        mkdir($path, 0777, true);

        file_put_contents($path . '/.meta', json_encode(['fileName' => $fileName]));

        $this->logger->info("Initialized upload: $uploadId for $fileName");
        return $uploadId;
    }

    public function handleChunk(string $uploadId, int $chunkIndex, string $chunkPath, int $totalChunks): array {
        $path = $this->chunksDir . '/' . $uploadId;

        if (!is_dir($path)) {
            throw new \Exception("Invalid upload ID");
        }

        $dest = $path . '/chunk_' . $chunkIndex;
        move_uploaded_file($chunkPath, $dest);

        $files = glob($path . '/chunk_*');
        if (count($files) === $totalChunks) {
            $finalPath = $this->reassembleFile($uploadId, $totalChunks);
            return [
                'status' => 'complete',
                'file_path' => $finalPath,
                'file_id' => $uploadId
            ];
        }

        return [
            'status' => 'chunk_received',
            'is_complete' => false
        ];
    }

    private function reassembleFile(string $uploadId, int $totalChunks): string {
        $path = $this->chunksDir . '/' . $uploadId;
        $meta = json_decode(file_get_contents($path . '/.meta'), true);
        $fileName = $meta['fileName'];

        $finalPath = $this->uploadsDir . '/' . $uploadId . '_' . $fileName;
        $out = fopen($finalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $path . '/chunk_' . $i;
            $in = fopen($chunkFile, 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        // Cleanup chunks
        $this->recursiveDelete($path);

        $this->logger->info("Reassembled file: $finalPath");
        return $finalPath;
    }

    public function getFilePath(string $fileId): ?string {
        $files = glob($this->uploadsDir . '/' . $fileId . '_*');
        return $files[0] ?? null;
    }

    protected function recursiveDelete(string $dir): void {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
