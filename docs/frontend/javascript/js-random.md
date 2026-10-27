# 随机生成

说明rndNum与rndStr的范围、候选字符和异常，不将随机结果用于认证。

## 随机生成

| 接口 | 参数与功能 | 返回约定 |
| --- | --- | --- |
| `rndNum(min, max)` | 无参数为 Math.random；一个参数表示 0～该上限；两个参数为闭区间，可交换上下界 | number；整数范围必须有效且在安全整数范围内，否则 RangeError |
| `rndStr(len, t_lst='', c_lst=[])` | len 为选取次数，向下取整，范围 0～1000000；四位开关依次为大写、小写、数字、汉字；默认有效开关 0110；c_lst 为额外字符串或数组 | string；额外单元可含多个字符，所以返回字符串长度不保证等于 len；候选池为空时空串 |

```javascript
const dice = rndNum(1, 6);
const label = rndStr(8, '1110');
const hexLabel = rndStr(8, '0000', '0123456789abcdef'.split(''));
console.log(dice, label, hexLabel);
```

rndStr额外字符串按一个候选单元处理；逐字符选取时使用split。随机值基于Math.random，不能生成密码、会话或CSRF令牌。

[文档总览](../../README.md)
