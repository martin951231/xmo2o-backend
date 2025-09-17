<?php

namespace app\common\model\service;

/**
 * Class CustomService
 */
class CustomService
{
    /**
     * 用户提交时验证：自定义表单验证用户提交的格式信息
     */
    public function checkUserCommit($custom_form)
    {
        if(empty($custom_form) || !is_array($custom_form)){
            throw new \think\Exception('格式有误');
        }
        foreach($custom_form as $k => $item){
            if(empty($item['value']) && $item['is_must'] == 1){
                throw new \think\Exception($item['title'] . '参数不能为空');
            }
            //图片
            if($item['type'] == 'image'){
                if(!is_array($item['value'])){
                    throw new \think\Exception('value格式有误！');
                }
                if(count($item['value']) > $item['image_max_num']){
                    throw new \think\Exception($item['title'] . '最多上传' . $item['image_max_num'] . '张图片');
                }
    
                $item['value'] = array_filter($item['value']);
            }
            //身份证
            if($item['type'] == 'idcard'){
                if($item['is_must'] == 1 && !is_idcard($item['value'])){
                    throw new \think\Exception('身份证号码填写不正确!');
                }
            }
            //选择
            if($item['type'] == 'select'){
                if(!empty($item['value']) && is_array($item['value']) && isset($item['value'][0]['value'])){
                    $item['value'] = $item['value'][0]['value'];
                }
            }
            //手机号
            if($item['type'] == 'phone'){
                if($item['is_must'] == 1 && !preg_match('/^1\d{10}$/ims' ,$item['value'])){
                    throw new \think\Exception('手机号填写不正确!');
                }
            }
            //邮箱
            if($item['type'] == 'email'){
                if($item['is_must'] == 1 && !preg_match('/^[a-zA-Z0-9]+([-_.][a-zA-Z0-9]+)*@([a-zA-Z0-9]+[-.])+([a-z]{2,5})$/ims' ,$item['value'])){
                    throw new \think\Exception('邮箱填写不正确!');
                }
            }
            $custom_form[$k] = $item;
        } 
        return $custom_form;
    }


    /**
     * 格式化自定义表单信息，用于用户端显示
     */
    public function formatOfUser($custom_form)
    {
        if(!empty($custom_form) && is_array($custom_form) && count($custom_form)){
            foreach($custom_form as $key => $val){
                if($val['type'] == 'select'){
                    $content = explode(',', $val['content']);
                    $custom_form[$key]['content'] = [];
                    foreach($content as $k => $v){
                        $custom_form[$key]['content'][] = [
                            'label'=> $v,
                            'value'=> $k
                        ];
                    }
                }
                if(empty($val['status'])){
                   unset($custom_form[$key]); 
                }
            }
        }
        return $custom_form;
    }


}
