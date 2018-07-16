<?php
class TrelloApi {
  private $key;
  private $token;

  public function __construct ($key, $token) {
    $this->key = $key;
    $this->token = $token;
  }

  public static function create() {
    return new TrelloApi( config('trello', 'key'), config('trello', 'token') );
  }

  public function request ($type, $request, $args = false, $add = true) {
    if (!$args)
      $args = array();
    elseif (!is_array($args))
      $args = array($args);

    $url = 'https://api.trello.com' . $request;
    $add && $url .= '?key=' . $this->key . '&token=' . $this->token;

    $c = curl_init();
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_VERBOSE, 0);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $url);

    if (count($args)) curl_setopt($c, CURLOPT_POSTFIELDS , http_build_query($args));

    switch ($type) {
      case 'POST': curl_setopt($c, CURLOPT_POST, 1); break;
      case 'GET': curl_setopt($c, CURLOPT_HTTPGET, 1); break;
      default: curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
    }

    $data = curl_exec($c);
    curl_close($c);

    return json_decode($data);
  }

  public function post($url, $param) {
    $url = 'https://api.trello.com' . $url;
    $data_string = json_encode($param);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }
  public function setWebhook($idModel, $desc, $query = false) {
    $url = '/1/tokens/' . config('trello', 'token') . '\/webhooks/';
    $param = [
      'key' => config('trello', 'key'),
      'callbackURL' => Url::base() . 'api/trelloCallback' . ($query ? '?' . implode('&', array_map(function($k, $v) { return  $k . '=' . $v; }, array_keys($query), array_values($query) ) ) : ''),
      'idModel' => $idModel,
      'description' => $desc,
    ];
    echo 'new: ' . $param['callbackURL'] . "\r\n";
    $result = $this->post($url, $param);
    var_dump($result);
    die;
  }
}
