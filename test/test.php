<?php

namespace semmelsamu;

include("../Imgs.php");

$image = new Imgs\Image("crop.png");

$image->crop(100);

$result = $image->render();

header("Content-type: image/png");
imagepng($result);
