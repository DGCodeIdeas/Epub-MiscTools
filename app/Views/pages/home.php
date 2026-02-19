<?php
ob_start();
?>

<div class="text-center mb-12">
    <h1 class="text-5xl font-extrabold mb-4 bg-gradient-to-r from-pink-500 to-purple-500 bg-clip-text text-transparent">
        GlyphShifter EPUB Lab
    </h1>
    <p class="text-xl text-gray-400">Modular, API-driven EPUB manipulation toolkit.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($tools as $tool): ?>
        <div class="card p-6 hover:border-pink-500 transition-all group rounded-xl">
            <div class="text-4xl mb-4"><?= $tool->getIcon() ?></div>
            <h3 class="text-xl font-bold mb-2 group-hover:text-pink-400"><?= $tool->getName() ?></h3>
            <p class="text-gray-400 mb-6"><?= $tool->getDescription() ?></p>
            <a href="/tool/<?= $tool->getSlug() ?>" class="inline-block btn-pink font-bold py-2 px-6 rounded-lg transition-colors">
                Open Tool
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
