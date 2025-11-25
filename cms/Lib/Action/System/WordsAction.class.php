<?php
/*
 * 字符类
 *
 * @  Writers    Jaty
 * @  BuildTime  2014/11/15 16:40
 * 
 */
class WordsAction extends Action{
	public function get_firstword(){
		import('@.ORG.words');
		$words_class = new words();
		$string = $words_class->stringgetFirstCharter($_GET['str']);
		if(empty($string)){
			$this->error('转换首字符失败！');
		}else{
			$this->success($string);
		}
	}
}
?>