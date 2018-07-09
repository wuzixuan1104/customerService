<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class ThumbnailGd extends ThumbnailBase {

  public function __construct ($filepame, $options = array ()) {
    parent::__construct ($filepame, $options);
  }

  protected function getOldImage ($format) {
    switch ($format) {
      case 'gif':  return imagecreatefromgif ($this->filepath); break;
      case 'jpg': return imagecreatefromjpeg ($this->filepath); break;
      case 'png': return imagecreatefrompng ($this->filepath); break;
      default: Thumbnail::mustError ('[ThumbnailGd] 找尋不到符合的格式，或者不支援此檔案格式。Format：' . $format); return null; break;
    }
  }

  public function getDimension ($image = null) {
    $image = $image ? $image : $this->getOldImage ($this->format);
    return new ThumbnailDimension (imagesx ($image), imagesy ($image));
  }

  private function _preserveAlpha ($image) {
    if (($this->format == 'png') && ($this->config['preserve_alpha'] === true)) {
      imagealphablending ($image, false);
      imagefill ($image, 0, 0, imagecolorallocatealpha ($image, $this->config['alpha_maskColor'][0], $this->config['alpha_maskColor'][1], $this->config['alpha_maskColor'][2], 0));
      imagesavealpha ($image, true);
    }

    if (($this->format == 'gif') && ($this->config['preserve_transparency'] === true)) {
      imagecolortransparent ($image, imagecolorallocate ($image, $this->config['transparency_mask_color'][0], $this->config['transparency_mask_color'][1], $this->config['transparency_mask_color'][2]));
      imagetruecolortopalette ($image, true, 256);
    }

    return $image;
  }

  private function _copyReSampled ($newImage, $oldImage, $newX, $newY, $oldX, $oldY, $newWidth, $newHeight, $oldWidth, $oldHeight) {
    imagecopyresampled ($newImage, $oldImage, $newX, $newY, $oldX, $oldY, $newWidth, $newHeight, $oldWidth, $oldHeight);
    return $this->_updateImage ($newImage);
  }

  private function _updateImage ($image) {
    $this->image = $image;
    $this->dimension = $this->getDimension ($this->image);
    return $this;
  }

  public function save ($save) {
    if (!$save && !Thumbnail::error ('[ThumbnailGd] 錯誤的儲存路徑，Path：' . $save))
      return false;

    isset ($this->config['interlace']) && imageinterlace ($this->image, $this->config['interlace'] ? 1 : 0);

    switch ($this->format) {
      case 'jpg': return @imagejpeg ($this->image, $save, $this->config['jpeg_quality']);
      case 'gif': return @imagegif ($this->image, $save);
      case 'png': return @imagepng ($this->image, $save);
      default: return false;
    }
  }

  private static function colorHex2Rgb ($hex) {
    if (($hex = str_replace ('#', '', $hex)) && ((strlen ($hex) == 3) || (strlen ($hex) == 6))) {
      if(strlen ($hex) == 3) {
        $r = hexdec (substr ($hex, 0, 1) . substr ($hex, 0, 1));
        $g = hexdec (substr ($hex, 1, 1) . substr ($hex, 1, 1));
        $b = hexdec (substr ($hex, 2, 1) . substr ($hex, 2, 1));
      } else {
        $r = hexdec (substr ($hex, 0, 2));
        $g = hexdec (substr ($hex, 2, 2));
        $b = hexdec (substr ($hex, 4, 2));
      }
      return array ($r, $g, $b);
    } else {
      return array ();
    }
  }

  private static function verifyColor (&$color) {
    $color = is_string ($color) ? self::colorHex2Rgb ($color) : $color;
    return is_array ($color) && (count ($color) == 3) && ($color[0] >= 0) && ($color[0] <= 255) && ($color[1] >= 0) && ($color[1] <= 255) && ($color[2] >= 0) && ($color[2] <= 255);
  }

  public function pad ($width, $height, $color = array (255, 255, 255)) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    if (!ThumbnailGd::verifyColor ($color) && !Thumbnail::error ('[ThumbnailGd] 色碼格式錯誤，目前只支援字串 HEX、RGB 陣列格式。'))
      return $this;

    if (($width < $this->dimension->width ()) || ($height < $this->dimension->height ()))
      $this->resize ($width, $height);

    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($width, $height) : imagecreate ($width, $height);
    imagefill ($newImage, 0, 0, imagecolorallocate ($newImage, $color[0], $color[1], $color[2]));

    return $this->_copyReSampled ($newImage, $this->image, intval (($width - $this->dimension->width ()) / 2), intval (($height - $this->dimension->height ()) / 2), 0, 0, $this->dimension->width (), $this->dimension->height (), $this->dimension->width (), $this->dimension->height ());
  }

  private function createNewDimension ($width, $height) {
    return new ThumbnailDimension (!$this->config['resize_up'] && ($width > $this->dimension->width ()) ? $this->dimension->width () : $width, !$this->config['resize_up'] && ($height > $this->dimension->height ()) ? $this->dimension->height () : $height);
  }

  public function resize ($width, $height, $method = 'both') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);

    switch ($method) {
      case 'b': case 'both': default: $newDimension = $this->calcImageSize ($this->dimension, $newDimension); break;
      case 'w': case 'width': $newDimension = $this->calcWidth ($this->dimension, $newDimension); break;
      case 'h': case 'height': $newDimension = $this->calcHeight ($this->dimension, $newDimension); break;
    }

    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($newDimension->width (), $newDimension->height ()) : imagecreate ($newDimension->width (), $newDimension->height ());
    $newImage = $this->_preserveAlpha ($newImage);

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, 0, 0, $newDimension->width (), $newDimension->height (), $this->dimension->width (), $this->dimension->height ());
  }

  public function adaptiveResizePercent ($width, $height, $percent) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (!(($percent > -1) && ($percent < 101)) && !Thumbnail::error ('[ThumbnailGd] 比例錯誤，Percent：' . $percent . '，百分比要在 0 ~ 100 之間。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);
    $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
    $this->resize ($newDimension->width (), $newDimension->height ());
    $newDimension = $this->createNewDimension ($width, $height);

    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($newDimension->width (), $newDimension->height ()) : imagecreate ($newDimension->width (), $newDimension->height ());
    $newImage = $this->_preserveAlpha ($newImage);

    $cropX = $cropY = 0;

    if ($this->dimension->width () > $newDimension->width ())
      $cropX = intval (($percent / 100) * ($this->dimension->width () - $newDimension->width ()));
    else if ($this->dimension->height () > $newDimension->height ())
      $cropY = intval (($percent / 100) * ($this->dimension->height () - $newDimension->height ()));

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, $cropX, $cropY, $newDimension->width (), $newDimension->height (), $newDimension->width (), $newDimension->height ());
  }

  public function adaptiveResize ($width, $height) {
    return $this->adaptiveResizePercent ($width, $height, 50);
  }

  public function resizePercent ($percent = 0) {
    if (!($percent > 0) && !Thumbnail::error ('[ThumbnailGd] 比例錯誤，Percent：' . $percent . '，百分比要大於 1。'))
      return $this;

    if ($percent == 100)
      return $this;

    $newDimension = $this->calcImageSizePercent ($percent, $this->dimension);
    return $this->resize ($newDimension->width (), $newDimension->height ());
  }

  public function crop ($startX, $startY, $width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailGd] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
      return $this;

    if (($startX == 0) && ($startY == 0) && ($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
    $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;
    $startX = ($startX + $width) > $this->dimension->width () ? $this->dimension->width () - $width : $startX;
    $startY = ($startY + $height) > $this->dimension->height () ? $this->dimension->height () - $height : $startY;
    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($width, $height) : imagecreate ($width, $height);
    $newImage = $this->_preserveAlpha ($newImage);

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, $startX, $startY, $width, $height, $width, $height);
  }

  public function cropFromCenter ($width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    if (($width > $this->dimension->width ()) && ($height > $this->dimension->height ()))
      return $this->pad ($width, $height);

    $startX = intval (($this->dimension->width () - $width) / 2);
    $startY = intval (($this->dimension->height () - $height) / 2);
    $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
    $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;

    return $this->crop ($startX, $startY, $width, $height);
  }

  public function rotate ($degree, $color = array (255, 255, 255)) {
    if (!function_exists ('imagerotate') && !Thumbnail::error ('[[ThumbnailGd] 沒有載入 imagerotate 函式。'))
      return $this;

    if (!is_numeric ($degree) && !Thumbnail::error ('[ThumbnailGd] 角度一定要是數字，Degree：' . $degree))
      return $this;

    if (!ThumbnailGd::verifyColor ($color) && !Thumbnail::error ('[ThumbnailGd] 色碼格式錯誤，目前只支援字串 HEX、RGB 陣列格式。'))
      return $this;

    if (!($degree % 360))
      return $this;

    $temp = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor (1, 1) : imagecreate (1, 1);
    $newImage = imagerotate ($this->image, 0 - $degree, imagecolorallocate ($temp, $color[0], $color[1], $color[2]));

    return $this->_updateImage ($newImage);
  }

  public function adaptiveResizeQuadrant ($width, $height, $item = 'c') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailGd] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);
    $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
    $this->resize ($newDimension->width (), $newDimension->height ());
    $newDimension = $this->createNewDimension ($width, $height);
    $newImage = function_exists ('imagecreatetruecolor') ? imagecreatetruecolor ($newDimension->width (), $newDimension->height ()) : imagecreate ($newDimension->width (), $newDimension->height ());
    $newImage = $this->_preserveAlpha ($newImage);

    $cropX = $cropY = 0;

    if ($this->dimension->width () > $newDimension->width ()) {
      switch ($item) {
        case 'l': case 'L': $cropX = 0; break;
        case 'r': case 'R': $cropX = intval ($this->dimension->width () - $newDimension->width ()); break;
        case 'c': case 'C': default: $cropX = intval (($this->dimension->width () - $newDimension->width ()) / 2); break;
      }
    } else if ($this->dimension->height () > $newDimension->height ()) {
      switch ($item) {
        case 't': case 'T': $cropY = 0; break;
        case 'b': case 'B': $cropY = intval ($this->dimension->height () - $newDimension->height ()); break;
        case 'c': case 'C': default: $cropY = intval(($this->dimension->height () - $newDimension->height ()) / 2); break;
      }
    }

    return $this->_copyReSampled ($newImage, $this->image, 0, 0, $cropX, $cropY, $newDimension->width (), $newDimension->height (), $newDimension->width (), $newDimension->height ());
  }

  public static function block9 ($files, $save, $interlace = null, $jpegQuality = 100) {
    if (!(count ($files) >= 9) && !Thumbnail::error ('[ThumbnailGd] 參數錯誤，Files Count：' . count ($files) . '，參數 Files 數量一定要大於等於 9。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailGd] 錯誤的儲存路徑，Path：' . $save))
      return $this;

    $positions = array (
      array ('left' =>   2, 'top' =>   2, 'width' => 130, 'height' => 130), array ('left' => 134, 'top' =>   2, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>   2, 'width' =>  64, 'height' =>  64),
      array ('left' => 134, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' =>   2, 'top' => 134, 'width' =>  64, 'height' =>  64),
      array ('left' =>  68, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 134, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' => 134, 'width' =>  64, 'height' =>  64),
    );

    $image = imagecreatetruecolor (266, 200);
    imagefill ($image, 0, 0, imagecolorallocate ($image, 255, 255, 255));
    for ($i = 0; $i < 9; $i++)
      imagecopymerge ($image, Thumbnail::createGd ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), $positions[$i]['left'], $positions[$i]['top'], 0, 0, $positions[$i]['width'], $positions[$i]['height'], 100);

    isset ($interlace) && imageinterlace ($image, $interlace ? 1 : 0);

    switch (pathinfo ($save, PATHINFO_EXTENSION)) {
      case 'jpg': return @imagejpeg ($image, $save, $jpegQuality);
      case 'gif': return @imagegif ($image, $save);
      default: case 'png': return @imagepng ($image, $save);
    }
  }

  public static function photos ($files, $save, $interlace = null, $jpegQuality = 100) {
    if (!(count ($files) >= 1) && !Thumbnail::error ('[ThumbnailGd] 參數錯誤，Files Count：' . count ($files), '參數 Files 數量一定要大於 1。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailGd] 錯誤的儲存路徑，Path：' . $save))
      return $this;

    $w = 1200;
    $h = 630;

    $image = imagecreatetruecolor ($w, $h);
    imagefill ($image, 0, 0, imagecolorallocate ($image, 255, 255, 255));

    $spacing = 5;
    $positions = array ();
    switch (count ($files)) {
      case 1: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h),); break;
      case 2: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h),); break;
      case 3: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 4: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 5: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 2 + $spacing, 'top' => 0, 'width' => $w / 2 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 6: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 7: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => 0, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing),); break;
      case 8: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 2 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 2 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
      default: case 9: $positions = array (array ('left' => 0, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => 0, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => $w / 3 + $spacing, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => 0, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => $h / 3 + $spacing, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing), array ('left' => ($w / 3 + $spacing) * 2, 'top' => ($h / 3 + $spacing) * 2, 'width' => $w / 3 - $spacing, 'height' => $h / 3 - $spacing),); break;
    }

    for ($i = 0; $i < count ($positions); $i++)
      imagecopymerge ($image, Thumbnail::createGd ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), $positions[$i]['left'], $positions[$i]['top'], 0, 0, $positions[$i]['width'], $positions[$i]['height'], 100);

    isset ($interlace) && imageinterlace ($image, $interlace ? 1 : 0);

    switch (pathinfo ($save, PATHINFO_EXTENSION)) {
      case 'jpg': return @imagejpeg ($image, $save, $jpegQuality);
      case 'gif': return @imagegif ($image, $save);
      default: case 'png': return @imagepng ($image, $save);
    }
  }
}
