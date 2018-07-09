<?php defined ('OACI') || exit ('此檔案不允許讀取。');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, OACI
 * @license     http://opensource.org/licenses/MIT  MIT License
 * @link        https://www.ioa.tw/
 */

interface AdminRestfulControllerInterface {
  public function index ();
  public function add ();
  public function create ();
  public function edit ($obj);
  public function update ($obj);
  public function destroy ($obj);
  public function show ($obj);
}

abstract class AdminRestfulController extends AdminController implements AdminRestfulControllerInterface {
  protected $obj = null;
  protected $parents = array ();
  protected $parent = null;

  public function __construct () {
    parent::__construct ();

    Load::lib ('RestfulIndex.php');
    Load::lib ('RestfulForm.php');
    $this->form = Restful\Form::create ();
  }

  public function _remap ($name, $params) {
    Router::$router || gg ('請設定正確的 Router RestfulUrl.');

    $this->parents = array_filter (array_map (function ($param) {
      $where = Where::create ();
      is_numeric ($param[1]) ? $where->and ('id = ?', $param[1]) : gg ('ID 資訊錯誤！');

      if (is_string ($param[0]) && class_exists ($class = $param[0]))
        return ($obj = $class::find ('one', array ('where' => $where))) ? $obj : gg ('錯誤！找不到指定物件。物件：' . $class);

      if (is_array ($param[0]) && isset ($param[0]['model']) && class_exists ($class = $param[0]['model'])) {
        isset ($param[0]['where']) && $where->and ($param[0]['where']);
        unset ($param[0]['model'], $param[0]['where']);
        return ($obj = $class::find ('one', array_merge ($param[0], array ('where' => $where)))) ? $obj : gg ('錯誤！找不到指定物件。物件：' . $class);
      }

      gg ('Router RestfulUrl Model 設置錯誤，Model：' . $class);
      // return null;
    }, Router::$router['params']), function ($t) { return $t !== null; });

    count (Router::$router['params']) == count ($this->parents) || gg ('不明原因錯誤！');

    if (!in_array ($name, array ('index', 'add', 'create', 'sorts')))
      $this->obj = array_pop ($this->parents);

    if (in_array ($name, array ('index'))) {
      $this->asset->addCSS ('/assets/css/admin/list.css')
                  ->addJS ('/assets/js/admin/list.js');
    }

    if (in_array ($name, array ('add', 'edit'))) {
      $this->view->with ('form', $this->form->setObj ($name == 'edit' ? $this->obj : null));
      $this->asset->addCSS ('/assets/css/admin/form.css')
                  ->addJS ('/assets/js/admin/form.js');
    }
      
    if (in_array ($name, array ('show')))
      $this->asset->addCSS ('/assets/css/admin/show.css')
                  ->addJS ('/assets/js/admin/show.js');

    RestfulUrl::setUrls (implode('/', Router::$router['group']), $this->parents);

    $this->parent = $this->parents ? $this->parents[count ($this->parents) - 1] : null;
    
    $this->view->with ('parent', $this->parent)
               ->with ('parents', $this->parents)
               ->with ('obj', $this->obj);

    if (in_array ($name, array ('index', 'add', 'create', 'sorts')))
      return call_user_func_array (array ($this, $name), $this->parents);
    
    return $this->obj ? call_user_func_array (array ($this, $name), array ($this->obj)) : gg ('找不到該物件！');
  }
}
