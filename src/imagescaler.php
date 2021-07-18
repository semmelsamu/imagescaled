<?php

namespace semmelsamu;

class Imagescaler 
{
    /**
     * Automatically import the image, detect commands via GET and scale the image, and output the image 
     */
    function __construct($image = null)
    {
        if(isset($image))
        {
            $this->import($image);
            $this->get();
            $this->output();
        }
    }

    /**
     * Check if the Imagescaler can work with the given image
     * @param string $image path to the image
     */
    function valid($image)
    {
        $valid_extentions = ["jpg", "jpeg"];
        $extention = substr($image, strrpos($image, ".")+1);
        return in_array($extention, $valid_extentions);
    }

    /**
     * Import an image
     * @param string $image path to the image
     */
    function import($image)
    {
        if(!$this->valid($image))
            throw new Exception("Image type not supported");

        $this->image = $image;
        $this->result = imagecreatefromjpeg($image);
    }

    /**
     * Automatically detect commands via GET and scale the image
     */
    function get()
    {
        if(isset($_GET["s"])) $this->scale(scale: $_GET["s"]);
        if(isset($_GET["w"])) $this->scale(width: $_GET["w"]);
        if(isset($_GET["h"])) $this->scale(height: $_GET["h"]);
    }

    /**
     * Scale the image
     * @param int $scale the size of the smallest side
     * @param int $width the new width of the image
     * @param int $height the new height of the image
     * Only one parameter can be processed. If no size is specified, the image will be scaled to 256 pixels.
     * TODO: force parameter, to strech the image if nessecary
     */
    function scale($scale = -1, $width = -1, $height = -1)
    {
        list($original_width, $original_height) = getimagesize($this->image);

        if($scale == -1 && $width == -1 && $height == -1)
            $scale = 256;

        if($scale != -1)
        {
            if($original_width < $original_height)
            {
                $new_width = $scale;
                $new_height = $new_width * ($original_height / $original_width);
            }
            else
            {
                $new_height = $scale;
                $new_width = $new_height * ($original_width / $original_height);
            }
        }
        else if($width != -1)
        {
            $new_width = $width;
            $new_height = $new_width * ($original_height / $original_width);
        }
        else
        {
            $new_height = $height;
            $new_width = $new_height * ($original_width / $original_height);
        }

        $result = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($result, $this->result, 0, 0, 0, 0, $new_width, $new_height, $original_width, $original_height);
        $this->result = $result;
    }

    /**
     * Output the image
     */
    function output()
    {
        header("Content-Type: image/jpeg");
        imagejpeg($this->result, null, 80);
        exit;
    }
}

?>