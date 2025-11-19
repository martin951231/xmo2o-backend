<?php
class qrRead{
	public function read_txt($url) {
		require_once  __DIR__ . '/lib/QrReader.php';
		$qrcode = new QrReader($url);  //图片路径
        echo $qrcode;
		$text = $qrcode->text(); //返回识别后的文本
		return $text;
	}
}
?>