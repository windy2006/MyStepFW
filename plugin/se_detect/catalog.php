<?php
/**
 * 功能说明：内置UA识别清单；只使用实际HTTP User-Agent名称，不含robots.txt专用令牌。
 * 使用说明：安装或后台“补充内置规则”写入缺少的规则；已有设置保留，不在线抓取更新。
 * 字段说明：name为名称，agent为UA关键字，category为用途；不是IP身份验证清单。
 */
return [
    'google'=>['Google搜索','Googlebot','search'],
    'bing'=>['Bing搜索','bingbot','search'],
    'baidu'=>['百度搜索','Baiduspider','search'],
    'sogou'=>['搜狗搜索','Sogou','search'],
    'duckduckgo'=>['DuckDuckGo搜索','DuckDuckBot','search'],
    'apple'=>['Apple搜索','Applebot','search'],
    'gptbot'=>['OpenAI训练','GPTBot','ai_train'],
    'oai_search'=>['OpenAI搜索','OAI-SearchBot','ai_search'],
    'chatgpt_user'=>['ChatGPT用户访问','ChatGPT-User','ai_user'],
    'oai_ads'=>['OpenAI广告检查','OAI-AdsBot','other'],
    'claude'=>['Claude训练','ClaudeBot','ai_train'],
    'claude_search'=>['Claude搜索','Claude-SearchBot','ai_search'],
    'claude_user'=>['Claude用户访问','Claude-User','ai_user'],
    'google_other'=>['Google通用抓取','GoogleOther','other'],
];
