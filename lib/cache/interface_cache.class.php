<?php
interface interface_cache {
	public function set($key, $value, $ttl);
	public function get($key);
	public function remove($key);
	public function clean();
}