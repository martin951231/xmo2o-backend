<?php
	namespace app\common\excel;
	use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
	use PhpOffice\PhpSpreadsheet\Cell\DataType;

	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	class CustomValueBinder extends AdvancedValueBinder
	{
		public static function dataTypeForValue ($value)
		{
			//只重写dataTypeForValue方法，去掉一些不必要的判断
			if (is_null($value)) {
				return DataType::TYPE_NULL;
			} elseif ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
				return DataType::TYPE_INLINE;
			} elseif (is_string($value) && $value[0] === '=' && strlen($value) > 1) {
				return DataType::TYPE_FORMULA;
			} elseif (is_bool($value)) {
				return DataType::TYPE_BOOL;
			} elseif (is_float($value) || is_int($value)) {
				return DataType::TYPE_NUMERIC;
			}
			return DataType::TYPE_STRING;
		}

	}