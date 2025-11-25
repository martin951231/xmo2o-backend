<?php
class plan_scrcu_record extends plan_base{
	public function runTask(){
		$scrcu = A('Index/LowFeePayNotice');
		$scrcu->scrcu_record();
        $this->keepThread();
		invoke_v20_service('\app\pay\controller\NotifyController/scrcu');
		return true;
	}

}