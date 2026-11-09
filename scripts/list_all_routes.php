<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Category;
use App\Models\TourPackage;

$categories = Category::all()->pluck('slug')->toArray();
$packages = TourPackage::all()->pluck('slug')->toArray();

echo "Categories (" . count($categories) . "):\n";
foreach ($categories as $c) {
    echo " - $c\n";
}

echo "\nPackages (" . count($packages) . "):\n";
foreach ($packages as $p) {
    echo " - $p\n";
}

