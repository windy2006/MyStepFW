'use strict';
const {spawnSync} = require('node:child_process');
const vm = require('node:vm');
const path = require('node:path');
const php = process.env.MYSTEP_PHP || '/Applications/ServBay/package/php/7.4/current/bin/php';
const cases = [
 ['arithmetic', 'var result = 2 + 3 * 4;'],
 ['increment boundaries', 'var a = 2, b = 3; var result = a + ++b;'],
 ['regex and comments', 'var result = /a\\/b/.test("a/b"); // removed\n'],
 ['string URL', 'var result = "https://example.test/a//b";'],
 ['unicode', 'var result = "中文\\n字符".length;'],
 ['template literal', 'const name = "World"; var result = `Hello ${name}`;'],
 ['class syntax', 'class Value { get() { return 7; } } var result = new Value().get();'],
 ['arrow functions', 'var result = [1, 2, 3].map(x => x * 2).join(",");'],
 ['automatic semicolon insertion', 'function x() { return\n{a: 1}; } var result = typeof x();'],
 ['retained license', '/*! license */\nvar result = "kept";'],
 ['regex character class', 'var result = /[/]/.test("/");'],
 ['nullish operator', 'var value = null; var result = value ?? "fallback";'],
];
let passed=0;
for(const [name, code] of cases) {
 const run=spawnSync(php,[path.join(__dirname,'composer_regression.php'),'--minify'],{input:JSON.stringify({code}),encoding:'utf8'});
 if(run.status!==0 || run.stderr) throw new Error(name+': PHP minifier failed: '+run.stderr);
 const out=JSON.parse(run.stdout);
 if(out.normal!==out.legacy) throw new Error(name+': compatibility output differs');
 const before=vm.runInNewContext(code+'\n;result;',{}, {timeout:1000});
 const after=vm.runInNewContext(out.normal+'\n;result;',{}, {timeout:1000});
 if(!Object.is(before,after)) throw new Error(name+': execution result changed');
 if(name==='retained license'&&!out.normal.includes('/*! license */'))throw new Error('license comment lost');
 console.log('PASS '+name);passed++;
}
console.log('Passed: '+passed+'; Failed: 0');
