/** 微信内动态引导；此文件由注入模板内联，customReady为管理员配置的函数。 */
if (window.MyStepWechat || !/MicroMessenger/i.test(navigator.userAgent)) return;
const options = JSON.parse(document.getElementById('mystep-wechat-config').textContent);
let generation = 0;
let configuredURL = '';
let timer;
const state = {
    status: 'loading',
    apis: [],
    supported: {},
    accountType: options.accountType,
    verified: options.verified,
    /** 用户手势敏感接口须直接在点击回调内调用；这里不会延迟调用或请求额外权限。 */
    call: function (name, params) {
        if (state.status !== 'ready' || state.apis.indexOf(name) < 0 || !state.supported[name]) {
            throw new Error('微信功能尚不可用：' + name);
        }
        window.wx[name](params || {});
    },
    /** SPA也可在路由完成后显式调用，不重复刷新服务器票据。 */
    refresh: function () { return configure(); }
};
window.MyStepWechat = state;
function emit(name, detail) {
    window.dispatchEvent(new CustomEvent('mystep:wechat-' + name, {detail: detail}));
}
function fail() {
    state.status = 'error';
    state.supported = {};
    emit('error', {message: '微信功能暂不可用，请检查账号权限、域名与网络。'});
    updateToolbar();
}
function loadSDK() {
    if (window.wx && typeof window.wx.config === 'function') return Promise.resolve();
    return new Promise(function (resolve, reject) {
        const script = document.createElement('script');
        script.src = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js';
        script.onload = function () {
            if (window.wx && typeof window.wx.config === 'function') resolve();
            else reject(new Error('SDK unavailable'));
        };
        script.onerror = reject;
        document.head.appendChild(script);
    });
}
const sdk = loadSDK();
// 在任何异步签名调用开始前接住SDK加载错误。
sdk.catch(function () {});
async function configure() {
    const sequence = ++generation;
    const url = location.href.split('#')[0];
    state.status = 'loading';
    state.supported = {};
    updateToolbar();
    try {
        const endpoint = new URL(url);
        endpoint.searchParams.set('_wechat_sdk', 'sign');
        endpoint.searchParams.set('url', url);
        const response = await fetch(endpoint.href, {
            credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}
        });
        if (!response.ok) throw new Error('Signature unavailable');
        const signature = await response.json();
        await sdk;
        if (sequence !== generation) return;
        if (!signature.appId || !signature.signature || !Array.isArray(signature.jsApiList)) throw new Error('Invalid signature');
        state.apis = signature.jsApiList.filter(function (name) { return options.apis.indexOf(name) >= 0; });
        const wx = window.wx;
        let finished = false;
        const timeout = setTimeout(function () {
            if (sequence === generation && !finished) { finished = true; fail(); }
        }, 15000);
        wx.error(function () {
            if (sequence === generation && !finished) {
                finished = true;
                clearTimeout(timeout);
                fail();
            }
        });
        wx.config({
            debug: false, appId: signature.appId, timestamp: signature.timestamp,
            nonceStr: signature.nonceStr, signature: signature.signature, jsApiList: state.apis
        });
        wx.ready(function () {
            if (sequence !== generation || finished) return;
            wx.checkJsApi({
                jsApiList: state.apis,
                success: function (result) {
                    if (sequence !== generation || finished) return;
                    finished = true;
                    clearTimeout(timeout);
                    let supported = result.checkResult || {};
                    if (typeof supported === 'string') {
                        try { supported = JSON.parse(supported); } catch (_) { supported = {}; }
                    }
                    state.apis.forEach(function (name) {
                        state.supported[name] = supported[name] === true || supported[name] === 'true';
                    });
                    state.status = 'ready';
                    configuredURL = url;
                    share();
                    updateToolbar();
                    emit('ready', state);
                    try { customReady(wx, state); } catch (_) {
                        emit('script-error', {message: '微信自定义初始化脚本执行失败。'});
                    }
                },
                fail: function () {
                    if (sequence === generation && !finished) {
                        finished = true;
                        clearTimeout(timeout);
                        fail();
                    }
                }
            });
        });
    } catch (_) {
        if (sequence === generation) fail();
    }
}
function meta(selector) {
    const node = document.querySelector(selector);
    return node ? node.content : '';
}
function share() {
    const data = {
        title: options.share.title || document.title,
        desc: options.share.desc || meta('meta[name="description"]'),
        imgUrl: options.share.imgUrl || meta('meta[property="og:image"]'),
        link: location.href.split('#')[0]
    };
    ['updateAppMessageShareData', 'updateTimelineShareData'].forEach(function (name) {
        if (state.supported[name]) state.call(name, data);
    });
}
let toolbar;
function updateToolbar() {
    if (!options.toolbar) return;
    if (!toolbar) {
        toolbar = document.createElement('div');
        toolbar.id = 'mystep-wechat-toolbar';
        toolbar.setAttribute('role', 'group');
        toolbar.setAttribute('aria-label', '微信网页功能');
        document.getElementById('mystep-wechat-content').appendChild(toolbar);
    }
    toolbar.replaceChildren();
    if (state.status !== 'ready') return;
    const actions = [
        ['scanQRCode', '扫一扫', function () {
            state.call('scanQRCode', {needResult: 1, scanType: ['qrCode', 'barCode'], success: function (result) { emit('scan', result); }});
        }],
        ['chooseImage', '拍照／选图', function () {
            state.call('chooseImage', {count: 1, sizeType: ['original', 'compressed'], sourceType: ['album', 'camera'], success: function (result) { emit('image', result); }});
        }],
        ['getLocation', '获取位置', function () {
            state.call('getLocation', {type: 'gcj02', success: function (result) { emit('location', result); }});
        }]
    ];
    actions.forEach(function (action) {
        if (!state.supported[action[0]]) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary';
        button.textContent = action[1];
        button.addEventListener('click', action[2]);
        toolbar.appendChild(button);
    });
}
function routeChanged() {
    clearTimeout(timer);
    timer = setTimeout(function () {
        if (location.href.split('#')[0] !== configuredURL) configure();
    }, 100);
}
['pushState', 'replaceState'].forEach(function (method) {
    const original = history[method];
    history[method] = function () {
        const result = original.apply(this, arguments);
        routeChanged();
        return result;
    };
});
window.addEventListener('popstate', routeChanged);
configure();
