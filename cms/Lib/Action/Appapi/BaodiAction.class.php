<?php
/**
 * 
 * 宝地小镇定制首页
 */
class BaodiAction extends BaseAction{
    
	public function index(){
		$output = [
			'block1' => [],
			'block2' => [],
			'block3' => [],
			'block4' => [],
		];

		$db = M("Baodi_index");
		$data = $db->where(['is_del'=>0, 'pic'=>['neq', '']])->field('id, block, title, pic, link, pid, bgcolor')->order('block desc, pid asc, sort desc, id asc')->select();
		foreach ($data as $key => $value) {
			$temp = [
				'pic' => replace_file_domain($value['pic']),
				'name' => $value['title'],
				'link' => htmlspecialchars_decode($value['link']),
				'bgcolor' => $value['bgcolor']
			];
			if($value['block'] == '1'){
				unset($temp['bgcolor']);
				if(count($output['block1']) < 5){
					$output['block1'][] = $temp;
				}
			}
			elseif($value['block'] == '2'){
				if($value['pid'] > 0 && isset($output['block2'][$value['pid']])){
					if(count($output['block2'][$value['pid']]['sons']) < 4){
						$output['block2'][$value['pid']]['sons'][] = $temp;
					}
				}
				elseif($value['pid'] == 0){
					$temp['sons'] = [];
					if(count($output['block2']) < 3){
						$output['block2'][$value['id']] = $temp;
					}
				}
			}
			elseif($value['block'] == '3'){
				if(count($output['block3']) < 8){
					$output['block3'][] = $temp;	
				}
			}
			elseif($value['block'] == '4'){
				if(count($output['block4']) < 5){
					$output['block4'][] = $temp;
				}
			}
		}
		$output['block2'] = array_values($output['block2']);
		
		//分享
		$output['share'] = [
			'title' => '辽宁阜新 宝地温泉小镇',
			'desc' => '邀您游玩',
			'img'	=> $this->config['site_url'] . '/static/images/baodiHomeShare.png',
			'url'	=> $this->config['site_url'] . '/wap.php'
		];
		
		$this->returnCode(0, $output);
	}
}