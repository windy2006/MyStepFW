# 数组扩展

本页代码在浏览器中运行，先加载本地jQuery及global.js。

## 方法参数

`array.append(items)`把数组参数的元素依次追加；传单值时只追加该值。返回原数组并修改它，可以链式调用。

## 明确示例

```javascript
const values = [1];
const same = values.append([2, 3]).append(4);
console.assert(same === values);
console.assert(JSON.stringify(values) === '[1,2,3,4]');

const repeated = [1, 2];
repeated.append(repeated);
console.assert(JSON.stringify(repeated) === '[1,2,1,2]');
```

向自身追加只复制调用开始时的那一组元素。append不做去重、深拷贝或数据格式验证；数组中的对象仍是原对象引用。需要不修改原数组时先创建副本。

[前端目录](../README.md) · [函数目录](global-reference.md)
