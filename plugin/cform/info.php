<?php
/** 表单构建系统；从旧CMS 1.6移植，保留Windy2000原作者信息。 */
return [
    "name" => "表单构建系统",
    "idx" => "cform",
    "ver" => "2.3.0",
    "intro" => "独立表单设计、报名收集与记录管理",
    "copyright" => '版权所有 2019-2026 <a href="mailto:windy2006@gmail.com">Windy2000</a>',
    "description" =>
        "中英文表单、独立页面模板、功能扩展、附件、记录管理、CSV导入导出、打印及确认邮件。",
    "app" => [],
    "admin" => [
        "manage" => ["name" => "表单管理", "path" => "cform/manage/", "app_admin" => true]
    ]
];
