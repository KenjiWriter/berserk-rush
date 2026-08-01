<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$brainDir = "C:\\Users\\macie\\.gemini\\antigravity-ide\\brain\\f8cbab13-42af-41da-ad71-2c28b71d9fee";
$targetDir = public_path("assets/monsters/avatars");

if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$mapping = [
    'widmowy_lesny_niedzwiedz' => 'widmowy-lesny-niedzwiedz.png',
    'starozytny_golem_kamienny' => 'starozytny-golem-kamienny.png',
    'mroczny_wladca_trolli' => 'mroczny-wladca-trolli.png',
    'wojownik_cienia_orkow' => 'wojownik-cienia-orkow.png',
    'bagnisty_behemot_cienia' => 'bagnisty-behemot-cienia.png',
    'wyvern_cienistego_szczytu' => 'wyvern-cienistego-szczytu.png',
    'arcymag_pustki_i_arkanow' => 'arcymag-pustki-i-arkanow.png',
    'wladca_skazenia_i_plagi' => 'wladca-skazenia-i-plagi.png',
];

$processed = 0;
foreach ($mapping as $key => $targetFile) {
    $files = glob($brainDir . DIRECTORY_SEPARATOR . $key . "_*.png");
    if (!empty($files)) {
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $src = $files[0];
        $dest = $targetDir . DIRECTORY_SEPARATOR . $targetFile;

        $srcData = file_get_contents($src);
        $srcImg = @imagecreatefromstring($srcData);
        if ($srcImg) {
            $w = imagesx($srcImg);
            $h = imagesy($srcImg);
            $dstImg = imagecreatetruecolor(256, 256);
            imagealphablending($dstImg, false);
            imagesavealpha($dstImg, true);

            imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, 256, 256, $w, $h);
            imagepng($dstImg, $dest, 8);
            imagedestroy($srcImg);
            imagedestroy($dstImg);
            echo "Processed monster avatar: {$targetFile}\n";
            $processed++;
        } else {
            copy($src, $dest);
            echo "Copied monster avatar: {$targetFile}\n";
            $processed++;
        }
    } else {
        echo "WARNING: Could not find image for {$key}\n";
    }
}

echo "Total monster avatars processed: {$processed} / " . count($mapping) . "\n";
