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

// get all the redis keys that need exporting
$allkeys = $red->keys($pattern);

// foreach key we find
foreach ($allkeys as $rediskey) {
	if ($rediskey) {
		// delete it
		$red->del($rediskey);
	}
}

echo "Deleted " . count($allkeys) . " keys\n";

?>