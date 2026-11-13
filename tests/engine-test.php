<?php
if (PHP_SAPI !== 'cli') { exit; }
require_once __DIR__ . '/../includes/class-fcoi-engine.php';
$n=0;
function cat($id,$s='',$d='',$sex='U'){ return array('id'=>$id,'name'=>$id,'sex'=>$sex,'sire'=>$s,'dam'=>$d); }
function check($label,$actual,$expected){global $n;if(abs($actual-$expected)>1e-12)throw new Exception("$label: $actual != $expected");$n++;echo "PASS $label\n";}
function rejected($label,$rows){global $n;try{new FCOI_Engine($rows);}catch(InvalidArgumentException $e){$n++;echo "PASS $label\n";return;}throw new Exception("Accepted invalid pedigree: $label");}
$base=array(cat('A'),cat('B'),cat('C'),cat('D'),cat('son','A','B','M'),cat('daughter','A','B','F'),cat('half','A','C','F'),cat('unrelated','C','D','F'));
$e=new FCOI_Engine($base);
check('unrelated pair',$e->calculate('son','unrelated')['coi'],0);
check('full siblings',$e->calculate('son','daughter')['coi'],.25);
check('half siblings',$e->calculate('son','half')['coi'],.125);
check('parent offspring',$e->calculate('A','daughter')['coi'],.25);
check('founder',$e->calculate('A')['coi'],0);
$rows=array_merge($base,array(cat('cousin1','son','C','M'),cat('cousin2','D','daughter','F'),cat('kitten','son','daughter'),cat('grandchild','son','C','F')));
$e=new FCOI_Engine($rows);
check('first cousins',$e->calculate('cousin1','cousin2')['coi'],.0625);
check('grandparent grandchild',$e->calculate('A','grandchild')['coi'],.125);
check('individual kitten',$e->calculate('kitten')['coi'],.25);
$r=$e->calculate('kitten');check('parent slots',$r['coverage'][0]['known'],2);check('grandparent slots (repeated)',$r['coverage'][1]['known'],4);check('missing great grandparents',$r['coverage'][2]['known'],0);
check('record order independent',(new FCOI_Engine(array_reverse($rows)))->calculate('kitten')['coi'],.25);
// Inbred parent: its relationship to its own offspring includes its inbreeding.
$rows[]=cat('inbred_child','kitten','C');
$e=new FCOI_Engine($rows);check('inbred parent adjustment',$e->calculate('kitten','inbred_child')['coi'],.3125);
// Independent tabular relationship matrix oracle on deterministic random DAGs.
mt_srand(917);for($trial=0;$trial<25;$trial++){$rows=array();$matrix=array();for($i=0;$i<30;$i++){$s=$i>2&&mt_rand(0,4)>0?mt_rand(0,$i-1):-1;$d=$i>2&&mt_rand(0,4)>0?mt_rand(0,$i-1):-1;if($d===$s)$d=-1;$rows[]=cat('n'.$i,$s<0?'':'n'.$s,$d<0?'':'n'.$d);$matrix[$i]=array();for($j=0;$j<$i;$j++){$v=.5*(($s<0?0:$matrix[$s][$j])+($d<0?0:$matrix[$d][$j]));$matrix[$i][$j]=$v;$matrix[$j][$i]=$v;}$matrix[$i][$i]=1+($s<0||$d<0?0:.5*$matrix[$s][$d]);}shuffle($rows);$engine=new FCOI_Engine($rows);for($i=0;$i<30;$i++){check('oracle '.$trial.':'.$i,$engine->calculate('n'.$i)['coi'],$matrix[$i][$i]-1);}}
rejected('self parent',array(cat('a','a')));rejected('missing parent',array(cat('a','missing')));rejected('cycle',array(cat('a','b'),cat('b','a')));rejected('same parents',array(cat('a'),cat('b','a','a')));rejected('duplicate IDs',array(cat('a'),cat('a')));rejected('wrong sex',array(cat('a','','','F'),cat('b','a')));
$rows=array(cat('n0'));for($i=1;$i<52;$i++)$rows[]=cat('n'.$i,'n'.($i-1));rejected('depth limit',$rows);
check('unknown single parent',(new FCOI_Engine(array(cat('a'),cat('b','a'))))->calculate('b')['coi'],0);
echo "All $n assertions passed.\n";
