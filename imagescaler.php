<?php

namespace semmelsamu;

class Imagescaled
{
    function __construct($image, $auto = true, $cache = "cache/", $max_size = 2000) 
    {
        $this->image = $image;
        $this->cache = $cache;
        $this->max_size = $max_size;

        if($auto)
        {
            $top = isset($_GET["t"]) ? $_GET["t"] : 0;
            $right = isset($_GET["r"]) ? $_GET["r"] : 0;
            $bottom = isset($_GET["b"]) ? $_GET["b"] : 0;
            $left = isset($_GET["l"]) ? $_GET["l"] : 0;
            $size = isset($_GET["s"]) ? $_GET["s"] : null;
            $width = isset($_GET["w"]) ? $_GET["w"] : null;
            $height = isset($_GET["h"]) ? $_GET["h"] : null;
            $format = isset($_GET["f"]) ? $_GET["f"] : null;
            $quality = isset($_GET["q"]) ? $_GET["q"] : null;

            $this->output($width, $height, $size, $top, $right, $bottom, $left, $format, $quality);
        }
    }

    function output($width = null, $height = null, $size = null, $top = 0, $right = 0, $bottom = 0, $left = 0, $format = null, $quality = null) 
    {
        // Get new image dimensions
        extract($this->calc_size($size, $width, $height));
        
        if(!isset($format) || $format == "jpeg")
            $format = "jpg";

        if(!isset($quality))
        {
            if($format == "jpg")
            {
                $quality = 80;
            }
        }
        
        $key = md5($this->image."?".$new_width.".".$new_height.".".$top.".".$right.".".$bottom.".".$left.".".$format.".".$quality);

        // Don't rescale image if it has been cached
        if(!($this->cache && file_exists($this->cache.$key)))
        {
            $this->result = imagecreatetruecolor($new_width, $new_height);
            imagecopyresampled($this->result, imagecreatefromjpeg($this->image), 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
        }

        if($this->cache)
        {
            // Cache the image
            if(!file_exists($this->cache.$key))
            {
                // Create cache folder
                if (!file_exists($this->cache))
                    mkdir($this->cache);

                // Save the image
                if($format == "jpg")
                {
                    imagejpeg($this->result, $this->cache.$key, $quality);
                }
            }

            // Output the cached image
            header("Content-Type: ".mime_content_type($this->cache.$key));
            readfile($this->cache.$key);
        }
        else
        {
            // Output the image directly
            if($format == "jpg")
            {
                header("Content-Type: image/jpeg");
                imagejpeg($this->result, null, $quality);
            }
        }
    }

    function calc_size($size = null, $width = null, $height = null)
    {
        list($original_width, $original_height) = getimagesize($this->image);

        if(isset($width))
        {
            $new_width = $width;
            $new_height = $new_width * ($original_height / $original_width);
        }
        else if(isset($height))
        {
            $new_height = $height;
            $new_width = $new_height * ($original_width / $original_height);
        }
        else if(isset($size))
        {
            if($original_width < $original_height)
            {
                $new_width = $size;
                $new_height = $new_width * ($original_height / $original_width);
            }
            else
            {
                $new_height = $size;
                $new_width = $new_height * ($original_width / $original_height);
            }
        }
        else
        {
            $new_width = $original_width;
            $new_height = $original_height;
        }

        // Images can't be bigger than max size
        if($new_height > $this->max_size)
        {
            return $this->calc_size(height: $this->max_size);
        }
        if($new_width > $this->max_size)
        {
            return $this->calc_size(width: $this->max_size);
        }

        return array(
            "new_width" => floor($new_width), 
            "new_height" => floor($new_height), 
            "original_width" => $original_width, 
            "original_height" => $original_height,
        );
    }

}

?>