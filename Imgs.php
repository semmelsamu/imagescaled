<?php

declare(strict_types=1);

namespace semmelsamu;


class Imgs
{
    const SUPPORTED_FORMATS = ["jpg", "png"];
    
    public function __construct(
        protected string $root = "./",
        protected string $cache_dir = "cache/",
        protected int $max_cache_files = 100
    )
    {
        if(!is_dir($cache_dir))
            mkdir($cache_dir);
    }
    
    public function prepare_from_url($url)
    {
        // todo
    }
    
    public function prepare_from_string(string $string)
    {
        $parsed_url = parse_url($string);
        
        parse_str($parsed_url["query"] ?? "", $parsed_string);
        
        if(empty($parsed_url["path"]))
            throw new \Exception("At least filename must be given");
        
        $this->prepare(
            filename: $parsed_url["path"],
            width: isset($parsed_string["w"]) ? intval($parsed_string["w"]) : null,
            height: isset($parsed_string["h"]) ? intval($parsed_string["h"]) : null,
            quality: isset($parsed_string["q"]) ? intval($parsed_string["q"]) : null,
            format: $parsed_string["f"] ?? null
        );
    }
    
    public function prepare(
        string $filename,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null
    )
    {
        $this->image = $this->root . $filename;
        
        list($this->original_width, $this->original_height) = getimagesize($this->image);
        
        $this->width = $width;
        $this->height = $height;
        
        $this->quality = $quality ?? -1;
        $this->format = $format;
        
        $this->calculate_rectangles();
    }
    
    public function calculate_rectangles()
    {
        $original_width = $this->original_width;
        $original_height = $this->original_height;
        
        $output_width = $this->width;
        $output_height = $this->height;
        
        if(!isset($output_height) && !isset($output_width))
        {
            $output_width = $original_width;
            $output_height = $original_height;
        }
        
        else if(isset($output_width) && !isset($output_height))
            $output_height = $original_height * ($output_width / $original_width);
            
        else if(isset($output_height) && !isset($output_width))
            $output_width = $original_width * ($output_height / $original_height);
            
        else
        {
            // todo
        }
        
        $this->dst_x = intval(round(0));
        $this->dst_y = intval(round(0));
        $this->src_x = intval(round(0));
        $this->src_y = intval(round(0));
        $this->dst_width = intval(round($output_width));
        $this->dst_height = intval(round($output_height));
        $this->src_width = intval(round($this->original_width));
        $this->src_height = intval(round($this->original_height));
    }
    
    public function get_width()
    {
        return $this->dst_width;
    }
    
    public function get_height()
    {
        return $this->dst_height;
    }
    
    public function output()
    {
        // Format Validation
        
        $original_format = pathinfo($this->image, PATHINFO_EXTENSION);
        $output_format = $this->format;
        
        if(!isset($output_format))
            $output_format = $original_format;
            
        if($original_format == "jpeg") $original_format = "jpg";
        if($output_format == "jpeg") $output_format = "jpg";
            
        if(!in_array($original_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Source image format $original_format is not supported.");
            
        if(!in_array($output_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Output image format $output_format is not supported.");
        
        
        // Cache
        
        $key = $this->image .
            "-" . $this->dst_x .
            "-" . $this->dst_y .
            "-" . $this->src_x .
            "-" . $this->src_y .
            "-" . $this->dst_width .
            "-" . $this->dst_height .
            "-" . $this->src_width .
            "-" . $this->src_height .
            "-" . $this->quality .
            "." . $output_format;
        
        $cached_image = $this->cache_dir . urlencode($key);
        
        if(!file_exists($cached_image))
        {
            switch($original_format)
            {
                case "png":
                    $original_image = imagecreatefrompng($this->image);
                    break;
                    
                case "jpg":
                default:
                    $original_image = imagecreatefromjpeg($this->image);
                    break;
            }
            
            $output_image = imagecreatetruecolor($this->dst_width, $this->dst_height);
            
            imagecopyresampled(
                $output_image,
                $original_image,
                $this->dst_x,
                $this->dst_y,
                $this->src_x,
                $this->src_y,
                $this->dst_width,
                $this->dst_height,
                $this->src_width,
                $this->src_height
            );
            
            switch($output_format)
            {
                case "png":
                    imagepng($output_image, $cached_image, $this->quality);
                    break;
                    
                case "jpg":
                default:
                    imagejpeg($output_image, $cached_image, $this->quality);
                    break;
            }
        }
        
        
        // Output
        
        header("Content-type: " . mime_content_type($cached_image));
        header('Content-Length: ' . filesize($cached_image));
        readfile($cached_image);
        
        exit;
    }
}