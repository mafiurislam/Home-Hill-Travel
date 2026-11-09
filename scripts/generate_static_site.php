<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\URL;
use App\Models\Category;
use App\Models\TourPackage;

$baseUrl = 'https://mafiurislam.github.io/homehill';
URL::forceRootUrl($baseUrl);
URL::forceScheme('https');
config(['app.asset_url' => $baseUrl]);

$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$outputDir = realpath(__DIR__ . '/..');

echo "Base URL: {$baseUrl}\n";
echo "Output Directory: {$outputDir}\n\n";

// Helper function to safely write a file
function savePage(string $relativeDir, string $fileName, string $content, string $outputDir)
{
    $dir = $outputDir . ($relativeDir ? '/' . trim($relativeDir, '/') : '');
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($dir . '/' . $fileName, $content);
}

// Helper function to copy a directory recursively
function copyDir(string $src, string $dst)
{
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    while (false !== ($file = readdir($dir))) {
        if ($file !== '.' && $file !== '..') {
            if (is_dir($src . '/' . $file)) {
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// 1. Copy Assets to root
echo "Copying assets to root...\n";
copyDir($outputDir . '/public/css', $outputDir . '/css');
copyDir($outputDir . '/public/images', $outputDir . '/images');
copyDir($outputDir . '/public/js', $outputDir . '/js');
if (file_exists($outputDir . '/public/favicon.ico')) {
    copy($outputDir . '/public/favicon.ico', $outputDir . '/favicon.ico');
}
if (file_exists($outputDir . '/public/robots.txt')) {
    copy($outputDir . '/public/robots.txt', $outputDir . '/robots.txt');
}
file_put_contents($outputDir . '/.nojekyll', '');
echo "Assets & .nojekyll ready.\n\n";

// List of routes to render
$routes = [
    '/' => ['', 'index.html'],
    '/about' => ['about', 'index.html', 'about.html'],
    '/services' => ['services', 'index.html', 'services.html'],
    '/group-tours' => ['group-tours', 'index.html', 'group-tours.html'],
    '/corporate-tours' => ['corporate-tours', 'index.html', 'corporate-tours.html'],
    '/excursions' => ['excursions', 'index.html', 'excursions.html'],
    '/gallery' => ['gallery', 'index.html', 'gallery.html'],
    '/contact' => ['contact', 'index.html', 'contact.html'],
    '/terms' => ['terms', 'index.html', 'terms.html'],
    '/privacy' => ['privacy', 'index.html', 'privacy.html'],
    '/packages' => ['packages', 'index.html', 'packages.html'],
];

// Add Category routes
$categories = Category::all();
foreach ($categories as $cat) {
    $routes['/packages/' . $cat->slug] = [
        'packages/' . $cat->slug,
        'index.html',
        $cat->slug . '.html' // also packages/<slug>.html
    ];
}

// Add Package routes
$packages = TourPackage::all();
foreach ($packages as $pkg) {
    $routes['/package/' . $pkg->slug] = [
        'package/' . $pkg->slug,
        'index.html',
        $pkg->slug . '.html' // also package/<slug>.html
    ];
}

echo "Generating " . count($routes) . " pages...\n";

$count = 0;
foreach ($routes as $uri => $info) {
    $request = \Illuminate\Http\Request::create($uri, 'GET');
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();

    if ($status === 200) {
        $html = $response->getContent();

        $relDir = $info[0];
        $mainFile = $info[1];
        savePage($relDir, $mainFile, $html, $outputDir);

        // Also save alternate extension if provided
        if (isset($info[2])) {
            if (str_contains($relDir, '/')) {
                // e.g. packages/himachal -> save packages/himachal.html
                $parentDir = dirname($relDir);
                savePage($parentDir, $info[2], $html, $outputDir);
            } else {
                savePage('', $info[2], $html, $outputDir);
            }
        }

        // If this is homepage, also make it 404.html for GitHub Pages SPA fallback
        if ($uri === '/') {
            file_put_contents($outputDir . '/404.html', $html);
        }

        $count++;
    } else {
        echo "FAILED ($status): $uri\n";
    }
}

echo "\nSuccessfully generated {$count} static pages with all assets!\n";

