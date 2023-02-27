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
        $this->string = $string;
        
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
        ?int $width = null,
        ?int $height = null,
        ?int $quality = null,
        ?string $format = null
    )
    {
        // Capture Parameters    
    
        $this->original_filename = $filename;
        $this->original_root_filename = $this->root_path . $filename;
        
        list($this->original_width, $this->original_height) = getimagesize($this->original_root_filename);
        
        $this->width = $width;
        $this->height = $height;
        
        $this->quality = $quality ?? -1;
        
        
        // Validate Format 
        
        $this->original_format = pathinfo($this->original_root_filename, PATHINFO_EXTENSION);
        $this->output_format = $format;
        
        if(!isset($this->output_format))
            $this->output_format = $this->original_format;
            
        if($this->original_format == "jpeg") $this->original_format = "jpg";
        if($this->output_format == "jpeg") $this->output_format = "jpg";
            
        if(!in_array($this->original_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Source image format $this->original_format is not supported.");
            
        if(!in_array($this->output_format, self::SUPPORTED_FORMATS))
            throw new \Exception("Output image format $this->output_format is not supported.");
        
        
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
        $this->load_original_image();
        $this->render_image();
        
        if($this->enable_cache)
        {
            $this->calculate_path_to_cached_image();
        
            if(!file_exists($this->path_to_cached_image))
            {
                $this->cache_rendered_image();
            }
            
            $this->output_cached_image();
        }
        else
        {
            $this->output_rendered_image();
        }
        
        
        exit;
    }
    
    # ~
    
    # Helper Functions
    
    # ~
    
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
        
        if(isset($this->max_size))
        {
            if(isset($output_width) && $output_width > $this->max_size)
                $output_width = $this->max_size;
            
            if(isset($output_height) && $output_height > $this->max_size)
                $output_height = $this->max_size;
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
    
    protected function calculate_path_to_cached_image()
    {
        $key = $this->original_root_filename .
            "-" . $this->dst_x .
            "-" . $this->dst_y .
            "-" . $this->src_x .
            "-" . $this->src_y .
            "-" . $this->dst_width .
            "-" . $this->dst_height .
            "-" . $this->src_width .
            "-" . $this->src_height .
            "-" . $this->quality .
            "." . $this->output_format;
            
        $this->path_to_cached_image = $this->cache_path . urlencode($key);
    }
    
    protected function load_original_image()
    {
        switch($this->original_format)
        {
            case "png":
                $this->original_image = imagecreatefrompng($this->original_root_filename);
                break;
                
            case "jpg":
            default:
                $this->original_image = imagecreatefromjpeg($this->original_root_filename);
                break;
        }
    }
    
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
    
    protected function cache_rendered_image()
    {
        switch($this->output_format)
        {
            case "png":
                imagepng($this->rendered_image, $this->path_to_cached_image, $this->quality);
                break;
                
            case "jpg":
            default:
                imagejpeg($this->rendered_image, $this->path_to_cached_image, $this->quality);
                break;
        }
    }
    
    protected function output_cached_image()
    {
        header("Content-Type: " . mime_content_type($this->path_to_cached_image));
        header('Content-Length: ' . filesize($this->path_to_cached_image));
        readfile($this->path_to_cached_image);
    }
    
    protected function output_rendered_image()
    {
        switch($this->output_format)
        {
            case "png":
                header("Content-Type: image/png");
                imagepng($this->rendered_image, quality: $this->quality);
                break;
                
            case "jpg":
            default:
                header("Content-Type: image/jpeg");
                imagejpeg($this->rendered_image, quality: $this->quality);
                break;
        }
    }
    
    public function html(?string $alt = null)
    {
        $src = 'src="' . ($this->string ?? $this->original_filename) . '"';
        $width = 'width="' . $this->dst_width . '"';
        $height = 'height="' . $this->dst_height . '"';
        $alt = isset($alt) ? 'alt="' . $alt . '"' : '';
        
        return "<img $src $width $height $alt>";
    }
}