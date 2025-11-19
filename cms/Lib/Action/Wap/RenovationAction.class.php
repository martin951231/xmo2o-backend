<?php


class RenovationAction extends BaseAction{

    //todo 装修预约 用户须知
    public function user_know(){
        if(empty($this->user_session)){
            $this->error_tips('请先进行登录',U('Login/index'));
        }
        $template_id = I('template_id');
        if(empty($template_id)){
            $this->error_tips('请选择对应模板',U('Login/index'));
        }
        $Plugin_material_diy_template_db=M('Plugin_material_diy_template');
        $template=$Plugin_material_diy_template_db->where(['template_id'=>$template_id])->field('template_id,from_id,tips_title,tips_content,tips_status')->find();
        if(empty($template)){
            $this->error_tips('模板不存在',U('Login/index'));
        }
        $village_id = I('village_id');
        $village_id = trim($village_id);
        $pigcms_id = I('pigcms_id');
        $pigcms_id = trim($pigcms_id);
        $paramUrl='';
        if($village_id>0){
            $paramUrl='&village_id='.$village_id;
        }
        if($pigcms_id>0){
            $paramUrl .='&pigcms_id='.$pigcms_id;
        }
        $base_url = D('House_village')->base_url;
        $refuse_url=$this->config['site_url'].$base_url;
        $agree_url = $this->config['site_url'].$base_url."pages/village/materialDiy/materialDiy?template_id={$template['template_id']}&from_id={$template['from_id']}".$paramUrl;
        if(intval($template['tips_status']) == 0){
            header('Location: '.$agree_url);
            exit;
        }
        $this->assign('res', [
            'refuse_url'=>$refuse_url,
            'agree_url'=>$agree_url,
            'title'=>$template['tips_title'],
            'content'=>$template['tips_content']
        ]);
        $this->display();
    }
}