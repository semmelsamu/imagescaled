<?php

namespace semmelsamu;

class Imgs
{
    function __construct(
        $auto = true, 
        $cache = "cache/", 
        $cache_expires = 86400, 
        $max_size = 2000
    )
    {
        // Import all parameters
        foreach(get_defined_vars() as $key => $val)
            $this->$key = $val;

        if($auto)
        {
            $this->image(
                top: isset($_GET["t"]) ? $_GET["t"] : null,
                right: isset($_GET["r"]) ? $_GET["r"] : null,
                bottom: isset($_GET["b"]) ? $_GET["b"] : null,
                left: isset($_GET["l"]) ? $_GET["l"] : null,
                width: isset($_GET["w"]) ? $_GET["w"] : null,
                height: isset($_GET["h"]) ? $_GET["h"] : null,
                format: isset($_GET["f"]) ? $_GET["f"] : null,
                quality: isset($_GET["q"]) ? $_GET["q"] : -1,
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
        $path = true,

        $top = 0,
        $right = 0,
        $bottom = 0,
        $left = 0,

        $width = null, 
        $height = null,

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

        if($this->path == true)
            $this->path = $_SERVER["DOCUMENT_ROOT"].parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

        if(!file_exists($this->path))
            throw new \Exception("Image '$this->path' does not exist."); 

        // Image formats

        $valid_formats = ["jpeg", "png", "webp"];

        $this->src_format = substr($this->path, strrpos($this->path, ".")+1);
        
        if($this->src_format == "jpg")
            $this->src_format = "jpeg";

        if($this->format == "jpg")
            $this->format = "jpeg";
        
        if(!isset($this->format))
            $this->format = $this->src_format;

        if(!in_array($this->src_format, $valid_formats) || !in_array($this->format, $valid_formats))
            throw new \Exception("Image format $this->src_format>>$this->format is not supported.");
    }


    /**
     * Scaling logic
     */
    private function calc_dimensions()
    {
        list($src_w, $src_h) = getimagesize($this->path);

        $crop_w = $src_w - $this->left - $this->right;
        $crop_h = $src_h - $this->top - $this->bottom;

        $src_ratio = $src_h / $src_w;

        if(!isset($this->width) && !isset($this->height))
        {
            $this->width = $crop_w;
            $this->height = $crop_h;
        }
        else if(isset($this->height) && !isset($this->width))
        {
            $this->width = $crop_w * $this->height / $crop_h;
        }
        else if(isset($this->width) && !isset($this->height))
        {
            $this->height = $crop_h * $this->width / $crop_w;
        }

        if(isset($this->max_size))
        {
            if($this->width > $this->max_size)
                $this->width = $this->max_size;

            if($this->height > $this->max_size)
                $this->height = $this->max_size;
        }

        $this->dst_w = $this->width;
        $this->dst_h = $this->height;

        $dst_ratio = $this->dst_h / $this->dst_w;

        $cut_w = $crop_w;
        $cut_h = $crop_w * $dst_ratio;

        if($cut_h > $crop_h)
        {
            $cut_h = $crop_h;
            $cut_w = $crop_h / $dst_ratio;
        }

        $this->cut_x = $this->left + ($crop_w - $cut_w) / 2;
        $this->cut_y = $this->top + ($crop_h - $cut_h) / 2;
        
        $this->cut_w = $cut_w;
        $this->cut_h = $cut_h;

        if(0)
        {
            ?>
            <h1>Debug</h1>
            <ul>
                <li>width: <?= $this->width ?></li>
                <li>height: <?= $this->height ?></li>
                <li>dst_ratio: <?= $dst_ratio ?></li>
                <li>crop_w: <?= $crop_w ?></li>
                <li>crop_h: <?= $crop_h ?></li>
                <br>
                <li>cut_x: <?= $this->cut_x ?></li>
                <li>cut_y: <?= $this->cut_y ?></li>
                <li>cut_w: <?= $cut_w ?></li>
                <li>cut_h: <?= $cut_h ?></li>
            </ul>
            <?php
            die;
        }
    }


    private function generate_cache_key()
    {
        $this->cache_key = md5(
            $this->path."?".
            $this->cut_x.".". 
            $this->cut_y.".". 
            $this->dst_w.".".
            $this->dst_h.".".
            $this->cut_w.".".
            $this->cut_h.".".
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
            case "jpeg": $this->src_image = imagecreatefromjpeg($this->path); break;
            case "png": $this->src_image = imagecreatefrompng($this->path); break;
            case "webp": $this->src_image = imagecreatefromwebp($this->path); break;
        }
    }


    private function render_image()
    {
        $this->image = imagecreatetruecolor(
            $this->dst_w,
            $this->dst_h
        );

        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        imagecopyresampled(
            $this->image,
            $this->src_image,
            0,
            0,
            $this->cut_x,
            $this->cut_y,
            $this->dst_w,
            $this->dst_h,
            $this->cut_w,
            $this->cut_h
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
            case "jpeg": imagejpeg($this->image, $this->cache.$this->cache_key, $this->quality); break;
            case "png": imagepng($this->image, $this->cache.$this->cache_key, $this->quality); break;
            case "webp": imagewebp($this->image, $this->cache.$this->cache_key, $this->quality); break;
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
            case "jpeg": header("Content-Type: image/jpeg"); imagejpeg($this->image, null, $this->quality); break;
            case "png": header("Content-Type: image/png"); imagepng($this->image, null, $this->quality); break;
            case "webp": header("Content-Type: image/webp"); imagewebp($this->image, null, $this->quality); break;
        }
    }
}

?>
