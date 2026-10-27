# 农历日期

说明date.js的日期结果、年份范围和解析期输出约定。

### 3. 农历日期 date.js

date.js 在加载时计算当前本地日期的农历信息，并通过 document.write 输出。它不是接收任意日期的通用日期组件。

| 接口 | 参数和结果 |
| --- | --- |
| `CalConv()` | 使用 new Date 的当前日期；成功返回四项数组：公历文字、星期、农历文字、节气 HTML；年份范围等异常时返回数字状态码 |
| `GetLeap(year)` | 公历闰年判断，返回 1 或 0 |
| `TagLunarCal(d, i, w, k, m1...m13)` | 内部历表构造器；设置 BaseDays、Intercalation、BaseWeekday、BaseKanChih、MonthDays |
| `CnEra(yearOffset)` | 返回天干地支文字；参数是以 1864 为起点的年份偏移，不能直接传公历年份 |
| `SolarTerm(date)` | 根据内置常数推算当前／后续节气，返回含 b 标签的展示 HTML |
| `cal_info` | 加载时 CalConv 的结果，顶层 let，不保证是 window 属性 |

```javascript
console.log(GetLeap(2024));         // 1
console.log(CnEra(2026 - 1864));    // 丙午
const todayInfo = CalConv();
if (Array.isArray(todayInfo)) {
    console.log(todayInfo[0], todayInfo[1], todayInfo[2]);
}
```

当前源码的年份判断是 `SolarYear <= 1998 || SolarYear > 2031` 时返回 1，也就是仅接受 1999～2031；月份／日检查分别可能返回 2／3。历表和节气算法是展示实现，不承诺任意年份的精确历法结果。脚本中的 document.write 不适合在页面完成后动态加载；普通日期格式化使用 global.js 的 Date.format。

[文档总览](../../README.md)
