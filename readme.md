Summary
--------
MyStep Framework is a website framework based on [PHP 7.0], which focus on building a development toolkit that can call common functions with a easier way, and coding a functional website with more concise code. The framework is also highly scalable. With the agent mode and composer, the third-party function modules can be convenient to integrate into the framework.

整体描述
--------
迈思框架（MyStep Framework）是一套基于 **[PHP 7.0]** 的web开发框架，旨在构建一个可以便捷调用常用功能，以最简洁的代码实现目标功能，同时具备高度可扩展性，可通过代理模式，方便的将第三方功能模块集成到框架中。
- 路由系统 - 框架通过 rewrite 方法接管所有响应，除 static 目录和自定义扩展类型外，其他文件均无法直接通过 url 访问，兼具高可控性和安全性。 （IIS对应web.config，Apache对应.htaccess，NginX需参考目录下文件手动添加）。  
- 模版系统 - 采用二次编译模式，严格实现模板与程序的分离，通过通俗的标签模式调用各类数据。基本模板格式简单易学，方便制作，只要对HTML有一定了解的设计师均可以很快上手，模板修改后即时生效。同时具备高度可扩展性，可根据实际需要任意扩充模版标签。  
- 插件系统 - 可插件模式扩展框架功能，无论是功能增强、系统优化、前台展示均可与系统无缝连接。内容评分、评论、投票、专题、检索、采集、统计等都可以通过插件实现，并可以无缝结合到系统中。  
- 应用接口 - 系统为各类插件提供了丰富的接口，无论是api、模板标签、代码嵌入、脚本附加、登录处理，都可以通过系统接口便捷地实现，为二次开发或插件开发提供最大限度的支持和自由。  
- 多语言支持 - 系统可以随意添加语言包，通过调整参数立即变化。
- Composer - 框架支持通过composer添加附属功能及相关依赖，具体请参见[composer文档](https://docs.phpcomposer.com/00-intro.html "简介")
- 域名绑定 - 每个应用或路由规则（仅限一级目录）均可以通过框架绑定到独立域名，但需要避免路由中与其他路由规则混淆（如：setting关键字默认为调用对应应用的设置信息），具体解决方法请见myStep应用。

缓存机制
--------
通过数据、页面、浏览器三层缓存机制保证系统高效运行。
- 数据缓存，用于缓存从数据库查询出的结果集，包含自建文件和数据库两种模式，也可通过代理模式扩展；
- 页面缓存，可将解析好的页面整体缓存到缓存文件，在过期前不用再次生成页面，即实现了静态化的效果，也保留了动态脚本的特性；
- 浏览器缓存，通过etag标识，在客户端再次请求页面数据时，如页面未发生变化，则直接从客户端缓存调用数据，减少了对服务器带宽的请求。

执行顺序
--------
所有响应网址均通过rewrite模块反馈给根目录下的index.php脚本统一处理，虽然框架也支持QueryString和PathInfo两种模式，但是为了更好的网址优化和安全性，建议采用rewrite的方式，主要执行流程如下：
- 初始化框架 - 通过框架根目录index.php，调用myStep::init()
- 路由模式判断 - 通过 $router->check() 判断是否存在自定义路由
   - 当前响应路径符合已设定的自定义路由规则，会先尝试加载APP目录下"config_[规则首层目录名].php"覆盖默认设置，再按规则调用指定的响应方法，可由多方法依次执行构成多级响应。可通过框架默认处理方法myStep::getModule()调用相应模块（具体处理流程详见核心类对应方法讲解），也可以根据需要替换为自定义方法。
   - 如未发现何时规则，则分析响应路径，将一级路径或默认app指定为响应app，并调用该app路径下的index.php处理
- 框架变量设置 - 在获取执行入口之后，框架将继续调用以下程序
   - myStep::setPara() - 此方法在执行入口脚本之前将调用，用于加载应用设置（config.php）、应用函数库（lib.php），并设置基本框架变量
   - $mystep->preload() - 并非myStep类中的原生方法，但是如果应用扩展类中存在此方法，将会在声明类后立即执行
   - lib.php - 应用函数库（app/[name]/lib.php，推荐使用命名空间），在核心类已加载并声明实例后加载
   - global.php - 本脚本为应用通用脚本，自定义路由模式下通过 myStep::getModule() 自动加载，其他模式下需手动加载，可用于在模版实例声明后做后期变量及程序调整。
   - $mystep->shutdown() - 并非myStep类中的原生方法，但是如果应用扩展类中存在此方法，将在页面结束时执行

路由规则
--------
路由分为路径调用和自定义路由两种，并以路径调用优先
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
        - "处理接口"为负责相应该路径的函数，如果为数组的话，则会依次执行，除最后一个外，如某个子判断返回false则终止执行，否则上一个函数的返回值将作为下一个函数最后一个参数；各处理函数如需加带参数，可依照"函数名,参数值"的格式，其中参数值可以指定固定值，也可以通过"$1","$2"的模式指定为对应的路径匹配部分（下例2中$1匹配[test]部分）；如处理函数为静态类方法，调用格式为"类名:方法名"；处理函数也可为闭包函数，如：
           ```php
           $rule = array(
              array('/test/[test]', array('perCheck,3','routeTest'))
           );
           $rule = array(
              array('/test/[test]', array('perCheck,$1','routeTest'))
           );
           ```
    - $api - 应用接口程序，格式如下：
         ```php 
         $api = array(
             'error' => 'app\myStep\getError'
         );
         ```

PHP常量
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

PHP变量
--------
- $s - 框架配置，通过对象模式调用，如$s->web-title
- $info_app - 当前调用应用的基本信息，除对应APP信息外（APP目录下info.php定义），还包括path（数组）和route（字符串）项目
- $mystep - 应用入口类，如应用路径下不存在以应用路径名命名的类（如test/test.class.php里面的test类，且此类应该是mystep类的扩展），则调用默认mystep类
- $db - 数据库操作类，在函数初始化时根据设置连接，采用代理模式，可扩展
- $cache - 数据缓存类，在函数初始化时根据设置连接，采用代理模式，可扩展
- $tpl_setting - 模版参数，从 app 设置中调用，并继承于全局变量
- $tpl_cache - 模版缓存参数，从 app 设置中调用，并继承于全局变量

JS变量
--------
相关变量是通过脚本在页面被调取时动态生成，在调用时建议在 onload 事件或 jQuery 的 $(document).ready() 中调用
- language - 调用系统语言设置（可自动扩展app语言包）
- setting - 调用系统设置（包括：language，router，debug，app，path_root，path_app，url_fix，url_prefix，url_prefix_app等信息，可通过APP设置中的 $setting['js'] 扩充）
- global - 全局变量，可在任何函数内部调用，可随意扩种，已包含以下子参数
    - global.root - 针对rewrite、pathinfo和querystring模式下的根路径
    - global.root_fix - 配合setURL，用于页面链接的自适应调整
    - global.editor_btn - 针对tinyMCE编辑器的按钮扩展
    - global.alert_leave - 在含表单的页面，如果内容发生变更，且通过非提交方式离开页面的话，将此变量设置为 true，即可出现警告
    - global.timer - 用于计时器的返回值记录（非强占，可灵活调用）
    - global.func - 页面载入后所需运行的函数组

控制类
--------
myController类为核心控制类，具体用法请参加功能类文档，其中几个重要方法说明如下：
- 页面附加内容设置 - 包括 setAddedContent 和 pushAddedContent 两个方法，可设置指定关键字的内容，并将相关内容插入到模版中"page_关键字"的位置
- 语言文件管理 - 包括 setLanguage，setLanguagePack 和 getLanguage 三个方法，可设置语言、语言包或调用指定语言、指定索引的文字
- 应用接口设置 - 包括 regApi 和 runApi 两个方法，可通过路由的 /api/[str]/[any] 调用
- 模块设置 - 包括 regModule 和 module 两个方法，可通过路由的 /module/[str]/[any] 调用
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
- header方法 - 返回指定的响应头（可以编码或指定的索引，具体参见源代码）

核心类
--------
myStep类扩展自myController类，具体用法请参加功能类文档，其中几个重要方法说明如下：
- start($set_plugin) - 执行于脚本主程序开始之前，用于设置框架类及其方法的调用别名，设定错误报告模式，加载应用对应插件，初始化cookie和session，声明数据库（$db, 如$s->db->auto为false，则不建立连接，以便于无数据库操作的应用）和缓存（$cache）实例，以及为状态变量赋值
- show(myTemplate $tpl) - 用于加载网站基本参数至模版实例，并将结果直接显示（在此可添加针对显示内容的预处理方法）；同时也检测并按需更新应用脚本文件（[appName].js 和 [appName].css，详情见相关专题），如设置"$mystep->setting->show = true"，则将在页面最下面显示基本运行信息。
- render(myTemplate $tpl) - 与 show 方法类似，但是返回通过模版实例所生成的页面内容，而不是直接显示
- setLink($content) - 针对所生成页面的链接，根据设定的链接模式（rewrite，pathinfo或querystring）进行处理，页面模版中只要按照rewrite模式书写，在页面显示时将自动通过本预处理方法调整为对应设置的链接。
- end() - 脚本结束时所用的方法，搜集并对比运行结束时的信息，结束并清空变量，并智能调用用户扩展类中自定义的 shutdown() 方法
- info($msg, $url) - 执行结果或提示信息显示，并在5秒后自动跳转到对应的链接
- redirect($url, $code) - 脚本内链接跳转，如$url为空则退回来路链接；$code默认是302临时跳转，可根据需要改变。
- init() - 静态方法，预初始化基本设置信息（如发现有错误将自动调整），声明类加载模式，如为首次执行框架的话，将自动跳转到初始设置页面
- go() - 框架执行入口，加载设置信息，判断静态文件并直接显示，否则根据路由规则调用相关脚本
- setPara() - 声明框架实例，默认直接调用myStep类，也可在对应APP中扩展，框架会自动调用APP目录下"[appName].class.php"中与APP同名的类。将APP配置覆盖全局配置，然后再调用start方法，同时声明预加载的css和js脚本文件以及模版的初始设置。
- vendor($class_info) - 调用位于VENDOR目录下的第三方PHP功能类，需要满足以下条件。
   - 如$class_info为字符串，所调用类（位于vendor目录下）的目录名、文件名和类名必须一致，其中文件名可为"名称.php"或"名称.class.php"
   - $class_info可以为数组，包含
      - dir - 目录名称，如不设置默认与 file 值相同
      - file - 不带扩展名的文件名称，扩展名可为".php"或".class.php"，如不设置默认与 dir 值相同
      - class - 调用类名称，如不设置默认与 file 值相同
      - namespace - 类的命名空间，没有请留空
   - 方法中除了首参数($class_info)外，后面的参数将用于在声明类时，构造函数(__construct()或init())的初始化
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

PHP函数
--------
- getMicrotime($rate) - 获取微秒时间
- getTimeDiff($time_start, $decimal, $micro) - 取得时间差
- getDate_cn($date) - 获取中文日期
- formatDate($date, $format) - 格式化日期
- shortUrl($url, $max_length) - 缩略链接
- tinyUrl($url) - 获取短网址
- isMobile() - 判断是否为移动设备
- isHttps() - 判断当前是否为SSL链接
- myEval($code) - 自定义代码执行
- checkPara($att_list, $parse) - 检测数据变量中是否有待解析的变量，并解析
- recursionFunction($func, $para) - 递归执行某一函数
- getOB() - 获取缓存区内容并清空
- debug系列函数 - 变量情况查看

JS函数
--------
- getPath(lvl) - 获取相对于网站根的到调用函数页面所在路径的lvl级路径
- $id(id) - 获取对应id的页面元素
- $name(name, idx) - 获取对应name的页面元素（组），idx可为first，last，数字索引，否则返回全部符合的元素组
- $tag(name, context) - 依照context（默认为document）获取对应tag的页面元素组
- isArray(para) - 判断变量是否为数组
- loadingShow(info) - 显示锁屏信息，再次调用则关闭
- openDialog(url, width, height, mode) - 开启模态窗口
- openWindow(url,width,height) - 新开窗口
- sleep(the_time) - 程序终端指定时间
- copy(obj) - 复制某一页面元素内容（value或innerText）或者一个字符串
- rndNum(min,max) - 生成指定范围内的随机数字
- rndStr(len, t_lst, c_lst) - 生成随机字符串（可自定义采样内容）
- watermark(obj, rate, copyright, char_c, jam_tag) - 添加字符串水印
- md5(str) - 生成str的md5编码
- debug(para, mode) - 检测指定变量para的内容
- checkObj(obj, func_show) - 查看对象属性
- reportError(msg, url, line) - 错误信息处理  
- checkSetting() - 通过在需要调用检language, setting变量的函数开始加上"if(!checkSetting()) return;"（参考global.js中setURL函数的用法）来保证对应函数执行时可调用系统变量
- setURL(prefix) - 配合域名绑定模式和路由模式，智能处理页面内链接
- gotoAnchor(theAnchor) - 滚动至对应的锚点
- setLocation(path, name) - 无刷新改变地址栏链接
- ms_func_reg(function) - 注册需要页面载入后运行的函数
- ms_func_run() - 运行于所有页面载入之后的函数（框架自动在page_end处运行）
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
   - $.vendor(name, option) - 加载vender目录下名称为name的第三方js扩展，可根据option判断是否同时加载样式表并调用回调函数，相关说明如下：
      - 目录名和js文件名须一致
      - option 中 add_css 设置为 true 时，将同时加载同名css文件，如设定为字符串，将作为css的文件名修正，如".min"将加载"名称.min.css"
      - option 中 name_fix 为文件名修正，如设定为".min"，将加载"名称.min.js"文件
      - option 中 callback 参数为匿名函数，用于在成功调用扩展后，执行相关的功能代码
   - $.cookie(name, value, options) - cookie管理（读取、添加、修改、删除）
   - $obj.serializeObject - 将jQuery对象序列化（如form）
   - $obj.outerHTML - 返回jQuery对象的外部超文本代码
   - $obj.cssText - 为jQuery对象批量添加CSS

框架接口
--------
以下接口为框架通过路由规则预定义的接口，接口处理函数统一返回数组格式数据，可通过在url最后加上"/返回类型"来控制（可选），格式默认为json，还可为xml、string、hex、script等，如需在某一APP内调用另外一个APP的插件接口（如captcha），可通过在url上加上"&app=[AppName]"的模式保重接口依照对应app的设置执行。
- /api/[str]/[any] - 自定义应用接口，[str]为app名称，[any]为接口名及参数，可通过_GET或_POST接收参数
- /module/[str]/[any] - 模块接口，[str]为app名称，[any]为模块名及参数
- /captcha/[any] - 验证码图像接口，[any]为随机数，保证新码生成，验证参数为$_SESSION['captcha']
- /ms_setting/[any] - 设置接口，[any]为应用名称，获取该应用json格式的设置
- /ms_language/[str]/[any] - 设置接口，[str]为应用名称，[any]为语种索引，获取该应用json格式的设置
- /api/myStep/upload - 文件上传接口，上传文件保存在常量FILE目录
- /api/myStep/download/[any] - 文件下载接口，[any]为文件索引
- /api/myStep/remove/[any] - 上传文件删除接口，[any]为文件索引

应用结构
--------
应用是在框架基础上的独立功能组合，相关文件存放在框架APP目录对应应用名称（应用名称首字母需要大写）的目录下，推荐结构如下：
- config - 配置文件描述，分语种，用于生成配置设置页面，模式可参考框架config目录，格式参照static/js/checkForm.js规范输入格式
- language - 语言包文件，可通过框架自动调用
- module - 针对不同请求的功能模块，默认处理脚本为 index.php
- template - 模版文件
- asset - 资源文件，此目录下的文件可以直接通过"/[appName]/asset/[fileName]"的方式调用存放于"模版样式名子目录"下的文件，框架可自行根据设置调用，如设置了域名绑定，可进一步简化为"/asset/[fileName]"。例如：应用名为MyApp，模版类型为default，文件存储在 app/MyApp/asset/default/myfile.txt，调用网址为：http://hostname/MyApp/asset/myfile.txt
- asset/style.css - 应用样式表文件（自动载入）
- asset/function.js - 应用脚本文件（自动载入）
- config.php - 配置信息（参考config目录下的描述文件）
- index.php - 入口文件（必需）
- info.php - 介绍文件（必需）
- lib.php - 应用通用函数库，自动加载，在myStep::setPara()前引入
- global.php - 应用通用脚本，自定义路由模式下通过myStep::getModule()自动加载，用于在模版实例声明后做后期调整，其他模式下需手动加载
- plugin.php - 插件引用记录（自动生成）
- route.php - 路由信息，格式详见路由章节

脚本加载
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
   - static/js/jquery.addon.js
   - static/js/bootstrap.bundle.js
   - static/js/global.js
   - [appName]/asset/function.js
   - [appName]/asset/[TemplateStyle]/function.js

功能插件
-------- 
插件为为应用添加某一组功能，可通过框架后台插件管理设置参数，并在应用管理的插件选项中设置对应应用都调用那些插件，推荐结构如下：
- index.php - 入口脚本，插件调用时将首先调用此文件（必需）
- info.php - 介绍文件（必需）
- class.php - 包含检测（check）、安装（install）、卸载（uninstall）以及其他基本功能（如模版标签解析，页面钩子等）的脚本（必需）
- config.php - 配置信息（参考config目录下的描述文件）
- module - 存放功能模块所需文件（脚本及模版），模块可以理解为针对插件功能的客户交互页面

框架钩子
--------
包含程序钩子和框架钩子两类
- 程序钩子 - 主控制类中包括 setFunction 和 run 两个方法，将在指定的位置（start，end，page等，也可自定义）依次（顺序或倒序）执行指定的方法
   - setFunction($func, $position) 用于注册某个方法（$func），并指定在某个位置（$position）响应
   - run($position, $desc, $para) 在特定位置（$position）执行已注册的所有方法，如果$desc为false的话，则按照注册顺序的反序执行，$para为对应方法的参数，需要为数组模式（即使参数本身即为数组，也需要将其作为数组的首变量，即[$para]），如未设置，则会将当前类作为作为参数传递给对应方法
- 模版钩子 - 模版类中包括 setAddedContent 和 pushAddedContent 两个方法
   - setAddedContent($position, $content) 用于在指定位置（$position）注册所需添加的内容，其中对应位置将被
   - pushAddedContent(myTemplate $tpl) 将已注册内容加入对应模版变量的"page_{$position}"的位置，如 $mystep->setAddedContent('somewhere', 'content1')、 $mystep->setAddedContent('somewhere', 'content2') ，对应模版中<!-page_somewhere->将被替换为'content1content2'

链接设定
--------
由于框架支持Rewrite，QueryString和PathInfo三种模式，在页面链接的设定方面可根据，按照如下两种方式：
- PHP脚本模式 - 链接直接按照路由对应规则（即rewrite模式）设置，通过myStep类中的静态方法 setURL 转换待转换链接即可，但是每次执行仅能转换一个链接
- JS脚本模式 - 链接直接按照路由对应规则（即rewrite模式）设置，在页面载入最后执行JS函数 setURL() （setURL函数的规则详见JS函数章节），即可智能转换页面所有链接
- 其他说明 - 在实际应用中需参考以下情况：
   - 在转换链接的同时，也会给链接加上路径信息，如：<br />框架存放在 /dir/ 目录下，设置模式为PathInfo，路由链接为 /func/method/id，经过转换后的链接为：/dir/index.php/func/method/id
   - 模版函数也将传递一个路径变量：<!--url_prefix-->，参照上例，此变量内容为：/dir/index.php/，如所调用文件为静态文件可直接在利用<!--path_root-->即可。