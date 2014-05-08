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
	$out = "SET " . $key . ' ' . $value;
	$out = trim(preg_replace('/\s+/', ' ', $out));
	return $out;
}

function genListRedis($key, $value)
{
	$out = 'LPUSH ' . $key . ' "' . implode('" "', $value) . '"';
	$out = trim(preg_replace('/\s+/', ' ', $out));
	return $out;
}

function genHashRedis($key, $values)
{
	$out = 'HMSET ' . $key . ' ';
	foreach ($values as $k => $v) {
		$out .= $k . ' "' . $v . '" ';
	}
	$out = trim(preg_replace('/\s+/', ' ', $out));
	return $out;
}

function genSetRedis($key, $values)
{
	$out = 'SADD ' . $key . ' ' . implode(' ', $values);
	$out = trim(preg_replace('/\s+/', ' ', $out));
	return $out;
}

function genZSetRedis($key, $values)
{
	$out = 'ZADD ' . $key . ' ';
	foreach ($values as $k => $v) {
		$out .= $v . ' ' . $k . ' ';
	}
	$out = trim(preg_replace('/\s+/', ' ', $out));
	return $out;
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
				echo genStringRedis($rediskey, $value) . PHP_EOL;
				break;
			case Redis::REDIS_LIST:
				$value = $red->lRange($rediskey, 0, -1);
				echo genListRedis($rediskey, $value) . PHP_EOL;
				break;
			case Redis::REDIS_HASH:
				$value = $red->hGetAll($rediskey);
				echo genHashRedis($rediskey, $value) . PHP_EOL;
				break;
			case Redis::REDIS_SET:
				$value = $red->sMembers($rediskey);
				echo genSetRedis($rediskey, $value) . PHP_EOL;
				break;
			case Redis::REDIS_ZSET:
				$value = $red->zRange($rediskey, 0, -1, true);
				echo genZSetRedis($rediskey, $value) . PHP_EOL;
				break;
		}

	}
}

?>