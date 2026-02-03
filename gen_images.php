<?php
$size = 64;

// 1.png - Red square with black border
$img1 = imagecreatetruecolor($size, $size);
$red = imagecolorallocate($img1, 255, 0, 0);
$black = imagecolorallocate($img1, 0, 0, 0);
imagefill($img1, 0, 0, $red);
imagerectangle($img1, 0, 0, $size - 1, $size - 1, $black);
imagepng($img1, 'c:/project/game/backend/public/storage/elements/1.png');
imagedestroy($img1);

// 2.png - Green square with black border
$img2 = imagecreatetruecolor($size, $size);
$green = imagecolorallocate($img2, 0, 255, 0);
imagefill($img2, 0, 0, $green);
imagerectangle($img2, 0, 0, $size - 1, $size - 1, $black);
imagepng($img2, 'c:/project/game/backend/public/storage/elements/2.png');
imagedestroy($img2);

echo "Images created successfully.\n";
