<?php
if (PHP_SAPI !== 'cli') { exit; }
/** Minimal WordPress contract harness. Not a live WordPress integration test. */
define('ABSPATH',__DIR__.'/');define('ARRAY_A','ARRAY_A');
$uid=7;$allowed=true;$admin=false;$routes=array();$hooks=array();
function add_action($name,$fn){global $hooks;$hooks[$name][]=$fn;}
function add_filter($name,$fn){}
function register_activation_hook($file,$fn){}
function add_shortcode($name,$fn){}
function register_rest_route($ns,$path,$args){global $routes;$routes[$path]=$args;}
function is_user_logged_in(){global $uid;return $uid>0;}
function current_user_can($cap){global $allowed,$admin;return $cap==='manage_options'?$admin:$allowed;}
function get_current_user_id(){global $uid;return $uid;}
function sanitize_text_field($v){return trim(strip_tags($v));}
function sanitize_file_name($v){return preg_replace('/[^a-zA-Z0-9._-]/','',$v);}
function wp_json_encode($v){return json_encode($v);}
class WP_Error{public $code,$message,$data;function __construct($c,$m,$d){$this->code=$c;$this->message=$m;$this->data=$d;}}
class Request implements ArrayAccess{private $r;function __construct($r){$this->r=$r;}function get_body(){return json_encode($this->r);}function get_json_params(){return $this->r;}function offsetExists($k):bool{return isset($this->r[$k]);}function offsetGet($k):mixed{return $this->r[$k];}function offsetSet($k,$v):void{$this->r[$k]=$v;}function offsetUnset($k):void{unset($this->r[$k]);}}
class FakeDB {
 public $prefix='wp_',$rows=array(),$docs=array(),$last=array();
 function prepare($sql,...$args){$this->last=array($sql,$args);return array($sql,$args);}
 function get_row($q,$format){list($sql,$a)=$q;if(strpos($sql,'fcoi_documents')!==false){$doc=$this->docs[$a[0]]??null;return $doc&&$doc['user_id']===$a[1]?$doc:null;}return $this->rows[$a[0]]??null;}
 function query($q){list($sql,$a)=$q;if(strpos($sql,'INSERT IGNORE')===0){if(!isset($this->rows[$a[0]]))$this->rows[$a[0]]=array('revision'=>0,'pedigree'=>'[]');return 1;}if(strpos($sql,'UPDATE')===0){list($json,$id,$rev)=$a;if($this->rows[$id]['revision']!==$rev)return 0;$this->rows[$id]=array('revision'=>$rev+1,'pedigree'=>$json);return 1;}throw new Exception('Unexpected SQL');}
 function delete($table,$where,$formats){if(strpos($table,'documents')!==false){$id=$where['id'];if(isset($this->docs[$id])&&$this->docs[$id]['user_id']===$where['user_id']){unset($this->docs[$id]);return 1;}return 0;}return 0;}
}
$wpdb=new FakeDB();require __DIR__.'/../feline-coi.php';foreach($hooks['rest_api_init'] as $fn)$fn();
$n=0;function ok($condition,$label){global $n;if(!$condition)throw new Exception($label);$n++;echo "PASS $label\n";}
$uid=0;ok(!fcoi_permission(),'signed-out access rejected');$uid=7;ok(fcoi_permission(),'signed-in read access');$allowed=false;ok(!fcoi_permission(),'capability required');$allowed=true;
ok(count($routes)===5,'all route groups registered');foreach($routes as $route){foreach(isset($route['callback'])?array($route):$route as $r)ok(isset($r['permission_callback']),'route permission callback');}
$cat=array('id'=>'a','name'=>'<b>Alpha</b>','sex'=>'M','sire'=>'','dam'=>'','registration'=>'1234-123456');
$r=fcoi_save(new Request(array('revision'=>0,'cats'=>array($cat))));ok($r['revision']===1&&$r['cats'][0]['name']==='Alpha','save validates and sanitizes');
$r=fcoi_save(new Request(array('revision'=>0,'cats'=>array())));ok($r instanceof WP_Error&&$r->data['status']===409,'stale write rejected');
$r=fcoi_calculate(new Request(array('id'=>'a')));ok($r['percent']===0.0&&$r['revision']===1,'server calculates saved pedigree');
$uid=8;ok(fcoi_read()['cats']===array(),'other account cannot read pedigree');$r=fcoi_calculate(new Request(array('id'=>'a')));ok($r instanceof WP_Error,'other account cannot calculate private cat');
$wpdb->docs[1]=array('user_id'=>7,'cat_id'=>'a','name'=>'a.pdf','mime'=>'application/pdf','content'=>'%PDF-test');
$r=fcoi_docs_get(new Request(array('id'=>1)));ok($r instanceof WP_Error&&$r->data['status']===404,'other account cannot download document');
$r=fcoi_docs_delete(new Request(array('id'=>1)));ok($r['deleted']===false&&isset($wpdb->docs[1]),'other account cannot delete document');
$uid=7;ok(fcoi_docs_get(new Request(array('id'=>1)))['data']===base64_encode('%PDF-test'),'owner can retrieve document');
ok(!fcoi_reader_enabled(),'reader disabled without key and model');
$r=fcoi_docs_upload(new Request(array('cat_id'=>'a','name'=>'x.html','data'=>base64_encode('<script>bad</script>'))));ok($r instanceof WP_Error,'HTML upload rejected');
$r=fcoi_docs_upload(new Request(array('cat_id'=>'missing','name'=>'x.pdf','data'=>base64_encode('%PDF-test'))));ok($r instanceof WP_Error,'document requires owned saved cat');
$bad=$cat;$bad['sire']='a';$r=fcoi_save(new Request(array('revision'=>1,'cats'=>array($bad))));ok($r instanceof WP_Error,'invalid graph cannot be saved');ok(fcoi_read()['revision']===1,'invalid graph leaves saved data intact');
echo "All $n WordPress contract assertions passed.\n";
