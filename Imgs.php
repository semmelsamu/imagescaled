<?php

declare(strict_types=1);

namespace semmelsamu;

class Imgs
{
    public function __construct(
        private bool $auto = true, 
        private bool|string $cache = "cache/",
        private int $cache_max_files = 100,
        private bool|int $max_size = 2000,
        private bool|string $default_format = false
    )
    {
        if($auto)
        {
            $this->image(from_url: true);
            $this->empty_cache();
            exit;
        }
    }

    public function empty_cache(): void
    {
        if(!$this->cache) 
            return;

        $cache = array();
        
        foreach(scandir($this->cache) as $file)
        {
            if(!is_dir($this->cache.$file))
                array_push($cache, $file);
        }
        
        if(sizeof($cache) > $this->cache_max_files)
        {
            foreach($cache as $file)
            {
                unlink($this->cache.$file);
            }
        }
    }


    public function image(
        string|bool $path = true,

        int $top = 0,
        int $right = 0,
        int $bottom = 0,
        int $left = 0,

        int $min_size = null, 

        int $width = null, 
        int $height = null,

        string $format = null, 

        int $quality = -1,

        bool $from_url = false
    ): void
    {
        // Import all parameters
        foreach(get_defined_vars() as $key => $val)
            $this->$key = $val;

        if($this->from_url)
        {
            if(isset($_GET["t"])) $this->top = (int) $_GET["t"];
            if(isset($_GET["r"])) $this->right = (int) $_GET["r"];
            if(isset($_GET["b"])) $this->bottom = (int) $_GET["b"];
            if(isset($_GET["l"])) $this->left = (int) $_GET["l"];
            if(isset($_GET["m"])) $this->min_size = (int) $_GET["m"];
            if(isset($_GET["w"])) $this->width = (int) $_GET["w"];
            if(isset($_GET["h"])) $this->height = (int) $_GET["h"];
            if(isset($_GET["f"])) $this->format = (string) $_GET["f"];
            if(isset($_GET["q"])) $this->quality = (int) $_GET["q"];
        }

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
            $this->path = $_SERVER["DOCUMENT_ROOT"].urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH));

        if(!file_exists($this->path))
            throw new \Exception("Image '$this->path' does not exist."); 

        // Image formats

        $valid_formats = ["jpeg", "png", "webp"];



        $this->src_format = substr($this->path, strrpos($this->path, ".")+1);

        if($this->src_format == "jpg")
            $this->src_format = "jpeg";

        if($this->default_format && !isset($this->format))
            $this->format = $this->default_format;

        if($this->format == "jpg")
            $this->format = "jpeg";

        if(!isset($this->format))
            $this->format = $this->src_format;

        if(!in_array($this->src_format, $valid_formats) || !in_array($this->format, $valid_formats))
            throw new \Exception("Image format $this->src_format>>$this->format is not supported.");

        if(!isset($this->quality))
            $this->quality = -1;
    }


    /**
     * Scaling logic
     */
    private function calc_dimensions()
    {
        list($src_w, $src_h) = getimagesize($this->path);

        $crop_w = $src_w - $this->left - $this->right;
        $crop_h = $src_h - $this->top - $this->bottom;

        if(isset($this->min_size))
        {
            if($crop_w > $crop_h)
            {
                $this->width = null;
                $this->height = $this->min_size;
            }
            else
            {
                $this->width = $this->min_size;
                $this->height = null;
            }
        }

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
            $ratio = $this->height / $this->width;

            if($this->width > $this->max_size)
            {
                $this->width = $this->max_size;
                $this->height = $this->width * $ratio;
            }

            if($this->height > $this->max_size)
            {
                $this->height = $this->max_size;
                $this->width = $this->height / $ratio;
            }
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
            intval(round($this->dst_w)),
            intval(round($this->dst_h))
        );

        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        imagecopyresampled(
            $this->image,
            $this->src_image,
            0,
            0,
            intval(round($this->cut_x)),
            intval(round($this->cut_y)),
            intval(round($this->dst_w)),
            intval(round($this->dst_h)),
            intval(round($this->cut_w)),
            intval(round($this->cut_h))
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
