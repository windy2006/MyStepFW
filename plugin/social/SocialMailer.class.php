<?PHP
/********************************************
*                                           *
* Name    : Social Mail Transport (new)     *
* Modifier: Windy2000                       *
* Time    : 2026-10-25                      *
* Email   : windy2006@gmail.com             *
* HomePage: www.mysteps.cn                  *
* Notice  : U Can Use & Modify it freely,   *
*           BUT PLEASE HOLD THIS ITEM.      *
*                                           *
********************************************/
/**
 * 文件：plugin/social/SocialMailer.class.php (new)
 * 功能说明：用框架myEmail发送本站验证链接；失败抛异常供流程撤销令牌，不回落到PHP mail。
 * 方法目录：
 *   public SocialMailer::__construct(array $smtp,string $site) — 保存受信邮件配置，不连接SMTP。
 *   public SocialMailer::deliver(string $email,string $link): void — 发送一次性本站邮箱验证邮件，只有SMTP确认接收才返回。
 *   protected SocialMailer::error($msg,$exit=false) — 阻止SMTP失败进入全局页面错误回调，交给调用方撤销未完成验证。
 * 使用说明：使用宿主应用email设置；测试只接本机SMTP或注入SocialFlow邮件替身，不向真实外部联系人发测试信。
 */
class SocialMailer extends myEmail {
    private array $smtp;
    private string $site;
    /**
     * 功能说明：保存受信邮件配置，不连接SMTP。
     * @param array $smtp 应用SMTP选项
     * @param string $site 站点显示名称
     * @return void 初始化完成
     */
    public function __construct(array $smtp,string $site){$this->smtp=$smtp;$this->site=$site;}
    /**
     * 功能说明：发送一次性本站邮箱验证邮件，只有SMTP确认接收才返回。
     * @param string $email 目标邮箱
     * @param string $link 本站带片段的确认URL
     * @return void 成功提交
     * @throws RuntimeException 邮件配置或传输失败
     */
    public function deliver(string $email,string $link): void {
        $from=(string)($this->smtp['from']??$this->smtp['user']??'');
        if(!filter_var($from,FILTER_VALIDATE_EMAIL)||(string)($this->smtp['host']??'')==='')throw new RuntimeException('请先设置应用SMTP服务器及有效发件邮箱。');
        $this->init($from,'UTF-8');$this->from($from,$this->site);$this->to($email);$this->subject($this->site.' - 邮箱验证');
        $text="请在发起第三方登录的同一浏览器中打开以下链接，并点击确认完成邮箱验证：\n".$link."\n\n若非本人操作，请忽略此邮件。验证链接短时有效，勿转发。";
        $safeLink=htmlspecialchars($link,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $this->setContent('<p>请在发起第三方登录的同一浏览器中确认邮箱：</p><p><a href="'.$safeLink.'">确认邮箱并继续</a></p><p>若非本人操作，请忽略此邮件。验证链接短时有效，勿转发。</p>',true,$text);
        $result=$this->send($this->smtp,false,3);if(!is_array($result)||!$result)throw new RuntimeException('验证邮件未能提交。');
        foreach($result as $message)if(!str_ends_with((string)$message,' sent successfully!'))throw new RuntimeException('验证邮件未能提交。');
    }
    /**
     * 功能说明：阻止SMTP失败进入全局页面错误回调，交给调用方撤销未完成验证。
     * @param string $msg 内部错误，不公开
     * @param bool $exit 默认false；继承占位
     * @return never 始终抛异常
     * @throws RuntimeException 验证邮件传输失败
     */
    protected function error($msg,$exit=false){throw new RuntimeException('验证邮件传输失败，请检查SMTP设置。');}
}
