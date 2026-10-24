<?php
/**
 * 文件：app/Admin/module/info/error.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：errors、clearErrors、downloadErrors（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：info_error。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：日志尾部及操作，支持鉴权后的GET下载，清空仍由POST动作处理。
     * 参数说明：无显式参数。
     * @return string 日志页HTML；日志不存在或为空时禁用下载/清空按钮，GET/HEAD的download=1发送完整日志并终止请求
     * @throws InvalidArgumentException 下载时日志不存在
     */
    'errors' => function() {
        // 此视图仅由Admin鉴权后调用；下载参数只读取GET，不分发写操作。
        if(in_array($_SERVER['REQUEST_METHOD']??'GET', ['GET', 'HEAD'], true) && myReq::get('download') === '1') {
            $result = $this->downloadErrors([]);
            myController::file($result['download']['file'], $result['download']['name']);
        }
        $file = ROOT . 'error.log';
        $text = '';
        if(is_file($file)) {
            $fp = fopen($file, 'rb');
            if(filesize($file) > 2097152)fseek($fp, -2097152, SEEK_END);
            $text = stream_get_contents($fp);
            fclose($fp);
        }
        return $this->ui->view('info_error', ['log' => $text === ''?'暂无错误日志。':$text, 'download_url' => $this->ui->url('error', ['download' => 1])], [], ['has_log' => $text !== '']);
    },
    /**
     * 功能说明：清空框架错误日志。
     * @param array $p 校验参数
     * @return array 操作结果
     */
    'clearErrors' => function($p) {
        if(is_file(ROOT . 'error.log') && !unlink(ROOT . 'error.log'))throw new RuntimeException('无法清空日志。');
        return $this->ui->success('错误日志已清空。');
    },
    /**
     * 功能说明：返回完整错误日志下载描述。
     * @param array $p 校验参数
     * @return array 下载结果
     */
    'downloadErrors' => function($p) {
        if(!is_file(ROOT . 'error.log'))throw new InvalidArgumentException('暂无日志。');
        return ['download' => ['file' => ROOT . 'error.log', 'name' => date('Ymd') . '_error.txt']];
    },
];
