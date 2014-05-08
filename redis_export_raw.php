#!/usr/bin/php -q
<?php

if (!extension_loaded('redis')) {
	die("ERROR: You need to have the Redis extension loaded.".PHP_EOL);
}

$argc = $_SERVER["argc"];
$argv = $_SERVER["argv"];

$options = getopt('P:h:p::');

if (!isset($options['P']) || !isset($options['h'])) {
	die("Usage ./redis_export.php -P<pattern> -h<host> -p<port>\n" .
		" e.g. php -f redis_export.php -P\"KeyphraseLookup *\" -h\"localhost\" -p6379" .
		"      \n");
}
$pattern = $options['P'];
$host = $options['h'];
$port = !empty($options['p']) ? $options['p'] : 6379;

// connect to redis (locally)
$red = new Redis();
$red->connect($host, $port);

try {
	$red->ping();
} catch (RedisException $e) {
	die("ERROR: Cannot reach the Redis server. Please check the parameters".PHP_EOL);
}

$red->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);

// function to generate a key value "SET" command in Redis protocol
function genStringRedis($key, $value)
{
	$value = trim(preg_replace('/\s+/', ' ', $value));
	$out = "*3\r\n$3\r\nSET\r\n$" . strlen($key) . "\r\n" . $key . "\r\n$" . strlen($value) . "\r\n" . $value . "\r\n";
	echo $out;
}

function genListRedis($key, $value)
{
	foreach ($value as $v) {
		$v = trim(preg_replace('/\s+/', ' ', $v));
		$out = "*3\r\n$5\r\nLPUSH\r\n$" . strlen($key) . "\r\n" . $key . "\r\n$" . strlen($v) . "\r\n" . $v . "\r\n";
		echo $out;
	}
}

function genHashRedis($key, $values)
{
	foreach ($values as $k => $v) {
		$v = trim(preg_replace('/\s+/', ' ', $v));
		$out = "*4\r\n$5\r\nHMSET\r\n$" . strlen($key) . "\r\n" . $key . "\r\n$" . strlen($k) . "\r\n" . $k . "\r\n$" . strlen($v) . "\r\n" . $v . "\r\n";
		echo $out;
	}

}

function genSetRedis($key, $values)
{
	foreach ($values as $k => $v) {
		$v = trim(preg_replace('/\s+/', ' ', $v));
		$out = "*3\r\n$4\r\nSADD\r\n$" . strlen($key) . "\r\n" . $key . "\r\n$" . strlen($v) . "\r\n" . $v . "\r\n";
		echo $out;
	}
}

function genZSetRedis($key, $values)
{
	foreach ($values as $k => $v) {
		$v = trim(preg_replace('/\s+/', ' ', $v));
		$out = "*4\r\n$4\r\nZADD\r\n$" . strlen($key) . "\r\n" . $key . "\r\n$" . strlen($v) . "\r\n" . $v . "\r\n$" . strlen($k) . "\r\n" . $k . "\r\n";
		echo $out;
	}
}

// get all the redis keys that need exporting
$allkeys = $red->keys($pattern);

// foreach key we find
foreach ($allkeys as $rediskey) {
	if ($rediskey) {
		// get it
		$type = $red->type($rediskey);

		switch ($type) {
			case Redis::REDIS_STRING:
				$value = $red->get($rediskey);
				genStringRedis($rediskey, $value);
				break;
			case Redis::REDIS_LIST:
				$value = $red->lRange($rediskey, 0, -1);
				genListRedis($rediskey, $value);
				break;
			case Redis::REDIS_HASH:
				$value = $red->hGetAll($rediskey);
				genHashRedis($rediskey, $value);
				break;
			case Redis::REDIS_SET:
				$value = $red->sMembers($rediskey);
				genSetRedis($rediskey, $value);
				break;
			case Redis::REDIS_ZSET:
				$value = $red->zRange($rediskey, 0, -1, true);
				genZSetRedis($rediskey, $value);
				break;
		}

	}
}

?>