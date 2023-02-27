<?php

namespace semmelsamu;

include("../Imgs.php");


$imgs = new Imgs(enable_cache: false);

$imgs->string("crop.png?t=100");

$imgs->image();