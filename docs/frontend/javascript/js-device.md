# 设备检测

说明isMobile的返回结构及UA与触控特征判断。

## 设备检测

| 接口 | 参数与功能 | 返回约定 |
| --- | --- | --- |
| `isMobile()` | 根据 UA 和桌面 UA 的触控 iPad 特征检测 | `{Android, BlackBerry, iOS, Windows, any}`，各值 boolean |

```javascript
const device = isMobile();
console.log(device.any, device.Android, device.iOS);
```

检测结果用于界面适配，不能据此判断身份或授予权限；桌面UA的触控iPad也按当前实现检查。

[文档总览](../../README.md)
