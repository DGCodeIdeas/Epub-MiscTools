<?php

namespace App\Services;

use Monolog\Logger;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class EpubManager {
    protected Logger $logger;
    protected string $storagePath;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->storagePath = $_ENV['APP_STORAGE_PATH'] ?? __DIR__ . '/../../storage';
    }

    public function unzipToTemp(string $epubPath): string {
        $zip = new ZipArchive();
        if ($zip->open($epubPath) !== TRUE) {
            throw new \Exception("Failed to open EPUB file: " . $epubPath);
        }

        $tempDir = $this->storagePath . '/tmp/epub_' . uniqid();
        if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);

        $zip->extractTo($tempDir);
        $zip->close();

        $this->logger->info("Extracted EPUB to $tempDir");
        return $tempDir;
    }

    public function injectFont(string $tempEpubDir, string $fontPath): array {
        // 1. Locate OPF
        $containerXml = $tempEpubDir . '/META-INF/container.xml';
        if (!file_exists($containerXml)) {
            throw new \Exception("Invalid EPUB: META-INF/container.xml not found");
        }

        $dom = new DOMDocument();
        $dom->load($containerXml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('c', 'urn:oasis:names:tc:opendocument:xmlns:container');
        $opfPath = $xpath->query('//c:rootfile/@full-path')->item(0)->nodeValue;

        $fullOpfPath = $tempEpubDir . '/' . $opfPath;
        $opfDir = dirname($fullOpfPath);

        // 2. Copy Font
        $fontsDir = $opfDir . '/fonts';
        if (!is_dir($fontsDir)) mkdir($fontsDir, 0777, true);

        $fontFileName = basename($fontPath);
        // Remove the upload ID prefix if present from ChunkUploaderService
        if (preg_match('/^upload_[a-z0-9.]+_/', $fontFileName)) {
            $fontFileName = preg_replace('/^upload_[a-z0-9.]+_/', '', $fontFileName);
        }
        $destFontPath = $fontsDir . '/' . $fontFileName;
        copy($fontPath, $destFontPath);

        // 3. Update Manifest (OPF)
        $opfDom = new DOMDocument();
        $opfDom->load($fullOpfPath);
        $opfXpath = new DOMXPath($opfDom);
        $opfXpath->registerNamespace('opf', 'http://www.idpf.org/2007/opf');

        $manifest = $opfXpath->query('//opf:manifest')->item(0);

        $fontId = 'injected-font-' . uniqid();
        $item = $opfDom->createElement('item');
        $item->setAttribute('id', $fontId);
        $item->setAttribute('href', 'fonts/' . $fontFileName);

        $mimeType = 'font/opentype';
        $ext = strtolower(pathinfo($fontFileName, PATHINFO_EXTENSION));
        if ($ext === 'woff') $mimeType = 'font/woff';
        elseif ($ext === 'woff2') $mimeType = 'font/woff2';
        elseif ($ext === 'ttf') $mimeType = 'font/ttf';

        $item->setAttribute('media-type', $mimeType);
        $manifest->appendChild($item);
        $opfDom->save($fullOpfPath);

        // 4. Update CSS
        $cssItems = $opfXpath->query('//opf:item[@media-type="text/css"]');
        $modifiedCss = [];

        $fontFace = "@font-face {\n" .
                    "  font-family: 'InjectedFont';\n" .
                    "  src: url('../fonts/$fontFileName');\n" .
                    "}\n" .
                    "body, p, div, span, h1, h2, h3, h4, h5, h6 {\n" .
                    "  font-family: 'InjectedFont' !important;\n" .
                    "}\n";

        foreach ($cssItems as $cssItem) {
            $cssHref = $cssItem->getAttribute('href');
            $fullCssPath = $opfDir . '/' . $cssHref;

            if (file_exists($fullCssPath)) {
                $content = file_get_contents($fullCssPath);
                file_put_contents($fullCssPath, $fontFace . $content);
                $modifiedCss[] = $cssHref;
            }
        }

        return [
            'status' => 'success',
            'opf_path' => $opfPath,
            'modified_css' => $modifiedCss
        ];
    }

    public function rebuildFromTemp(string $tempEpubDir): string {
        $epubFile = $this->storagePath . '/uploads/processed_' . uniqid() . '.epub';
        $zip = new ZipArchive();

        if ($zip->open($epubFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new \Exception("Could not create ZIP archive");
        }

        // 1. mimetype (first, uncompressed)
        $zip->addFile($tempEpubDir . '/mimetype', 'mimetype');
        $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);

        // 2. recursive add
        $this->addDirToZip($tempEpubDir, $zip, $tempEpubDir);

        $zip->close();

        // Cleanup temp
        $this->recursiveDelete($tempEpubDir);

        return $epubFile;
    }

    protected function addDirToZip(string $dir, ZipArchive $zip, string $baseDir): void {
        $files = array_diff(scandir($dir), ['.', '..', 'mimetype']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            $localPath = ltrim(str_replace($baseDir, '', $path), '/');

            if (is_dir($path)) {
                $zip->addEmptyDir($localPath);
                $this->addDirToZip($path, $zip, $baseDir);
            } else {
                $zip->addFile($path, $localPath);
            }
        }
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
