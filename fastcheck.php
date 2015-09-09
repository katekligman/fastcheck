<?php

require('imagecompare.class.php');

$folder = $argv[1];
$url1 = $argv[2];
$url2 = $argv[3];

if (empty($url1) || empty($url2) || empty($folder)) {
  die("Usage: fastcheck.php [folder] [url1] [url2]\r\n");
}

@mkdir($folder);

// snapshot the first url multiple times to get the images for the mask
system("phantomjs snap.js $url1 800 image1a.png");
system("phantomjs snap.js $url1 800 image1b.png");
// snapshot the second url for comparison later
system("phantomjs snap.js $url2 800 image2.png");

$block_size = 50;

$im = new ImageCompare();
$im->chop_image('image1a.png', $block_size, $block_size);
$im->chop_image('image1b.png', $block_size, $block_size);
$im->create_mask('image1a.png', 'image1b.png', 'mask.png', $block_size, $block_size);
$im->apply_mask('image1a.png', 'mask.png', 'image1c.png');
$im->apply_mask('image2.png', 'mask.png', 'image2c.png');
$im->compare('image1c.png', 'image2c.png', 'image-diff.png');
