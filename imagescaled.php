<?php

namespace semmelsamu;
function db($variable, $exit = false) {echo "\n\n<pre>"; var_dump($variable); echo "</pre>\n\n"; if($exit) exit;}

class Imagescaled
{
    function __construct($path, $auto = true, $cache = "cache/", $cache_expires = 86400, $max_size = 2000) 
    {
        // Import all parameters
        foreach(get_defined_vars() as $key => $val)
            $this->$key = $val;

        if($auto)
        {   
            $width = isset($_GET["w"]) ? $_GET["w"] : null;
            $height = isset($_GET["h"]) ? $_GET["h"] : null;
            $crop = isset($_GET["c"]) ? $_GET["c"] : 1;
            $size = isset($_GET["s"]) ? $_GET["s"] : null;

            $top = isset($_GET["t"]) ? $_GET["t"] : 0;
            $right = isset($_GET["r"]) ? $_GET["r"] : 0;
            $bottom = isset($_GET["b"]) ? $_GET["b"] : 0;
            $left = isset($_GET["l"]) ? $_GET["l"] : 0;

            $format = isset($_GET["f"]) ? $_GET["f"] : null;
            $quality = isset($_GET["q"]) ? $_GET["q"] : -1;

            $this->image(
                width: $width, 
                height: $height, 
                crop: $crop, 
                size: $size, 

                top: $top, 
                right: $right, 
                bottom: $bottom, 
                left: $left, 

                format: $format, 
                quality: $quality
            );

            $this->empty_cache();

            exit;
        }
    }


    function empty_cache()
    {
        if(!$this->cache) 
            return;

        foreach(scandir($this->cache) as $file)
        {
            if(strpos($file, ".") == false)
            {
                if((filectime($this->cache.$file)+$this->cache_expires) < time())
                {
                    unlink($this->cache.$file);
                }
            }
        }
    }


    function image(
        $width = null, 
        $height = null, 
        $crop = true, 
        $size = null, 

        $top = 0, 
        $right = 0, 
        $bottom = 0, 
        $left = 0, 

        $format = null, 
        $quality = -1
    ) 
    {
        // Import all parameters
        foreach(get_defined_vars() as $key => $val)
            $this->$key = $val;

        $this->process_inputs();
        $this->calc_dimensions();
        $this->generate_cache_key();

        if(!$this->cache || ($this->cache && !$this->image_cached()))
        {
            $this->import_image();
            $this->render_image();
        }
        
        if($this->cache)
        {
            if(!$this->image_cached())
            {
                $this->cache_image();
            }
            $this->output_cached_image();
        }
        else
        {
            $this->output_rendered_image();
        }
    }


    private function process_inputs()
    {
        // File exists

        if(!file_exists($this->path))
            throw new \Exception("Image '$this->path' does not exist."); 
        

        // Image formats

        $valid_formats = ["jpg", "jpeg", "png"];

        $this->src_format = substr($this->path, strrpos($this->path, ".")+1);
        
        if($this->src_format == "jpeg")
            $this->src_format = "jpg";
        
        if(!isset($this->format))
            $this->format = $this->src_format;

        if(!in_array($this->src_format, $valid_formats) || !in_array($this->format, $valid_formats))
            throw new \Exception("Image format $this->src_format>>$this->format is not supported.");
    }


    private function calc_dimensions()
    {
        list($this->src_width, $this->src_height) = getimagesize($this->path);


        // Process size parameter

        if(isset($this->size))
        {
            if($this->src_width < $this->src_height)
            {
                $this->width = $this->size;
                $this->height = null;
            }
            else
            {
                $this->width = null;
                $this->height = $this->size;
            }
        }


        // Set width and height

        if(!isset($this->width) && !isset($this->height))
        {
            $this->width = $this->src_width;
            $this->height = $this->src_height;
        }
        else if(isset($this->width) && !isset($this->height))
        {
            $this->height = $this->src_height * ($this->width / $this->src_width);
        }
        else if(isset($this->height) && !isset($this->width))
        {
            $this->width = $this->src_width * ($this->height / $this->src_height);
        }


        // Process cropping
        
        $this->scale_h = $this->width / $this->src_width;
        $this->scale_v = $this->height / $this->src_height;

        if($this->crop)
        {
            if($this->scale_h < $this->scale_v)
            {
                $crop_h = abs(($this->src_width * $this->scale_v - $this->width) / $this->scale_v) / 2;
                $this->top += $crop_h;
                $this->bottom += $crop_h;
            }
            else if($this->scale_h > $this->scale_v)
            {
                $crop_v = abs(($this->src_height * $this->scale_h - $this->height) / $this->scale_h) / 2;
                $this->top += $crop_v;
                $this->bottom += $crop_v;
            }
        }


        // Set scale variables

        $this->canvas_width = $this->width;
        $this->canvas_height = $this->height;

        $this->dst_x = 0;
        $this->dst_y = 0;
        $this->src_x = $this->left;
        $this->src_y = $this->top;
        $this->dst_width = $this->canvas_width;
        $this->dst_height = $this->canvas_height;
        $this->src_width -= $this->left + $this->right;
        $this->src_height -= $this->top + $this->bottom;


        // Max Size

        if($this->max_size)
        {
            if($this->canvas_width > $this->max_size)
            {
                $this->width = $this->max_size;
                $this->height = null;
                $this->size = null;
                $this->calc_dimensions();
            }
            else if($this->canvas_height > $this->max_size)
            {
                $this->height = $this->max_size;
                $this->width = null;
                $this->size = null;
                $this->calc_dimensions();
            }
        }
    }


    private function generate_cache_key()
    {
        $this->cache_key = md5(
            $this->path."?".
            $this->dst_x.".".
            $this->dst_y.".". 
            $this->src_x.".". 
            $this->src_y.".". 
            $this->dst_width.".". 
            $this->dst_height.".". 
            $this->src_width.".". 
            $this->src_height.".".
            $this->format.".".$this->quality
        );
    }


    private function image_cached()
    {
        return file_exists($this->cache.$this->cache_key);
    }


    private function import_image()
    {
        switch($this->src_format)
        {
            case "jpg": $this->src_image = imagecreatefromjpeg($this->path); break;
            case "png": $this->src_image = imagecreatefrompng($this->path); break;
        }
    }


    private function render_image()
    {
        $this->image = imagecreatetruecolor(
            $this->canvas_width, 
            $this->canvas_height
        );

        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        imagecopyresampled(
            $this->image, 
            $this->src_image, 
            $this->dst_x, 
            $this->dst_y, 
            $this->src_x, 
            $this->src_y, 
            $this->dst_width, 
            $this->dst_height, 
            $this->src_width, 
            $this->src_height
        );
    }


    private function cache_image()
    {
        // Create cache folder
        if (!file_exists($this->cache))
            mkdir($this->cache);

        // Save the image
        switch($this->format)
        {
            case "jpg": imagejpeg($this->image, $this->cache.$this->cache_key, $this->quality); break;
            case "png": imagepng($this->image, $this->cache.$this->cache_key, $this->quality); break;
        }
    }

    private function output_cached_image()
    {
        header("Content-Type: ".mime_content_type($this->cache.$this->cache_key));
        readfile($this->cache.$this->cache_key);
    }

    private function output_rendered_image()
    {
        switch($this->format)
        {
            case "jpg": header("Content-Type: image/jpeg"); imagejpeg($this->image, null, $this->quality); break;
            case "png": header("Content-Type: image/png"); imagepng($this->image, null, $this->quality); break;
        }
    }
}

?>