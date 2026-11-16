# 访问来源分析（visitstat） <small>v2.1.0</small>

## 功能概述

记录外部来源、直接访问、搜索关键词及UTM活动，按应用分开累计。

## 安装与设置

MyStepFW 2.6.3、PHP >=8.5.12且<9、mbstring。后台安装、启用并绑定应用。管理地址`visitstat/`；关键词`?view=keyword`，活动`?view=campaign`。使用end钩子记录GET页面，跳过管理员、爬虫和已知AI抓取，不保证覆盖API、静态文件等提前出口。

## 来源和关键词

来源为空归到`None`，与本站同主机名的内部跳转不累计落地来源；主机名精确比较，忽略大小写及结尾点。来源记录总计、本月、本年、首次及最近时间，周期计数按月份/年份重置。

关键词从Referer的k/q/wd/w/query/keyword文本中提取，数组忽略。浏览器只传域名或没有Referer时，不能恢复未提供的关键词。来访次数不是独立访客数，不采集指纹或设备标识。

## UTM活动

落地页链接示例：

```text
https://example.com/产品?utm_source=newsletter&utm_medium=email&utm_campaign=autumn&utm_content=button-a
```

支持`utm_source`、`utm_medium`、`utm_campaign`、`utm_content`、`utm_term`、`utm_id`。source为必需项，其余可空；只接收有效UTF-8文本，每项最多200字符。按应用及全部UTM字段组合累计；本站内部跳转不再次累计落地活动。

活动统计独立于来源/关键词，不改变原有累计。只保存白名单UTM值及不含查询串的落地路径，不记录任意查询参数；不会从广告平台恢复未提供的来源信息。

```php
plugin_visitstat::record(
    $_SERVER['HTTP_REFERER'] ?? '',
    $_SERVER['HTTP_HOST'] ?? '',
    $_SERVER['HTTP_USER_AGENT'] ?? '',
    '/产品', time(), 'CMS',
    ['utm_source' => 'newsletter', 'utm_medium' => 'email']
);
```

正常应用由插件钩子自动传入当前GET参数，自行调用时避免重复累计。

## 管理与存储

后台分别查看来源、关键词和UTM活动，按次数排序，每页50条，只为当前页生成操作表单。可逐条删除和完整JSON导出，不合并导入历史统计，以免重复累计。

私有文件为`config/plugins/visitstat/state.php`。各集合最多20000项，8MiB预算下优先清理较旧统计；长期归档请导出。稳定锁与原子替换保护写入，卸载保留数据。写操作经过POST、管理员及CSRF验证，不需要MySQL或其他插件。

## 设置说明

安装／设置页的“填写说明”会在独立插件指南中打开本文件对应章节。所有示例均为示范值，请按实际环境填写；不要复制示例密钥作为生产凭据。配置保存在本插件的 `config.php`。

本插件没有全局参数，具体内容在插件管理入口维护。

## 更新历史

版本变更和需要调整的配置见[版本更新历史](CHANGELOG.md)。

## 版权信息

版权所有 2019-2026 [Windy2000](mailto:windy2006@gmail.com)。
