<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GlyphShifter' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2d3748">
    <style>
        body { background-color: #1a202c; color: #e2e8f0; }
        .card { background-color: #2d3748; border-color: #4a5568; color: #e2e8f0; }
        .btn-pink { background-color: #ed64a6; color: white; }
        .btn-pink:hover { background-color: #d53f8c; color: white; }
    </style>
</head>
<body class="flex flex-col min-h-screen">
    <nav class="bg-gray-800 border-b border-gray-700 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="/" class="text-2xl font-bold text-pink-500">GlyphShifter</a>
            <div class="space-x-4">
                <a href="/" class="hover:text-pink-400">Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto py-8 px-4 flex-grow">
        <?= $content ?>
    </main>

    <footer class="py-6 border-t border-gray-800 text-center text-gray-500">
        <p>&copy; <?= date('Y') ?> GlyphShifter EPUB Tools</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
