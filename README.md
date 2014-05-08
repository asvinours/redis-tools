redis-tools
===========

Redis command-line tools to allow mass export and deletion of keys

## Introduction

Redis doesn't come with any tools to allow groups of keys to be exported. You can build a database, save it (as dump.rdb) and use it as another Redis instance's database, but sometimes you just want to export a group of keys from one database (e.g. your staging environment) and apply them to another database (e.g. production).

Requirements:
* PHP with Redis extension

## Redis export

This tool exports a group of single key/value pairs to stdout. The output format is the plain text protocol used by Redis e.g.

```
  # export all keys starting with ABC to stdout
  ./redis_export_raw.php -P"ABC*" -h"localhost"
  
  # export keys with ABC anywhere in the key, to a file
  ./redis_export_raw.php -P"*ABC* -h"localhost" > text.txt
  
  # export all keys to a file
  ./redis_export_raw.php -P"*" -h"localhost" > test.txt
  
```

If you want a list a command, in a human format:

```
  # export all keys starting with ABC to stdout
  ./redis_export.php -P"ABC*" -h"localhost"

  # export keys with ABC anywhere in the key, to a file
  ./redis_export.php -P"*ABC* -h"localhost" > text.txt

  # export all keys to a file
  ./redis_export.php -P"*" -h"localhost" > test.txt

```

## Redis import

Data exported using "redis_export.php", can be imported directly into a Redis server:

```
  # apply the data stored in test.txt to the local Redis server
  cat test.txt | redis-cli -h new_server.local --pipe

```

If you have used the second script, you now have a list of human format command

```
  cat test.txt | redis-cli -h new_server.local

```


## Redis delete

To delete a range of keys:

```
  # delete all keys starting with ABC
  ./redis_delete.php -P'ABC*' -h"localhost"

```
