<?php

declare(strict_types=1);

namespace semmelsamu;


class Imgs
{
    # ~
    
    # Class Constants
    
    # ~
    
    /**
     * SUPPORTED_FORMATS
     * 
     * Array of formats supported by the class. Applies for reading and outputting images.
     * Notice: "jpeg" is not in the list, as it gets converted to "jpg".
     */
    const SUPPORTED_FORMATS = ["jpg", "png"];
    
    # ~
    
    # Constructor
    
    # ~
    
    /**
     * __construct
     * 
     * @param string $root_path The prefix for where images will be loaded.
     * @param ?int $max_size The maximum size of a side of the image. When trying to scale an image 
     *                       larger than that, it will be scaled to this. If not set (not recommended), 
     *                       the image can be resized as large as PHP can handle it.
     * @param bool $enable_cache Specifies if the caching function should be used.
     * @param string $cache_path The path to the directory where the rendered images will be stored.
     * @param int $max_cache_files The number of maximal images in the cache. If more 
     *                             images are added, old ones will be deleted.
     *
     * @return self
     */
    public function __construct(
        protected string $root_path = "./",
        protected ?int $max_size = 2000,
        protected bool $enable_cache = true,
        protected string $cache_path = "cache/",
        protected int $max_cache_files = 100
    )
    {
        if($this->enable_cache)
        {
            if(!is_dir($this->cache_path))
                mkdir($this->cache_path);
        }
    }
    
    # ~ 
    
    # Prepare Functions
    
    # ~
        
    /**
     * string
     * 
     * Parses parameters of a string and calls the prepare function.
     * @see prepare()
     *
     * @param  mixed $string
     * @return self
     */
    public function string(string $string)
    {
        $this->html_src = $string;
        
        $parsed_url = parse_url($string);
        
        parse_str($parsed_url["query"] ?? "", $parsed_string);
        
        if(empty($parsed_url["path"]))
            throw new \Exception("At least filename must be given");
        
        $this->prepare(
            filename: $parsed_url["path"],
            top: isset($parsed_string["t"]) ? intval($parsed_string["t"]) : null,
            right: isset($parsed_string["r"]) ? intval($parsed_string["r"]) : null,
            bottom: isset($parsed_string["b"]) ? intval($parsed_string["b"]) : null,
            left: isset($parsed_string["l"]) ? intval($parsed_string["l"]) : null,
            width: isset($parsed_string["w"]) ? intval($parsed_string["w"]) : null,
            height: isset($parsed_string["h"]) ? intval($parsed_string["h"]) : null,
            quality: isset($parsed_string["q"]) ? intval($parsed_string["q"]) : null,
            format: $parsed_string["f"] ?? null
        );
        
        return $this;
    }
        
    /**
     * prepare
     * 
     * Captures and validates every parameter for the image.
     * 
     * @param string $filename The Filename of the image. Will be prefixed with $root_path (@see __construct)
     * @param ?int $width The output width of the image. If not set, it will be calculated automatically.
     * @param ?int $height The output height of the image. If not set, it will be calculated automatically.
     *     If neither $width or $height are set, the image will have the original width and height.
     *     If both $width and $height are set and do not match the original aspect ratio, the image will be cropped.
     * @param ?int $quality The quality of the output image. 
     *     E.g. for jpeg quality, @see https://www.php.net/manual/de/function.imagejpeg.php#refsect1-function.imagejpeg-parameters 
     * @param ?string $format The output format. If not set, the format will be the same as the original source.
     *     @see SUPPORTED_FORMATS
     * 
     * @return self
     */
    public function prepare(
        string $filename,
        ?int $top = null,
        ?int $right = null,
        ?int $bottom = null,
        ?int $left = null,
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null
    )
    {
        // Capture Parameters    
    
        # Src 
        if(!isset($this->html_src))
            $this->html_src = $filename;
            
        # Path
        $this->original_path = $this->root_path . $filename;
        
        # Crop
        $this->output_top = $top ?? 0;
        $this->output_right = $right ?? 0;
        $this->output_bottom = $bottom ?? 0;
        $this->output_left = $left ?? 0;
        
        # Original Dimensions
        list($this->original_width, $this->original_height) = getimagesize($this->original_path);
        
        # Output Dimensions
        $this->output_width = $width;
        $this->output_height = $height;
        
        # Quality
        $this->output_quality = $quality ?? -1;
        
        # Format
        $this->original_format = pathinfo($this->original_path, PATHINFO_EXTENSION);
        $this->output_format = $format ?? $this->original_format;
        
        
        // Validate and Prepare
        
        $this->validate_format();
        $this->calculate_rectangles();
        return $this;
    }
    
    # ~
    
    # Image Function
    
    # ~
        
    /**
     * image
     * 
     * If not cached, renders and saves the prepared image to cache.
     * Then, outputs the corresponding header and image to the user. Finally, ends the script.
     *
     * @return void
     */
    public function image()
    {
        
        if($this->enable_cache)
        {
            $this->calculate_cached_path();
        
            if(!file_exists($this->cached_path))
            {
                $this->load_original_image();
                
                $this->render_image();
                imagedestroy($this->original_image);
                
                $this->cache_rendered_image();
                imagedestroy($this->rendered_image);
                
                $this->invalidate_cache();
            }
            
            $this->output_cached_image();
        }
        else
        {
            $this->load_original_image();
            
            $this->render_image();
            imagedestroy($this->original_image);
            
            $this->output_rendered_image();
            imagedestroy($this->rendered_image);
        }
        
        exit;
    }
    
    # ~
    
    # Helper Functions
    
    # ~
        
    /**
     * validate_format
     * 
     * Checks if the format specified is actually supported. If not, throws an exception.
     *
     * @return void
     */
    protected function validate_format()
    {
        if($this->original_format == "jpeg") $this->original_format = "jpg";
        if($this->output_format == "jpeg") $this->output_format = "jpg";
            
        if(!in_array($this->original_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Source image format $this->original_format is not supported.");
            
        if(!in_array($this->output_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Output image format $this->output_format is not supported.");
    }
    
    /**
     * calculate_rectangles
     * 
     * Calculates the coordinates and dimensions of the source and destination rectangles,
     * used later to resize the image. @see image()
     *
     * @return void
     */
    protected function calculate_rectangles()
    {
        // Import Values for easier reading
        
        $original_width = $this->original_width;
        $original_height = $this->original_height;
        
        $width = $this->output_width;
        $height = $this->output_height;
        
        $top = $this->output_top;
        $right = $this->output_right;
        $bottom = $this->output_bottom;
        $left = $this->output_left;
        
        $max_size = $this->max_size;
        
        $src_x = 0;
        $src_y = 0;
        $src_width = $original_width;
        $src_height = $original_height;
        
        
        // Util variables
        
        $crop_w = $src_width - $left - $right;
        $crop_h = $src_height - $top - $bottom;

        $src_ratio = $src_height / $src_width;
        
        
        // Default dimensions

        if(!isset($width) && !isset($height))
        {
            $width = $crop_w;
            $height = $crop_h;
        }
        
        
        // Auto calculation missing dimensions
        
        if(isset($height) && !isset($width))
            $width = $crop_w * ($height / $crop_h);
        
        else if(isset($width) && !isset($height))
            $height = $crop_h * ($width / $crop_w);
        
        
        // Max size

        if(isset($max_size))
        {
            $ratio = $height / $width;

            if($width > $max_size)
            {
                $width = $max_size;
                $height = $width * $ratio;
            }

            if($height > $max_size)
            {
                $height = $max_size;
                $width = $height / $ratio;
            }
        }
        
        
        // Crop image if aspect ratio changes

        $dst_ratio = $height / $width;

        $cut_w = $crop_w;
        $cut_h = $crop_w * $dst_ratio;

        if($cut_h > $crop_h)
        {
            $cut_h = $crop_h;
            $cut_w = $crop_h / $dst_ratio;
        }

        $src_x = $left + ($crop_w - $cut_w) / 2;
        $src_y = $top + ($crop_h - $cut_h) / 2;

        $src_width = $cut_w;
        $src_height = $cut_h;
        
        
        // Export Values
        
        $this->dst_x      = 0;
        $this->dst_y      = 0;
        $this->src_x      = intval(round($src_x));
        $this->src_y      = intval(round($src_y));
        $this->dst_width  = intval(round($width));
        $this->dst_height = intval(round($height));
        $this->src_width  = intval(round($src_width));
        $this->src_height = intval(round($src_height));
    }
        
    /**
     * calculate_cached_path
     * 
     * Calculates the path to the cached image.
     *
     * @return void
     */
    protected function calculate_cached_path()
    {
        $key = $this->original_path .
            "-" . $this->dst_x .
            "-" . $this->dst_y .
            "-" . $this->src_x .
            "-" . $this->src_y .
            "-" . $this->dst_width .
            "-" . $this->dst_height .
            "-" . $this->src_width .
            "-" . $this->src_height .
            "-" . $this->output_quality .
            "." . $this->output_format;
            
        $this->cached_path = $this->cache_path . urlencode($key);
    }
        
    /**
     * load_original_image
     * 
     * Loads the original image into a GdImage object.
     *
     * @return void
     */
    protected function load_original_image()
    {
        switch($this->original_format)
        {
            case "png":
                $this->original_image = imagecreatefrompng($this->original_path);
                break;
                
            case "jpg":
            default:
                $this->original_image = imagecreatefromjpeg($this->original_path);
                break;
        }
    }
        
    /**
     * render_image
     * 
     * Renders the image by resampling it.
     * @see calculate_rectangles()
     *
     * @return void
     */
    protected function render_image()
    {
        $this->rendered_image = imagecreatetruecolor($this->dst_width, $this->dst_height);
            
        imagealphablending($this->rendered_image, false);
        imagesavealpha($this->rendered_image, true);
        
        imagecopyresampled(
            $this->rendered_image,
            $this->original_image,
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
        
    /**
     * cache_rendered_image
     * 
     * Saves the rendered image to the cache location.
     * @see render_image()
     *
     * @return void
     */
    protected function cache_rendered_image()
    {
        switch($this->output_format)
        {
            case "png":
                imagepng($this->rendered_image, $this->cached_path, $this->output_quality);
                break;
                
            case "jpg":
            default:
                imagejpeg($this->rendered_image, $this->cached_path, $this->output_quality);
                break;
        }
    }
        
    /**
     * output_cached_image
     * 
     * Reads the contents of the cached image and outputs it and the corresponding headers to the user.
     * @see cache_rendered_image()
     *
     * @return void
     */
    protected function output_cached_image()
    {
        header('Content-Length: ' . filesize($this->cached_path));
        header("Content-Type: " . mime_content_type($this->cached_path));
        
        readfile($this->cached_path);
    }
        
    /**
     * invalidate_cache
     * 
     * When there are more files in the cache folder than $max_cache_files, they will be deleted.
     *
     * @return void
     */
    protected function invalidate_cache()
    {
        // Get Files in cache

        $all_files = scandir($this->cache_path);

        $files = array_diff($all_files, array('.', '..'));


        // Sort by last modified

        usort($files, function($a, $b) {
            return filemtime($this->cache_path . $a) - filemtime($this->cache_path . $b);
        });


        // If there are more files than $max_cache_files, delete them

        $files_to_delete = sizeof($files) - $this->max_cache_files;

        for($i = 0; $i < $files_to_delete; $i++)
        {
            unlink($this->cache_path . array_values($files)[$i]);
        }
    }
        
    /**
     * output_rendered_image
     * 
     * Outputs rendered image and corresponding headers to the user. 
     * @see render_image()
     *
     * @return void
     */
    protected function output_rendered_image()
    {
        switch($this->output_format)
        {
            case "png":
                header("Content-Type: image/png");
                imagepng($this->rendered_image, quality: $this->output_quality);
                break;
                
            case "jpg":
            default:
                header("Content-Type: image/jpeg");
                imagejpeg($this->rendered_image, quality: $this->output_quality);
                break;
        }
    }
        
    /**
     * html
     * 
     * Returns a HTML image tag, with the source of the image, and matching the 
     * width and height of the image after cropping. Useful for preventing layout shifting.
     *
     * @param  mixed $alt The alt text of the image.
     * @return string The HTML image tag. Example:    <img src="image.jpg?w=800" 
     *                                                 width="800" height="600" alt="Alt text.">
     */
    public function html(?string $alt = null)
    {
        if(!file_exists($this->original_path))
            return;
        
        $src = 'src="' . $this->html_src . '"';
        $width = 'width="' . $this->dst_width . '"';
        $height = 'height="' . $this->dst_height . '"';
        $alt = isset($alt) ? 'alt="' . $alt . '"' : '';
        
        return "<img $src $width $height $alt>";
    }
}