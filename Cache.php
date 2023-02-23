<?php

declare(strict_types=1);

namespace semmelsamu\Imgs;


class Cache
{
    # ~
    
    # Constructor
    
    # ~
    
    public function __construct(
        protected string $path, 
        protected int $max_files
    )
    {
        if(!is_dir($path))
            mkdir($path);
            
        if($max_files < 1)
            throw new \Exception("Max files must be greater than 0");
    }
    
    # ~
    
    # Public functions
    
    # ~
    
    public function is_cached($filename)
    {
        return file_exists($this->path . $this->cache_name($filename));
    }
    
    public function save($filename, $data)
    {
        file_put_contents($this->path . $this->cache_name($filename), $data);
    }
    
    public function load($filename)
    {
        return file_get_contents($this->path . $this->cache_name($filename));
    }
    
    public function invalidate_cache()
    {
        // Get Files in cache
        
        $all_files = scandir($this->path);
        
        $files = array_diff($all_files, array('.', '..'));
        
        
        // Sort by last modified
        
        usort($files, function($a, $b) {
            return filemtime($this->path . $a) - filemtime($this->path . $b);
        });
        
        
        // If there are more files than $max_files, delete them
        
        $files_to_delete = sizeof($files) - $this->max_files;
        
        for($i = 0; $i < $files_to_delete; $i++)
        {
            unlink($this->path . array_values($files)[$i]);
        }
    }
    
    # ~
    
    # Util
    
    # ~
    
    protected function cache_name($filename)
    {
        return urlencode($filename);
    }
}