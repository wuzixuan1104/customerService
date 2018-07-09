<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

class S3 {
  const ACL_PRIVATE = 'private';
  const ACL_PUBLIC_READ = 'public-read';
  const ACL_PUBLIC_READ_WRITE = 'public-read-write';
  const ACL_AUTHENTICATED_READ = 'authenticated-read';

  public static $useSsl = false;
  public static $verifyPeer = true;
  public static $accessKey = null;
  public static $secretKey = null;

  public static function init ($accessKey, $secretKey) {
    self::$accessKey = $accessKey;
    self::$secretKey = $secretKey;
    Load::sysFunc ('file.php');
    return true;
  }

  private static function error () {
    throw new S3Exception (call_user_func_array ('sprintf', func_get_args ()));
  }

  private static function getMimeType ($file) {
    return ($extension = get_mime_by_extension ($file)) ? $extension : 'text/plain';
  }

  private static function fileMD5 ($filePath) {
    return base64_encode (md5_file ($filePath, true));
  }
  
  private static function isSuccess ($rest, $codes = array (200)) {
    return $rest->error === null && in_array ($rest->code, $codes);
  }

  public static function test () {
    return self::isSuccess (S3Request::create ('GET')->getResponse ());
  }

  public static function buckets () {
    self::isSuccess ($rest = S3Request::create ('GET')->getResponse ()) || self::error ("S3::listBuckets(): [%s] %s", $rest->code, 'Unexpected HTTP status');

    $buckets = array ();

    if (!isset ($rest->body->Buckets))
      return $buckets;

    foreach ($rest->body->Buckets->Bucket as $bucket)
      array_push ($buckets, (String)$bucket->Name);

    return $buckets;
  }

  public static function detailBuckets () {
    self::isSuccess ($rest = S3Request::create ('GET')->getResponse ()) || self::error ("S3::listBuckets(): [%s] %s", $rest->code, 'Unexpected HTTP status');

    $results = array ();

    if (!isset ($rest->body->Buckets))
      return $results;

    if (isset ($rest->body->Owner, $rest->body->Owner->ID, $rest->body->Owner->DisplayName))
      $results['owner'] = array ('id' => (String)$rest->body->Owner->ID, 'name' => (String)$rest->body->Owner->ID);

    $results['buckets'] = array ();
    foreach ($rest->body->Buckets->Bucket as $bucket)
      array_push ($results['buckets'], array ('name' => (String)$bucket->Name, 'time' => date ('Y-m-d H:i:s', strtotime ((String)$bucket->CreationDate))));

    return $results;
  }

  public static function bucket ($bucket, $prefix = null, $marker = null, $maxKeys = null, $delimiter = null, $returnCommonPrefixes = false) {
    self::isSuccess ($rest = S3Request::create ('GET', $bucket)->setParameter ('prefix', $prefix)->setParameter ('marker', $marker)->setParameter ('max-keys', $maxKeys)->setParameter ('delimiter', $delimiter)->getResponse ()) || self::error ("S3::getBucket(): [%s] %s", $rest->code, 'Unexpected HTTP status');

    $nextMarker = null;
    $results = array ();

    if (isset ($rest->body, $rest->body->Contents))
      foreach ($rest->body->Contents as $content)
        $results[$nextMarker = (String)$content->Key] = array ('name' => (String)$content->Key, 'time' => date ('Y-m-d H:i:s', strtotime ((String)$content->LastModified)), 'size' => (int)$content->Size, 'hash' => substr ((String)$content->ETag, 1, -1));

    if ($returnCommonPrefixes && isset ($rest->body, $rest->body->CommonPrefixes))
      foreach ($rest->body->CommonPrefixes as $content)
        $results[(String) $content->Prefix] = array ('prefix' => (String)$content->Prefix);

    if (isset ($rest->body, $rest->body->IsTruncated) && (((String)$rest->body->IsTruncated) == 'false'))
      return $results;

    if (isset ($rest->body, $rest->body->NextMarker))
      $nextMarker = (String)$rest->body->NextMarker;

    if ($maxKeys || !$nextMarker || (((String)$rest->body->IsTruncated) != 'true'))
      return $results;

    do {
      if (!self::isSuccess ($rest = S3Request::create ('GET', $bucket)->setParameter ('marker', $nextMarker)->setParameter ('prefix', $prefix)->setParameter ('delimiter', $delimiter)->getResponse ()))
        break;

      if (isset ($rest->body, $rest->body->Contents))
        foreach ($rest->body->Contents as $content)
          $results[$nextMarker = (String)$content->Key] = array ('name' => (String)$content->Key, 'time' => date ('Y-m-d H:i:s', strtotime ((String)$content->LastModified)), 'size' => (int) $content->Size, 'hash' => substr ((String)$content->ETag, 1, -1));

      if ($returnCommonPrefixes && isset ($rest->body, $rest->body->CommonPrefixes))
        foreach ($rest->body->CommonPrefixes as $content)
          $results[(String)$content->Prefix] = array ('prefix' => (String)$content->Prefix);

      if (isset ($rest->body, $rest->body->NextMarker))
        $nextMarker = (String)$rest->body->NextMarker;

    } while (($rest !== false) && (((String)$rest->body->IsTruncated) == 'true'));

    return $results;
  }

  public static function createBucket ($bucket, $acl = self::ACL_PRIVATE, $location = false) {
    $rest = S3Request::create ('PUT', $bucket)->setAmzHeader ('x-amz-acl', $acl);

    if ($location) {
      $dom = new DOMDocument ();
      $configuration = $dom->createElement ('CreateBucketConfiguration');
      $configuration->appendChild ($dom->createElement ('LocationConstraint', strtoupper ($location)));
      $dom->appendChild ($configuration);

      $rest->setHeader ('Content-Type', 'application/xml')
           ->setData ($dom->saveXML ());
    }

    self::isSuccess ($rest = $rest->getResponse ()) || self::error ("S3::putBucket(%s, %s, %s): [%s] %s", $bucket, $acl, $location, $rest->code, 'Unexpected HTTP status');

    return true;
  }

  public static function deleteBucket ($bucket) {
    self::isSuccess ($rest = S3Request::create ('DELETE', $bucket)->getResponse (), array (200, 204)) || self::error ("S3::deleteBucket(%s): [%s] %s", $bucket, $rest->code, 'Unexpected HTTP status');
    return true;
  }

  // $headers => "Cache-Control" => "max-age=5", setcache
  public static function putObject ($filePath, $bucket, $s3Path, $acl = self::ACL_PUBLIC_READ, $amzHeaders = array (), $headers = array ()) {
    is_file ($filePath) && is_readable ($filePath) || self::error ("S3::putObject (): Unable to open input file: %s", $filePath);

    $rest = S3Request::create ('PUT', $bucket, $s3Path)->setHeaders (array_merge (array ('Content-Type' => self::getMimeType ($filePath), 'Content-MD5' => self::fileMD5 ($filePath)), $headers))->setAmzHeaders (array_merge (array ( 'x-amz-acl' => $acl), $amzHeaders))->setFile ($filePath);

    $rest->getSize () >= 0 && $rest->getFile () !== null || self::error ("S3::putObject(): [%s] %s", 0, 'Missing input parameters');
    self::isSuccess ($rest = $rest->getResponse ()) || self::error ("S3::putObject(): [%s] %s", $rest->code, 'Unexpected HTTP status');

    return true;
  }

  public static function getObject ($bucket, $uri, $saveTo = null) {
    $rest = S3Request::create ('GET', $bucket, $uri);
    $saveTo && ($rest->setFile ($saveTo, 'wb', false)->getFile () !== null && $rest->file = realpath ($saveTo) || self::error ("S3::getObject(%s, %s): [%s] %s", $bucket, $uri, 0, 'Unable to open save file for writing: ' . $saveTo));
    self::isSuccess ($rest = $rest->getResponse ()) || self::error ("S3::getObject(%s, %s): [%s] %s", $bucket, $uri, $rest->code, 'Unexpected HTTP status');
    return $rest;
  }

  public static function getObjectInfo ($bucket, $uri) {
    self::isSuccess ($rest = S3Request::create ('HEAD', $bucket, $uri)->getResponse (), array (200, 404)) || self::error ("S3::getObjectInfo(%s, %s): [%s] %s", $bucket, $uri, $rest->code, 'Unexpected HTTP status');
    return $rest->code == 200 ? $rest->headers : false;
  }

  public static function copyObject ($srcBucket, $srcUri, $bucket, $uri, $acl = self::ACL_PUBLIC_READ, $amzHeaders = array (), $headers = array ()) {
    self::isSuccess ($rest = S3Request::create ('PUT', $bucket, $uri)->setHeaders ($headers = array_merge (array ('Content-Length' => 0), $headers))->setAmzHeaders ($amzHeaders = array_merge (array ('x-amz-acl' => $acl, 'x-amz-copy-source' => sprintf ('/%s/%s', $srcBucket, $srcUri)), $amzHeaders))->setAmzHeader ('x-amz-metadata-directive', $headers || $amzHeaders ? 'REPLACE' : null)->getResponse ()) || self::error ("S3::copyObject(%s, %s, %s, %s): [%s] %s", $srcBucket, $srcUri, $bucket, $uri, $rest->code, 'Unexpected HTTP status');
    return isset ($rest->body->LastModified, $rest->body->ETag) ? array ('time' => date ('Y-m-d H:i:s', strtotime ((String)$rest->body->LastModified)), 'hash' => substr ((String)$rest->body->ETag, 1, -1)) : false;
  }

  public static function deleteObject ($bucket, $uri) {
    self::isSuccess ($rest = S3Request::create ('DELETE', $bucket, $uri)->getResponse (), array (200, 204)) || self::error ("S3::deleteObject(): [%s] %s", $rest->code, 'Unexpected HTTP status');
    return true;
  }

  public static function getSignature ($string) {
    return 'AWS ' . self::$accessKey . ':' . self::getHash ($string);
  }

  private static function getHash($string) {
    return base64_encode (extension_loaded ('hash') ? hash_hmac ('sha1', $string, self::$secretKey, true) : pack ('H*', sha1 ((str_pad (self::$secretKey, 64, chr (0x00)) ^ (str_repeat (chr (0x5c), 64))) . pack ('H*', sha1 ((str_pad (self::$secretKey, 64, chr (0x00)) ^ (str_repeat (chr (0x36), 64))) . $string)))));
  }
}

class S3Exception extends Exception {}

final class S3Request {
  private $verb;
  private $bucket;
  private $uri;
  private $resource = '';

  private $parameters = array ();
  private $amzHeaders = array ();
  private $headers = array (
    'Host' => '',
    'Date' => '',
    'Content-MD5' => '',
    'Content-Type' => '');

  public $fp = null;
  public $data = null;
  public $size = 0;

  public $response = null;

  public function __construct ($verb, $bucket = '', $uri = '', $defaultHost = 's3.amazonaws.com') {
    $this->verb = strtoupper ($verb);
    $this->bucket = strtolower ($bucket);
    $this->uri = $uri ? '/' . str_replace ('%2F', '/', rawurlencode ($uri)) : '/';
    $this->resource = ($this->bucket ? '/' . $this->bucket : '') . $this->uri;
    
    $this->headers['Host'] = ($this->bucket ? $this->bucket . '.' : '') . $defaultHost;
    $this->headers['Date'] = gmdate ('D, d M Y H:i:s T');

    $this->response = new STDClass;
    $this->response->error = null;
    $this->response->body = '';
    $this->response->code = null;
  }

  public function setParameter ($key, $value) {
    $value && $this->parameters[$key] = $value;
    return $this;
  }

  public function setHeaders ($arr) {
    foreach ($arr as $key => $value)
      $this->setHeader ($key, $value);
    return $this;
  }

  public function setHeader ($key, $value) {
    $value && $this->headers[$key] = $value;
    return $this;
  }

  public function setAmzHeaders ($arr) {
    foreach ($arr as $key => $value)
      $this->setAmzHeader ($key, $value);
    return $this;
  }

  public function setAmzHeader ($key, $value) {
    $value && $this->amzHeaders[preg_match ('/^x-amz-.*$/', $key) ? $key : 'x-amz-meta-' . $key] = $value;
    return $this;
  }

  public function setData ($data) {
    $this->data = $data;
    $this->setSize (strlen ($data));
    return $this;
  }
  public function getSize () {
    return $this->fp;
  }
  public function setFile ($file, $mode = 'rb', $autoSetSize = true) {
    $this->fp = @fopen ($file, $mode);
    $autoSetSize && $this->setSize (filesize ($file));
    return $this;
  }
  public function getFile () {
    return $this->size;
  }
  public function setSize ($size) {
    $this->size = $size;
    return $this;
  }

  private function makeAmz () {
    $amz = array ();

    foreach ($this->amzHeaders as $header => $value)
      $value && array_push ($amz, strtolower ($header) . ':' . $value);

    if (!$amz)
      return '';

    sort ($amz);

    return "\n" . implode ("\n", $amz);
  }
  
  private function makeHeader () {
    $headers = array ();

    foreach ($this->amzHeaders as $header => $value)
      if ($value)
        array_push ($headers, $header . ': ' . $value);
    
    foreach ($this->headers as $header => $value)
      if ($value)
        array_push ($headers, $header . ': ' . $value);
    
    array_push ($headers, 'Authorization: ' . S3::getSignature ($this->headers['Host'] == 'cloudfront.amazonaws.com' ? $this->headers['Date'] : $this->verb . "\n" . $this->headers['Content-MD5'] . "\n" . $this->headers['Content-Type'] . "\n" . $this->headers['Date'] . $this->makeAmz () . "\n" . $this->resource));

    return $headers;
  }

  public function getResponse () {
    $query = '';

    if ($this->parameters) {
      $query = substr ($this->uri, -1) !== '?' ? '?' : '&';

      foreach ($this->parameters as $var => $value)
        $query .= ($value == null) || ($value == '') ? $var . '&' : $var . '=' . rawurlencode($value) . '&';

      $this->uri .= $query = substr ($query, 0, -1);

      if (isset ($this->parameters['acl']) || isset ($this->parameters['location']) || isset ($this->parameters['torrent']) || isset ($this->parameters['logging']))
        $this->resource .= $query;
    }

    $url = (S3::$useSsl && extension_loaded ('openssl') ? 'https://' : 'http://') . $this->headers['Host'] . $this->uri;

    $curlSetopts = array (
        CURLOPT_URL => $url,
        CURLOPT_USERAGENT => 'S3/php',
        CURLOPT_HTTPHEADER => $this->makeHeader (),
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => array (&$this, 'responseWriteCallback'),
        CURLOPT_HEADERFUNCTION => array (&$this, 'responseHeaderCallback'),
        CURLOPT_FOLLOWLOCATION => true,
      );
    S3::$useSsl && $curlSetopts[CURLOPT_SSL_VERIFYHOST] = 1;
    S3::$useSsl && $curlSetopts[CURLOPT_SSL_VERIFYPEER] = S3::$verifyPeer ? 1 : FALSE;

    switch ($this->verb) {
      case 'PUT': case 'POST':
        if ($this->fp !== null) {
          $curlSetopts[CURLOPT_PUT] = true;
          $curlSetopts[CURLOPT_INFILE] = $this->fp;
          $this->size && $curlSetopts[CURLOPT_INFILESIZE] = $this->size;
          break;
        }

        $curlSetopts[CURLOPT_CUSTOMREQUEST] = $this->verb;

        if ($this->data !== null) {
          $curlSetopts[CURLOPT_POSTFIELDS] = $this->data;
          $this->size && $curlSetopts[CURLOPT_BUFFERSIZE] = $this->size;
        }
        break;

      case 'HEAD':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'HEAD';
        $curlSetopts[CURLOPT_NOBODY] = true;
        break;

      case 'DELETE':
        $curlSetopts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        break;

      case 'GET': default: break;
    }
    
    $curl = curl_init ();
    curl_setopt_array ($curl, $curlSetopts);
    
    if (curl_exec ($curl))
      $this->response->code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
    else
      $this->response->error = array (
        'code' => curl_errno ($curl),
        'message' => curl_error ($curl),
        'resource' => $this->resource);

    curl_close ($curl);

    if ($this->response->error === null && isset ($this->response->headers['type']) && $this->response->headers['type'] == 'application/xml' && isset ($this->response->body) && ($this->response->body = simplexml_load_string ($this->response->body)))
      if (!in_array ($this->response->code, array (200, 204)) && isset ($this->response->body->Code, $this->response->body->Message))
        $this->response->error = array (
          'code' => (String)$this->response->body->Code,
          'message' => (String)$this->response->body->Message,
          'resource' => isset ($this->response->body->Resource) ? (String)$this->response->body->Resource : null);

    if ($this->fp !== null && is_resource ($this->fp))
      fclose ($this->fp);

    return $this->response;
  }

  private function responseWriteCallback (&$curl, &$data) {
    if ($this->response->code == 200 && $this->fp !== null)
      return fwrite ($this->fp, $data);

    $this->response->body .= $data;
    return strlen ($data);
  }

  private function responseHeaderCallback (&$curl, &$data) {
    if (($strlen = strlen ($data)) <= 2)
      return $strlen;
    
    if (substr ($data, 0, 4) == 'HTTP') {
      $this->response->code = (int)substr ($data, 9, 3);
    } else {
      list ($header, $value) = explode (': ', trim ($data), 2);
      $header == 'Last-Modified' && $this->response->headers['time'] = strtotime ($value);
      $header == 'Content-Length' && $this->response->headers['size'] = (int)$value;
      $header == 'Content-Type' && $this->response->headers['type'] = $value;
      $header == 'ETag' && $this->response->headers['hash'] = $value{0} == '"' ? substr ($value, 1, -1) : $value;
      preg_match ('/^x-amz-meta-.*$/', $header) && $this->response->headers[$header] = is_numeric ($value) ? (int)$value : $value;
    }

    return $strlen;
  }

  public static function create ($verb, $bucket = '', $uri = '') {
    return new S3Request ($verb, $bucket, $uri);
  }
}