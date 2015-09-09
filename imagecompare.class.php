<?php

class ImageCompare {

  public function __construct() {
    @mkdir("process");
  }

  public function compare($image_path1, $image_path2, $image_diff_out, $fuzz = 0, $color = 'blue') {
    $output = @exec("compare -dissimilarity-threshold 1 -fuzz $fuzz -metric AE -highlight-color $color $image_path1 $image_path2 $image_diff_out");
    return $output;
  }

  function chop_image($image_path, $step_width, $step_height) {
    $image = new Imagick($image_path);

    $height = $image->getImageHeight();
    $width = $image->getImageWidth();

    for ($i = 0; $i <= $width - $step_width; $i += $step_width) {
      for ($j = 0; $j <= $height - $step_height; $j += $step_height) {
        $compare = clone $image;
        $compare->cropimage($step_width, $step_height, $i, $j);
        $compare->writeImage("process/" . basename($image_path, '.png') . "-{$i}-{$j}.png");
      }
    }
  }

  function create_mask($image_path_1, $image_path_2, $mask_path, $step_width, $step_height) {
    $info = new Imagick($image_path_1);
    $mask = new Imagick();
    $mask->newImage($info->width, $info->height, 'none');
    $mask->setImageFormat('png');
    $chunk = new Imagick();
    $chunk->newImage($step_width, $step_height, new ImagickPixel('black'));
    $chunk->setImageFormat('png');

    $prefix1 = basename($image_path_1, '.png');
    $prefix2 = basename($image_path_2, '.png');

    $paths = glob("process/{$prefix1}*.png");
    foreach ($paths as $path) {
      $path2 = "process/" . $prefix2 . substr(basename($path), strlen($prefix2));
      // compare 1 and 2
      $a = new Imagick($path);
      $b = new Imagick($path2);
      $diff = $a->compareImages($b, Imagick::METRIC_MEANSQUAREERROR);
      if ($diff[1] > 0) {
        preg_match('~.*?(\d+)\-(\d+).png~', $path, $m) or die("Issue fetching the x y from $path\r\n");
        $mask->compositeImage($chunk, Imagick::COMPOSITE_DEFAULT, $m[1], $m[2]);
      }
    }
    $mask->writeImage($mask_path);
  }

  function apply_mask($image_src_path, $mask_path, $image_dest_path) {
    $src = new Imagick($image_src_path);
    $mask = new Imagick($mask_path);
    $src->compositeImage($mask, Imagick::COMPOSITE_DEFAULT, 0, 0);
    $src->writeImage($image_dest_path);
  }

  function cleanup() {
    exec("rm process/*.png");
  }
}

/*
$units = 50;
chop_image('pan1.png', $units, $units);
chop_image('pan2.png', $units, $units);
create_mask('pan1.png', 'pan2.png', 'pan-mask.png', $units, $units);
apply_mask('pan1.png', 'pan-mask.png', 'pan-test.png');
*/
