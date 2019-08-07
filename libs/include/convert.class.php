<?php
/**
$obj = new convert(32);
$res1 = $obj->idToString(12);

结果:
85U43DM


$obj = new convert(32);
$res1 = $obj->stringToId('85U43DM');

结果:
12


不超过10位的数字
**/

class convert
{

	/**
	 * 初始数字，自定义
	 */
	const INIT_NUM = 123789456;

	/**
	 * @var 进制的基本字符串
	 */
	private $baseChar;

	/**
	 * @var 进制类型
	 */
	private $type;

	/**
	 * @var array 各进制字符串列表
	 */
	private static $convertList = array(
		'32' => '0123456789ABCDEFGHJKMNPQRSTVWXYZ',//不含ILOU
	);

	public function __construct($type='32')
	{
		$this->type = $type;
		$this->baseChar = self::$convertList[$type];
	}

	/**
	 * 公用方法，数字进行进制转换
	 * @param $num
	 * @return string
	 */
	private function _idToString($num){
		$str = '';
		while ($num!=0){
			$tmp = $num % $this->type;
			$str .= $this->baseChar[$tmp];
			$num = intval($num/$this->type);
		}
		return $str;
	}

	/**
	 * @desc  im:十机制数转换成三十二进制数
	 * @param (string)$char 三十二进制数
	 * return 返回：十进制数
	 */
	public function idToString($id){//10位内id 返回7位字母数字
		//数组 增加备用数值
		$id += self::INIT_NUM;

		//左补0 补齐10位
		$str = str_pad($id,10,'0',STR_PAD_LEFT);

		//按位 拆分 4 6位（32进制 4 6位划分）
		$num1 = intval($str[0].$str[2].$str[6].$str[9]);
		$num2 = intval($str[1].$str[3].$str[4].$str[5].$str[7].$str[8]);
		$str1 = $str2 = '';

		$str1 = $this->_idToString($num1);
		$str1 = strrev($str1);

		$str2 = $this->_idToString($num2);
		$str2 = strrev($str2);

		//4 补足 3 4位 U L
		return str_pad($str1,3,'U',STR_PAD_RIGHT).str_pad($str2,4,'L',STR_PAD_RIGHT);
	}

	/**
	 * @desc  im:三十二进制数转换成十机制数
	 * @param (string)$char 三十二进制数
	 * return 返回：十进制数
	 */
	public function stringToId($str){
		//1 清除 3 4 位补足位
		$str1 = trim(substr($str,0,3),'U');
		$str2 = trim(substr($str,3,4),'L');

		$num1 = $this->_stringToId($str1);
		$num2 = $this->_stringToId($str2);

		//补位拼接
		$str1 = str_pad($num1,4,'0',STR_PAD_LEFT);
		$str2 = str_pad($num2,6,'0',STR_PAD_LEFT);

		$id = ltrim($str1[0].$str2[0].$str1[1].$str2[1].$str2[2].$str2[3].$str1[2].$str2[4].$str2[5].$str1[3],'0');

		//减去 备用数值
		$id -= self::INIT_NUM;
		return $id;
	}

	/**
	 * 公用方法字符串转数字
	 * @param $str
	 * @return float|int|string
	 */
	private function _stringToId($str){

		//转换为数组
		$charArr = array_flip(str_split($this->baseChar));
		$num = 0;

		for ($i=0;$i<=strlen($str)-1;$i++)
		{
			$linshi = substr($str,$i,1);
			if(!isset($charArr[$linshi])){
				return '';
			}
			$num += $charArr[$linshi]*pow($this->type,strlen($str)-$i-1);
		}
		return $num;
	}
}
