<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitResumeInvitation extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 面试邀请保存
     */
    public function recruitResumeInvitationList($id, $params){
    if($id >0){
            $where = ['id'=>$id];
            $this->where($where)->update($params);
            $result['id'] = $id;
        }else{
            $gid = $this->add($params);
            $result['id'] = $gid;
        }
        return $result;
    }
}