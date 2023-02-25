<?php

namespace semmelsamu;

include("../Imgs.php");


$imgs = new Imgs();

$imgs->prepare("mountains.jpg", quality: "100");

$imgs->output();