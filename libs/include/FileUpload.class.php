<?php

require_once( LIB_PATH . "/include/photoEdit.class.php" );

class CUpload extends CPhotoEditDB
{
	public $FormName = "";
	public $MaxSize =  102400;//1048576
	public $StoreFile = "";
	public $AllowType = ",image/gif,image/x-png,image/jpeg,image/pjpeg,";
	public $file_type = "";

	public function UpLoadAction(&$MsgString){

		if(!$this->CheckInput($MsgString)){
			return false;	
		}
		$upload_file=$_FILES[$this->FormName]['tmp_name'];
		$upload_file_name=$_FILES[$this->FormName]['name'];

		if ($_FILES[$this->FormName]['size'] == 0) {// 检查文件大小
			$MsgString="请选择上传文件";
			unlink($upload_file);
			return false;
		}
		if ($_FILES[$this->FormName]['size'] > $this->MaxSize) {// 检查文件大小
			$MsgString="文件大小超过了允许上传的大小";
			unlink($upload_file);
			return false;
		}

		$this -> file_type = strtolower($_FILES[$this->FormName]["type"]);
		if( !strstr($this->AllowType, (",".strtolower($_FILES[$this->FormName]["type"]).",")) ){// 检查文件格式
			$MsgString="文件格式错误(".$_FILES[$this->FormName]["type"]."), 允许上传的文件的格式为 " .substr($this->AllowType,1);		
			unlink($upload_file);
			return false;
		}

		if (!move_uploaded_file($upload_file,$this->StoreFile)) {//复制文件到指定目录
			$MsgString="文件上传失败, 内部错误,请与网站部联系";
			unlink($upload_file);
			return false;
		}

		return true;	
	}

	public function CheckInput(&$MsgString)
	{
		$MsgString ="参数传递错误";

		switch($_FILES[$this->FormName]['error'])
		{
			case 1:
				$MsgString ="上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值.";
				return false;
				break;
			case 2:
				$MsgString ="上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。";
				return false;
				break;
			case 3:
				$MsgString ="文件只有部分被上传";
				return false;
				break;
			case 4:
				$MsgString ="没有文件被上传";
				return false;
				break;
		}
		
		if($this->FormName=="")
			return false;
		
		
		if($this->StoreFile=="")
			return false;

		
		if((int)($this->MaxSize)<=0)
			return false;
		
		if ($_FILES[$this->FormName]['size'] == 0) 
			return false;
	
		return true;
	}

}
