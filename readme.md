整体描述
--------
迈思框架（MyStep Framework）是一套基于 **[PHP 7.0]** 的web开发框架，旨在构建一个可以便捷调用常用功能，以最简洁的代码实现目标功能，同时具备高度可扩展性，可通过代理模式，方便的将第三方功能模块集成到框架中。
- 路由系统 - 框架通过 rewrite 方法接管所有响应，除 static 目录和自定义扩展类型外，其他文件均无法直接通过 url 访问，兼具高可控性和安全性。 （IIS对应web.config，Apache对应.htaccess，Nginx需参考目录下文件手动添加）。
- 模版系统 - 采用二次编译模式，严格实现模板与程序的分离，通过通俗的标签模式调用各类数据。基本模板格式简单易学，方便制作，只要对HTML有一定了解的设计师均可以很快上手，模板修改后即时生效。同时具备高度可扩展性，可根据实际需要任意扩充模版标签。
- 插件系统 - 扩展框架功能，无论是功能增强、系统优化、前台展示均可与系统无缝连接。内容评分、评论、投票、专题、检索、采集、统计等都可以通过插件实现。
- 应用接口 - 系统为各类插件提供了丰富的接口，无论是api、模板标签、代码嵌入、脚本附加、登录处理，都可以通过系统接口便捷地实现，为二次开发或插件开发提供最大限度的支持和自由。
- 缓存机制 - 通过数据、页面、浏览器三层缓存机制保证系统高效运行。
- 域名绑定 - 每个应用或路由规则（仅限一级目录）均可以通过框架绑定到独立域名。
- 多语言支持 - 系统可以随意添加语言包，通过调整参数立即变化。
- Composer - 框架支持通过composer添加附属功能及相关依赖，具体请参见[composer文档](https://docs.phpcomposer.com/00-intro.html "简介")

Summary
--------
MyStep Framework is a website framework based on **[PHP 7.0]**, which focus on building a development toolkit that can call common functions with an easier way, and coding a functional website with more concise code. The framework is also highly scalable. With the agent mode and composer, the third-party function modules can be convenient to integrate into the framework.
- Routing - With the rewriting module of server, MyStepFW can handle all the requesting for static files and functions.
- Template - With 2-steps compilation, MyStepFW strictly separate the program code from the view code, and you can also expand the template function with custom tag.
- Plugin - Expand MyStepFW function which can be seamlessly connected with the system. 
- API - MyStepFW provides rich API for various use with different formats, and provides maximum convenience for application or secondary development.
- Cache - MyStepFW can be efficiently run by three-layer caching mechanism of data, page and browser side.
- Domain - Each application or routing rule can be bound to an independent domain name through MyStepFW.
- I18n - MyStepFW can add language package easily and can be change immediately by adjusting parameters.
- Composer - MyStepFW supports compile new functions and related dependencies through composer. Please refer to [composer document](https://docs.phpcomposer.com/00-intro.html "Introduction") for details.

