<?php
use Workerman\Worker;
use Workerman\Lib\Timer;
require_once './Workerman/Autoloader.php';
require_once './db.php';
require_once './solution.php';
require_once './functions.php';
require_once '../../mo-config.php';

$worker_tasker = new Worker("Text://0.0.0.0:6666");
$worker_tasker->name = 'Tasker';

$task = array();
$cid = array();

$worker_tasker->onWorkerStart = function($worker_tasker)
{
	global $db;
	$db = new DB();
	$db->init( DB_HOST, DB_USER, DB_PASS, DB_NAME );
	while ( !$db->connect() ) // 未连接自动重连
	{
		sleep(1);
	}
	Timer::add(3, function()use($worker_tasker) // 每3秒，自动发送心跳包
	{
		foreach($worker_tasker->connections as $connection)
			sendMsg($connection, array('action' => 'online'));
	});
	Timer::add(10, 'check_lost'); // 每10秒，检查无响应的评测请求
 };

$worker_tasker->onConnect = function($connection)
{
	$connection->IP = $connection->getRemoteIp();
	$connection->cid = 0;
	$connection->name = '';
	$connection->timestamp = 0;
	$connection->deadline = Timer::add(5, function()use($connection) // 登录限时5秒，超时断开连接
	{
		Timer::del($connection->deadline);
		$connection->deadline = 0;
		sendMsg($connection, array('action' => 'refuse'));
		$connection->close();
		p("A client timeout logging in. ( IP = $connection->IP )");
	});
	p("A new client has joined. ( IP = $connection->IP )");
};

$worker_tasker->onMessage = function($connection, $data)
{
	$data = json_decode($data, True);
	if ($data == NULL || !isset($data['action']))
	{
		p("Json decoding failed or in bad format. ( cid = $connection->cid, IP = $connection->IP )");
		return;
	}
	if (isset($data['timestamp']))
	{
		if ((int)$data['timestamp'] < $connection->timestamp)
			return;
		else
			$connection->timestamp = (int)$data['timestamp'];
	}
	switch ($data['action'])
	{
		case 'heartbeat': // 评测端信息更新
			heartbeat($connection, $data);
			break;
		case 'login': // 评测端登录
			login($connection, $data);
			break;
		default:
			p("Unknown Action ( cid = $connection->cid, IP = $connection->IP )");
	}
	
	debuggy();
};

$worker_tasker->onClose = function($connection)
{
	global $cid;
	if($connection->deadline)
		Timer::del($connection->deadline);
	if (isset($cid[$connection->cid]))
		unset($cid[$connection->cid]);
    p("A client closed the connection. ( cid = $connection->cid, IP = $connection->IP )");
};

Worker::runAll();

function debuggy()
{
	$s = new Solution(array('sid'=>1, 'pid'=>1, 'uid'=>1, 'lang'=>1, 'code'=>'1234aaa'));
	$s->push();
}
