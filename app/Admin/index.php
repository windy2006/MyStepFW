<?php
/**
 * 文件：app/Admin/index.php
 * 功能说明：默认入口按应用内页面清单分发，与显式路由共用认证和模块执行流程。
 * 方法目录：无新增函数。
 * 使用说明：由框架包含，使用已初始化的info_app；不重新启动框架或会话。
 */
Admin::dispatch(implode('/', $info_app['path']??[]));
