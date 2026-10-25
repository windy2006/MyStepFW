<?php
/**
 * 文件：plugin/redis/index.php (new)
 * 功能说明：应用绑定时提供Redis业务客户端声明，框架存储由早期加载器负责。
 * 方法目录：本文件不直接声明方法或函数。
 * 使用说明：不覆盖全局缓存、不在每个页面连接服务器；应用module显式调用plugin_redis::options()。
 */
myStep::storagePlugin('redis');
