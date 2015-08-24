<?php
if (!defined('FPAPI')) die();

class MySQL extends mysqli {
	public $conn;
	public $debug;
	public $table;
	Private $dbbegin;
	public $result;
	public $DB;
	
	function __construct($DB='') {
		//parent::__construct();
		if (empty($DB)){
			global $_MYSQL_DB;
			$DB=$_MYSQL_DB;
		}
		$this->debug=false;
		
		$this->DB=$DB;
		
		$this->conn = parent::__construct($DB['server'],$DB['user'],$DB['passwd'],$DB['db']);
		if (mysqli_connect_error()) {
			die('Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error());
		}
		$this->set_charset($DB['charset']);
		return $this->conn;
	}
	
	
	Protected function sql_query($sql){
		if (!empty($this->debug)) echo '---: '.$sql.' ---';
		return $this->query($sql);
	}
	public function debug($dbg=true){
		if ($dbg){
			$this->debug=true;
		}else{
			$this->debug='';
		}
	}
	Protected function v_arr($arr){
		foreach ($arr as $k=>$v){
			$arr[$k]=$this->real_escape_string($v);
		}
		return $arr;

	}
	public function v($var){
		return $this->real_escape_string($var);
	}
	
	public function table($table,$notchange=false){
		return $this->table=$table;
	}
	
	public function arr($result=''){

		$res=array();
		if (empty($result)){
			$result=$this->result;
		}
		
		if (is_object($result)&&($result->num_rows>0)) {
			/*while($arr=mysql_fetch_assoc($result)){
				$res[]=$arr;
			}*/
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) $res[]=$row;
		}
		return $res;
	}
	public function one($result=''){
		$res=array();
		if (empty($result)){
			$result=$this->result;
		}
		
		if (is_object($result)&&($result->num_rows>0)) {
			$res=$result->fetch_array(MYSQLI_ASSOC);
		}
		return $res;
	}
	public function make_global($result='',$header='',$replace=true){//header : 变量前缀
		$res=false;
		if (is_array($result)){
			$arr=$result;
		}else{
			if (empty($result)){
		
				$result=$this->result;
			}

		
			$arr=$this->one($result);
		}
		
		foreach($arr as $k=>$v){
			$keyname=$header.$k;
			global ${$keyname};

			if ((isset(${$keyname})) &&(!$replace)) $this->sql_die('sql var repeated : '.$keyname);
			${$keyname}=$v;
			$res=true;
		}
		return $res;

	}
	public function lastid(){
		return $this->insert_id;
	}
	
	public function insert($arr){

		$res='';
		$arr=strtoarray($arr);
		if (empty($this->table)) $this->sql_die('sql table error:');
		
		$ks='';
		$vs='';
		foreach($arr as $v){
			$v=trim($v);
			$v=trim($v,'`');
			$ks.="`$v`,";

			global ${$v};
			if (!isset(${$v})) $this->sql_die('sql var error : '.$v);
			$_tmp_v=$this->real_escape_string(${$v});
			$vs.="'$_tmp_v',";
	
		}
		$ks=trim($ks,',');
		$vs=trim($vs,',');

		if (!empty($ks)) $res="INSERT INTO  `".$this->table."` ($ks) VALUES ($vs);";
		return $this->sql_query($res);
	
	}
	public function insert_array($arr){
		return $this->set_array($arr,'insert');
	}
	public function replace_array($arr){
		return $this->set_array($arr,'replace');
	}
	public function set_array($iarr,$act="insert"){

		$res=0;
		if (empty($this->table)) $this->sql_die('sql table error:');

		if (is_array($iarr)){
			

			
			if (!is_array(current($iarr))) $iarr=array($iarr);			
	
			
			$result=$this->sql_query("show columns from `".$this->table."`");
			$farr=$this->arr($result);
			$allfield=array();
			$ks='';
			$vs='';
			$ktype='';
		
			foreach($farr as $f){
				$allfield[]=$f['Field'];
				$ks.="`".$f['Field']."`,";
				$vs.="?,";
				
				
				$_types=strtolower($f['Type']);
				if (strpos($_types,'int')!==FALSE){
					$ktype.='i';
			
				}else if (strpos($_types,'float')!==FALSE){
					$ktype.='d';
			
				}else if (strpos($_types,'double')!==FALSE){
					$ktype.='d';
			
				}else if (strpos($_types,'real')!==FALSE){
					$ktype.='d';
			
				}else if (strpos($_types,'bool')!==FALSE){
					$ktype.='b';
		
				}else{
					$ktype.='s';
				}
		
		
			}
			$ks=trim($ks,',');
			$vs=trim($vs,',');
			
			$sqls=$act." INTO  `".$this->table."` ($ks) VALUES ($vs);";
			
			
			
			if ($stmt = $this->prepare($sqls)) {
				
				$p=array($ktype);
				$pp=array();
				foreach($allfield as $f){
					$p[]='';
				}
				foreach($p as $k=>$v) $pp[] = &$p[$k];
				call_user_func_array(array($stmt, 'bind_param'),$pp);
				
				while($ia=array_shift($iarr)){

	
					
					$i=1;
					foreach($allfield as $f){
						$fv=(isset($ia[$f]))?$ia[$f]:'';
						$p[$i]=$fv;
						$i++;
					}

					$stmt->execute();
					$res+=$stmt->affected_rows;
				}

				$stmt->close();
				
				/*while($ia=array_shift($iarr)){

	
					$p=array($ktype);
					$pp=array();
					foreach($allfield as $f){
						$fv=(isset($ia[$f]))?$ia[$f]:'';
						$p[]=$fv;
					}
					foreach($p as $k=>$v) $pp[] = &$p[$k];
			
					
					call_user_func_array(array($stmt, 'bind_param'),$pp);
				
					$stmt->execute();
					$res+=$stmt->affected_rows;
				}

				$stmt->close();*/
				
				
			}
			
		}
		
		return $res;
	
	}
	
	public function q($sql){


		$pars=array();
	
		if (!empty($this->table)){
			$sql=str_replace('`{_table_}`','`'.$this->table.'`',$sql);
			$sql=str_replace('`{table}`','`'.$this->table.'`',$sql);
		}
		$str=$sql;
	
		$pa=func_get_args();
		if (isset($pa[1])){
	
			$p=array();
			$pos=0;
			$args_num = func_num_args();

			for($i=1;$i<=$args_num-1;$i++){
			
				if ((!is_string($pa[$i])) and (!is_numeric($pa[$i]))) $this->sql_die('sql var error');
		
				$vv=$this->real_escape_string($pa[$i]);
		

				$pos = strpos($sql, '?',$pos);
				if ($pos === false) {

				}else{
			
					$sql=substr_replace($sql,$vv,$pos,1);
					$pos=$pos+strlen($vv);
				}
			
			}
	
		
		}


		$this->result=$this->sql_query($sql);
		return $this->result;

	}
	
	public function select($arr='*',$where='true'){

		$res='';
		$ks='';
		if (empty($arr)) $arr='*';
		if (empty($this->table)) $this->sql_die('sql table error');
		$arr=strtoarray($arr);
		foreach($arr as $v){
			$v=trim($v);
			$v=trim($v,'`');
			$v=trim($v);
			if ($v=='*') {
				$ks.="$v,";
			}else if ((strpos($v, '(') !== false) || (strpos($v, ' ') !== false)){
				$ks.="$v,";
		
			}else{
				$ks.="`$v`,";
			}
		}
		$ks=trim($ks,',');
	
		$pa=func_get_args();
		if (isset($pa[2])){
	
			$p=array();
			$args_num = func_num_args();

			for($i=2;$i<=$args_num-1;$i++){
				$p=array_merge($p,strtoarray($pa[$i]));
			
			}

			foreach($p as $s){
		

				$vv=$this->real_escape_string($s);
		

		
				$pos = strpos($where, '?');
				if ($pos === false) {

				}else{
			
					$where=substr_replace($where,$vv,$pos,1);
				}
			
			}
	
		
		}
	
		$res="SELECT $ks FROM `".$this->table."` where $where";

		$this->result=$this->sql_query($res);
		return $this->result;

	}
	
	public function delete($where='true'){

		$res='';
		if (empty($this->table)) $this->sql_die('sql table error');
	
		$pa=func_get_args();
		if (isset($pa[1])){
	
			$p=array();
			$args_num = func_num_args();

			for($i=1;$i<=$args_num-1;$i++){
				$p=array_merge($p,strtoarray($pa[$i]));
			
			}

			foreach($p as $s){
				$vv=$this->real_escape_string($s);
				$count=1;
				$where=str_replace('?',$vv,$where,$count);

			}
	
		
		}

		$res="DELETE FROM `".$this->table."` where $where";
		
		$this->result=$this->sql_query($res);
		return $this->result;
	}
	
	public function update($arr,$where,$mywhere=''){

		$res='';
		$arr=strtoarray($arr);
		if (empty($this->table)) $this->sql_die('sql table error');
		
		$ks='';

		foreach($arr as $v){
			$v=trim($v);
			$v=trim($v,'`');
			global ${$v};
			if (!isset(${$v})) $this->sql_die('sql var error : '.$v);
			$_tmp_v=$this->real_escape_string(${$v});
			$ks.="`$v`='$_tmp_v',";
	
		}
		$ks=trim($ks,',');
	
		if (!empty($where)){
			global ${$where};
			if (!isset(${$v})) $this->sql_die('sql var error');
			$where="`$where`='${$where}'";
		}
	
		if (empty($where)&& empty($mywhere)) $this->sql_die('sql where error');
	
		if (!empty($ks)) $res="UPDATE `".$this->table."` SET $ks WHERE $where $mywhere;";
	
	
		$this->result=$this->sql_query($res);
		return $this->result;
	
	}
	
	public function count($result=''){

		if (empty($result)){
		
			$result=$this->result;
		}
		if (empty($result)) $this->sql_die('sql result error');
		return $result->num_rows;
	}


	public function mbegin(){

		if (!$this->dbbegin){
			$this->dbbegin=$this->begin_transaction()?true:false;
		}
	
		return $this->dbbegin;

	}
	public function mrollback(){

	
		$this->dbbegin=$this->rollback()?false:true;
		return $this->dbbegin;

	}
	public function mcommit(){


		$this->dbbegin=$this->commit()?false:true;
		return $this->dbbegin;

	}
	public function merror(){

		if ((isset($this->dbbegin))&&($this->dbbegin)){
			$this->mrollback();
		}

		$msg=(!empty($this->debug))?$this->error:'数据库遇到错误';
		
		return $msg;
	}
	public function sql_die($msg=''){

		if ((isset($this->dbbegin))&&($this->dbbegin)){
			$this->mrollback();
		}

		die($msg);
		
	}
	
}

?>
