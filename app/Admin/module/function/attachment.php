<?php
/**
 * 文件：app/Admin/module/function/attachment.php
 * 功能说明：本功能页面读取与动作处理，保持业务和对应模板独立维护。
 * 方法目录：attachments、deleteAttachment（受信模块回调，功能及参数说明见各回调PHPDoc）。
 * 对应模板：function_attachment。
 * 使用说明：由AdminService按lib.php中pages()的固定模块映射加载；$this为应用服务，ui为当前Admin。
 * 返回说明：视图回调返回模板HTML，动作返回反馈或下载描述；HTTP鉴权及参数校验由Admin统一完成。
 */
return [
    /**
     * 功能说明：附件按年月浏览，保持已记录与未记录附件的区别。
     * 参数说明：无显式参数。
     * @return string 附件按年月浏览，保持已记录与未记录附件的区别
     */
    'attachments' => function() {
        $years = $this->directories(FILE, '/\A\d{4}\z/D');
        $year = $this->ui->input('y', $years[0]??'');
        if(!is_string($year) || !in_array($year, $years, true))$year = $years[0]??'';
        $months = $year === ''?[]:$this->directories(FILE . $year, '/\A(?:0[1-9]|1[0-2])\z/D');
        $month = $this->ui->input('m', $months[0]??'');
        if(!is_string($month) || !in_array($month, $months, true))$month = $months[0]??'';
        $yearLinks = $monthLinks = [];
        foreach($years as $y) $yearLinks[] = ['name'=>$y,'url'=>$this->ui->url('function/attachment',['y'=>$y]),'current'=>$y === $year?'page':'false'];
        foreach($months as $m) $monthLinks[] = ['name'=>$m.'月','url'=>$this->ui->url('function/attachment',['y'=>$year,'m'=>$m]),'current'=>$m === $month?'page':'false'];
        $rows = [];
        if($year !== '' && $month !== '') {
            $dir = FILE . $year . '/' . $month . '/';
            $log = [];
            if(is_file($dir . 'log.txt') && !is_link($dir . 'log.txt'))foreach(file($dir . 'log.txt') as $line) {
                $pieces = explode('::', rtrim($line, "\r\n"));
                if(count($pieces) >= 2)$log[$pieces[0]] = $pieces[1];
            }
            foreach(new DirectoryIterator($dir) as $f) {
                $name = $f->getFilename();
                if(!$f->isFile() || $f->isLink() || $name === 'log.txt' || $name[0] === '.')continue;
                $logged = isset($log[$name]);
                $relative = $year . '/' . $month . '/' . $name;
                $idx = $logged?pathinfo($name, PATHINFO_FILENAME):$relative;
                $download = $logged?myStep::setURL('api/myStep/download/' . $idx . '/?core=Admin'):ROOT_WEB . 'files/' . $relative;
                $rows[] = ['name'=>$log[$name]??$name,'state'=>$logged?'已记录':'未记录','size'=>myFile::formatSize($f->getSize()),'date'=>date('Y-m-d H:i:s',$f->getMTime()),'idx'=>$idx,'logged'=>$logged?1:0,'download'=>$download];
            }
        }
        return $this->ui->view('function_attachment', ['year'=>$year,'month'=>$month], ['years'=>$yearLinks,'months'=>$monthLinks,'files'=>$rows]);
    },
    /**
     * 功能说明：按已记录或未记录模式精确删除单个附件。
     * @param array $p idx/logged
     * @return array 删除反馈
     */
    'deleteAttachment' => function($p) {
        if($p['logged']) {
            $record = myAttachment::find(FILE, $p['idx'], $this->ui->setting->upload->path_mode);
            if(!$record || !myAttachment::remove(FILE, $p['idx'], $this->ui->setting->upload->path_mode))throw new InvalidArgumentException('未找到附件或删除失败。');
        }
        else {
            $target = myFile::extractionPath(FILE, $p['idx']);
            if(!$target || !is_file($target) || basename($target) === 'log.txt')throw new InvalidArgumentException('未记录附件路径无效。');
            $log = dirname($target) . '/log.txt';
            if(is_file($log))foreach(file($log) as $line)if(explode('::', $line)[0] === basename($target))throw new InvalidArgumentException('该文件有记录，请按已记录附件删除。');
            if(!unlink($target))throw new RuntimeException('附件删除失败。');
        }
        return $this->ui->success('附件已删除。', 'function/attachment', ['y' => $this->ui->input('y', ''), 'm' => $this->ui->input('m', '')]);
    },
];
