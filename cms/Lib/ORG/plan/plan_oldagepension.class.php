<?php
/**
 * 养老金同步给公证处
 */
class plan_oldagepension extends plan_base{
	public function runTask(){
		$time = time() - 30*86400;//一个月内的
		$where = [
			'time' => ['gt', $time],
			'status' => 0
		];
		$data = M("Old_age_pension_records")->field("uid, sum(money) as money")->where($where)->group("uid")->select();
		import('@.ORG.OldAgePension');

		foreach ($data as $key => $value) {
			$oldAgePension = new OldAgePension();
			try{
	            $oldAgePension->in10($value['uid'], $value['money'], []);
	            M("Old_age_pension_records")->where(['uid'=>$value['uid'],'time' => ['gt', $time],'status' => 0])->data(['status'=>1])->save();
	        }catch(Exception $e){
	            fdump_sql(['error'=>$e->getMessage(), 'money'=>$value['money'], 'uid'=>$value['uid']], "old_age_pension");
	        }
		}       

		return true;
	}
}