<?php
/**
 * 文件：app/Admin/module/setting/execute.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：execute、runCode（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：setting_execute。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：代码工具，GET只展示源码，POST才执行。
     * 参数说明：无显式参数。
     * @return string 代码工具，GET只展示源码，POST才执行
     */
    'execute' => function() {
        $code = "<?php\necho PHP_VERSION;\n";
        $example = $this->ui->input('example', '');
        if($example !== '') {
            $example = AdminStore::identifier($example);
            $file = myFile::resolveWithin(APP . 'Document/module/', $example . '.php');
            if(!$file)throw new InvalidArgumentException('示例不存在。');
            $code = file_get_contents($file);
        }
        $result = myReq::session('_admin_code_result');
        myReq::removeSession('_admin_code_result');
        return $this->ui->view('setting_execute', ['code' => $code, 'result' => is_string($result)?$result:'尚未执行。']);
    },
    /**
     * 功能说明：执行管理员明确提交的PHP代码并暂存有限长度输出。
     * @param array $p code
     * @return array PRG反馈
     */
    'runCode' => function($p) {
        $result = myEval($p['code'], false);
        myReq::session('_admin_code_result', substr((string)$result, 0, 1048576));
        return $this->ui->success('代码已执行。');
    },
];
