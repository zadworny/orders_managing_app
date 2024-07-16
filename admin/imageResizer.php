<?php
ini_set('memory_limit', '512M');

class ImageResizer {
    private $image = null;
    private $imageType;

    // Improved loading with error handling and memory efficiency
    public function load($filename): bool {
        $imageInfo = getimagesize($filename);
        if (!$imageInfo) {
            return false;
        }
        
        $this->imageType = $imageInfo[2];
        switch ($this->imageType) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($filename);
                break;
            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($filename);
                break;
            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($filename);
                break;
            default:
                // Unsupported image type
                return false;
        }

        return true;
    }

    // Improved save with error handling
    public function save($filename, $imageType = IMAGETYPE_JPEG, $compression = 70, $permissions = null): bool {
        $result = false;
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg($this->image, $filename, $compression);
                break;
            case IMAGETYPE_GIF:
                $result = imagegif($this->image, $filename);
                break;
            case IMAGETYPE_PNG:
                // Normalize compression for PNG
                $pngQuality = 9 - round($compression / 11.11);
                $result = imagepng($this->image, $filename, $pngQuality);
                break;
        }

        if ($result && $permissions !== null) {
            chmod($filename, $permissions);
        }

        return $result;
    }

    // Efficient resizing methods
    public function resizeToLongestSide($size) {
        $width = $this->getWidth();
        $height = $this->getHeight();
        
        if ($width > $height) {
            $this->resizeToWidth($size);
        } else {
            $this->resizeToHeight($size);
        }
    }

    public function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        $this->resize($width, $height);
    }

    public function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getHeight() * $scale / 100;
        $this->resize($width, $height);
    }

    // Revised resize method with error handling and optimized memory usage
    public function resize($width, $height) {
        $newImage = imagecreatetruecolor(intval($width), intval($height));
        if (!$newImage) {
            return false;
        }

        if (!imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, intval($width), intval($height), intval($this->getWidth()), intval($this->getHeight()))) {
            return false;
        }

        imagedestroy($this->image); // Immediately free memory of the old image
        $this->image = $newImage;

        return true;
    }

    public function getWidth(): ?int {
        return $this->image ? imagesx($this->image) : null;
    }

    public function getHeight(): ?int {
        return $this->image ? imagesy($this->image) : null;
    }

    // Improved destructor for memory management
    public function __destruct() {
        if ($this->image !== null) {
            imagedestroy($this->image);
            $this->image = null; // Ensure reference is cleared
        }
    }
}
