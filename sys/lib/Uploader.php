<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class Uploader {
  private static $debug = false;
  
  private $driverConfigs = array ();
  protected $tmpDir = null;

  protected $orm = null;
  protected $column = null;
  protected $value = null;

  public function __construct ($orm, $column) {
    $attrs = array_keys ($orm->attributes ());

    if (!in_array ($column, $attrs))
      return;

    in_array ($column, $attrs) || Uploader::mustError ('[Uploader] Class 「' . get_class ($orm) . '」 無 「' . $column . '」 欄位。');
    in_array ($this->uniqueColumn (), $attrs) || Uploader::mustError ('[Uploader] Class 「' . get_class ($orm) . '」 無 「' . $this->uniqueColumn () . '」 欄位。');

    $this->orm = $orm;
    $this->column = $column;
    $this->value = $orm->$column;
    $orm->$column = $this;

    is_really_writable ($this->tmpDir = config ('uploader', 'tmp_dir')) || Uploader::mustError ('[Uploader] Tmp 目錄沒有權限寫入。Path：' . $this->tmpDir);
    ($this->driverConfigs = config ('uploader', 'drivers', $this->getDriver ())) || Uploader::mustError ('[Uploader] Driver 設定錯誤。');
    
    if ($this->getDriver () == 's3') {
      !class_exists ('S3', false) && !Load::sysLib ('S3.php') && Uploader::mustError ('[Uploader] 導入 S3 物件失敗。');
      S3::init ($this->driverConfigs['access_key'], $this->driverConfigs['secret_key']);
    }
  }

  protected function uniqueColumn () {
    return 'id';
  }

  protected function getS3Bucket () {
    return $this->driverConfigs['bucket'];
  }

  protected function getDriver () {
    return config ('uploader', 'driver');
  }
  
  public static function mkdir ($pathname, $mode = 0777, $recursive = false) {
    $oldmask = umask (0);
    @mkdir ($pathname, $mode, $recursive);
    umask ($oldmask);
  }
  
  public static function chmod ($pathname, $mode = 0777) {
    $oldmask = umask (0);
    @chmod ($pathname, $mode);
    umask ($oldmask);
  }
  
  public static function mustError () {
    throw new UploaderException (call_user_func_array ('sprintf', func_get_args ()));
  }
  
  public static function s3ExceptionRetuen () {
    if (!(class_exists ('S3') && ($args = func_get_args ()) && ($method = array_shift ($args)) && is_callable (array ('S3', $method))))
      return Uploader::error ('S3 物件未初始，或錯誤的 Method。');

    try {
      return call_user_func_array (array ('S3', $method), $args);
    } catch (S3Exception $e) {
      return Uploader::error ($e->getMessage ());
    }
  }
  
  public static function error () {
    if (self::$debug)
      throw new UploaderException (call_user_func_array ('sprintf', func_get_args ()));

    Log::error (call_user_func_array ('sprintf', func_get_args ()));
    return false;
  }

  public function __toString () {
    return  $this->getValue ();
  }
  
  public function getValue () {
    return (String)$this->value;
  }

  public static function bind ($column, $class = null) {
    ($trace = debug_backtrace (DEBUG_BACKTRACE_PROVIDE_OBJECT)) || Uploader::mustError ('[Uploader] 取得 debug_backtrace 發生錯誤。');
    isset ($trace[1]['object']) && is_object ($orm = $trace[1]['object']) || Uploader::mustError ('[Uploader] 取得 debug_backtrace 回傳結構有誤，無法取得上層物件。');
    class_exists ($class) || $class = get_called_class ();

    return new $class ($orm, $column);
  }

  protected function d4Url () {
    return isset ($this->driverConfigs['d4_url']) ? $this->driverConfigs['d4_url'] : '';
  }

  public function toImageTag ($key = '', $attrs = array ()) { // $attrs = array ('class' => 'i')
    return ($url = ($url = $this->url ($key)) ? $url : $this->d4Url ()) ? '<img src="' . $url . '"' . ($attrs ? ' ' . implode (' ', array_map (function ($key, $value) { return $key . '="' . $value . '"'; }, array_keys ($attrs), $attrs)) : '') . '>' : '';
  }
  public function toDivImageTag ($key = '', $attrs = array ()) { // $attrs = array ('class' => 'i')
    return ($str = $this->toImageTag ($key)) ? '<div' . ($attrs ? ' ' . implode (' ', array_map (function ($key, $value) { return $key . '="' . $value . '"'; }, array_keys ($attrs), $attrs)) : '') . '>' . $str . '</div>' : '';
  }
  public function url ($key = '') {
    switch ($this->getDriver ()) {
      case 'local':
        return ($path = $this->path ($key)) ? implode ('/', array_merge (array (rtrim ($this->driverConfigs['base_url'], '/')) , $path)) : $this->d4Url ();
        break;
      
      case 's3':
        return ($path = $this->path ($key)) ? implode ('/', array_merge (array (rtrim ($this->driverConfigs['base_url'], '/')) , $path)) : $this->d4Url ();
        break;
    }
  }

  public function path ($fileName = '') {
    switch ($this->getDriver ()) {
      case 'local':
        return is_readable (FCPATH . implode (DIRECTORY_SEPARATOR, $path = array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($fileName)))) ? $path : array ();
        break;

      case 's3':
        return array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($fileName));
        break;
    }
  }

  protected function getBaseDirectory () {
    return $this->driverConfigs['base_dir'];
  }
  
  public function getSavePath () {
    return is_numeric ($id = $this->getColumnValue ($this->uniqueColumn ())) ? array_merge (array ($this->getTableName (), $this->getColumnName ()), str_split (sprintf('%016s', dechex($id)), 2)) : array ($this->getTableName (), $this->getColumnName ());
  }
  
  protected function getColumnValue ($column) {
    $attrs = array_keys ($this->orm->attributes ());
    return in_array ($column, $attrs) ? $this->orm->$column : '';
  }
  
  protected function getTableName () {
    return $this->orm->table ()->table;
  }
  
  protected function getColumnName () {
    return $this->column;
  }
  
  protected function getRandomName () {
    return md5 (uniqid (mt_rand (), true));
  }
  
  public function put ($fileInfo) {
    
    if (!($fileInfo && (is_array ($fileInfo) || (is_string ($fileInfo) && file_exists ($fileInfo)))) && !Uploader::error ('[Uploader] put 格式有誤。'))
      return false;

    $isUseMoveUploadedFile = false;

    if (is_array ($fileInfo)) {
      foreach (array ('name', 'tmp_name', 'type', 'error', 'size') as $key)
        if (!isset ($fileInfo[$key]))
          return false;

      $name = $fileInfo['name'];
      $isUseMoveUploadedFile = true;
    } else {
      $name = basename ($fileInfo);
      $fileInfo = array ('name' => 'file', 'tmp_name' => $fileInfo, 'type' => '', 'error' => '', 'size' => '1');
    }

    $name = preg_replace ("/[^a-zA-Z0-9\\._-]/", "", $name);
    $format = ($format = pathinfo ($name, PATHINFO_EXTENSION)) ? '.' . $format : '';
    $name = ($name = pathinfo ($name, PATHINFO_FILENAME)) ? $name . $format : $this->getRandomName () . $format;

    if (!($temp = $this->moveOriFile ($fileInfo, $isUseMoveUploadedFile)) && !Uploader::error ('[Uploader] 搬移至暫存資料夾時發生錯誤。'))
      return false;

    if (!($savePath = $this->verifySavePath ()) && !Uploader::error ('[Uploader] 確認儲存路徑發生錯誤。'))
      return false;

    if (!($result = $this->moveFileAndUploadColumn ($temp, $savePath, $name)) && !Uploader::error ('[Uploader] 搬移預設位置時發生錯誤。'))
      return false;

    return true;
  }

  private function moveOriFile ($fileInfo, $isUseMoveUploadedFile) {
    $temp = $this->tmpDir . 'uploader_' . $this->getRandomName ();

    if ($isUseMoveUploadedFile)
      @move_uploaded_file ($fileInfo['tmp_name'], $temp);
    else
      @rename ($fileInfo['tmp_name'], $temp);

    Uploader::chmod ($temp, 0777);

    if (!file_exists ($temp) && !Uploader::error ('[Uploader] moveOriFile 移動檔案失敗。Path：' . $temp))
      return false;

    return $temp;
  }

  private function verifySavePath () {
    switch ($this->getDriver ()) {
      case 'local':
        if (!is_really_writable ($path = FCPATH . implode (DIRECTORY_SEPARATOR, $this->getBaseDirectory ())) && !Uploader::error ('[Uploader] verifySavePath 資料夾不能儲存。Path：' . $path))
          return false;

        if (!file_exists ($t = FCPATH . implode (DIRECTORY_SEPARATOR, $path = array_merge ($this->getBaseDirectory (), $this->getSavePath ()))))
          Uploader::mkdir ($t, 0777, true);

        return is_really_writable ($t) ? $path : Uploader::error ('[Uploader] verifySavePath 資料夾不能儲存。Path：' . $path);
        break;

      case 's3':
        return array_merge ($this->getBaseDirectory (), $this->getSavePath ());
        break;
    }
    return false;
  }

  protected function moveFileAndUploadColumn ($temp, $savePath, $oriName) {
    switch ($this->getDriver ()) {
      case 'local':
        return @rename ($temp, $savePath = FCPATH . implode (DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $oriName) && $this->uploadColumnAndUpload ('') ? $this->uploadColumnAndUpload ($oriName) : Uploader::error ('[Uploader] moveFileAndUploadColumn 搬移預設位置時發生錯誤。');
        break;

      case 's3':
        return Uploader::s3ExceptionRetuen ('putObject', $temp, $this->getS3Bucket (), implode ('/', $savePath) . '/' . $oriName) && $this->uploadColumnAndUpload ('') ? $this->uploadColumnAndUpload ($oriName) && @unlink ($temp) : Uploader::error ('[Uploader] moveFileAndUploadColumn 搬移預設位置時發生錯誤。');
        break;
    }
    return false;
  }
  
  protected function uploadColumnAndUpload ($value, $isSave = true) {
    return !$this->cleanOldFile () ? Uploader::error ('[Uploader] uploadColumnAndUpload 清除檔案發生錯誤。') : ($isSave ? $this->uploadColumn ($value) : true);
  }
  
  protected function cleanOldFile () {
    switch ($this->getDriver ()) {
      case 'local':
        if ($paths = $this->getAllPaths ())
          foreach ($paths as $path)
            if (is_file ($path = FCPATH . implode (DIRECTORY_SEPARATOR, $path)) && is_writable ($path))
              if (!@unlink ($path))
                return Uploader::error ('[Uploader] cleanOldFile 清除檔案發生錯誤。Path：' . $path);
        return true;
        break;
      
      case 's3':
        if ($paths = $this->getAllPaths ())
          foreach ($paths as $path)
            if (!Uploader::s3ExceptionRetuen ('deleteObject', $this->getS3Bucket (), implode ('/', $path)))
              return Uploader::error ('[Uploader] cleanOldFile 清除檔案發生錯誤。Path：' . $path);
        return true;
        break;
    }
    return false;
  }
  
  public function getAllPaths () {
    if (!$this->getValue ())
      return array ();

    return array (array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($this->getValue ())));
  }
  
  protected function uploadColumn ($value) {
    $column = $this->column;
    $this->orm->$column = $value;

    if (!$this->orm->save ())
      return false;

    $this->value = $value;
    $this->orm->$column = $this;
    return true;
  }

  public function cleanAllFiles ($isSave = true) {
    return $this->uploadColumnAndUpload ('');
  }

  public function putUrl ($url) {
    Load::sysFunc ('download.php');
    $format = pathinfo ($url, PATHINFO_EXTENSION);
    $temp = $this->tmpDir . implode (DIRECTORY_SEPARATOR, array ($this->getRandomName () . ($format ? '.' . $format : '')));
    return ($temp = download_web_file ($url, $temp)) && $this->put ($temp, false) ? file_exists ($temp) ? @unlink ($temp) : true : false;
  }
}

class FileUploader extends Uploader {
  public function __construct ($orm, $column) {
    parent::__construct ($orm, $column);
  }

  public function url ($url ='') {
    return parent::url ('');
  }

  public function path ($fileName = '') {
    return parent::path ($this->getValue ());
  }
}

class ImageUploader extends Uploader {
  private $config = array ();

  public function __construct ($orm, $column) {
    parent::__construct ($orm, $column);

    $this->config = config ('uploader', 'thumbnail');
  }

  protected function getVersions () {
    return $this->config['default_version'];
  }
  private function versions () {
    return ($versions = $this->getVersions ()) ? $versions : $this->config['default_version'];
  }

  public function path ($key = '') {
    return ($versions = $this->versions ()) && isset ($versions[$key]) && ($value = $this->getValue ()) && ($fileName = $key . $this->config['separate_symbol'] . $value) ? parent::path ($fileName) : array ();
  }

  public function getAllPaths () {
    if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] getAllPaths Versions 格式錯誤。'))
      return array ();

    $paths = array ();
    foreach ($versions as $key => $version)
      array_push ($paths, array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($key . $this->config['separate_symbol'] . $this->getValue ())));
        
    return $paths;
  }

  protected function moveFileAndUploadColumn ($temp, $savePath, $ori_name) {
    if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn Versions 格式錯誤。'))
      return false;

    Load::sysLib ('Thumbnail.php');
    $method = 'create' . $this->config['driver'];
    
    if (!(class_exists ('Thumbnail') && is_callable (array ('Thumbnail', $method))) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn 錯誤的函式。Method：' . $method))
      return false;

    $news = array ();
    $info = @exif_read_data ($temp);
    $orientation = $info && isset ($info['Orientation']) ? $info['Orientation'] : 0;

    try {

      foreach ($versions as $key => $version) {
        $image = Thumbnail::$method ($temp);
        $image->rotate ($orientation == 6 ? 90 : ($orientation == 8 ? -90 : ($orientation == 3 ? 180 : 0)));
        
        $name = !isset ($name) ? $this->getRandomName () . ($this->config['auto_add_format'] ? '.' . $image->getFormat () : '') : $name;
        $new_name = $key . $this->config['separate_symbol'] . $name;

        $new_path = $this->tmpDir . $new_name;
        $this->_utility ($image, $new_path, $key, $version) || Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。');
        array_push ($news, array ('name' => $new_name, 'path' => $new_path));
      }
    } catch (Exception $e) {
      return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。Message：' . $e->getMessage ());
    }

    count ($news) == count ($versions) || Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');

    switch ($this->getDriver ()) {
      case 'local':
        foreach ($news as $new)
          if (!@rename ($new['path'], FCPATH . implode (DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $new['name']))
            return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');
        return self::uploadColumnAndUpload ('') && self::uploadColumnAndUpload ($name) && @unlink ($temp);
        break;

      case 's3':
        foreach ($news as $new)
          if (!(Uploader::s3ExceptionRetuen ('putObject', $new['path'], $this->getS3Bucket (), implode (DIRECTORY_SEPARATOR, $savePath) . DIRECTORY_SEPARATOR . $new['name']) && @unlink ($new['path'])))
            return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 不明原因錯誤。');
        return self::uploadColumnAndUpload ('') && self::uploadColumnAndUpload ($name) && @unlink ($temp);
        break;
    }
    return false;
  }

  private function _utility ($image, $save, $key, $version) {
    if (!$version)
      return $image->save ($save, true);

    if (!is_callable (array ($image, $method = array_shift ($version))))
      return Uploader::error ('[ImageUploader] _utility 無法呼叫的 Method，Method：' . $method);

    call_user_func_array (array ($image, $method), $version);
    return $image->save ($save, true);
  }

  public function saveAs ($key, $version) {
    if (!($key && $version) && !Uploader::error ('[ImageUploader] saveAs 參數錯誤。'))
      return false;

    Load::sysLib ('Thumbnail.php');
    $method = 'create' . $this->config['driver'];
    
    if (!(class_exists ('Thumbnail') && is_callable (array ('Thumbnail', $method))) && !Uploader::error ('[ImageUploader] moveFileAndUploadColumn 錯誤的函式。Method：' . $method))
      return false;

    if (!($versions = $this->versions ()) && !Uploader::error ('[ImageUploader] saveAs 沒有其他版本可用。'))
      return false;

    if (isset ($versions[$key]) && !Uploader::error ('[ImageUploader] saveAs 已經有相符合的 key 名稱。'))
      return false;

    switch ($this->getDriver ()) {
      case 'local':
        foreach (array_keys ($versions) as $oriKey)
          if (is_readable ($oriPath = FCPATH . implode (DIRECTORY_SEPARATOR, array_merge ($this->getBaseDirectory (), $this->getSavePath (), array ($oriKey . $this->config['separate_symbol'] . ($name = $this->getValue ()))))))
            break;

        if (!file_exists ($t = FCPATH . implode (DIRECTORY_SEPARATOR, ($path = array_merge ($this->getBaseDirectory (), $this->getSavePath ())))))
          Uploader::mkdir ($t, 0777, true);

        if (!is_writable ($t) && !Uploader::error ('[ImageUploader] saveAs 資料夾不能儲存。Path：' . $path))
          return @unlink ($t) && false;

        try {
          $image = Thumbnail::$method ($oriPath);
          $path = $t . DIRECTORY_SEPARATOR . $key . $this->config['separate_symbol'] . $name;

          if (!$this->_utility ($image, $path, $key, $version))
            return false;

          return $path;
        } catch (Exception $e) {
          return Uploader::error ('[ImageUploader] moveFileAndUploadColumn 圖像處理失敗。Message：' . $e->getMessage ());
        }
        break;

      case 's3':
        if (!Uploader::s3ExceptionRetuen ('getObject', implode (DIRECTORY_SEPARATOR, array_merge ($path = array_merge ($this->getBaseDirectory (), $this->getSavePath ()), array ($fileName = array_shift (array_keys ($versions)) . $this->config['separate_symbol'] . ($name = $this->getValue ())))), FCPATH . implode (DIRECTORY_SEPARATOR, $fileName = array_merge ($this->getTempDirectory (), array ($fileName))))) 
          return $this->getDebug () ? error ('ImageUploader 錯誤。', '沒有任何的檔案可以被使用。', '請確認 getVersions () 函式內有存在的檔案可被另存。', '請程式設計者確認狀況。') : array ();

        try {
          $image = ImageUtility::create ($fileName = FCPATH . implode (DIRECTORY_SEPARATOR, $fileName), null);
          $newPath = array_merge ($path, array ($newName = $key . $this->config['separate_symbol'] . $name));


          if ($this->_utility ($image, FCPATH . implode (DIRECTORY_SEPARATOR, $newFileName = array_merge ($this->getTempDirectory (), array ($newName))), $key, $version) && Uploader::s3ExceptionRetuen ('putFile', $newFileName = FCPATH . implode (DIRECTORY_SEPARATOR, $newFileName), $this->getS3Bucket (), implode (DIRECTORY_SEPARATOR, $newPath)) && @unlink ($newFileName) && @unlink ($fileName))
            return $newPath;  
          else
            return array ();
        } catch (Exception $e) {
          return $this->getDebug () ? call_user_func_array ('error', $e->getMessages ()) : '';
        }
        break;
    }

    return false;
  }
}

class UploaderException extends Exception {}

