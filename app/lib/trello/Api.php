<?php
Load::lib('Curl.php');

class Api {
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

  public function put($req, $args) {
    return Curl::init($this->url . $req, ['key' => $this->key, 'token' => $this->token])
            ->setParamField($args)->custom('PUT');
  }

  public function delete($req, $args = []) {
    $args = json_encode($args);
    return Curl::init($this->url . $req, ['key' => $this->key, 'token' => $this->token])
            ->setHttpHeader(['Content-Type' => 'application/json', 'Content-Length' => strlen($args) ])
            ->setParamField($args)->custom("DELETE");
  }

  public function setWebhook($idModel, $desc, $query = false) {
    $url = 'https://api.trello.com/1/tokens/' . config('trello', 'token') . '/webhooks/?key=' . config('trello', 'key');
    $param = json_encode([
        'callbackURL' => Url::base() . 'api/trelloCallback' . ($query ? '?' . implode('&', array_map(function($k, $v) { return  $k . '=' . $v; }, array_keys($query), array_values($query) ) ) : ''),
        'idModel' => $idModel,
        'description' => $desc,
      ]);

    return Curl::init($url)
            ->setHttpHeader(['Content-Type' => 'application/json', 'Content-Length' => strlen($param) ])
            ->setParamField($param)->custom('POST');
  }
}
