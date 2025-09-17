<?php
namespace app\recruit\model\db;

use think\Model;

class NewRecruitCompanyUserForbidden extends Model {

    use \app\common\model\db\db_trait\CommonFunc;

    public function deleteForbidden($id){
    	return $this->delete($id);
    }
}