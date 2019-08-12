整体描述：
--------
迈思框架（MyStep Framework）是一套基于 <b>[PHP 7.0]</b> 的web开发框架，旨在构建一个可以便捷调用常用功能，以最简洁的代码实现目标功能，同时具备高度可扩展性，可通过代理模式，方便的将第三方功能模块集成到框架中。
- 路由系统 - 框架通过 rewrite 方法接管所有相应，除 static 目录和自定义扩展类型外，其他文件均无法直接通过 url 访问，兼具高可控性和安全性。 （IIS对应web.config，Apache对应.htaccess，NginX需参考目录下文件手动添加）。  
为增加环境适应度，框架也支持QueryString和PathInfo模式，页面中站内URL只需要按照rewrite的模式书写（相对于框架目录，首位无需加"/"），框架将自动调整为对应模式。
- 模版系统 - 采用二次编译模式，严格实现模板与程序的分离，通过通俗的标签模式调用各类数据。基本模板格式简单易学，方便制作，只要对HTML有一定了解的设计师均可以很快上手，模板修改后即时生效。同时具备高度可扩展性，可根据实际需要任意扩充模版标签。  
- 插件系统 - 可插件模式扩展框架功能，无论是功能增强、系统优化、前台展示均可与系统无缝连接。内容评分、评论、投票、专题、检索、采集、统计等都可以通过插件实现，并可以无缝结合到系统中。  
- 应用接口 - 系统为各类插件提供了丰富的接口，无论是api、模板标签、代码嵌入、脚本附加、登录处理，都可以通过系统接口便捷地实现，为二次开发或插件开发提供最大限度的支持和自由。  
- 多语言支持 - 系统可以随意添加语言包，通过调整参数立即变化。  
- 缓存机制 - 通过三级缓存保证高效  
   - 数据缓存，用于缓存从数据库查询出的结果集，包含自建文件和数据库两种模式，也可通过代理模式扩展；  
   - 页面缓存，可将解析好的页面整体缓存到缓存文件，在过期前不用再次生成页面，即实现了静态化的效果，也保留了动态脚本的特性；  
   - 浏览器缓存，通过etag标识，在客户端再次请求页面数据时，如页面未发生变化，则直接从客户端缓存调用数据，减少了对服务器带宽的请求。

公共函数：
--------
- getMicrotime($rate) - 获取微秒时间
- getTimeDiff($time_start, $decimal, $micro) - 取得时间差
- getDate_cn($date) - 获取中文日期
- tinyUrl($url) - 获取短网址
- isMobile() - 判断是否为移动设备
- isHttps() - 判断当前是否为SSL链接
- myEval($code) - 自定义代码执行
- recursionFunction($func, $para) - 递归执行某一函数
- debug系列函数 - 变量情况查看

执行顺序：
--------
- 自定义路径模式 - index.php（调用myStep::init()） -> app/[name]/lib.php（应用目录下的预载文件，调用myStep::setPara()，可在此函数调用后，对部分参数做相关修正）-> myStep::getModule()（可以添加其他前置检验函数）
- 程序路径模式 - index.php（调用myStep::init()） -> app/[name]/index.php（程序目录下的控制文件，建议在此调用预载文件做相关参数初始化）

自动载入：
--------
- 静态文件 - 将自动载入应用路径下的'asset/style.css'和'asset/模版样式/style.css'，以及'asset/function.js'和'asset/模版样式/function.js'（自动判断是否存在）
- 模块入口 - 将按如下次序，载入应用目录下'module'子目录总首先出现的'模版样式/模块名称.php'，'模块名称.php'，'模版样式/index.php'，'index.php'

PHP常量：
--------
- PATH - 当前应用路径
- ROOT - 框架根目录路径
- APP - 应用存放路径
- LIB - 函数及类存放路径
- CACHE - 缓存及临时文件存放路径
- CONFIG - 配置文件存放路径
- PLUGIN - 插件存放路径
- STATICS - CSS、JS及图片等静态文件存放路径
- VENDOR - 第三方应用库存放路径
- FILE - 文件上传目录

全局变量：
--------
- $s - 框架配置，通过对象模式调用，如$s->web-title
- $info_app - 当前调用应用的基本信息，除对应APP信息外（APP目录下info.php定义），还包括path（数组）和route（字符串）项目
- $mystep - 应用入口类，如应用路径下不存在以应用路径名命名的类（如test/test.class.php里面的test类，且此类应该是mystep类的扩展），则调用默认mystep类，并会预载类下的 preload 方法，在页面结束时会调用类下 shutdown 方法
- $db - 数据库操作类，在函数初始化时根据设置连接，采用代理模式，可扩展
- $cache - 数据缓存类，在函数初始化时根据设置连接，采用代理模式，可扩展
- $setting_tpl - 模版参数，从 app 设置中调用，并继承于全局变量
- $setting_cache - 模版缓存参数，从 app 设置中调用，并继承于全局变量

JS变量：
--------
- language - 调用系统语言设置（可自动扩展app语言包）
- setting - 调用系统设置 （app可通过$setting['js']扩充）

路由：
--------
路由分为路径调用和自定义路由两种
- 路径调用 - 相关路径信息将直接传递给应用目录下的index.php处理，格式为：网址/应用目录名/路径信息
- 自定义路由 - 可在框架的应用设置中载入应用路由配置，格式如下：
   - $preload - 预载文件，即应用通用函数库
   - $format - 路径辨别格式，含如下默认格式：
      - [any] - 任意非路径字符
      - [str] - 字母、数字及下划线
      - [int] - 任意数字
      - 自定义格式为 array(格式索引 => 格式正则)，示例如下：
        ```php
         $format = array(
            'test' => '([A-Z][a-z]+)+',
         );
         ```
   - $rule - 路由规则，格式为 array(请求路径, 处理接口)
      - "请求路径"中可包含预定义路径（格式为[格式索引]），每个路径间隔符中，仅能包含一个自定义格式且不能有其他字符；
      - "处理接口"为负责相应该路径的函数，如果为数组的话，则会依次执行，除最后一个外，如某个子判断返回false则终止执行，否则上一个函数的返回值将作为下一个函数最后一个参数；各处理函数如需加带参数，可依照"函数名,参数值"的格式；如处理函数为静态类方法，调用格式为"类名:方法名"；处理函数也可为闭包函数，如：
         ```php
         $rule = array(
            array('/test/[test]', array('perCheck,3','routeTest'))
         );
         ```
   - $api - 应用接口程序，格式如下： 
        ```php 
        $api = array(
            'error' => 'app\myStep\getError'
        );
        ```
- 可以通过自定义方法处理自定义路由，框架也提供'myStep::getModule'方法处理路由，机制如下：
   - 输入参数 $m - 本参数传递路由外的路径信息，如路由为 /manager/[any]，URI 为 /manager/path1/path2，则 $m 为 path1/path2，此参数可直接在对应的处理脚本内调用（如需在下级函数中调用，需要先进行global处理）
   - 本方法将通过 myStep::setPara 方法调用当前 app 设置中的模版参数设置（可继承于全局设置，存储于全局变量 $setting_tpl 中）
   - 本方法将按照如下顺序调用处理脚本（发现可用脚本后将立即调用并停止试探）
      - app路径/module/模版样式/$m.php（$m 为输入参数）
      - app路径/module/模版样式/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/$m.php（$m 为输入参数）
      - app路径/module/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/模版样式/index.php（模版样式为设置中对应的内容）
      - app路径/module/index.php

接口：
--------
- /api/[any] - 自定义应用接口，[any]为接口名及参数，可通过_GET或_POST接收参数
- /module/[any] - 模块接口，[any]为模块名及参数
- /setting/[any] - 设置接口，[any]为应用名称，获取该应用json格式的设置
- /captcha/[any] - 验证码图像接口，[any]为随机数，保证新码生成，验证参数为$_SESSION['captcha']
- /upload - 文件上传接口，上传文件保存在常量FILE目录
- /download/[any] - 文件下载接口，[any]为文件索引

应用：
--------
应用是在框架基础上的独立功能组合，推荐结构如下：
- config - 配置文件描述，分语种，用于生成配置设置页面，参照static/js/checkForm.js规范输入格式
- language - 语言包文件，可通过框架自动调用
- module - 针对不同请求的功能模块，默认处理脚本为 index.php
- template - 模版文件
- asset - 资源文件
- asset/style.css - 应用样式表文件（自动载入）
- asset/function.js - 应用脚本文件（自动载入）
- config.php - 配置信息（参考config目录下的描述文件）
- index.php - 入口文件（必需）
- info.php - 介绍文件（必需）
- lib.php - 应用通用函数库（可用于预载）
- plugin.php - 插件引用记录（自动生成）
- route.php - 路由信息，格式详见路由章节


脚本调用：
--------
每个应用将自动生成 cache/script/[appName].js 和 cache/script/[appName].css（[appName]表示应用名称），供页面调用，这两个文件经压缩处理，可根据相关文件内容改变自动更新。载入规则如下（如文件不存在将自动忽略，其中[TemplateStyle]为模版样式名称）：
- [appName].css 将自动载入以下文件：
   - static/css/bootstrap.css
   - static/css/font-awesome.css
   - static/css/glyphicons.css
   - static/css/global.css
   - static/asset/style.css
   - static/asset/[TemplateStyle]/style.css 
- [appName].js 将自动载入以下文件：
   - static/js/jquery.js
   - static/js/jquery-ui.js
   - static/js/jquery.addon.js
   - static/js/bootstrap.bundle.js
   - static/js/global.js
   - static/asset/function.js
   - static/asset/[TemplateStyle]/function.js

插件：
-------- 
插件为为应用添加某一组功能，可通过框架后台插件管理设置参数，并在应用管理的插件选项中设置对应应用都调用那些插件，推荐结构如下：
- index.php - 入口脚本，插件调用时将首先调用此文件（必需）
- info.php - 介绍文件（必需）
- class.php - 包含检测（check）、安装（install）、卸载（uninstall）以及其他基本功能（如模版标签解析，页面钩子等）的脚本（必需）
- config.php - 配置信息（参考config目录下的描述文件）
- module - 存放功能模块所需文件（脚本及模版），模块可以理解为针对插件功能的客户交互页面
