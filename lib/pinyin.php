<?php
/**
 * 获取汉字拼音及缩写
 * $pinyin = pinyin($str, &$abbr)
 * Modified by windy2000 <windy2006@gmail.com>
 */
class pinyin{}

$GLOBALS['py_idx'] = array (0 => 'a', 2 => 'ai', 15 => 'an', 24 => 'ang', 27 => 'ao', 36 => 'ba', 54 => 'bai', 62 => 'ban', 77 => 'bang', 89 => 'bao', 268 => 'bei', 283 => 'ben', 287 => 'beng', 293 => 'bi', 317 => 'bian', 329 => 'biao', 333 => 'bie', 337 => 'bin', 343 => 'bing', 514 => 'bo', 535 => 'bu', 544 => 'ca', 545 => 'cai', 556 => 'can', 563 => 'cang', 568 => 'cao', 573 => 'ce', 578 => 'ceng', 580 => 'cha', 591 => 'chai', 594 => 'chan', 604 => 'chang', 779 => 'chao', 788 => 'che', 794 => 'chen', 804 => 'cheng', 819 => 'chi', 835 => 'chong', 840 => 'chou', 852 => 'chu', 1030 => 'chuai', 1031 => 'chuan', 1038 => 'chuang', 1044 => 'chui', 1049 => 'chun', 1056 => 'chuo', 1058 => 'ci', 1070 => 'cong', 1076 => 'cou', 1077 => 'cu', 1081 => 'cuan', 1084 => 'cui', 1092 => 'cun', 1095 => 'cuo', 1101 => 'da', 1107 => 'dai', 1281 => 'dan', 1296 => 'dang', 1301 => 'dao', 1313 => 'de', 1316 => 'deng', 1323 => 'di', 1342 => 'dian', 1358 => 'diao', 1367 => 'die', 1536 => 'ding', 1545 => 'diu', 1546 => 'dong', 1556 => 'dou', 1563 => 'du', 1578 => 'duan', 1584 => 'dui', 1588 => 'dun', 1597 => 'duo', 1609 => 'e', 1622 => 'en', 1623 => 'er', 1793 => 'fa', 1801 => 'fan', 1818 => 'fang', 1829 => 'fei', 1841 => 'fen', 1856 => 'feng', 1871 => 'fo', 1872 => 'fou', 1873 => 'fu', 2080 => 'ga', 2082 => 'gai', 2088 => 'gan', 2099 => 'gang', 2108 => 'gao', 2118 => 'ge', 2135 => 'gei', 2136 => 'gen', 2138 => 'geng', 2307 => 'gong', 2322 => 'gou', 2331 => 'gu', 2349 => 'gua', 2355 => 'guai', 2358 => 'guan', 2369 => 'guang', 2372 => 'gui', 2388 => 'gun', 2391 => 'guo', 2397 => 'ha', 2560 => 'hai', 2567 => 'han', 2586 => 'hang', 2589 => 'hao', 2598 => 'he', 2616 => 'hei', 2618 => 'hen', 2622 => 'heng', 2627 => 'hong', 2636 => 'hou', 2643 => 'hu', 2823 => 'hua', 2832 => 'huai', 2837 => 'huan', 2851 => 'huang', 2865 => 'hui', 2886 => 'hun', 2892 => 'huo', 2902 => 'ji', 3117 => 'jia', 3134 => 'jian', 3336 => 'jiang', 3349 => 'jiao', 3377 => 'jie', 3404 => 'jin', 3586 => 'jing', 3611 => 'jiong', 3613 => 'jiu', 3630 => 'ju', 3655 => 'juan', 3662 => 'jue', 3672 => 'jun', 3845 => 'ka', 3849 => 'kai', 3854 => 'kan', 3860 => 'kang', 3867 => 'kao', 3871 => 'ke', 3886 => 'ken', 3890 => 'keng', 3892 => 'kong', 3896 => 'kou', 3900 => 'ku', 3907 => 'kua', 3912 => 'kuai', 3916 => 'kuan', 3918 => 'kuang', 3926 => 'kui', 4099 => 'kun', 4103 => 'kuo', 4107 => 'la', 4114 => 'lai', 4117 => 'lan', 4132 => 'lang', 4139 => 'lao', 4148 => 'le', 4150 => 'lei', 4161 => 'leng', 4164 => 'li', 4360 => 'lia', 4361 => 'lian', 4375 => 'liang', 4386 => 'liao', 4399 => 'lie', 4404 => 'lin', 4416 => 'ling', 4430 => 'liu', 4441 => 'long', 4612 => 'lou', 4618 => 'lu', 4638 => 'lv', 4652 => 'luan', 4658 => 'lue', 4660 => 'lun', 4667 => 'luo', 4679 => 'ma', 4688 => 'mai', 4694 => 'man', 4865 => 'mang', 4871 => 'mao', 4883 => 'me', 4884 => 'mei', 4900 => 'men', 4903 => 'meng', 4911 => 'mi', 4925 => 'mian', 4934 => 'miao', 4942 => 'mie', 4944 => 'min', 4950 => 'ming', 4956 => 'miu', 4957 => 'mo', 5136 => 'mou', 5139 => 'mu', 5154 => 'na', 5161 => 'nai', 5166 => 'nan', 5169 => 'nang', 5170 => 'nao', 5175 => 'ne', 5176 => 'nei', 5178 => 'nen', 5179 => 'neng', 5180 => 'ni', 5191 => 'nian', 5198 => 'niang', 5200 => 'niao', 5202 => 'nie', 5209 => 'nin', 5210 => 'ning', 5378 => 'niu', 5382 => 'nong', 5386 => 'nu', 5389 => 'nv', 5390 => 'nuan', 5391 => 'nue', 5393 => 'nuo', 5397 => 'o', 5398 => 'ou', 5405 => 'pa', 5411 => 'pai', 5417 => 'pan', 5425 => 'pang', 5430 => 'pao', 5437 => 'pei', 5446 => 'pen', 5448 => 'peng', 5462 => 'pi', 5641 => 'pian', 5645 => 'piao', 5649 => 'pie', 5651 => 'pin', 5656 => 'ping', 5665 => 'po', 5674 => 'pu', 5689 => 'qi', 5725 => 'qia', 5890 => 'qian', 5912 => 'qiang', 5920 => 'qiao', 5935 => 'qie', 5940 => 'qin', 5951 => 'qing', 5964 => 'qiong', 5966 => 'qiu', 5974 => 'qu', 6149 => 'quan', 6160 => 'que', 6168 => 'qun', 6170 => 'ran', 6174 => 'rang', 6179 => 'rao', 6182 => 're', 6184 => 'ren', 6194 => 'reng', 6196 => 'ri', 6197 => 'rong', 6207 => 'rou', 6210 => 'ru', 6220 => 'ruan', 6222 => 'rui', 6225 => 'run', 6227 => 'ruo', 6229 => 'sa', 6232 => 'sai', 6236 => 'san', 6402 => 'sang', 6405 => 'sao', 6409 => 'se', 6412 => 'sen', 6413 => 'seng', 6414 => 'sha', 6423 => 'shai', 6425 => 'shan', 6441 => 'shang', 6449 => 'shao', 6460 => 'she', 6472 => 'shen', 6488 => 'sheng', 6661 => 'shi', 6708 => 'shou', 6718 => 'shu', 6913 => 'shua', 6915 => 'shuai', 6919 => 'shuan', 6921 => 'shuang', 6924 => 'shui', 6928 => 'shun', 6932 => 'shuo', 6936 => 'si', 6952 => 'song', 6960 => 'sou', 6963 => 'su', 6976 => 'suan', 6979 => 'sui', 6990 => 'sun', 6993 => 'suo', 7001 => 'ta', 7172 => 'tai', 7181 => 'tan', 7199 => 'tang', 7212 => 'tao', 7223 => 'te', 7224 => 'teng', 7228 => 'ti', 7243 => 'tian', 7251 => 'tiao', 7256 => 'tie', 7259 => 'ting', 7431 => 'tong', 7444 => 'tou', 7448 => 'tu', 7459 => 'tuan', 7461 => 'tui', 7467 => 'tun', 7470 => 'tuo', 7481 => 'wa', 7488 => 'wai', 7490 => 'wan', 7507 => 'wang', 7517 => 'wei', 7712 => 'wen', 7722 => 'weng', 7725 => 'wo', 7734 => 'wu', 7763 => 'xi', 7960 => 'xia', 7973 => 'xian', 7999 => 'xiang', 8019 => 'xiao', 8199 => 'xie', 8220 => 'xin', 8230 => 'xing', 8245 => 'xiong', 8252 => 'xiu', 8261 => 'xu', 8280 => 'xuan', 8452 => 'xue', 8458 => 'xun', 8472 => 'ya', 8488 => 'yan', 8521 => 'yang', 8538 => 'yao', 8715 => 'ye', 8730 => 'yi', 8783 => 'yin', 8961 => 'ying', 8979 => 'yo', 8980 => 'yong', 8995 => 'you', 9016 => 'yu', 9222 => 'yuan', 9242 => 'yue', 9252 => 'yun', 9264 => 'za', 9267 => 'zai', 9274 => 'zan', 9278 => 'zang', 9281 => 'zao', 9295 => 'ze', 9299 => 'zei', 9300 => 'zen', 9301 => 'zeng', 9305 => 'zha', 9481 => 'zhai', 9487 => 'zhan', 9504 => 'zhang', 9519 => 'zhao', 9529 => 'zhe', 9539 => 'zhen', 9555 => 'zheng', 9732 => 'zhi', 9775 => 'zhong', 9786 => 'zhou', 9800 => 'zhu', 9988 => 'zhua', 9990 => 'zhuai', 9991 => 'zhuan', 9997 => 'zhuang', 10004 => 'zhui', 10010 => 'zhun', 10012 => 'zhuo', 10023 => 'zi', 10038 => 'zong', 10045 => 'zou', 10049 => 'zu', 10057 => 'zuan', 10059 => 'zui', 10063 => 'zun', 10065 => 'zuo');

/**
 * 获得某个字的拼音索引
 * @param $idx
 * @return string
 */
function pinyinIdx($idx) {
	global $py_idx;
	if($idx>0 && $idx<160) return chr($idx);
	$idx+=20319;
	$result = "";
	if($idx>=0) {
		for(;$idx>=0; $idx--) {
			if($idx<0) break;
			if(isset($py_idx[$idx])) {
				$result = $py_idx[$idx];
				break;
			}
		}
	}
	return $result;
}

/**
 * 获取字符串拼音
 * @param $str
 * @param string $abbr
 * @return array
 */
function pinyin($str, &$abbr="") {
	$result = array();
	$result_abbr = array();
	$str = myString::setCharset($str, 'GBK');
	for($i=0; $i<strlen($str); $i++) {
		$p = ord(substr($str,$i,1));
		if($p>160) {
			$q = ord(substr($str,++$i,1));
			$p = $p*256+$q-65536;
		}
		$cur_py = pinyinIdx($p);
		if(!empty($cur_py)) {
			$result[] = $cur_py;
			$result_abbr[] = substr($cur_py, 0, 1);
		}
	}
	$abbr = implode("", $result_abbr);
	return $result;
}