<?php
class LangModel extends Model
{
	public $from = [
		'0' => '商家后台',
		'1' => '配送员app',
		'2' => '商家app',
		'3' => '店员app',
		'4' => '用户端app',
	];

	public $service_lang_table = [//请全部小写
		'appintro', 
		'area', 
		'card_coupon_send_history', 
		'card_group', 
		'card_new', 
		'card_new_coupon', 
		'card_new_record', 
		'home_menu', 
		'merchant_category', 
		'merchant', 
		'merchant_store', 
		'merchant_store_shop', 
		'national_phone', 
		'new_merchant_menu', 
		//'pick_address', 自提点去掉多语言
		'shop_category', 
		'shop_goods', 
		'shop_goods_sort', 
		'shop_goods_spec', 
		'shop_goods_spec_value', 
		'shop_goods_subsidiary_group',
		'shop_subsidiary_piece',
		'slider',
		'system_coupon', 
		'user_level', 
		'shop_goods_properties', 
		'shop_combination', 
		'merchant_store_foodshop_data', 
		'foodshop_customer_form', 
		'foodshop_table_type', 
		'foodshop_table', 
		'merchant_store_foodshop', 
		'foodshop_customer_label',
        'meal_store_category',
        'foodshop_goods_package',
        'foodshop_goods_package_detail',
        'foodshop_goods_sort',
        'group',
	];

	//为每个表指定除主键外最重要的字段标识，（在进行数据多语言导入时，若该important_field为空，则删除这条数据）
	public $lang_table_important_field = [
		'merchant' => 'name',
		'merchant_store' => 'name',
		'shop_goods' => 'name',
		'shop_goods_properties' => 'name',
	];

	public $service_lang = array();

	public function _initialize(){
		$open_multilingual = M('Config')->where(['name'=>'open_multilingual'])->getField('value');
		if($open_multilingual == '1'){
			$this->service_lang = S('service_lang');
			if(empty($this->service_lang)){
				$this->service_lang = M("Lang_service")->select();
				S('service_lang',$this->service_lang);
			}
		}
	}
	
	public function get_alias_by_lang($lang = ''){
		if(empty($lang)){
			$lang = C('config.system_lang');
		}
		foreach($this->service_lang as $value){
			if($value['val'] == $lang){
				return $value['alias'];
			}
		}
		return $this->get_alias_by_lang(C('config.system_lang'));
	}

	//多语言表字段
	public $type_fields = array(
		'merchant' => array(
			'mer_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '商家名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'txt_info' 	=> array(
				'name' => '商家介绍',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'meal_alias_name' 	=> array(
				'name' => '堂扫别名',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'meal_book_alias_name' 	=> array(
				'name' => '堂扫订桌别名',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'meal_table_alias_name' 	=> array(
				'name' => '堂扫桌台别名',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			)
		),
		'merchant_store' => array(
			'store_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '店铺名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'txt_info' 	=> array(
				'name' => '文字描述',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'feature' 	=> array(
				'name' => '本店特色',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'trafficroute' 	=> array(
				'name' => '交通路线',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			)
		),
		'merchant_store_shop' => array(
			'store_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'store_notice' 	=> array(
				'name' => '店铺公告',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'pack_alias' 	=> array(
				'name' => '包装费别名',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'freight_alias' 	=> array(
				'name' => '运费别名',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			)
		),
		'shop_goods' => array(
			'goods_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '商品名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'describe' 	=> array(
				'name' => '商品描述',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'unit' 	=> array(
				'name' => '单位',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'des' 	=> array(
				'name' => '描述',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'flavor' 	=> array(
				'name' => '口味',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'material' 	=> array(
				'name' => '原料',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'special_food_materials' 	=> array(
				'name' => '特殊食材',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'cooking_methods' 	=> array(
				'name' => '做法',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			)
		),
        'slider' => array(
            'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '导航名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
            'mark_name' 	=> array(
                'name' => '角标名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
        'new_merchant_menu' => array(
        	'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '菜单名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
        ),
        'area' => array(
        	'area_id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'area_name' 	=> array(
                'name' => '地区名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
        ),
        'home_menu' => array(
            'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
        'appintro' => array(
            'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'title' 	=> array(
                'name' => '标题',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
            'content' 	=> array(
                'name' => '内容',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
        'shop_goods_subsidiary_group' => array(
            'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '分组名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
            't_name' 	=> array(
                'name' => '前端显示名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
        'shop_subsidiary_piece' => array(
            'id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '区块名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
        // 'pick_address' => array(
        //     'id' 	=> array(
        //         'name' => '数据ID',//列名
        //         'width' => 10,//当前列宽度  true=自适应  数字越大越宽
        //     ),
        //     'pick_addr' 	=> array(
        //         'name' => '详细地址',//列名
        //         'width' => 20,//当前列宽度  true=自适应  数字越大越宽
        //     )
        // ),
		'merchant_category' => array(
			'cat_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_name' 	=> array(
					'name' => '分类名称',//列名
					'width' =>20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'user_level' => array(
			'id' 	=> array(
					'name' => '数据ID',//列名
					'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'lname' 	=> array(
					'name' => '等级名称',//列名
					'width' =>20,//当前列宽度  true=自适应  数字越大越宽
			),
			'description' 	=> array(
					'name' => '等级描述',//列名
					'width' =>20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
        'system_coupon' => array(
            'coupon_id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '优惠券名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
            'des' 	=> array(
                'name' => '优惠券描述',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
            'des_detial' 	=> array(
                'name' => '优惠券描述详情',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            )
        ),
		'shop_goods_sort' => array(
				'sort_id' 	=> array(
						'name' => '数据ID',//列名
						'width' => 10,//当前列宽度  true=自适应  数字越大越宽
				),
				'sort_name' 	=> array(
						'name' => '分类名称',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
		),
		'card_group' => array(
				'id' 	=> array(
						'name' => '数据ID',//列名
						'width' => 10,//当前列宽度  true=自适应  数字越大越宽
				),
				'name' 	=> array(
						'name' => '分组名称',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'des' 	=> array(
						'name' => '分组描述',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
		),
		'card_new' => array(
				'card_id' 	=> array(
						'name' => '数据ID',//列名
						'width' => 10,//当前列宽度  true=自适应  数字越大越宽
				),
				'cardname' 	=> array(
						'name' => '会员卡名称',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'info' 	=> array(
						'name' => '会员卡详情说明',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'recharge_des' 	=> array(
						'name' => '返现说明',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'score_des' 	=> array(
						'name' => '积分描述',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'cardsign' 	=> array(
						'name' => '及时的签名',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'recharge_rule' 	=> array(
						'name' => '充值规则',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
		),
		'config' => array(
				'name' 	=> array(
						'name' => '配置项',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
				'value' 	=> array(
						'name' => '配置值',//列名
						'width' => 20,//当前列宽度  true=自适应  数字越大越宽
				),
		),
		'national_phone' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'national' 	=> array(
				'name' => '国家名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'shop_goods_properties' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '属性名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'val' 	=> array(
				'name' => '属性值',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'shop_goods_spec' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '规格名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'shop_goods_spec_value' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '规格值名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'shop_category' => array(
			'cat_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_name' 	=> array(
				'name' => '分类名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'shop_combination' => array(
			'com_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '套餐名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'merchant_store_foodshop_data' => array(
			'store_id' 	=> array(
				'name' => '店铺id',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'preferential_name' 	=> array(
				'name' => '今日特惠名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_hot_name' 	=> array(
				'name' => '热销名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_hot_desc' 	=> array(
				'name' => '热销描述',//列名
				'width' => 40,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_discount_name' 	=> array(
				'name' => '优惠名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_discount_desc' 	=> array(
				'name' => '优惠描述',//列名
				'width' => 40,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_customer_form' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '自定义名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_table_type' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '自定义名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_table' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '自定义名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'merchant_store_foodshop' => array(
			'store_id' 	=> array(
				'name' => '店铺ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'store_notice' 	=> array(
				'name' => '店铺公告',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'dining_notice' 	=> array(
				'name' => '柜台取餐方式提示语',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_customer_label' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '标签名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
        'meal_store_category' => array(
			'cat_id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'cat_name' 	=> array(
				'name' => '分类名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_goods_package' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'name' 	=> array(
				'name' => '套餐名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
			'note' 	=> array(
				'name' => '套餐详情',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
		'foodshop_goods_package_detail' => array(
			'id' 	=> array(
				'name' => '数据ID',//列名
				'width' => 10,//当前列宽度  true=自适应  数字越大越宽
			),
			'package_name' 	=> array(
				'name' => '分组名称',//列名
				'width' => 20,//当前列宽度  true=自适应  数字越大越宽
			),
		),
        'foodshop_goods_sort' => array(
            'sort_id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'sort_name' 	=> array(
                'name' => '分类名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
        ),
        'group' => array(
            'group_id' 	=> array(
                'name' => '数据ID',//列名
                'width' => 10,//当前列宽度  true=自适应  数字越大越宽
            ),
            'name' 	=> array(
                'name' => '商品名称',//列名
                'width' => 20,//当前列宽度  true=自适应  数字越大越宽
            ),
        ),
	);

	// public function _initialize(){
	// 	foreach ($this->type_fields as $key => $value) {
	// 		foreach ($value as $k => $val) {
	// 			$this->type_fields[$key][$k]['name'] =  L_($val['name']);//多语言初始化
	// 		}
	// 	}
	// }

	/**
	 * 为业务表增加多语言数据
	 * $table 业务表名
	 * $data  业务数据
	 */
	public function add_lang_data($table, $data, $def = false){
		$open_multilingual = C('config.open_multilingual');//是否开启多语言
		
		if($open_multilingual == '1'){
			$default_language = C('config.default_language');//默认语言
			$config_lang = C('config.system_lang');//当前语言
			import("@.ORG.ZhConvert");
			if($config_lang == $default_language && !$def){
				//多种语言全部添加
				foreach ($this->service_lang as $l) {
					$table_model = M($table.'_'.$l['val']);
					$db_fields = $table_model->getDbFields();
					if(empty($db_fields)) continue;
					$temp = array();
					foreach ($db_fields as $f) {
						$_key = str_replace('_'.$l['val'], '', $f);
						if(isset($data[$_key])){
							if($l['val'] == 'traditional'){
								$temp[$f] = ZhConvert::zh($data[$_key]);
							}
							else{
								$temp[$f] = $data[$_key];
							}
						}
					}
					$table_model->data($temp)->add();
				}
			}
			else{
				$config_lang = $config_lang ? : $default_language;
				$table_model = M($table.'_'.$config_lang);
				$db_fields = $table_model->getDbFields();
				if(empty($db_fields)) return ;
				$temp = array();
				foreach ($db_fields as $f) {
					$_key = str_replace('_'.$config_lang, '', $f);
					if(isset($data[$_key])){
						if($config_lang == 'traditional'){
							$temp[$f] = ZhConvert::zh($data[$_key]);
						}
						else{
							$temp[$f] = $data[$_key];
						}
					}
				}
				$table_model->data($temp)->add();
			}
		}
		return ;
	}

	/**
	 * 修改数据
	 */
	public function edit_lang_data($table, $data){
		$open_multilingual = C('config.open_multilingual');//是否开启多语言
		$default_language = C('config.default_language');//默认语言
		$config_lang = C('config.system_lang');//当前语言
		if($open_multilingual == '1'){		
				//只修改当前语言的表
			$config_lang = $config_lang ? : $default_language;
			$table_model = M($table.'_'.$config_lang);
			$db_fields = $table_model->getDbFields();
			$pk = $db_fields[0];
			if(empty($db_fields)) return ;
			$temp = array();
			$where = '0';
			foreach ($db_fields as $f) {
				if($f != $pk){
					$_key = str_replace('_'.$config_lang, '', $f);
					if(isset($data[$_key])){
						$temp[$f] = $data[$_key];
					}
				}
				else{
					$where = $pk . '='.$data[str_replace('_'.$config_lang, '', $f)];//构建where条件
				}
			}
			if(!$table_model->where($where)->find()){
				//如果没有，就重新加
				$this->add_lang_data($table, $data, true);
			}
			else{
				$table_model->data($temp)->where($where)->save();
			}
		}
		return ;
	}

	public function lang_list(){

        $default = '';
        $return = array(
            'lang_list' => array(),
            'now_lang' => M('Config')->where(['name' => 'default_language'])->getField('value')
        );
		if(empty($return['now_lang'])){
			$return['now_lang'] = 'chinese';
		}
        $client_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        if($client_lang){
        	$client_lang_arr = explode(',', $client_lang);
        	$first = $client_lang_arr[0] ? strtolower($client_lang_arr[0]) : strtolower($default);			
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])){
        	preg_match_all('/[lL]anguage\/([a-zA-Z_]+){1}/', $_SERVER['HTTP_USER_AGENT'], $result);
        	$first = $result[1] ? strtolower($result[1][0]) : strtolower($default);
        }
        if($this->service_lang){
            foreach ($this->service_lang as $key => $value) {
                $value['as'] = strtolower($value['as']);
                if($first && strpos($value['as'],$first) !== false){
                	$return['now_lang'] = $value['val'];
                }
                $return['lang_list'][] = $value;
            }
        }
		if(C('config.system_lang')){
			$return['now_lang'] = C('config.system_lang');
		}
        return $return;
	}

	//处理数据
	public function deal_data($data){
		if(empty($data)) return $data;
		$new_merchant_menu_db = M('new_merchant_menu');
		$all_merchant_menu = $new_merchant_menu_db->select();
		foreach ($data as $key => $value) {
			$module = [];
			if(isset($value['from'])){
				$module[] = $this->from[$value['from']];
				if($value['from'] == '0'){//商家后台
					if($value['module_name']){
						$find = $this->find_value($all_merchant_menu, array('fid' => 0,'module' => $value['module_name']), 'name');
						if($find) $module[] = $find;
						if($value['action_name']){
							$find = $this->find_value($all_merchant_menu, array('module' => $value['module_name'],'action' => $value['action_name']), 'name');
							if($find) $module[] = $find;
						}
					}					
				}
			}
			$data[$key]['module'] = implode('->', $module);
		}
		return $data;
	}

	protected function find_value($all_merchant_menu, $where, $field){
		if(empty($all_merchant_menu)) return '';
		if(empty($where) || empty($field)) return '';

		foreach ($all_merchant_menu as $key => $value) {
			$_find = array();
			$loop = 0;
			foreach ($where as $k => $v) {
				if($value[$k] != $v){
					break;
				}
				$loop++;
			}
			if($loop == count($where)) return $value[$field];
		}
		return "";//没有找到	
	}
}
?>