<?php

namespace app\pay\model\service\libs\icbc;
	class IcbcEncrypt{
		public static function encryptContent($content, $encryptType, $encryptKey, $charset){
			dd(func_get_args());
			if(IcbcConstants::$ENCRYPT_TYPE_AES == $encryptType){
				return AES::AesEncrypt($content,base64_decode($encryptKey));
			}else{
				throw new \Exception("Only support AES encrypt!");
			}
		}

		public static function decryptContent($encryptedContent, $encryptType, $encryptKey, $charset){
			if(IcbcConstants::$ENCRYPT_TYPE_AES == $encryptType){
				return AES::AesDecrypt($encryptedContent,base64_decode($encryptKey));
			}else{
				throw new \Exception("Only support AES decrypt!");
			}
		}
	}
?>