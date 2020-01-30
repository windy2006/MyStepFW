<?php
interface interface_session {
    public static function open($sess_path, $sess_name);
    public static function close();
    public static function read($sid);
    public static function write($sid, $sess_data);
    public static function destroy($sid);
    public static function gc($maxlifetime);
}