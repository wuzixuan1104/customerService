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

  public function request ($type, $request, $args = false) {
    if (!$args) {
      $args = array();
    } elseif (!is_array($args)) {
      $args = array($args);
    }

    if (strstr($request, '?')) {
      $url = 'https://api.trello.com' . $request . '&key=' . $this->key . '&token=' . $this->token;
    } else {
      $url = 'https://api.trello.com' . $request . '?key=' . $this->key . '&token=' . $this->token;
    }

    $c = curl_init();
    curl_setopt($c, CURLOPT_HEADER, 0);
    curl_setopt($c, CURLOPT_VERBOSE, 0);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($c, CURLOPT_URL, $url);

    if (count($args)) curl_setopt($c, CURLOPT_POSTFIELDS , http_build_query($args));

    switch ($type) {
      case 'POST':
        curl_setopt($c, CURLOPT_POST, 1);
        break;
      case 'GET':
        curl_setopt($c, CURLOPT_HTTPGET, 1);
        break;
      default:
        curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
    }

    $data = curl_exec($c);
    curl_close($c);

    return json_decode($data);
  }
}
