<?php
/**
 * facade/DB.php @ XenOnline
 *
 * The facade of the database object.
 *
 * Authored by Moycat <moycat@makedie.net>
 * Licensed under GPLv2, see file LICENSE in this source tree.
 */

use MongoDB\Client;

class DB {
    private static $client;
    private static $db;
    private static $col;

    public static function init($host, $port, $database, $username, $password)
    {
        if ($username && $password) {
            $conn_string = 'mongodb://'. $username. ':'. $password. '@'.
                $host. ':'. $port;
        } else {
            $conn_string = 'mongodb://'. $host. ':'. $port;
        }
        self::$client = new Client($conn_string);
        self::$db = self::$client->selectDatabase($database);
        debug('Connected to the database.');
    }

    public static function select($collection)
    {
        self::$col = self::$db->selectCollection($collection);
    }

    public static function __callStatic($name, $arg)
    {
        return call_user_func_array([self::$col, $name], $arg);
    }
}