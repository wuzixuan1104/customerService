<?php
class Curl{
  const DEFAULT_TIMEOUT = 30;
  const CONTENT_TYPE = ['text/plain' => 'text', 'application/json' => 'json', 'application/xml' => 'xml'];

  public $ch;
  public $url;
  public $options;
  public $params = null;
  public $contentType = null;

  public function __construct($url, $qryStr) {
    $this->ch = curl_init();
    $this->setUrl($url, $qryStr);
    $this->defaultSetting();
  }

  public static function init($url, $qryStr = '') {
    return new Curl($url, $qryStr);
  }

  public function defaultSetting() {
    $this->setHeader();
    $this->setVerbose();
    $this->setDefaultTimeout();
    $this->setReturnTransferer();
  }

  public function setUrl($url, $qryStr = '') {
    if( empty($url) ) return $this;
    $this->url = $this->buildUrl($url, $qryStr);
    $this->setOpt(CURLOPT_URL, $this->url);
  }

  public function setParamField($data) {
    if( empty($data) || !$data ) return $this;
    if( !empty($this->contentType) && self::CONTENT_TYPE[$this->contentType] == 'json' && is_array($data) )
      $data = json_encode($data);
    $this->params = $data;
    return $this;
  }

  public function setHeader($bool = false) {
    $this->setOpt(CURLOPT_HEADER, $bool);
    return $this;
  }

  public function setHttpHeader($headers) {
    if( empty($headers) || !is_array($headers) ) return $this;
    $httpHeaders = [];
    foreach($headers as $k => $header) {
      if(strtolower($k) == 'content-type')
        $this->contentType = $header;

      array_push($httpHeaders, $k . ':' . $header);
    }

    $this->setOpt(CURLOPT_HTTPHEADER, $httpHeaders);
    return $this;
  }

  public function setReturnTransferer($bool = true) {
    $this->setOpt(CURLOPT_RETURNTRANSFER, $bool);
    return $this;
  }

  public function setVerbose($bool = false) {
    $this->setOpt(CURLOPT_VERBOSE, $bool);
    return $this;
  }

  public function setDefaultTimeout() {
    $this->setTimeout(self::DEFAULT_TIMEOUT);
  }

  public function setTimeout($seconds) {
    $this->setOpt(CURLOPT_TIMEOUT, $seconds);
    return $this;
  }

  private function buildUrl($url, $mixData = '') {
    $qryStr = '';
    if (!empty($mixData)) {
      $qryMark = strpos($url, '?') > 0 ? '&' : '?';
      if (is_string($mixData))
        $qryStr .= $qryMark . $mixData;
      elseif (is_array($mixData))
        $qryStr .= $qryMark . http_build_query($mixData, '', '&');
    }
    return $url . $qryStr;
  }

  public function setOpt($option, $value) {
    $reqireOptions = [ CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER' ];
    if (in_array($option, array_keys($reqireOptions), true) && !($value === true)) {
      gg($reqireOptions[$option] . ' is a required option');
    }
    if( $res = curl_setopt($this->ch, $option, $value) )
      $this->options[$option] = $value;
    return $res;
  }

  public function custom($method) {
    if( !in_array($method, ['POST', 'GET', 'PUT', 'DELETE']) )
      return $this;

    $this->setOpt(CURLOPT_CUSTOMREQUEST, $method);

    if(is_array($this->params))
      $this->setOpt(CURLOPT_POSTFIELDS, http_build_query($this->params) );
    elseif($this->params !== null)
      $this->setOpt(CURLOPT_POSTFIELDS, $this->params );

    $res = curl_exec($this->ch);
    curl_close($this->ch);
    return json_decode($res, true);
  }

  public function get() {
    $this->setOpt(CURLOPT_HTTPGET, 1);

    $res = curl_exec($this->ch);
    curl_close($this->ch);
    return json_decode($res, true);
  }

  public function post() {
    if( empty($this->params) )
      return $this;

    $this->setOpt(CURLOPT_POST, 1);
    if (is_array($this->params))
      $this->setOpt(CURLOPT_POSTFIELDS , http_build_query($this->params));
    else
      $this->setOpt(CURLOPT_POSTFIELDS , $this->params);

    $res = curl_exec($this->ch);
    curl_close($this->ch);
    return json_decode($res, true);
  }
}
