<?php

class IndexAction extends BaseAction
{
    public function index()
    {
		$this->display();
    }
	public function store_list(){
		//layerError('店铺为空');
		
		
		$count = 100;
		$data = [
			[
				'store_id' => '1',
				'store_name' => '213',
				'store_phone' => '213',
			],
			[
				'store_id' => '1',
				'store_name' => '213',
				'store_phone' => '213',
			],
			[
				'store_id' => '1',
				'store_name' => '213',
				'store_phone' => '213',
			],
		];
		
		layerOk($count,$data);
	}
}