整体描述：
--------
迈思框架（MyStep Framework）是一套基于 <b>[PHP 7.0]</b> 的web开发框架，旨在构建一个可以便捷调用常用功能，以最简洁的代码实现目标功能，同时具备高度可扩展性，可通过代理模式，方便的将第三方功能模块集成到框架中。
- 路由系统 - 框架通过 rewrite 方法接管所有响应，除 static 目录和自定义扩展类型外，其他文件均无法直接通过 url 访问，兼具高可控性和安全性。 （IIS对应web.config，Apache对应.htaccess，NginX需参考目录下文件手动添加）。  
- 路由模式 - 为增加环境适应度，框架同时支持Rewrite，QueryString和PathInfo三种模式，页面中站内URL只需要按照rewrite的模式书写（相对于框架目录，首位无需加"/"），框架将自动调整为对应模式，但为保证最大兼容性，php脚本内的链接多以QueryString模式处理。
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
所有响应网址均通过rewrite模块反馈给根目录下的index.php脚本统一处理，虽然框架也支持QueryString和PathInfo两种模式，但是为了更好的网址优化和安全性，建议采用rewrite的方式，主要执行流程如下：
- 初始化框架 - 通过框架根目录index.php，调用myStep::init()
- 路由模式判断 - 通过 $router->check() 判断是否存在自定义路由
   - 当前响应路径符合已设定的自定义路由规则，按规则调用指定的响应方法，可由多方法依次执行构成多级响应。框架默认处理方法是myStep::getModule()（具体处理流程详见核心类对应方法讲解），也可以根据需要替换为自定义方法。
   - 如未发现何时规则，则分析响应路径，将一级路径或默认app指定为响应app，并调用该app路径下的index.php处理
- 框架变量设置 - 在调用第二部处理方法或脚本之前会调用应用预加载脚本（app/[name]/lib.php），并执行框架变量设置方法为 myStep::setPara()。如需调整框架变量，可直接在lib.php中预先执行 myStep::setPara() 方法，并做响应调整，此方法不会二次执行。

PHP常量：
--------
- PATH - 当前应用路径
- ROOT - 框架根目录路径
- ROOT_WEB - 框架相对于网站根目录的相对路径
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
- $tpl_setting - 模版参数，从 app 设置中调用，并继承于全局变量
- $tpl_cache - 模版缓存参数，从 app 设置中调用，并继承于全局变量

基础类：
--------
myBase为抽象类，可为所有其他子类提供统一的构建方法和错误处理；myTrait为扩展类，可为所调用的类提供一整套魔术方法
- myBase->__construct - 将构造函数引导至init方法
- myBase->setErrHandler - 设置错误处理函数
- myBase->error - 通过异常处理类处理代码错误
- myTrait->__set - 添加类动态变量，即没有在类中声明过的变量
- myTrait->__get - 调用类动态变量，如变量名为instatnce，则直接返回新的当前类实例
- myTrait->__destruct - unset类时，注销所有类内部变量
- myTrait->__call - 智能判断并调用方法别名，动态方法或类外部函数
- myTrait->addMethod - 动态添加类方法
- myTrait->regAlias - 注册类内方法别名

控制类：
--------
myController类为核心控制类，具体用法请参加功能类文档，其中几个重要方法说明如下：
- 页面附加内容设置 - 包括 setAddedContent 和 pushAddedContent 两个方法，可设置指定关键字的内容，并将相关内容插入到模版中"page_关键字"的位置
- 语言文件管理 - 包括 setLanguage，setLanguagePack 和 getLanguage 三个方法，可设置语言、语言包或调用指定语言、指定索引的文字
- 应用接口设置 - 包括 regApi 和 runApi 两个方法，可通过路由的 /api/[any] 调用
- 模块设置 - 包括 regModule 和 module 两个方法，可通过路由的 /module/[any] 调用
- 模版标签设置 - 包括 regTag 一个方法，将在调用show方法时加载给模版类
- 链接设置 - 包括 regUrl 和 url 两个方法，通过指定方法和相关参数生成对应链接
- 插件设置 - 包括 regPlugin 和 plugin 两个方法，每个插件是应用接口，模块，标签和链接的组合
- 代码钩子设置 - 包括 setFunction 和 run 两个方法，将在指定的位置（start，end，page等，也可自定义）依次（顺序或倒序）执行指定的方法
- 用户账户管理 - 包括 regLog，login，logout 和 chg_psw 四个方法，用于与第三方用户系统对接
- 脚本管理 - 包括 addCSS，removeCSS，clearCSS，CSS，addJS，removeJS，clearJS 和 JS 八个方法，用于动态加载js和css脚本
- 页面控制 - 包括 start，show 和 end 三个方法，用于页面起始、显示和结束
- etag方法 - 用于赋予或调用指定标识的浏览器缓存
- file方法 - 直接显示指定文件
- guid方法 - 生成唯一ID
- setOp方法 - OPcache设置与调用
- regClass方法 - 设置类自动载入规则
- setAlias方法 - 设置类调用别名
- header方法 - 返回指定的相应头

核心类：
--------
myStep类扩展自myController类，具体用法请参加功能类文档，其中几个重要方法说明如下：
- start($setPlugin) - 执行于脚本主程序开始之前，用于设置框架类及其方法的调用别名，设定错误报告模式，加载应用对应插件，初始化cookie和session，声明数据库（$db, 如果存在全局变量$no_db，且其值为'y'，则不建立连接，以便于无数据库操作的应用）和缓存（$$cache）实例，以及为状态变量赋值
- show(myTemplate $tpl) - 用于加载网站基本参数至模版实例，并将结果直接显示（在此可添加针对显示内容的预处理方法）；同时也检测并按需更新应用脚本文件（[appName].js 和 [appName].css，详情见相关专题）
- parseTpl(myTemplate $tpl) - 与 show 方法类似，但是返回通过模版实例所生成的页面内容，而不是直接显示
- setLink($content) - 针对所生成页面的链接，根据设定的链接模式（rewrite，pathinfo或querystring）进行处理，页面模版中只要按照rewrite模式书写，在页面显示时将自动通过本预处理方法调整为对应设置的链接。
- end() - 脚本结束时所用的方法，搜集并对比运行结束时的信息，结束并清空变量，并智能调用用户扩展类中自定义的shutdown()方法
- info($msg, $url) - 执行结果或提示信息显示，并在5秒后自动跳转到对应的链接
- redirect($url, $code) - 脚本内链接跳转，如$url为空则退回来路链接；$code默认是302临时跳转，可根据需要改变。
- init() - 静态方法，预初始化基本设置信息（如发现有错误将自动调整），声明类加载模式，如为首次执行框架的话，将自动跳转到初始设置页面
- go() - 框架执行入口，加载设置信息，判断静态文件并直接显示，否则根据路由规则调用相关脚本
- setPara() - 声明框架实例，默认直接调用myStep类，也可在对应APP中扩展，框架会自动调用APP目录下"[appName].class.php"中与APP同名的类，如存在preload方法，则优先调用。将APP配置覆盖全局配置，然后再调用start方法，同时声明预加载的css和js脚本文件以及模版的初始设置。
- vendor() - 调用位于VENDOR目录下的第三方PHP功能类。
- getModule($m) - 自定义路由处理函数（也可以通过自定义方法处理自定义路由，详情参见"自定义路由"专题），机制如下：
   - 传入参数 $m - 本参数传递路由外的路径信息，如路由为 /manager/[any]，URI 为 /manager/path1/path2，则 $m 为 path1/path2，即[any]部分，但需要注意的是在本方法中，$m 被截取为 path1。此参数可直接在自定义的路由处理脚本内调用，但如需在下级函数中调用，需要先进行global处理。
   - 本方法将通过 myStep::setPara 方法调用当前 app 设置中的模版参数设置（可继承于全局设置，存储于全局变量 $tpl_setting 中）
   - 本方法将按照如下顺序调用处理脚本（发现可用脚本后将立即调用并停止试探）
      - app路径/module/模版样式/$m.php（$m 为输入参数）
      - app路径/module/模版样式/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/$m.php（$m 为输入参数）
      - app路径/module/路由名称.php （如路由为 /manager/[any]，路由名称为 manager）
      - app路径/module/模版样式/index.php（模版样式为设置中对应的内容）
      - app路径/module/index.php

JS变量：
--------
相关变量是通过脚本在页面被调取时动态生成，在调用时建议在onload事件或jQuery的$(function(){<!--code-->})中调用
- language - 调用系统语言设置（可自动扩展app语言包）
- setting - 调用系统设置 （app可通过$setting['js']扩充）

JS函数：
--------
- getPath(lvl) - 获取相对于网站根的到调用函数页面所在路径的lvl级路径
- $id(id) - 获取对应id的页面元素
- $name(name, idx) - 获取对应name的页面元素（组），idx可为first，last，数字索引，否则返回全部符合的元素组
- $tag(name, context) - 依照context（默认为document）获取对应tag的页面元素组
- isArray(para) - 判断变量是否为数组
- loadingShow(info) - 显示锁屏信息
- openDialog(url, width, height, mode) - 开启模态窗口
- openWindow(url,width,height) - 新开窗口
- sleep(the_time) - 程序终端指定时间
- rndNum(min,max) - 生成指定范围内的随机数字
- rndStr(len, t_lst, c_lst) - 生成随机字符串（可自定义采样内容）
- watermark(obj, rate, copyright, char_c, jam_tag) - 添加字符串水印
- md5(str) - 生成str的md5编码
- debug(para, mode) - 检测指定变量para的内容
- checkObj(obj, func_show) - 查看对象属性
- reportError(msg, url, line) - 错误信息处理  
- checkNrun(func, params) - 检测language, setting可被调用后运行指定函数，func为需要运行的函数，params为对应函数数组形式的变量
- setURL() - 配合checkNrun函数（需用到setting设置），处理页面内链接，以符合设置的链接模式
- 对象方法扩展 - 针对 String，Data，Number，Array 等对象
   - string.blen - 返回某字符串的二进制长度
   - string.trim - 去除字符串首尾空字符
   - string.printf - 字符串赋值
   - data.format - 格式化日期（YYYY-MM-dd hh:mm:ss）
   - array.append - 扩展数组
   - number.formatMoney - 格式化金额
- jQuery扩展 - 包括功能扩展（$.xxxx）和对象方法扩展（$obj.xxx）两类
   - $.toJSON - 将指定对象转换为json
   - $.evalJSON - 将json字符串转换为对象或数组
   - $.setJs - 批量顺序加载js脚本
   - $.setCss - 批量加载css样式表
   - $.vendor(name, option) - 加载vender目录下名称为name的第三方js扩展，可根据option判断是否同时加载样式表并调用回调函数
   - $.cookie(name, value, options) - cookie管理（读取、添加、修改、删除）
   - $obj.serializeObject - 将jQuery对象序列化（如form）
   - $obj.outerHTML - 返回jQuery对象的外部超文本代码
   - $obj.cssText - 为jQuery对象批量添加CSS

路由：
--------
路由分为路径调用和自定义路由两种
- 路径调用 - 相关路径信息将直接传递给应用目录下的index.php处理，格式为：网址/应用目录名/路径信息
- 自定义路由 - 可在框架的应用设置中载入应用路由配置，格式如下：
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

接口：
--------
如果接口规则是以"/[str]/"开头，则直接将该规则视为"[str]"所对应APP向下的接口（参见自定义应用接口和模块接口）。接口处理函数统一返回数组格式，客户端响应格式需要在url最后加上"/返回类型"（可选），格式默认为json，还可为xml、string、hex、script等
- /[str]/api/[any] - 自定义应用接口，[str]为app名称，[any]为接口名及参数，可通过_GET或_POST接收参数
- /[str]/module/[any] - 模块接口，[str]为app名称，[any]为模块名及参数
- /setting/[any] - 设置接口，[any]为应用名称，获取该应用json格式的设置
- /captcha/[any] - 验证码图像接口，[any]为随机数，保证新码生成，验证参数为$_SESSION['captcha']
- /upload - 文件上传接口，上传文件保存在常量FILE目录
- /download/[any] - 文件下载接口，[any]为文件索引
- /remove_ul/[any] - 上传文件删除接口，[any]为文件索引

应用：
--------
应用是在框架基础上的独立功能组合，相关文件存放在框架APP目录对应应用名称（应用名称首字母需要大写）的目录下，推荐结构如下：
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

脚本：
--------
每个应用可通过myStep::setPara()自动生成 cache/script/[appName].js 和 cache/script/[appName].css（[appName]表示应用名称），供页面调用，这两个文件经压缩处理，可根据相关文件内容改变自动更新。载入规则如下（如文件不存在将自动忽略，其中[TemplateStyle]为模版样式名称）：
- cache/script/[appName].css - 将自动载入以下文件（其中部分static目录下的文件可在设置中调整）：
   - static/css/bootstrap.css
   - static/css/font-awesome.css
   - static/css/glyphicons.css
   - static/css/global.css
   - [appName]/asset/style.css
   - [appName]/asset/[TemplateStyle]/style.css
- cache/script/[appName].js - 将自动载入以下文件（其中前四个文件可在设置中调整）：
   - static/js/jquery.js
   - static/js/jquery-ui.js
   - static/js/jquery.addon.js
   - static/js/bootstrap.bundle.js
   - static/js/global.js
   - [appName]/asset/function.js
   - [appName]/asset/[TemplateStyle]/function.js

插件：
-------- 
插件为为应用添加某一组功能，可通过框架后台插件管理设置参数，并在应用管理的插件选项中设置对应应用都调用那些插件，推荐结构如下：
- index.php - 入口脚本，插件调用时将首先调用此文件（必需）
- info.php - 介绍文件（必需）
- class.php - 包含检测（check）、安装（install）、卸载（uninstall）以及其他基本功能（如模版标签解析，页面钩子等）的脚本（必需）
- config.php - 配置信息（参考config目录下的描述文件）
- module - 存放功能模块所需文件（脚本及模版），模块可以理解为针对插件功能的客户交互页面

域名：
--------
每个应用或路由规则（仅限一级目录）均可以通过框架绑定到独立域名，但需要避免路由中与其他路由规则混淆（如：setting关键字默认为调用对应应用的设置信息），具体解决方法请见myStep应用