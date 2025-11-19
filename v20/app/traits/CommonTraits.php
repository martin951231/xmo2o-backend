<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */

	namespace app\traits;

	trait CommonTraits
	{
		/**
		 * 处理各种日期转换为 int 类型
		 *
		 * @param        $dateStr
		 *                        支持如：
		 *                        1、 Excel读取的数字：  54355
		 *                        2、月/日/年  1/9/2019
		 *                        3、x年x月x日 2019年1月9日
		 *                        4、用 - 隔离  2019-1-9
		 * @param string $sfm  默认空即： 00:00:00 ，或者传： 23:59:59
		 *
		 * @return false|int
		 */
		public function traitConvertDateToInt($dateStr, $sfm='00:00:00')
		{
			if (is_numeric($dateStr)){
				$value = date("Y-m-d", ($dateStr - 25569) * 24 * 3600);
				$dateStr =strtotime($value.' '.$sfm);
			}else if (preg_match('/[\x7f-\xff]/',$dateStr)){
				$arr = date_parse_from_format('Y年m月d日', $dateStr);
				if ($sfm) {
					$dateStr = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
				}else {
					$dateStr = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
				}
			} else{
				$dateStr =strtotime($dateStr.' '.$sfm);
			}
			return $dateStr;
		}
	}