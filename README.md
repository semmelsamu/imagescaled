# imgs

> Small image scaler and cropper with simple caching for PHP 

## Setup

Copy `src/imgs.php` a static location on your webserver and include the file. Use the namespace `semmelsamu`:
```php
include("imgs.php");
use \semmelsamu\Imgs;
```

## Imgs

```php
new Imgs($path, [$auto, $cache, $cache_expires, $max_size]) : void
```

Create a new Imagescaler.

#### Parameters

- `$path`
    - The path/filename of the image, e.g. `path/to/your/image.jpg`.
    - Type: `string`
- `$auto`
    - States if the scaler yould automatically scale and output your image with arguments passed via GET in the URL [see here](#auto-scaling) and deletes expired cached images.
    - Type: `bool`
    - Default: `true`
- `$cache`
    - The folder where already scaled images should be cached. If set to `false`, scaled images will not be cached.
    - Type: `bool|string`
    - Default: `cache/`
- `$cache_expires`
    - The expiration time for every cached element.
    - Type: `int`
    - Default: `86400` (= 1 day)
- `$max_size`
    - The maximum size of an image's edge. If set to `false`, images don't have a maximum size (not recommended).
    - Type: `bool|int`
    - Default: `2000`


### image

```php
Imgs::image([$width, $height, $size, $top, $right, $bottom, $left, $format, $quality]) : bool
```

Output an image. **Every parameter is optional.**

#### Parameters

- `$width` and `$height`
    - The width and height of the scaled image. If only one is given, the other will be automatically calculated.
    - Type: `int`
- `$crop`
    - States if the image should be automatically cropped if the new dimensions don't match its native resolution
    - Type: `bool`
    - Default: `true`
- `$size`
    - The size of the smallest edge of the scaled image.
    - Type: `int`
- `$top`, `$right`, `$bottom` and `$left`
    - The amount of pixels cropped into the image from the top, right, bottom and left before the image is scaled.
    - Type: `int`
    - Default: `0`
- `$format`
    - The image type.
    - Type: `"jpg"|"png"`
- `$quality`
    - The quality of the image. For more information, see in the PHP manual for [JPGs](https://www.php.net/manual/en/function.imagejpeg.php) and [PNGs](https://www.php.net/manual/en/function.imagepng.php) respectively.
    - Type: `int`
    - Default: `-1`

#### Return values

Returns `true` if the image could be processed, otherwise it returns `false`.


### empty_cache

```php
Imgs::empty_cache() : void
```

Deletes every cached element older than `$cache_expires` seconds. By default the expiration time is set to 1 day.


## Auto scaling

If `$auto` is set to `true`, images will be automatically scaled via GET-parameters in the URL:

```
www.url.to/your/image.jpg?w=500
```

will produce an image with a width of 500 pixels.

|Parameter|Abbreviation in GET|
| --- | --- |
|`$width` | `w` |
| `$height` | `h` |
| `$size` | `s` |
| `$crop` | `c` |
| `$top` | `t` |
| `$right` | `r` |
| `$bottom` | `b` |
| `$left` | `l` |
| `$format` | `f` |
| `$quality` | `q` |
