<?php
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <nav aria-label="breadcrumb" class="mb-6">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/" class="text-pink-400">Dashboard</a></li>
            <li class="breadcrumb-item active text-gray-400" aria-current="page"><?= $tool->getName() ?></li>
        </ol>
    </nav>

    <div class="card p-8 rounded-2xl shadow-2xl">
        <div class="flex items-center mb-8">
            <div class="text-5xl mr-4"><?= $tool->getIcon() ?></div>
            <div>
                <h1 class="text-3xl font-bold"><?= $tool->getName() ?></h1>
                <p class="text-gray-400"><?= $tool->getDescription() ?></p>
            </div>
        </div>

        <div id="upload-container" class="space-y-8">
            <div class="upload-section">
                <label class="block text-sm font-medium mb-2">1. Select EPUB File</label>
                <div id="epub-dropzone" class="border-2 border-dashed border-gray-600 rounded-xl p-8 text-center hover:border-pink-500 transition-colors cursor-pointer">
                    <p id="epub-name">Drag & drop EPUB file here or click to browse</p>
                    <input type="file" id="epub-input" class="hidden" accept=".epub">
                    <div id="epub-progress" class="hidden mt-4">
                        <div class="progress bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="progress-bar bg-pink-500 w-0 h-full transition-all"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="upload-section">
                <label class="block text-sm font-medium mb-2">2. Select Font File (.ttf, .otf, .woff, .woff2)</label>
                <div id="font-dropzone" class="border-2 border-dashed border-gray-600 rounded-xl p-8 text-center hover:border-pink-500 transition-colors cursor-pointer">
                    <p id="font-name">Drag & drop font file here or click to browse</p>
                    <input type="file" id="font-input" class="hidden" accept=".ttf,.otf,.woff,.woff2">
                    <div id="font-progress" class="hidden mt-4">
                        <div class="progress bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="progress-bar bg-pink-500 w-0 h-full transition-all"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center pt-4">
                <button id="process-btn" class="btn-pink px-12 py-3 rounded-full font-bold text-lg disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Process EPUB
                </button>
            </div>
        </div>

        <div id="result-container" class="hidden text-center py-12">
            <div class="text-6xl mb-6">🎉</div>
            <h2 class="text-2xl font-bold mb-4">Processing Complete!</h2>
            <p class="text-gray-400 mb-8">Your modified EPUB is ready for download.</p>
            <a id="download-link" href="#" class="btn-pink px-12 py-3 rounded-full font-bold text-lg">
                Download EPUB
            </a>
            <div class="mt-8">
                <button onclick="location.reload()" class="text-pink-400 hover:underline">Start Over</button>
            </div>
        </div>
    </div>
</div>

<script>
    // These will be used by app.js
    const TOOL_SLUG = '<?= $tool->getSlug() ?>';
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
