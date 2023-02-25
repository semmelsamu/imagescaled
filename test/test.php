<?php

namespace semmelsamu;

include("../Imgs.php");


$imgs = new Imgs();

$imgs->prepare_from_string("mountains.jpg?w=800&f=png");

$imgs->output();