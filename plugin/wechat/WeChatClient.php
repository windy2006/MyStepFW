<?php
/**
 * 微信服务器凭证客户端：固定微信API目标、TLS校验、并发锁、原子私有缓存。
 * 可注入HTTP传输用于隔离验证，生产默认使用cURL；不向页面公开凭证。
 */
class WeChatClient {
    /** @param array $options 已验证的账号配置 @param string $directory 私有缓存目录 */
    public function __construct(private array $options, private string $directory, private $transport = null) {}

    /** 为不含片段的完整URL产生JSSDK签名，只返回公开参数。 */
    public function signature(string $url): array {
        $ticket = $this->ticket();
        $nonce = bin2hex(random_bytes(16));
        $timestamp = time();
        return [
            'appId' => $this->options['appid'],
            'timestamp' => $timestamp,
            'nonceStr' => $nonce,
            'signature' => sha1('jsapi_ticket='.$ticket.'&noncestr='.$nonce.'&timestamp='.$timestamp.'&url='.$url),
        ];
    }

    /** 持锁复用凭证；testConnection为true时重新请求token和ticket，测试不强制使微信既有token失效。 */
    public function ticket(bool $testConnection = false): string {
        $dir = $this->directory;
        if (is_link($dir)) throw new RuntimeException('Invalid credential directory');
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) throw new RuntimeException('Cannot create credential directory');
        clearstatcache(true, $dir);
        if (is_link($dir) || !is_dir($dir)) throw new RuntimeException('Invalid credential directory');
        // 空ignore文件使框架打包器关闭该运行目录，避免票据进入安装包。
        $ignore = $dir.'/ignore';
        if (!file_exists($ignore)) {
            $marker = @fopen($ignore, 'x');
            if ($marker) { fclose($marker); chmod($ignore, 0600); }
        }
        clearstatcache(true, $ignore);
        if (is_link($ignore) || !is_file($ignore) || filesize($ignore) !== 0) throw new RuntimeException('Credential cache must be excluded from packages');
        $key = hash('sha256', $this->options['appid']."\0".$this->options['secret']);
        $file = $dir.'/'.$key.'.php';
        $lockfile = $dir.'/'.$key.'.lock';
        if (is_link($file) || is_link($lockfile)) throw new RuntimeException('Invalid credential file');
        $lock = fopen($lockfile, 'c');
        if (!$lock) throw new RuntimeException('Cannot open credential lock');
        chmod($lockfile, 0600);
        $locked = false;
        try {
            $deadline = microtime(true) + 3;
            do {
                $locked = flock($lock, LOCK_EX|LOCK_NB);
                if (!$locked) usleep(20000);
            } while (!$locked && microtime(true) < $deadline);
            if (!$locked) throw new RuntimeException('Credential lock busy');
            $state = is_file($file) ? include $file : [];
            if (!is_array($state)) $state = [];
            $now = time();
            if (!$testConnection && ($state['ticket_until'] ?? 0) > $now && !empty($state['ticket'])) return $state['ticket'];
            if (($state['retry_at'] ?? 0) > $now) throw new RuntimeException('Credential refresh cooling down');
            try {
                if ($testConnection || ($state['token_until'] ?? 0) <= $now || empty($state['token'])) {
                    // stable_token默认不强制刷新，避免使同账号的其他接入失效。
                    $token = $this->request('https://api.weixin.qq.com/cgi-bin/stable_token', [
                        'grant_type'=>'client_credential', 'appid'=>$this->options['appid'],
                        'secret'=>$this->options['secret'], 'force_refresh'=>false,
                    ]);
                    $state['token'] = $this->value($token, 'access_token');
                    $state['token_until'] = $now + $this->lifetime($token);
                }
                $ticket = $this->request('https://api.weixin.qq.com/cgi-bin/ticket/getticket?'.http_build_query([
                    'access_token'=>$state['token'], 'type'=>'jsapi',
                ]));
                $state['ticket'] = $this->value($ticket, 'ticket');
                $state['ticket_until'] = $now + $this->lifetime($ticket);
                $state['retry_at'] = 0;
                $this->save($file, $state);
                return $state['ticket'];
            } catch (Throwable $error) {
                // 被外部接入使token失效时，下次冷却后重新获取，不在一次请求中反复刷新。
                if (in_array((int)$error->getCode(), [40001,40014,42001], true)) $state['token_until'] = 0;
                $state['retry_at'] = time() + 30;
                $this->save($file, $state);
                throw new RuntimeException('WeChat credentials unavailable');
            }
        } finally {
            if ($locked) flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** 固定微信API HTTPS请求，不跟随重定向、不将上游错误正文暴露给调用者。 */
    private function request(string $url, ?array $body = null): array {
        if ($this->transport !== null) {
            $data = ($this->transport)($url, $body);
        } else {
            $curl = curl_init($url);
            $response = '';
            curl_setopt_array($curl, [
                CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>8, CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
                CURLOPT_PROTOCOLS_STR=>'https',
                CURLOPT_WRITEFUNCTION=>static function ($handle, $chunk) use (&$response) {
                    if (strlen($response) + strlen($chunk) > 65536) return 0;
                    $response .= $chunk;
                    return strlen($chunk);
                },
            ]);
            if ($body !== null) curl_setopt_array($curl, [
                CURLOPT_POST=>true, CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
                CURLOPT_POSTFIELDS=>json_encode($body, JSON_THROW_ON_ERROR),
            ]);
            try {
                $ok = curl_exec($curl);
                if ($ok === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) throw new RuntimeException('WeChat transport failed');
                $data = json_decode($response, true, 32, JSON_THROW_ON_ERROR);
            } finally {
                unset($curl);
            }
        }
        if (!is_array($data)) throw new RuntimeException('Invalid WeChat response');
        if (!empty($data['errcode'])) throw new RuntimeException('WeChat API rejected request', (int)$data['errcode']);
        return $data;
    }

    /** 验证凭证值，禁止把非预期上游结构缓存为成功。 */
    private function value(array $data, string $key): string {
        if (!is_string($data[$key] ?? null) || $data[$key] === '' || strlen($data[$key]) > 4096) throw new RuntimeException('Missing credential');
        return $data[$key];
    }

    /** 在微信有效期前至少120秒刷新，拒绝没有有效期的响应。 */
    private function lifetime(array $data): int {
        $ttl = filter_var($data['expires_in'] ?? null, FILTER_VALIDATE_INT);
        if ($ttl === false || $ttl < 180) throw new RuntimeException('Invalid credential lifetime');
        return min($ttl, 7200) - 120;
    }

    /** PHP返回文件可防止正常Web访问读取；临时文件与目标均设置0600且原子替换。 */
    private function save(string $file, array $state): void {
        $temp = tempnam($this->directory, '.wechat-');
        if ($temp === false) throw new RuntimeException('Cannot write credential cache');
        try {
            chmod($temp, 0600);
            $content = '<?php return '.var_export($state, true).';';
            if (file_put_contents($temp, $content) !== strlen($content) || !rename($temp, $file)) throw new RuntimeException('Cannot publish credential cache');
            if (function_exists('opcache_invalidate')) opcache_invalidate($file, true);
        } finally {
            if (is_file($temp)) unlink($temp);
        }
    }
}
