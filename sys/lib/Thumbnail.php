<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Thumbnail {
  private $object = null;
  private static $debug = true;

  public function __construct ($filepath = '', $class = '', $options = array ()) {
    Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . $class . EXT, '[Thumbnail] 載入物件失敗。');
    $class = 'Thumbnail' . $class;
    $this->object = new $class ($filepath, $options);
  }

  public static function mustError () {
    throw new ThumbnailException (call_user_func_array ('sprintf', func_get_args ()));
  }
  
  public static function error () {
    if (self::$debug)
      throw new ThumbnailException (call_user_func_array ('sprintf', func_get_args ()));
    else
      return false;
  }

  public function getObject () {
    return $this->object;
  }

  public static function createGd ($filepath, $options = array ()) {
    $uti = new Thumbnail ($filepath, 'Gd', $options);
    return $uti->getObject ();
  }

  public static function createImagick ($filepath, $options = array ()) {
    $uti = new Thumbnail ($filepath, 'Imagick', $options);
    return $uti->getObject ();
  }

  public static function createGdBlock9 ($files, $save) {
    Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Gd' . EXT, '[Thumbnail] 載入物件失敗。');
    return call_user_func_array (array ('ThumbnailGd', 'block9'), array ($files, $save));
  }

  public static function createImagickBlock9 ($files, $save) {
    Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Imagick' . EXT, '[Thumbnail] 載入物件失敗。');
    return call_user_func_array (array ('ThumbnailImagick', 'block9'), array ($files, $save));
  }

  public static function createGdPhotos ($files, $save) {
    Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Gd' . EXT, '[Thumbnail] 載入物件失敗。');
    return call_user_func_array (array ('ThumbnailGd', 'photos'), array ($files, $save));
  }
  
  public static function createImagickPhotos ($files, $save) {
    Load::sysLib ('ThumbnailDrivers' . DIRECTORY_SEPARATOR . 'Imagick' . EXT, '[Thumbnail] 載入物件失敗。');
    return call_user_func_array (array ('ThumbnailImagick', 'photos'), array ($files, $save));
  }
}

class ThumbnailException extends Exception {}

class ThumbnailDimension {
  private $width, $height;

  public function __construct ($width, $height) {
    $this->width = intval ($width);
    $this->height = intval ($height);
    (is_numeric ($this->width) && is_numeric ($this->height) && $this->width > 0 && $this->height > 0) || Thumbnail::mustError ('參數格式錯誤。Width：' . $this->width . '，Height：' . $this->height);
  }

  public function width () {
    return $this->width;
  }

  public function height () {
    return $this->height;
  }
}

class ThumbnailBase {
  private $class = null;

  protected $filepath = null;
  protected $mime = null;
  protected $format = null;
  protected $image = null;
  protected $dimension = null;
  protected $config = array ();

  public function __construct ($filepath, $options = array ()) {
    Load::sysFunc ('file.php');

    is_file ($filepath) && is_readable ($filepath) || Thumbnail::mustError ('[ImageBaseUtility] 檔案不可讀取，或者不存在。Path：' . $filepath);
    
    $this->class = get_called_class ();
    $this->filepath = $filepath;

    ($this->config = config ('thumbnail', $this->class)) || Thumbnail::mustError ('[ImageBaseUtility] 沒有載入 Config。Class Name：' . $this->class);
    $this->setOptions ($options);
   
    $this->init ();
  }

  public function getFormat () {
    return $this->format;
  }

  protected function setOptions ($options) {
    $this->config = array_merge ($this->config, $options);
  }

  protected function init () {
    if (!function_exists ('mime_content_type'))
      Thumbnail::mustError ('[ThumbnailBase] 沒有載入 mime_content_type 函式。');

    if (!$this->mime = mime_content_type ($this->filepath))
      Thumbnail::mustError ('[ThumbnailBase] 取不到檔案的 mime。Mime：' . $this->mime);

    if (($this->format = get_extension_by_mime ($this->mime)) === false || !in_array ($this->format, $this->config['allow']))
      Thumbnail::mustError ('[ThumbnailBase] 找尋不到符合的格式，或者不支援此檔案格式。');

    switch ($this->class) {
      case 'ThumbnailImagick':
        if (!$this->image = new Imagick ($this->filepath))
          Thumbnail::mustError ('[ThumbnailBase] Create image 失敗。');
          break;
      
      case 'ThumbnailGd':
        if (!$this->image = $this->getOldImage ($this->format))
          Thumbnail::mustError ('[ThumbnailBase] Create image 失敗。');
        break;
    }

    $this->dimension = $this->getDimension ($this->image);
  }

  public function getImage () {
    return $this->image;
  }

  protected function calcImageSizePercent ($percent, $dimension) {
    return new ThumbnailDimension (ceil ($dimension->width () * $percent / 100), ceil ($dimension->height () * $percent / 100));
  }

  protected function calcWidth ($oldDimension, $newDimension) {
    $newWidthPercentage = 100 * $newDimension->width () / $oldDimension->width ();
    $height = ceil ($oldDimension->height () * $newWidthPercentage / 100);
    return new ThumbnailDimension ($newDimension->width (), $height);
  }

  protected function calcHeight ($oldDimension, $newDimension) {
    $newHeightPercentage  = 100 * $newDimension->height () / $oldDimension->height ();
    $width = ceil ($oldDimension->width () * $newHeightPercentage / 100);
    return new ThumbnailDimension ($width, $newDimension->height ());
  }

  protected function calcImageSize ($oldDimension, $newDimension) {
    $newSize = new ThumbnailDimension ($oldDimension->width (), $oldDimension->height ());

    if ($newDimension->width () > 0) {
      $newSize = $this->calcWidth ($oldDimension, $newDimension);
      ($newDimension->height () > 0) && ($newSize->height () > $newDimension->height ()) && $newSize = $this->calcHeight ($oldDimension, $newDimension);
    }
    if ($newDimension->height () > 0) {
      $newSize = $this->calcHeight ($oldDimension, $newDimension);
      ($newDimension->width () > 0) && ($newSize->width () > $newDimension->width ()) && $newSize = $this->calcWidth ($oldDimension, $newDimension);
    }
    return $newSize;
  }

  protected function calcImageSizeStrict ($oldDimension, $newDimension) {
    $newSize = new ThumbnailDimension ($newDimension->width (), $newDimension->height ());

    if ($newDimension->width () >= $newDimension->height ()) {
      if ($oldDimension->width () > $oldDimension->height ())  {
        $newSize = $this->calcHeight ($oldDimension, $newDimension);
        $newSize->width () < $newDimension->width () && $newSize = $this->calcWidth ($oldDimension, $newDimension);
      } else if ($oldDimension->height () >= $oldDimension->width ()) {
        $newSize = $this->calcWidth ($oldDimension, $newDimension);
        $newSize->height () < $newDimension->height () && $newSize = $this->calcHeight ($oldDimension, $newDimension);
      }
    } else if ($newDimension->height () > $newDimension->width ()) {
      if ($oldDimension->width () >= $oldDimension->height ()) {
        $newSize = $this->calcWidth ($oldDimension, $newDimension);
        $newSize->height () < $newDimension->height () && $newSize = $this->calcHeight ($oldDimension, $newDimension);
      } else if ($oldDimension->height () > $oldDimension->width ()) {
        $newSize = $this->calcHeight ($oldDimension, $newDimension);
        $newSize->width () < $newDimension->width () && $newSize = $this->calcWidth ($oldDimension, $newDimension);
      }
    }
    return $newSize;
  }
}