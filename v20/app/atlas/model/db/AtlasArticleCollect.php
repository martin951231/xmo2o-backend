<?php
/**
 * 图文管理文章收藏model
 * Author: wangchen
 * Date Time: 2021/5/29
 */

namespace app\atlas\model\db;
use think\Model;
class AtlasArticleCollect extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    // 获取一条信息
    public function getOptionOne($where){
    	$result = $this->where($where)->find();
    	return $result;
    }

    // 修改
    public function collectArticleEdit($where, $data){
    	$result = $this->where($where)->update($data);
    	return $result;
    }

    // 添加
    public function collectArticleAdd($data){
        $result = $this->insertGetId($data);
        return $result;
    }

    
}