<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$brainDir = "C:\\Users\\macie\\.gemini\\antigravity-ide\\brain\\f8cbab13-42af-41da-ad71-2c28b71d9fee";
$targetDir = "C:\\dev\\berserk-rush\\public\\assets\\skills\\icons";

$json = file_get_contents(base_path('scratch/skills_to_generate.json'));
$list = json_decode($json, true);

$copied = 0;
foreach ($list as $item) {
    $targetFile = $item['file'];
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $targetFile;

    // e.g. arrow-spread.png -> arrow_spread
    $baseName = str_replace(['-', '.png'], ['_', ''], $targetFile);

    // find newest file in brainDir starting with $baseName
    $files = glob($brainDir . DIRECTORY_SEPARATOR . $baseName . "_*.png");
    if (!empty($files)) {
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $newest = $files[0];

        // Copy and resize using Gd
        if (extension_loaded('gd')) {
            $srcData = file_get_contents($newest);
            $srcImg = @imagecreatefromstring($srcData);
            if ($srcImg) {
                $w = imagesx($srcImg);
                $h = imagesy($srcImg);
                $dstImg = imagecreatetruecolor(256, 256);
                imagealphablending($dstImg, false);
                imagesavealpha($dstImg, true);

                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, 256, 256, $w, $h);
                imagepng($dstImg, $targetPath, 8); // compression level 8
                imagedestroy($srcImg);
                imagedestroy($dstImg);
                echo "Processed & saved GD icon: {$targetFile}\n";
                $copied++;
                continue;
            }
        }

        copy($newest, $targetPath);
        echo "Copied raw icon: {$targetFile}\n";
        $copied++;
    }
}

echo "Total processed icons: {$copied} / " . count($list) . "\n";
