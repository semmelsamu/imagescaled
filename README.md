# Imagescaled

> Small image scaling and cropping class with simple caching for PHP

## Setup

Copy `imagescaled.php` a static location on your webserver and include the file. Use the namespace `semmelsamu`:
```php
include("imagescaler.php");
use \semmelsamu\Imagescaled;
```

## Functions

### Imagescaled

```php
new Imagescaled($image, [$auto, $cache, $max_size]);
```
Create a new Imagescaler.

**Prameters**
- `$image`
    - The path/filename of the image, e.g. `path/to/your/image.jpg`.
    - Type: `string`
- `$auto`
    - States if the scaler yould automatically scale and output your image with arguments passed via GET in the URL [see here](#auto-scaling).
    - Type: `bool`
    - Default: `true`
- `$cache`
    - The folder where already scaled images should be cached. If set to `false`, scaled images will not be cached.
    - Type: `bool|string`
    - Default: `cache/`
- `$max_size`
    - The maximum size of an image's edge. If set to `false`, images don't have a maximum size. This is not recommended.
    - Type: `bool|int`
    - Default: `2000`

### output

```php
Imagescaled::output([$width, $height, $size, $top, $right, $bottom, $left, $format, $quality]) 
```

Output an image. **Every parameter is optional.**

- `$width` and `$height`
    - The width and height of the scaled image. If only one is given, the other will be automatically calculated.
    - Type: `int`
- `$size`
    - The size of the smallest edge of the scaled image.
    - Type: `int`
- `$top`, `$right`, `$bottom` and `$left`
    - The amount of pixels cropped into the image from the top, right, bottom and left before the image is scaled.
    - Type: `int`
    - Default: `0`
- `$format`
    - The image type of the image output.
    - Type: `"jpg"|"png"`
- `$quality`
    - The quality of the image output. For more information, see in the PHP manual for [JPGs](https://www.php.net/manual/en/function.imagejpeg.php) and [PNGs](https://www.php.net/manual/en/function.imagepng.php) respectively.
    - Type: `int`

## Auto scaling

If `$auto` is set to `true`, images will be automatically scaled via GET-parameters in the URL:

```
www.url.to/your/image.jpg?w=500
```

will produce an image with a width of 500 pixels.

### Abbreviations
- `w` for `$width`
- `h` for `$height`
- `s` for `$size`
- `t` for `$top`
- `r` for `$right`
- `b` for `$bottom`
- `l` for `$left`
- `f` for `$format`
- `q` for `$quality`
