<?php
/**
 * 微信网页插件生命周期、设置校验、账号能力筛选与page钩子。
 * 签名动作在module/sign.php，HTML在template，浏览器逻辑在asset。
 */
class plugin_wechat implements interface_plugin {
    /** 检查安装环境；不连接微信、不更改账号配置。 */
    public static function check(&$result = '') {
        $ok = extension_loaded('curl');
        $result = $ok ? 'cURL可用；请配置账号、出口IP白名单和JS接口安全域名。' : '需要启用PHP cURL扩展。';
        return $ok;
    }

    /** 安装不自动启用网页功能或修改应用绑定。 */
    public static function install() {
        if (!self::check($result)) throw new RuntimeException($result);
        // 独立插件包不带在线config.php，首次安装从无凭据默认值建立。
        if (!is_file(__DIR__.'/config.php')) {
            $config = new myConfig(__DIR__.'/config.php');
            $config->merge(include __DIR__.'/config_default.php');
            $config->save('php');
        }
        return true;
    }

    /** 卸载保留站点设置及私有缓存，既有有效票据到期后失效。 */
    public static function uninstall() { return true; }

    /** 读取配置并补齐默认值，防止缺省字段造成开放权限。 */
    public static function options(): array {
        return self::normalize(array_replace(include __DIR__.'/config_default.php', (new myConfig(__DIR__.'/config.php'))->get(true)));
    }

    /** 后台开启前先测试真实微信凭证和票据，失败不发布待保存配置；关闭时允许保存草稿。 */
    public static function validateConfig(myConfig $config, ?callable $transport = null) {
        $options = self::normalize(array_replace(include __DIR__.'/config_default.php', $config->get(true)));
        if ($options['enabled']) self::testConnection($options, $transport);
    }

    /**
     * 验证待开启的设置并重新向微信获取凭证及JS-SDK票据；传输替身仅供隔离测试。
     * 服务器测试不能代替微信客户端的域名、具体接口及用户授权验证。
     * @throws InvalidArgumentException 配置或上游测试失败，消息不包含凭据和微信响应正文。
     */
    public static function testConnection(array $options, ?callable $transport = null, ?string $directory = null): void {
        $options = self::normalize(array_replace($options, ['enabled'=>true]));
        if (!self::capabilities($options)) throw new InvalidArgumentException('开启前请至少填写一个符合账号条件的JS接口。');
        require_once __DIR__.'/WeChatClient.php';
        try {
            $client = new WeChatClient($options, $directory ?? CONFIG.'wechat_runtime', $transport);
            $client->ticket(true);
        } catch (Throwable) {
            throw new InvalidArgumentException('微信API测试未通过，未保存本次开启设置。请核对AppID、AppSecret、服务器出口IP白名单及网络，等待30秒后重试。安全域名和具体功能还需在微信内验证。');
        }
    }

    /** 严格规范输入；未知接口拒绝保存，已知但不符账号权限的接口运行时过滤。 */
    public static function normalize(array $options): array {
        foreach (['enabled','verified','card_enabled','pay_enabled','toolbar'] as $key) {
            $options[$key] = in_array($options[$key] ?? false, [true,1,'1','true','y'], true);
        }
        if (!in_array($options['account_type'] ?? '', ['subscription','service'], true)) {
            throw new InvalidArgumentException('请选择实际的订阅号或服务号类型。');
        }
        foreach (['appid','secret','origins','exclude_paths','apis','share_title','share_desc','share_image','html','ready_script'] as $key) {
            if (!is_string($options[$key] ?? null)) throw new InvalidArgumentException('微信设置字段格式错误：'.$key);
        }
        if (strlen($options['html']) > 65536 || strlen($options['ready_script']) > 32768) {
            throw new InvalidArgumentException('注入内容过长。');
        }
        if (preg_match('~</script|<!--~i', $options['ready_script'])) throw new InvalidArgumentException('初始化脚本不能包含HTML脚本边界。');
        $origins = preg_split('/\s+/', trim($options['origins']), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($origins as $origin) {
            if (!preg_match('~\Ahttps://[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z~D', $origin)
                || filter_var(substr($origin, 8), FILTER_VALIDATE_IP)
                || str_contains($origin, '..')) {
                throw new InvalidArgumentException('网站来源须为精确HTTPS域名，不含路径、端口、IP或通配符。');
            }
        }
        foreach (self::lines($options['exclude_paths']) as $prefix) {
            if (!str_starts_with($prefix, '/') || preg_match('/[\x00-\x20?#]/', $prefix)) {
                throw new InvalidArgumentException('排除路径须以/开头且不含查询串或空白。');
            }
        }
        $apis = self::requested($options);
        if (array_diff($apis, array_keys(self::catalog()))) throw new InvalidArgumentException('接口列表含未支持或已弃用的接口，请核对README。');
        if ($options['share_image'] !== '' && (!filter_var($options['share_image'], FILTER_VALIDATE_URL)
            || parse_url($options['share_image'], PHP_URL_SCHEME) !== 'https')) {
            throw new InvalidArgumentException('分享封面须使用完整HTTPS地址。');
        }
        if ($options['enabled'] && (!preg_match('/\Awx[a-zA-Z0-9]{16}\z/D', $options['appid'])
            || !preg_match('/\A[a-zA-Z0-9]{16,128}\z/D', $options['secret']) || !$origins)) {
            throw new InvalidArgumentException('开启前须填写AppID、AppSecret及允许的网站来源。');
        }
        return $options;
    }

    /** 返回逐行非空配置项。 */
    public static function lines(string $text): array {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text)), static fn($s) => $s !== ''));
    }

    /** 支持接口与权限组；不暴露旧分享、iBeacon等历史接口。 */
    public static function catalog(): array {
        $groups = [
            'basic' => 'checkJsApi chooseImage previewImage uploadImage downloadImage getLocalImgData startRecord stopRecord onVoiceRecordEnd playVoice pauseVoice stopVoice onVoicePlayEnd uploadVoice downloadVoice translateVoice getNetworkType openLocation getLocation closeWindow hideMenuItems showMenuItems hideAllNonBaseMenuItem showAllNonBaseMenuItem scanQRCode',
            'verified' => 'updateAppMessageShareData updateTimelineShareData',
            'card' => 'chooseCard addCard openCard',
            'pay' => 'chooseWXPay',
        ];
        $result = [];
        foreach ($groups as $group => $names) foreach (explode(' ', $names) as $name) $result[$name] = $group;
        return $result;
    }

    /** 按管理员配置读取接口，不默默申请额外业务接口。 */
    public static function requested(array $options): array {
        return array_values(array_unique(preg_split('/[\s,]+/', trim($options['apis']), -1, PREG_SPLIT_NO_EMPTY)));
    }

    /** 账号权限交集；真实权限仍由wx.config和客户端检测裁决。 */
    public static function capabilities(array $options): array {
        $catalog = self::catalog();
        return array_values(array_filter(self::requested($options), static function ($api) use ($options, $catalog) {
            return match ($catalog[$api] ?? '') {
                'basic' => true,
                'verified' => $options['verified'],
                'card' => $options['verified'] && $options['card_enabled'],
                'pay' => $options['verified'] && $options['account_type'] === 'service' && $options['pay_enabled'],
                default => false,
            };
        }));
    }

    /** 验证待签名URL，保持查询参数编码与顺序，只剥离片段；不采用Host或代理头建立信任。 */
    public static function validatedURL(string $url, array $options): string {
        $url = explode('#', $url, 2)[0];
        if (strlen($url) > 8192 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)) throw new InvalidArgumentException('非法网页地址。');
        $parts = parse_url($url);
        if (!$parts || isset($parts['user'], $parts['pass']) || isset($parts['user']) || isset($parts['port'])
            || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) throw new InvalidArgumentException('非法网页来源。');
        $origin = 'https://'.$parts['host'];
        if (!in_array($origin, preg_split('/\s+/', trim($options['origins'])), true)) throw new InvalidArgumentException('网页来源不在允许列表。');
        $path = $parts['path'] ?? '/';
        $decoded = rawurldecode($path);
        if (preg_match('/[\\\\\x00-\x20]/', $decoded) || preg_match('~(?:^|/)\.{1,2}(?:/|$)~', $decoded)) {
            throw new InvalidArgumentException('非法网页路径。');
        }
        foreach (self::lines($options['exclude_paths']) as $prefix) {
            if (str_starts_with($path, $prefix) || str_starts_with($decoded, $prefix)) throw new InvalidArgumentException('该页面已排除。');
        }
        return $url;
    }

    /** 当前实际请求的URL；与配置的精确来源比较后才允许签名或注入。 */
    public static function requestURL(): string {
        return 'https://'.($_SERVER['HTTP_HOST'] ?? '').($_SERVER['REQUEST_URI'] ?? '/');
    }

    /** 只用于选择浏览器体验；UA并非微信身份认证。 */
    public static function inWechat(): bool {
        return stripos($_SERVER['HTTP_USER_AGENT'] ?? '', 'MicroMessenger') !== false;
    }

    /** 最终HTML page钩子；模板缓存之后注入，普通浏览器及非完整HTML保持原样。 */
    public static function page(string $page, array $options): string {
        if (!headers_sent()) {
            header('Cache-Control: private, no-store');
            header('Vary: User-Agent', false);
        }
        if (!self::inWechat() || !preg_match('~</body\s*>~i', $page) || !preg_match('~<html(?:\s|>)~i', $page)
            || str_contains($page, 'id="mystep-wechat-config"')) return $page;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'text/html') === false) return $page;
            if (stripos($header, 'Content-Disposition:') === 0) return $page;
        }
        try { self::validatedURL(self::requestURL(), $options); } catch (InvalidArgumentException) { return $page; }
        $public = [
            'apis' => self::capabilities($options),
            'accountType' => $options['account_type'],
            'verified' => $options['verified'],
            'share' => ['title'=>$options['share_title'], 'desc'=>$options['share_desc'], 'imgUrl'=>$options['share_image']],
            'toolbar' => $options['toolbar'],
        ];
        $json = json_encode($public, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
        $html = $options['html'];
        // 初始化脚本仅由管理员维护，已拒绝HTML脚本边界，作为ready回调函数体输出。
        $script = $options['ready_script'];
        ob_start();
        include __DIR__.'/template/inject.php';
        $injection = ob_get_clean();
        preg_match_all('~</body\s*>~i', $page, $matches, PREG_OFFSET_CAPTURE);
        $position = $matches[0][count($matches[0]) - 1][1];
        return substr_replace($page, $injection, $position, 0);
    }
}
