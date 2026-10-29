# 日期输入

日期值使用 `YYYY-MM-DD`。实例从输入框读取初值；选择日期后写回并触发原输入框的 `change`。

```html
<input id="date" value="2026-10-28">
```
```javascript
$.vendor('jquery.date_input', {add_css:true, callback:function () {
    $('#date').date_input({start_of_week:1}).on('change',function () {
        console.log(this.value);
    });
}});
```

`start_of_week`：0 为周日，1 为周一。可设置 `month_names`（12 项）、`short_month_names`（12 项）、`short_day_names`（7 项，按周日到周六排列）。框架已有语言包时会为 `need="date"`、`need="date_"` 输入框自动初始化并设为只读；独立调用无需等待语言变量。

```javascript
$('#date').date_input('show');
$('#date').date_input('hide');
$('#date').date_input('destroy');
```

重复传入选项会重建日历；禁用输入框时不展开。方向键移动日期，Page Up/Down 按月移动，Ctrl 加 Page Up/Down 按年移动，Enter 确认，Escape 或 Tab 关闭。传入非法日期时日历定位到当天，但不会擅自覆盖输入值。
