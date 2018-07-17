<?php
Load::lib('Curl.php');

class TrelloApi {
  private $key;
  private $token;
  public $url;

  public function __construct ($key, $token) {
    $this->key = $key;
    $this->token = $token;
    $this->url = 'https://api.trello.com';
  }

  public static function create() {
    return new TrelloApi( config('trello', 'key'), config('trello', 'token') );
  }

  public function post($req, $args = false) {
    return Curl::init($this->url . $req, ['key' => $this->key, 'token' => $this->token])
            ->setParamField($args)->post();
  }

  public function get($req) {
    return Curl::init($this->url . $req, ['key' => $this->key, 'token' => $this->token])->get();
  }

  public function setWebhook($idModel, $desc, $query = false) {
    $url = 'https://api.trello.com/1/tokens/' . config('trello', 'token') . '/webhooks/?key=' . config('trello', 'key');
    $param = json_encode([
        'callbackURL' => 'https://qa.kerker.tw/' . 'api/trelloCallback' . ($query ? '?' . implode('&', array_map(function($k, $v) { return  $k . '=' . $v; }, array_keys($query), array_values($query) ) ) : ''),
        'idModel' => $idModel,
        'description' => $desc,
      ]);

    return Curl::init($url)
            ->setHttpHeader(['Content-Type' => 'application/json', 'Content-Length' => strlen($param) ])
            ->setParamField($param)->custom('POST');
  }

  // public function request ($type, $request, $args = false, $add = true) {
  //   if (!$args)
  //     $args = array();
  //   elseif (!is_array($args))
  //     $args = array($args);
  //
  //   $url = 'https://api.trello.com' . $request;
  //   $add && $url .= '?key=' . $this->key . '&token=' . $this->token;
  //
  //   $c = curl_init();
  //   curl_setopt($c, CURLOPT_HEADER, 0);
  //   curl_setopt($c, CURLOPT_VERBOSE, 0);
  //   curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
  //   curl_setopt($c, CURLOPT_URL, $url);
  //
  //   if (count($args)) curl_setopt($c, CURLOPT_POSTFIELDS , http_build_query($args));
  //
  //   switch ($type) {
  //     case 'POST': curl_setopt($c, CURLOPT_POST, 1); break;
  //     case 'GET': curl_setopt($c, CURLOPT_HTTPGET, 1); break;
  //     default: curl_setopt($c, CURLOPT_CUSTOMREQUEST, $type);
  //   }
  //
  //   $data = curl_exec($c);
  //   curl_close($c);
  //
  //   return json_decode($data);
  // }
}
