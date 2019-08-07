<?php

class CPhotoEditDB
{
	public $oldFile = "";
	public $newFile = "";

	public $oldFileWidth = 0;
	public $oldFileHeight = 0;
	public $oldFileType = 0;

	public $newFileWidth = 0;
	public $newFileHeight = 0;

	public $ImgEditObj;
	public $errorMsg = "";
	public function SetEditFile($type=0){
		
		if($this -> oldFile=="" || !file_exists($this -> oldFile)){
			$this -> errorMsg = "原文件不存在， 请重新设置。";
			return false;
		}

		if($this -> newFile=="" ){
			$this -> errorMsg = "生成的新文件未设置。";
			return false;
		}

		$imageMsg = @getimagesize($this -> oldFile);
		if(!$imageMsg){
			$this -> errorMsg = "旧文件不是图片或为系统不支持格式。";
			return false;
		}
		
		if($this -> newFileWidth == 0 && $this -> newFileHeight == 0){
			$this -> errorMsg = "请定义新文件的图片的长宽中的一个。";
			return false;
		}
		
		if (!$handle = @fopen($this -> newFile, 'w')) {
			$this -> errorMsg = "生成的新图片没有权限，请与网站部联系.";
			return false;
		}
		fclose($handle);
		unlink($this -> newFile);
		

		$this -> oldFileWidth = $imageMsg[0];
		$this -> oldFileHeight = $imageMsg[1];
		$this -> oldFileType = $imageMsg["mime"];

		//不允许图片做拉长动作
		if($this -> newFileWidth > $this -> oldFileWidth){
			$this -> newFileWidth = $this -> oldFileWidth;
		}
		if($this -> newFileHeight > $this -> oldFileHeight){
			$this -> newFileHeight = $this -> oldFileHeight;
		}


		if($this -> newFileWidth ==0){
			$this -> newFileWidth = (int)($this -> oldFileWidth * $this -> newFileHeight / $this -> oldFileHeight);
		}else if($this -> newFileHeight ==0){
			$this -> newFileHeight = (int)($this -> oldFileHeight * $this -> newFileWidth / $this -> oldFileWidth);
		}

		$src_x = 0;
		$src_y = 0;
		switch($type){
			case 1:
				$src_x = $this->newFileWidth;
				$src_y = $this->newFileHeight;
				break;
			default:
				$src_x = $this->oldFileWidth;
				$src_y = $this->oldFileHeight;
				break;
		}
		
		
		switch ($imageMsg[2]) {
			case 1: //图片类型，1是GIF图

				$oldIm = @ImageCreateFromGIF($this -> oldFile);
				if(!$oldIm){
					$this -> errorMsg = "系统不支持".$imageMsg["mime"]."图片格式的php函数，请与网站部联系。";
					return false;
				}
				$newIm = imagecreatetruecolor($this -> newFileWidth,$this -> newFileHeight);
				imagecopyresampled($newIm,$oldIm,0,0,0,0,$this -> newFileWidth,$this -> newFileHeight,$src_x,$src_y);
				imagejpeg($newIm,$this->newFile,100);
				imagedestroy($newIm);
				imagedestroy($oldIm);
				break;
			case 2: //图片类型，2是JPG图

				$oldIm = @imagecreatefromjpeg($this -> oldFile);
				if(!$oldIm){
					$this -> errorMsg = "系统不支持".$imageMsg["mime"]."图片格式的php函数，请与网站部联系。";
					return false;
				}
				$newIm = imagecreatetruecolor($this -> newFileWidth,$this -> newFileHeight);
				imagecopyresampled($newIm,$oldIm,0,0,0,0,$this -> newFileWidth,$this -> newFileHeight,$src_x,$src_y);
				imagejpeg($newIm,$this->newFile,100);
				imagedestroy($newIm);
				imagedestroy($oldIm);
				break;
			case 3: //图片类型，3是PNG图
			
				$oldIm = @imagecreatefrompng($this -> oldFile);
//				$oldIm = @ImageCreateFromPNG($this -> oldFile);
				if(!$oldIm){
					$this -> errorMsg = "系统不支持".$imageMsg["mime"]."图片格式的php函数，请与网站部联系。";
					return false;
				}
				$newIm = imagecreatetruecolor($this -> newFileWidth,$this -> newFileHeight);
				imagealphablending($newIm,false);//这里很重要,意思是不合并颜色,直接用$img图像颜色替换,包括透明色;
				imagesavealpha($newIm,true);//这里很重要,意思是不要丢了$thumb图像的透明色;
				imagecopyresampled($newIm,$oldIm,0,0,0,0,$this -> newFileWidth,$this -> newFileHeight,$src_x,$src_y);
				imagepng($newIm,$this->newFile);
//				imagejpeg($newIm,$this->newFile,100);
				imagedestroy($newIm);
				imagedestroy($oldIm);
				break;
			default:
				$this -> errorMsg = "旧文件为系统不支持格式。";
				return false;
				break;
		}
		return true;
	}

	public function copyImgToNewSize($newWidth=0,$newHeight=0){
		$this -> oldFile = $this->newFile;
		$temp = dirname($this->newFile);
		$tempImg = $temp."/".time().rand(0,100)."_temp.jpg";
		$this->newFile = $tempImg;

		$imageMsg = @getimagesize($this -> oldFile);
		$IndexWidth = $newWidth;
		$IndexHeight = $newHeight;
		$imgX = $imageMsg[0];
		$imgY = $imageMsg[1];
		if($imgX==0 || $imgY==0)
			return false;
		$per = $imgY/$imgX;
		
		if($newWidth==0 && $newHeight==0)
		{
			$newWidth = $imageMsg[0];
		}

		$tempY = $IndexWidth * $per;
		if($tempY >= $IndexHeight){
			$this->newFileWidth = $newWidth;
			$this->newFileHeight = $tempY;
					
			if(!$this -> SetEditFile())
			{			
				return false;
			}
			@unlink($this->oldFile);
			rename($this->newFile, $this->oldFile);
			
		}else{
			$this->newFileWidth = $IndexHeight * ( $imgX / $imgY);
			$this->newFileHeight = $newHeight;
			if(!$this -> SetEditFile())
			{
				return false;
			}
			@unlink($this->oldFile);
			rename($this->newFile, $this->oldFile);
		}
		
		return true;
		
	}
}
