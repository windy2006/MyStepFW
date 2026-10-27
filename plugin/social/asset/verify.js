/* 文件：plugin/social/asset/verify.js (new)
 * 功能：从邮件URL片段读取一次性验证信息，仅填入POST隐藏字段，并立即移除地址栏片段。
 * 使用：仅social验证模板加载；不发网络请求、不记录或展示令牌，不自动提交，用户仍须点击确认。
 */
(function () {
    'use strict';
    function prepare() {
        var form=document.getElementById('social-mail-confirm');
        if(!form)return;
        var fields=new URLSearchParams(window.location.hash.slice(1)),id=fields.get('id'),token=fields.get('token');
        if(window.history&&window.history.replaceState)window.history.replaceState(null,'',window.location.pathname+window.location.search);
        if(!/^[a-f0-9]{64}$/.test(id||'')||!/^[a-f0-9]{64}$/.test(token||'')) {
            document.getElementById('social-mail-status').textContent='验证信息缺失或无效，请重新打开邮件链接。';return;
        }
        form.elements.namedItem('pending_id').value=id;form.elements.namedItem('mail_token').value=token;
        document.getElementById('social-mail-submit').disabled=false;
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',prepare);else prepare();
}());
