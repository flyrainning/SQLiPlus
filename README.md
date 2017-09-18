# SQLiPlus
php 自带mysql连接库 mysqli的扩展，新增几个实用功能。

本项目已停止维护，请使用PDO版本[FPPDO](https://github.com/flyrainning/FPPDO)代替。
PHP7以上无法使用此版本，请使用PDO版本[FPPDO](https://github.com/flyrainning/FPPDO)

## 配置
```php
//设置全局变$_MYSQL_DB，存放数据库连接信息。若不设置$_MYSQL_DB，则必须在实例化的时候将$_MYSQL_DB的数组结构作为参数
$_MYSQL_DB=array(
	'server' => 'sqlserver.com',
	'user' => 'u',
	'passwd' => 'p',
	'db' => 'dbname',
	'charset' => 'utf8',

);
define('FPAPI',true);//引用前定义标志，页面会检测，你也可以从源文件中注释检测。
```

## 使用
```php
$sql=new MySQL();

//或者如果未定义$_MYSQL_DB

$sql=new MySQL(array(
	'server' => 'sqlserver.com',
	'user' => 'u',
	'passwd' => 'p',
	'db' => 'dbname',
	'charset' => 'utf8',
));


```

## 功能
*注意* 这只是mysqli的功能扩展，php自带mysqli类的功能均不影响使用。

```php
//打开一个表，打开后，后续所有操作都将给予这个表
$sql->table('table_name');

//变量格式化，防注入。SQLiPlus中自带功能已经内置，不需再格式化变量。
$value=$sql->v($_POST['value']);

//开启调试，开启后每次执行的sql语句都会通过echo输出
$sql->debug();

//通用查询
$sql->q('');
// 可执行任意sql语句，`{table}` 会转换为当前表名，? 可代入变量。
$sql->q("select * form `user` where `uid`='1' and `status`='2'");
//等同于
$sql->table('user');
$sql->q("select * form `{table}` where `uid`='1' and `status`='2'");
//等同于
$sql->table('user');
$uid='1';
$status='2';
$sql->q("select * form `{table}` where `uid`='?' and `status`='?'",$uid,$status);
//最后一种方法会将变量自动做防注入处理，推荐使用此方法。


//快速查询$sql->select(查询字段,条件[,变量列表]);
$sql->select("username,password,uid","`uid`='?' and `status`='?'",$uid,$status);
$sql->select("*","true");//全部记录

//格式化查询结果
$result=$sql->one();//返回一条查询结果
echo $result['username'];
echo $result['uid'];

$result_arr=$sql->arr();//返回所有结果数组
foreach($result_arr as $result){
  echo $result['username'];
  echo $result['uid'];
}

$sql->make_global();//将一条结果所有的字段转换为全局变量。
echo $username;
echo $uid;

//快速插入
$username='u';
$password='p';
$sql->insert('username,password');//自动搜寻字段同名全局变量，并将值作为记录插入数据库。

//插入的id
$id=$sql->lastid();

//快速删除$sql->delete(条件)
$uid='1';
$sql->delete("`uid`='?'",$uid);

//快速更新$sql->update(字段，条件字段)
$uid='1';
$username='u';
$password='p';
$sql->update("username,password",'uid');//自动搜寻字段同名全局变量，将uid=1的记录更新username和password

//当前结果纪录条数
$count=$sql->count();

//批量插入，使用prepare功能，大量数据建议采用此方法
$data=array(
  array(
    'username'=>'u1',
    'password'=>'p1',
  ),
  array(
    'username'=>'u2',
    'password'=>'p2',
  ),
);

$sql->insert_array($data);

//批量更新，同批量插入，采用sql中采用replace代替insert，所以更新数据集中必须包含主键等能为replace定位的字段
$sql->replace_array($data);



```
