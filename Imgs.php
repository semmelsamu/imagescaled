<?php

declare(strict_types=1);

namespace semmelsamu;

include("Cache.php");
include("Image.php");


class Imgs
{
    public function __construct(
        string $path = "cache/",
        int $max_files = 100
    )
    {
        $this->cache = new Imgs\Cache($path, $max_files);
    }
    
    # ~
    
    # Public functions
    
    # ~
    
    public function url($root)
    {
        $this->cache->invalidate_cache();
        
        $this->image(
            $root . urldecode(parse_url($_SERVER["REQUEST_URI"])["path"]),
            isset($_GET["t"]) ? intval($_GET["t"]) : 0,
            isset($_GET["r"]) ? intval($_GET["r"]) : 0,
            isset($_GET["b"]) ? intval($_GET["b"]) : 0,
            isset($_GET["l"]) ? intval($_GET["l"]) : 0,
            isset($_GET["w"]) ? intval($_GET["w"]) : null,
            isset($_GET["h"]) ? intval($_GET["h"]) : null,
            isset($_GET["q"]) ? intval($_GET["q"]) : -1,
            isset($_GET["f"]) ? intval($_GET["f"]) : null
        );
    }
    
    public function image(
        string $filename, 
        int $top = 0, 
        int $right = 0, 
        int $bottom = 0, 
        int $left = 0, 
        int $width = null, 
        int $height = null, 
        int $quality = -1, 
        string $format = null
    )
    {
        // Validate Inputs
        
        if(!file_exists($filename))
            throw new \Exception("File $filename does not exist");
            
        if(!isset($format))
            $format = pathinfo($filename, PATHINFO_EXTENSION);
        
        
        $key = "$filename-$top-$right-$bottom-$left-$width-$height-$quality.$format";
        
        header("Content-type: $format");
        
        if($this->cache->is_cached($key))
        {
            $image = $this->cache->load($key);
        }
        else
        {
            $image = $this->render_image($filename, $top, $right, $bottom, $left, $width, $height, $quality, $format);
            $this->cache->save($key, $image);
        }
        
        echo $image;
        
        exit;
    }
    
    private function render_image(
        $filename, 
        $top, 
        $right, 
        $bottom, 
        $left, 
        $width, 
        $height, 
        $quality, 
        $format
    )
    {
        $image = new Imgs\Image($filename);
        
        if(isset($width) || isset($height))
        {
            $image->resize($width, $height);
        }
        
        $rendered_image = $image->get_image();
        
        
        ob_start();
        
        switch($format)
        {
            case "png":
                
                imagepng($rendered_image, quality: $quality);
                break;
            
            case "jpeg":
            case "jpg":
            default:
                
                imagejpeg($rendered_image, quality: $quality);
                break;
        }
        
        return ob_get_clean();
    }
}