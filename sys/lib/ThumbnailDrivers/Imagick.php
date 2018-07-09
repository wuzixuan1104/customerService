<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class ThumbnailImagick extends ThumbnailBase {
  public function __construct ($filepame, $options = array ()) {
    parent::__construct ($filepame, $options);
  }

  public function getDimension ($image = null) {
    $image || $image = clone $this->image;

    if (!((($imagePage = $image->getImagePage ()) && isset ($imagePage['width'], $imagePage['height']) && $imagePage['width'] > 0 && $imagePage['height'] > 0) || (($imagePage = $image->getImageGeometry ()) && isset ($imagePage['width'], $imagePage['height']) && $imagePage['width'] > 0 && $imagePage['height'] > 0)))
      return Thumbnail::mustError ('[ThumbnailImagick] 無法取得尺寸。');

    return new ThumbnailDimension ($imagePage['width'], $imagePage['height']);
  }

  private function _machiningImageResize ($newDimension) {
    $newImage = clone $this->image;
    $newImage = $newImage->coalesceImages ();

    if ($this->format == 'gif')
      do {
        $newImage->thumbnailImage ($newDimension->width (), $newDimension->height (), false);
      } while ($newImage->nextImage () || !$newImage = $newImage->deconstructImages ());
    else
      $newImage->thumbnailImage ($newDimension->width (), $newDimension->height (), false);

    return $newImage;
  }

  private function _machiningImageCrop ($cropX, $cropY, $width, $height, $color = 'transparent') {
    $newImage = new Imagick ();
    $newImage->setFormat ($this->format);

    if ($this->format == 'gif') {
      $imagick = clone $this->image;
      $imagick = $imagick->coalesceImages ();
      
      do {
        $temp = new Imagick ();
        $temp->newImage ($width, $height, new ImagickPixel ($color));
        $imagick->chopImage ($cropX, $cropY, 0, 0);
        $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);

        $newImage->addImage ($temp);
        $newImage->setImageDelay ($imagick->getImageDelay ());
      } while ($imagick->nextImage ());
    } else {
      $imagick = clone $this->image;
      $imagick->chopImage ($cropX, $cropY, 0, 0);
      $newImage->newImage ($width, $height, new ImagickPixel ($color));
      $newImage->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0 );
    }
    return $newImage;
  }

  private function _machiningImageRotate ($degree, $color = 'transparent') {
    $newImage = new Imagick ();
    $newImage->setFormat ($this->format);
    $imagick = clone $this->image;

    if ($this->format == 'gif') {
      $imagick->coalesceImages();
      
      do {
        $temp = new Imagick ();
        $imagick->rotateImage (new ImagickPixel ($color), $degree);
        $newDimension = $this->getDimension ($imagick);
        $temp->newImage ($newDimension->width (), $newDimension->height (), new ImagickPixel ($color));
        $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
        $newImage->addImage ($temp);
        $newImage->setImageDelay ($imagick->getImageDelay ());
      } while ($imagick->nextImage ());
    } else {
      $imagick->rotateImage (new ImagickPixel ($color), $degree);
      $newDimension = $this->getDimension ($imagick);
      $newImage->newImage ($newDimension->width (), $newDimension->height (), new ImagickPixel ($color));
      $newImage->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
    }
    return $newImage;
  }

  private function _updateImage ($image) {
    $this->image = $image;
    $this->dimension = $this->getDimension ($image);
    return $this;
  }

  private function _machiningImageFilter ($radius, $sigma, $channel) {
    if ($this->format == 'gif') {
      $newImage = clone $this->image;
      $newImage = $newImage->coalesceImages ();
      
      do {
        $newImage->adaptiveBlurImage ($radius, $sigma, $channel);
      } while ($newImage->nextImage () || !$newImage = $newImage->deconstructImages ());
    } else {
      $newImage = clone $this->image;
      $newImage->adaptiveBlurImage ($radius, $sigma, $channel);
    }
    return $newImage;
  }

  private function _createFont ($font, $fontSize, $color, $alpha) {
    $draw = new ImagickDraw ();
    $draw->setFont ($font);
    $draw->setFontSize ($fontSize);
    $draw->setFillColor ($color);
    // $draw->setFillAlpha ($alpha);
    return $draw;
  }

  public function save ($save, $rawData = true) {
    return $save ? $this->image->writeImages ($save, $rawData) : Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save);
  }

  public function pad ($width, $height, $color = 'transparent') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
      return $this;

    if (($width < $this->dimension->width ()) || ($height < $this->dimension->height ()))
      $this->resize ($width, $height);

    $newImage = new Imagick ();
    $newImage->setFormat ($this->format);

    if ($this->format == 'gif') {
      $imagick = clone $this->image;
      $imagick = $imagick->coalesceImages ();
      do {
        $temp = new Imagick ();
        $temp->newImage ($width, $height, new ImagickPixel ($color));
        $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, intval (($width - $this->dimension->width ()) / 2), intval (($height - $this->dimension->height ()) / 2) );

        $newImage->addImage ($temp);
        $newImage->setImageDelay ($imagick->getImageDelay ());
      } while ($imagick->nextImage ());
    } else {
      $newImage->newImage ($width, $height, new ImagickPixel ($color));
      $newImage->compositeImage (clone $this->image, imagick::COMPOSITE_DEFAULT, intval (($width - $this->dimension->width ()) / 2), intval (($height - $this->dimension->height ()) / 2));
    }

    return $this->_updateImage ($newImage);
  }

  private function createNewDimension ($width, $height) {
    return new ThumbnailDimension (!$this->config['resize_up'] && ($width > $this->dimension->width ()) ? $this->dimension->width () : $width, !$this->config['resize_up'] && ($height > $this->dimension->height ()) ? $this->dimension->height () : $height);
  }

  public function resize ($width, $height, $method = 'b') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);

    switch ($method) {
      case 'b': case 'both': default: $newDimension = $this->calcImageSize ($this->dimension, $newDimension); break;
      case 'w': case 'width': $newDimension = $this->calcWidth ($this->dimension, $newDimension); break;
      case 'h': case 'height': $newDimension = $this->calcHeight ($this->dimension, $newDimension); break;
    }

    $workingImage = $this->_machiningImageResize ($newDimension);

    return $this->_updateImage ($workingImage);
  }

  public function adaptiveResizePercent ($width, $height, $percent) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (!(($percent > -1) && ($percent < 101)) && !Thumbnail::error ('[ThumbnailImagick] 比例錯誤，Percent：' . $percent . '，百分比要在 0 ~ 100 之間。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;
    
    $newDimension = $this->createNewDimension ($width, $height);
    $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
    $this->resize ($newDimension->width (), $newDimension->height ());
    $newDimension = $this->createNewDimension ($width, $height);

    $cropX = $cropY = 0;

    if ($this->dimension->width () > $newDimension->width ())
      $cropX = intval (($percent / 100) * ($this->dimension->width () - $newDimension->width ()));
    else if ($this->dimension->height () > $newDimension->height ())
      $cropY = intval (($percent / 100) * ($this->dimension->height () - $newDimension->height ()));

    $workingImage = $this->_machiningImageCrop ($cropX, $cropY, $newDimension->width (), $newDimension->height ());
    return $this->_updateImage ($workingImage);
  }

  public function adaptiveResize ($width, $height) {
    return $this->adaptiveResizePercent ($width, $height, 50);
  }

  public function resizePercent ($percent = 0) {
    if ($percent < 1 && !Thumbnail::error ('[ThumbnailImagick] 比例錯誤，Percent：' . $percent . '，百分比要大於 1。'))
      return $this;

    if ($percent == 100)
      return $this;

    $newDimension = $this->calcImageSizePercent ($percent, $this->dimension);
    return $this->resize ($newDimension->width (), $newDimension->height ());
  }

  public function crop ($startX, $startY, $width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailImagick] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
      return $this;

    if (($startX == 0) && ($startY == 0) && ($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $width  = $this->dimension->width () < $width ? $this->dimension->width () : $width;
    $height = $this->dimension->height () < $height ? $this->dimension->height () : $height;

    $startX + $width > $this->dimension->width () && $startX = $this->dimension->width () - $width;
    $startY + $height > $this->dimension->height () && $startY = $this->dimension->height () - $height;

    $workingImage = $this->_machiningImageCrop ($startX, $startY, $width, $height);
    return $this->_updateImage ($workingImage);
  }

  public function cropFromCenter ($width, $height) {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
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

  public function rotate ($degree, $color = 'transparent') {
    if (!is_numeric ($degree) && !Thumbnail::error ('[ThumbnailImagick] 角度一定要是數字，Degree：' . $degree))
      return $this;

    if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
      return $this;

    if (!($degree % 360))
      return $this;

    $workingImage = $this->_machiningImageRotate ($degree, $color);

    return $this->_updateImage ($workingImage);
  }

  public function adaptiveResizeQuadrant ($width, $height, $item = 'c') {
    if (!((($width = intval ($width)) > 0) && (($height = intval ($height)) > 0)) && !Thumbnail::error ('[ThumbnailImagick] 新尺寸錯誤，Width：' . $width . '，Height：' . $height . '，尺寸寬高一定要大於 0。'))
      return $this;

    if (($width == $this->dimension->width ()) && ($height == $this->dimension->height ()))
      return $this;

    $newDimension = $this->createNewDimension ($width, $height);
    $newDimension = $this->calcImageSizeStrict ($this->dimension, $newDimension);
    $this->resize ($newDimension->width (), $newDimension->height ());
    $newDimension = $this->createNewDimension ($width, $height);
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

    $workingImage = $this->_machiningImageCrop ($cropX, $cropY, $newDimension->width (), $newDimension->height ());

    return $this->_updateImage ($workingImage);
  }

  public function filter ($radius, $sigma, $channel = Imagick::CHANNEL_DEFAULT) {
    $items = array (imagick::CHANNEL_UNDEFINED, imagick::CHANNEL_RED,     imagick::CHANNEL_GRAY,  imagick::CHANNEL_CYAN,
                    imagick::CHANNEL_GREEN,     imagick::CHANNEL_MAGENTA, imagick::CHANNEL_BLUE,  imagick::CHANNEL_YELLOW,
                    imagick::CHANNEL_ALPHA,     imagick::CHANNEL_OPACITY, imagick::CHANNEL_BLACK,
                    imagick::CHANNEL_INDEX,     imagick::CHANNEL_ALL,     imagick::CHANNEL_DEFAULT);

    if (!is_numeric ($radius) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Radius：' . $radius . '，參數 Radius 要為數字。'))
      return $this;

    if (!is_numeric ($sigma) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Sigma：' . $sigma . '，參數 Sigma 要為數字。'))
      return $this;

    if (!in_array ($channel, $items) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Channel：' . $channel . '，參數 Channel 格式不正確。'))
      return $this;

    $workingImage = $this->_machiningImageFilter ($radius, $sigma, $channel);

    return $this->_updateImage ($workingImage);
  }

  public function lomography () {
    $newImage = new Imagick ();
    $newImage->setFormat ($this->format);

    if ($this->format == 'gif') {
      $imagick = clone $this->image;
      $imagick = $imagick->coalesceImages ();
      
      do {
        $temp = new Imagick ();
        $imagick->setimagebackgroundcolor ("black");
        $imagick->gammaImage (0.75);
        $imagick->vignetteImage (0, max ($this->dimension->width (), $this->dimension->height ()) * 0.2, 0 - ($this->dimension->width () * 0.05), 0 - ($this->dimension->height () * 0.05));
        $temp->newImage ($this->dimension->width (), $this->dimension->height (), new ImagickPixel ('transparent'));
        $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);

        $newImage->addImage ($temp);
        $newImage->setImageDelay ($imagick->getImageDelay ());
      } while ($imagick->nextImage ());
    } else {
      $newImage = clone $this->image;
      $newImage->setimagebackgroundcolor("black");
      $newImage->gammaImage (0.75);
      $newImage->vignetteImage (0, max ($this->dimension->width (), $this->dimension->height ()) * 0.2, 0 - ($this->dimension->width () * 0.05), 0 - ($this->dimension->height () * 0.05));
    }
    return $this->_updateImage ($newImage);
  }

  public function getAnalysisDatas ($maxCount = 10) {
    if (!($maxCount > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Max Count：' . $maxCount . '，參數 Max Count 一定要大於 0。'))
      return array ();

    $temp = clone $this->image;

    $temp->quantizeImage ($maxCount, Imagick::COLORSPACE_RGB, 0, false, false );
    $pixels = $temp->getImageHistogram ();

    $datas = array ();
    $index = 0;
    $pixelCount = $this->dimension->width () * $this->dimension->height ();

    if ($pixels && $maxCount)
      foreach ($pixels as $pixel)
        if ($index++ < $maxCount)
          array_push ($datas, array ('color' => $pixel->getColor (), 'count' => $pixel->getColorCount (), 'percent' => round ($pixel->getColorCount () / $pixelCount * 100)));
        else
          break;

    return sort_2d_array ('count', $datas);
  }

  public function saveAnalysisChart ($filepame, $font, $maxCount = 10, $fontSize = 14, $rawData = true) {
    if (!is_readable ($font) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Font：' . $font . '，字型檔案不存在。'))
      return $this;

    if (!($maxCount > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，MaxCount：' . $maxCount . '，參數 MaxCount 一定要大於 0。'))
      return $this;

    if (!($fontSize > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，FontSize：' . $fontSize . '，參數 FontSize 大小一定要大於 0。'))
      return $this;

    $format = pathinfo ($filepame, PATHINFO_EXTENSION);
    if (!($format && in_array ($format, $this->config['allow'])) && !Thumbnail::error ('[ThumbnailImagick] 不支援此檔案格式，Format：' . $format))
      return $this;

    if (!($datas = $this->getAnalysisDatas ($maxCount)) && !Thumbnail::error ('[ThumbnailImagick] 圖像分析錯誤。'))
      return $this;

    $newImage = new Imagick ();

    foreach ($datas as $data) {
      $newImage->newImage (400, 20, new ImagickPixel ('white'));

      $draw = new ImagickDraw ();
      $draw->setFont ($font);
      $draw->setFontSize ($fontSize);
      $newImage->annotateImage ($draw, 25, 14, 0, 'Percentage of total pixels : ' . (strlen ($data['percent'])<2?' ':'') . $data['percent'] . '% (' . $data['count'] . ')');

      $tile = new Imagick ();
      $tile->newImage (20, 20, new ImagickPixel ('rgb(' . $data['color']['r'] . ',' . $data['color']['g'] . ',' . $data['color']['b'] . ')'));

      $newImage->compositeImage ($tile, Imagick::COMPOSITE_OVER, 0, 0);
    }

    $newImage = $newImage->montageImage (new imagickdraw (), '1x' . count ($datas) . '+0+0', '400x20+4+2>', imagick::MONTAGEMODE_UNFRAME, '0x0+3+3');
    $newImage->setImageFormat ($format);
    $newImage->setFormat ($format);
    $newImage->writeImages ($filepame, $rawData);

    return $this;
  }

  public function addFont ($text, $font, $startX = 0, $startY = 12, $color = 'black', $fontSize = 12, $alpha = 1, $degree = 0) {
    if (!$text)
      return $this;

    if (!is_readable ($font) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Font：' . $font . '，字型檔案不存在。'))
      return $this;

    if (!(($startX >= 0) && ($startY >= 0)) && !Thumbnail::error ('[ThumbnailImagick] 起始點錯誤，X：' . $startX . '，Y：' . $startY . '，水平、垂直的起始點一定要大於 0。'))
      return $this;

    if (!is_string ($color) && !Thumbnail::error ('[ThumbnailImagick] 色碼格式錯誤，目前只支援字串 HEX 格式。'))
      return $this;

    if (!($fontSize > 0) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，FontSize：' . $fontSize . '，FontSize 大小一定要大於 0。'))
      return $this;

    if (!($alpha && is_numeric ($alpha) && ($alpha >= 0) && ($alpha <= 1)) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Alpha：' . $alpha . '，參數 Alpha 一定要是 0 或 1。'))
      return $this;

    if (!is_numeric ($degree %= 360) && !Thumbnail::error ('[ThumbnailImagick] 角度一定要是數字，Degree：' . $degree ))
      return $this;

    if (!($draw = $this->_createFont ($font, $fontSize, $color, $alpha)) && !Thumbnail::error ('[ThumbnailImagick]  Create 文字物件失敗'))
      return $this;

    if ($this->format == 'gif') {
      $newImage = new Imagick ();
      $newImage->setFormat ($this->format);
      $imagick = clone $this->image;
      $imagick = $imagick->coalesceImages ();
      
      do {
        $temp = new Imagick ();
        $temp->newImage ($this->dimension->width (), $this->dimension->height (), new ImagickPixel ('transparent'));
        $temp->compositeImage ($imagick, imagick::COMPOSITE_DEFAULT, 0, 0);
        $temp->annotateImage ($draw, $startX, $startY, $degree, $text);
        $newImage->addImage ($temp);
        $newImage->setImageDelay ($imagick->getImageDelay ());
      } while ($imagick->nextImage ());
    } else {
      $newImage = clone $this->image;
      $newImage->annotateImage ($draw, $startX, $startY, $degree, $text);
    }

    return $this->_updateImage ($newImage);
  }

  public static function block9 ($files, $save = null, $rawData = true) {
    if (!(count ($files) >= 9) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Files Count：' . count ($files) . '，參數 Files 數量一定要大於等於 9。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save))
      return $this;

    $newImage = new Imagick ();
    $newImage->newImage (266, 200, new ImagickPixel ('white'));
    $newImage->setFormat (pathinfo ($save, PATHINFO_EXTENSION));

    $positions = array (
      array ('left' =>   2, 'top' =>   2, 'width' => 130, 'height' => 130), array ('left' => 134, 'top' =>   2, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>   2, 'width' =>  64, 'height' =>  64),
      array ('left' => 134, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' =>  68, 'width' =>  64, 'height' =>  64), array ('left' =>   2, 'top' => 134, 'width' =>  64, 'height' =>  64),
      array ('left' =>  68, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 134, 'top' => 134, 'width' =>  64, 'height' =>  64), array ('left' => 200, 'top' => 134, 'width' =>  64, 'height' =>  64),
    );

    for ($i = 0; $i < 9; $i++)
      $newImage->compositeImage (Thumbnail::createImagick ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), imagick::COMPOSITE_DEFAULT, $positions[$i]['left'], $positions[$i]['top']);

    return $newImage->writeImages ($save, $rawData);
  }

  public static function photos ($files, $save = null, $rawData = true) {
    if (!(count ($files) >= 1) && !Thumbnail::error ('[ThumbnailImagick] 參數錯誤，Files Count：' . count ($files), '參數 Files 數量一定要大於 1。'))
      return $this;

    if (!$save && !Thumbnail::error ('[ThumbnailImagick] 錯誤的儲存路徑，Path：' . $save))
      return $this;
    
    $w = 1200;
    $h = 630;

    $newImage = new Imagick ();
    $newImage->newImage ($w, $h, new ImagickPixel ('white'));
    $newImage->setFormat (pathinfo ($save, PATHINFO_EXTENSION));
    
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
      $newImage->compositeImage (Thumbnail::createImagick ($files[$i])->adaptiveResizeQuadrant ($positions[$i]['width'], $positions[$i]['height'])->getImage (), Imagick::COMPOSITE_DEFAULT, $positions[$i]['left'], $positions[$i]['top']);

    return $newImage->writeImages ($save, $rawData);
  }
}