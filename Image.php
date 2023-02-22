<?php

declare(strict_types=1);

namespace semmelsamu\Imgs;


class Image
{
    # ~
    
    # Member variables
    
    # ~
    
    protected $image, $content_type;
    protected $width, $height;
    
    # ~
    
    # Constructor
    
    # ~
    
    public function __construct(protected string $path) 
    {
        if(!file_exists($path))
            throw new \Exception("File $path could not be found.");
        
        $this->image = imagecreatefromstring(file_get_contents($path));
        
        $this->content_type = pathinfo($path, PATHINFO_EXTENSION);
    }
    
    # ~
    
    # Image manipulating functions
    
    # ~
    
    public function resize(
        int $width = null,
        int $height = null,
    )
    {
        if(!isset($width) && !isset($height))
            throw new \Exception("At least one dimension is required");
        
        
        // Auto-calculate correct aspect ratio if only one dimension is given
        
        if(!isset($width))
            $width = $this->get_width() * ($height / $this->get_height());
            
        else if(!isset($height))
            $height = $this->get_height() * ($width / $this->get_width());
        
        
        // Round
        
        $width = intval(round($width));
        $height = intval(round($height));
        
        
        // Resize the image
        
        $resized = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized, $this->image, 0, 0, 0, 0, $width, $height, imagesx($this->image), imagesy($this->image));
        $this->image = $resized;
        
    }
    
    /*public function scale(float $percentage)
    {
        if($percentage <= 0)
            throw new \Exception("Percentage has to be greater than 0");
        
        $this->scale_percentage = $percentage;
    }*/
    
    /*public function crop(
        int $top = 0,
        int $right = 0,
        int $bottom = 0,
        int $left = 0
    ) 
    {
        $this->crop_top = $top;
        $this->crop_right = $right;
        $this->crop_bottom = $bottom;
        $this->crop_left = $left;
        
        return $this;
    }*/
    
    /*public function resize(
        int $width = null,
        int $height = null,
    )
    {
        if(!isset($width) && !isset($height))
            throw new \Exception("At least one dimension is required");
        
        // Auto-calculate correct aspect ratio if only one dimension is given
        
        if(!isset($width))
        {
            $this->resize_width = $this->get_original_width() * ($height / $this->get_original_height());
            $this->resize_height = $height;
        }
        else if(!isset($height))
        {
            $this->resize_width = $width;
            $this->resize_height = $this->get_original_height() * ($width / $this->get_original_width());
        }
        else
        {
            $this->resize_width = $width;
            $this->resize_height = $height;
        }
        
        return $this;
    }*/
    
    # ~
    
    # Render functions
    
    # ~
    
    /*public function render()
    {
        // Contains dst_x, dst_y, src_x, src_y, ...
        $rectangles = $this->calculate_rectangles();
        extract($rectangles);
        
        $dst_image = imagecreatetruecolor(
            $dst_width,
            $dst_height
        );
        
        $src_image = $this->image;
        
        imagecopyresampled(
            $dst_image,
            $src_image,
            $dst_x,
            $dst_y,
            $src_x,
            $src_y,
            $dst_width,
            $dst_height,
            $src_width,
            $src_height
        );
        
        return $dst_image;
    }*/
    
    /*protected function calculate_rectangles()
    {
        // Import original dimensions
        
        $original_width = $this->get_width();
        $original_height = $this->get_height();
        
        
        // Import member variables
        
        $scale_percentage = $this->scale_percentage ?? 1;
        
        $crop_top = $this->crop_top ?? 0;
        $crop_right = $this->crop_right ?? 0;
        $crop_bottom = $this->crop_bottom ?? 0;
        $crop_left = $this->crop_left ?? 0;
        
        $resize_width = $this->resize_width ?? $original_width;
        $resize_height = $this->resize_height ?? $original_height;
        
        
        // Source rectangle
        
        $src_x = 0;
        $src_y = 0;
        
        $src_width = $original_width;
        $src_height = $original_height;
        
        
        // Destination rectangle
        
        $dst_x = 0;
        $dst_y = 0;
        
        $dst_width = $original_width * $scale_percentage;
        $dst_height = $original_height * $scale_percentage;
        
        
        // Collect variables
        
        $rectangles_float = compact("dst_x", "dst_y", "src_x", "src_y", "dst_width", "dst_height", "src_width", "src_height");
        
        
        // Round to integers
        
        $rectangles = array_map(fn ($val) => intval(round($val)), $rectangles_float);
        
        
        // Debug
        
        # var_dump($rectangles_float, $crop_width_percentage, $crop_height_percentage); die;
        
        
        return $rectangles;
    }*/
    
    # ~
    
    # Getter functions
    
    # ~
    
    public function get_image()
    {
        return $this->image;
    }
    
    public function get_dimensions()
    {
        if(!isset($this->width) || !isset($this->height))
            list($this->width, $this->height) = getimagesize($this->path);
            
        return array(
            "width" => $this->width,
            "height" => $this->height
        );
    }
    
    public function get_width()
    {
        return $this->get_dimensions()["width"];
    }
    
    public function get_height()
    {
        return $this->get_dimensions()["height"];
    }
    
    public function get_content_type()
    {
        return $this->content_type;
    }
}