<?php
/**
 * 文件：app/Admin/lib.php
 * 功能说明：加载当前应用辅助类，供核心类和功能模块使用。
 * 方法目录：无新增函数；辅助能力由AdminService和AdminStore提供。
 * 使用说明：框架及Admin核心按需require_once，不输出页面，不执行写入操作。
 */
namespace app\Admin;
require_once __DIR__.'/AdminStore.class.php';
require_once __DIR__.'/AdminService.class.php';
