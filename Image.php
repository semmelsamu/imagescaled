<?php

declare(strict_types=1);

namespace semmelsamu\Imgs;


class Image
{
    # ~
    
    # Member variables
    
    # ~
    
    protected $original_image, $original_content_type;
    protected $original_width, $original_height;
    
    # ~
    
    # Constructor
    
    # ~
    
    public function __construct(protected string $path) 
    {
        if(!file_exists($path))
            throw new \Exception("File $path could not be found.");
        
        $this->original_image = imagecreatefromstring(file_get_contents($path));
        
        $this->original_content_type = pathinfo($path, PATHINFO_EXTENSION);
    }
    
    # ~
    
    # Image manipulating functions
    
    # ~
    
    public function crop(
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
    }
    
    public function resize(
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
    }
    
    # ~
    
    # Render functions
    
    # ~
    
    public function render()
    {
        // Contains dst_x, dst_y, src_x, src_y, ...
        $rectangles = $this->calculate_rectangles();
        extract($rectangles);
        
        $dst_image = imagecreatetruecolor(
            $dst_width,
            $dst_height
        );
        
        $src_image = $this->original_image;
        
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
    }
    
    protected function calculate_rectangles()
    {
        // Import original dimensions
        
        $original_width = $this->get_original_width();
        $original_height = $this->get_original_height();
        
        
        // Import member variables
        
        $crop_top = $this->crop_top ?? 0;
        $crop_right = $this->crop_right ?? 0;
        $crop_bottom = $this->crop_bottom ?? 0;
        $crop_left = $this->crop_left ?? 0;
        
        $resize_width = $this->resize_width ?? $original_width;
        $resize_height = $this->resize_height ?? $original_height;
        
        
        // Add cropping if aspect ratio changes
        
        $original_aspect_ratio = $original_width / $original_height;
        $resized_aspect_ratio = $resize_width / $resize_height;
        
        if($original_aspect_ratio < $resized_aspect_ratio)
        {
            $scale = $original_width / $resize_width;
            
            $crop_height_add = $original_height - ($resize_height * $scale);
            
            $crop_top += $crop_height_add / 2;
            $crop_bottom += $crop_height_add / 2;
        }
        else if($original_aspect_ratio > $resized_aspect_ratio)
        {
            $scale = $original_height / $resize_height;
            
            $crop_width_add = $original_width - ($resize_width * $scale);
            
            $crop_left += $crop_width_add / 2;
            $crop_right += $crop_width_add / 2;
        }
        
        
        // Source rectangle
        
        $crop_width = $crop_left + $crop_right;
        $crop_height = $crop_top + $crop_bottom;
        
        $src_x = $crop_left;
        $src_y = $crop_top;
        
        $src_width = $original_width - $crop_width;
        $src_height = $original_height - $crop_height;
        
        
        // Destination rectangle
        
        $crop_width_percentage = $src_width / $original_width;
        $crop_height_percentage = $src_height / $original_height;
        
        $dst_x = 0;
        $dst_y = 0;
        
        $dst_width = $resize_width;
        $dst_height = $resize_height;
        
        
        // Collect variables
        
        $rectangles_float = compact("dst_x", "dst_y", "src_x", "src_y", "dst_width", "dst_height", "src_width", "src_height");
        
        
        // Round to integers
        
        $rectangles = array_map(fn ($val) => intval(round($val)), $rectangles_float);
        
        
        // Debug
        
        # var_dump($rectangles_float, $crop_width_percentage, $crop_height_percentage); die;
        
        
        return $rectangles;
    }
    
    # ~
    
    # Getter functions
    
    # ~
    
    public function get_original_dimensions()
    {
        if(!isset($this->original_width) || !isset($this->original_height))
            list($this->original_width, $this->original_height) = getimagesize($this->path);
            
        return array(
            "width" => $this->original_width,
            "height" => $this->original_height
        );
    }
    
    public function get_original_width()
    {
        return $this->get_original_dimensions()["width"];
    }
    
    public function get_original_height()
    {
        return $this->get_original_dimensions()["height"];
    }
    
    public function get_original_content_type()
    {
        return $this->source_content_type;
    }
}