<?php
/**
 * 
 * Created by subline.
 * Author: hengtingmei
 * Date Time: 2020/5/18 11:17
 */

namespace app\common\model\db;
use think\Exception;
use think\Model;
class Recognition extends Model {

    use \app\common\model\db\db_trait\CommonFunc;
    /**
     * 获得一条数据
     * @param $account
     * @return array|bool|Model|null
     */
    public function getOne() {
        $result = $this->find();
        return $result;
    }

    /**
     * 新增一条记录，如果存在则更新
     * @param $thirdType 业务类型
     * @param $thirdId 业务ID
     * @author 张涛
     * @date 2020/07/03
     */
    public function addNewQrcodeRow($thirdType, $thirdId)
    {
        $recognition = [
            'third_type' => $thirdType,
            'third_id' => $thirdId,
            'status' => 1,
            'add_time' => time()
        ];
        $record = $this->where(['third_type' => $thirdType, 'third_id' => $thirdId])->find();
        if (empty($record)) {
            $rs = $qrcodeId = $this->insertGetId($recognition);
        } else {
            $record->status = 1;
            $rs = $record->save();
            $qrcodeId = $record->id;
        }
        if ($rs) {
            return $qrcodeId;
        } else {
            throw new Exception('二维码生成ID失败');
        }
    }
}