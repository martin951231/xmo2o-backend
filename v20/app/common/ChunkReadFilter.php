<?php
	namespace app\common;
	use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      代码功能描述
	 */



	class ChunkReadFilter implements IReadFilter
	{
		private $startRow = 0;

		private $endRow = 0;

		/**
		 * Set the list of rows that we want to read.
		 *
		 * @param mixed $startRow
		 * @param mixed $chunkSize
		 */
		public function setRows($startRow, $chunkSize)
		{
			$this->startRow = $startRow;
			$this->endRow = $startRow + $chunkSize;
		}

		public function readCell ($column, $row, $worksheetName = '')
		{
			if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
				return true;
			}

			return false;
		}
	}