# Imagescaler

> Small image scaler class for PHP

## Installation

Copy this repo to a static location on your webserver.
Include the `index.php` file:
```php
include("imagescaler/index.php");
```

## Setup

Use the namespace `semmelsamu` and create a new instance of the scaler:
```php
use \semmelsamu\Imagescaler;
$imagescaler = new Imagescaler();
```

## Functions

### valid

Check if the Imagescaler can work with the given image
```php
$imagescaler->valid(string $image): bool
```
- `$image`: Path to the image
- `return`: Returns true if the scaler can work with the given image

### import

Import an image
```php
$imagescaler->import(string $image): void
```
- `$image`: Path to the image

### get

Automatically detect commands via GET and scale the image
```php
$imagescaler->get(): void
```

### valid

Check if the Imagescaler can work with the given image
```php
$imagescaler->scale(int $scale = -1, int $width = -1, int $height = -1): void
```
- `$scale`: The size of the smallest side
- `$width`: The new width of the image
- `$height`: The new height of the imageimage

Only one parameter can be processed. If no size is specified, the image will be scaled to 256 pixels.

### get

Output the image
```php
$imagescaler->output(): void
```

## Auto-scale

If you create a new instance of the Imagescaler and give it the image as a parameter, it will automatically import, scale and output it.

```php
new Imagescaler($image);
```
- `$image`: Path to the image