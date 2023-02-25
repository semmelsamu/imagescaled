<?php

namespace semmelsamu;

include("../Imgs.php");


$imgs = new Imgs();

$imgs->prepare_from_string("mountains.jpg?f=png");

$imgs->output();