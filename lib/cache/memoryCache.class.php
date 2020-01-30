<?php
/********************************************
*                                           *
* Name    : Memory Cache Manager            *
* Author  : Windy2000                       *
* Time    : 2009-11-05                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT HOLD THIS ITEM PLEASE.      *
*                                           *
********************************************/

/**
How To Use:
$mc = new memoryCache(array (
            'server' => '127.0.0.1:18888',
            'weight' => '2',
            'persistant' => false,
            'timeout' => '1',
            'retry_interval' => 30,
            'expire' => 60*60*24,
));
*/
class memoryCache implements interface_cache {
    protected
        $mc = null,
        $mc_cnnopt = array();

    public function __construct($options) {
        if(!class_exists('Memcached')) {
            trigger_error('Cannot load Memcached Class!');
            exit;
        }
        $this->mc_cnnopt['expire'] = isset($options['expire']) ? $options['expire'] : 259200;
        $this->mc_cnnopt['persistant'] = isset($options['persistant']) ? $options['persistant'] :    null;
        $this->mc_cnnopt['weight'] = isset($options['weight']) ? $options['weight'] : 5;
        $this->mc_cnnopt['timeout'] = isset($options['timeout']) ? $options['timeout'] : 1;
        $this->mc_cnnopt['retry_interval'] = isset($options['retry_interval']) ? $options['retry_interval'] : 1;
        $this->mc = new Memcached($this->mc_cnnopt['persistant']);
        $this->mc->setOption(Memcached::OPT_CONNECT_TIMEOUT, $this->mc_cnnopt['timeout']);
        $this->mc->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 2);
        $this->mc->setOption(Memcached::OPT_RETRY_TIMEOUT, $this->mc_cnnopt['retry_interval']);
        $this->mc->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        $this->mc->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true);

        $server = explode(':', $options['server']);
        if(count($server)==1) $server[1] = '11211';
        $this->mc->addServer($server[0], $server[1], $this->mc_cnnopt['weight']);
        $this->check();
        return true;
    }

    public function set($key, $value = '', $ttl = 0) {
        if(empty($value)) {
            $this->mc->delete($key, 0);
        } else {
            if($ttl===0) $ttl = $this->mc_cnnopt['expire'];
            return $this->mc->set($key, $value, $ttl);
        }
    }

    public function get($key) {
        return $this->mc->get($key);
    }

    public function remove($key, $timeout = 0) {
        return $this->mc->delete($key, $timeout);
    }

    public function clean() {
        return $this->mc->flush();
    }

    public function close() {
        return $this->mc->close();
    }

    public function obj() {
        return $this->mc;
    }

    public function check() {
        $result = (INT)$this->mc->getResultCode();
        $codes = array(
            0 => 'MEMCACHED_SUCCESS',
            1 => 'MEMCACHED_FAILURE',
            2 => 'MEMCACHED_HOST_LOOKUP_FAILURE',
            3 => 'MEMCACHED_CONNECTION_FAILURE',
            4 => 'MEMCACHED_CONNECTION_BIND_FAILURE',
            5 => 'MEMCACHED_WRITE_FAILURE',
            6 => 'MEMCACHED_READ_FAILURE',
            7 => 'MEMCACHED_UNKNOWN_READ_FAILURE',
            8 => 'MEMCACHED_PROTOCOL_ERROR',
            9 => 'MEMCACHED_CLIENT_ERROR',
            10 => 'MEMCACHED_SERVER_ERROR',
            11 => 'MEMCACHED_ERROR',
            12 => 'MEMCACHED_DATA_EXISTS',
            13 => 'MEMCACHED_DATA_DOES_NOT_EXIST',
            14 => 'MEMCACHED_NOTSTORED',
            15 => 'MEMCACHED_STORED',
            16 => 'MEMCACHED_NOTFOUND',
            17 => 'MEMCACHED_MEMORY_ALLOCATION_FAILURE',
            18 => 'MEMCACHED_PARTIAL_READ',
            19 => 'MEMCACHED_SOME_ERRORS',
            20 => 'MEMCACHED_NO_SERVERS',
            21 => 'MEMCACHED_END',
            22 => 'MEMCACHED_DELETED',
            23 => 'MEMCACHED_VALUE',
            24 => 'MEMCACHED_STAT',
            25 => 'MEMCACHED_ITEM',
            26 => 'MEMCACHED_ERRNO',
            27 => 'MEMCACHED_FAIL_UNIX_SOCKET',
            28 => 'MEMCACHED_NOT_SUPPORTED',
            29 => 'MEMCACHED_NO_KEY_PROVIDED',
            30 => 'MEMCACHED_FETCH_NOTFINISHED',
            31 => 'MEMCACHED_TIMEOUT',
            32 => 'MEMCACHED_BUFFERED',
            33 => 'MEMCACHED_BAD_KEY_PROVIDED',
            34 => 'MEMCACHED_INVALID_HOST_PROTOCOL',
            35 => 'MEMCACHED_SERVER_MARKED_DEAD',
            36 => 'MEMCACHED_UNKNOWN_STAT_KEY',
            37 => 'MEMCACHED_E2BIG',
            38 => 'MEMCACHED_INVALID_ARGUMENTS',
            39 => 'MEMCACHED_KEY_TOO_BIG',
            40 => 'MEMCACHED_AUTH_PROBLEM',
            41 => 'MEMCACHED_AUTH_FAILURE',
            42 => 'MEMCACHED_AUTH_CONTINUE',
            43 => 'MEMCACHED_PARSE_ERROR',
            44 => 'MEMCACHED_PARSE_USER_ERROR',
            45 => 'MEMCACHED_DEPRECATED',
            46 => 'MEMCACHED_IN_PROGRESS',
            47 => 'MEMCACHED_SERVER_TEMPORARILY_DISABLED',
            48 => 'MEMCACHED_SERVER_MEMORY_ALLOCATION_FAILURE',
            49 => 'MEMCACHED_MAXIMUM_RETURN',
        );
        if($result>0) {
            if(isset($codes[$result])) {
                trigger_error('Memcached error: '.$codes[$result]);
            } else {
                trigger_error('Memcached unknown error - '.$result);
            }
        }
    }
}
?>