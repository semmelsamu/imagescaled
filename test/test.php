<?php

namespace semmelsamu;

include("../Imgs.php");


$imgs = new Imgs(enable_cache: false);

$imgs->string("mountains.jpg?w=800&f=png");

$imgs->image();