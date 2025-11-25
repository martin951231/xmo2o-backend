<?php


namespace app\common\controller\api;


use app\complaint\model\service\ComplaintService;

class UpdateComplaintController
{
    public function updateComplaint(){
        $res = (new ComplaintService())->updateData();
        dd($res);
    }
}