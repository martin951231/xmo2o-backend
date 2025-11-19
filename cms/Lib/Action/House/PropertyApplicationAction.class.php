<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * Date: 2020/8/22
 * Time: 13:19
 */
class PropertyApplicationAction extends BaseAction
{
    use customization;

    public function index(){

        // 营山域名限制
        $domain_restrict_huizhisq = $this->hasCloudIntercom();
        // 杂淘域名限制
        $domain_restrict_zatao = $this->hasThirdZatao();


        $condition_application = array();
        // 默认所属物业应用和小区
        $condition_application['from'] = ['in', [0,1]];

        //插件列表
//        $application_list = S('application_list_0_1');
        if(empty($application_list)){
            $application_cat = M('Application_cat')->where(['cat_from'=>['in', [0,1]]])->order('`cat_sort` DESC,`cat_id` ASC')->select();
            foreach($application_cat as $value){
                $application_list[$value['cat_id']] = $value;
                $application_list[$value['cat_id']]['application_list'] = [];
            }
            //2020/8/26 start 过滤没有购买的功能应用
            $property_id = session('house.property_id');
            $packageDatas = D('PrivilegePackage')->getPackageContent($property_id);

            // 公安人脸 营山定制是需要一直显示的
            if($domain_restrict_huizhisq){
                $packageDatas['application_id'][] = 47;
            }else{
                $packageDatas['application_id'] = array_diff($packageDatas['application_id'],[47]);
            }

            $package_application_id = $packageDatas['application_id'];
            if(!$package_application_id || count($package_application_id)<=0){
                $package_application_id = [0];
            }
            //2020/8/26 end

            $database_house_menu = D('House_menu_new');
            $condition_house_menu['status'] = 1;
            $condition_house_menu['show'] = 1;
            $condition_house_menu['type'] = array('neq',2);
            $condition_house_menu['application_id'] = array('neq',0);
            //2020/8/26 start 过滤没有购买的功能应用
            $condition_house_menu['application_id'] = ['in',$package_application_id];
            //2020/8/26 end
            $house_menu = $database_house_menu->field(true)->where($condition_house_menu)->getField('application_id,id,application_id,name');

            $application = M('Application_list')->where($condition_application)->order('`application_sort` DESC,`application_id` ASC')->select();

            foreach($application as $value){
//                if (!$value['from'] && $house_menu && !$house_menu[$value['application_id']]) {
//                    continue;
//                }
                if ($value['application_id']==10 || $value['application_id']==11) {
                    continue;
                }
                if(!empty($value)){
                    $value['application_ico_arr'] = array_flip(explode(' ',$value['application_ico']));
                }

                // 公安人脸 营山定制 暂不放开
                if ($value['application_id']==47&&!$domain_restrict_huizhisq){
                    continue;
                }
                // 杂淘 杂淘定制 暂不放开
                if ($value['application_id']==46&&!$domain_restrict_zatao){
                    continue;
                }
                $application_list[$value['cat_id']]['application_list'][] = $value;
            }
            $application_list = array_values($application_list);
            S('application_list_0_1', $application_list);
        }
        $this->assign('application_list',$application_list);

        $this->display();
    }

    // 应用详情页面共用的一部分
    public function page_public() {
        $application_id = I('application_id');
        $condition_application = array(
            'application_id' => $application_id
        );
        $is_add = false;
        if (37==$application_id) {
            $is_add = true;
        }
        $application = M('Application_list')->where($condition_application)->find();
        if ($application['application_detail']) {
            $application['application_detail'] = replace_file_domain_content($application['application_detail']);
        }
        if ($application['application_img']) {
            $application['application_img'] = replace_file_domain($application['application_img']);
        } else {
            if (!$application['application_label']) {
                $application['application_label'] = 'default';
            }
            $application['application_img'] = $this->config['site']."/static/application/img/{$application['application_label']}_detail.png?t=001";
        }
        if ($application['application_property']) {
            $url = $this->config['site_url'] . $application['application_property'] .'&iframe='.$_GET['iframe'];
            $this->assign('url',$url);
        }
        $this->assign('is_add',$is_add);

        $property_id = $this->property_id;
        $packageData = D('PrivilegePackage')->getPackageContent($property_id);
        if (in_array($application_id, $packageData['application_id'])) {
            $application['is_has'] = true;
        } else {
            $application['is_has'] = false;
        }

        $this->assign('application_detail',$application);
    }

    public function detail() {
        $this->page_public();
        if (!$_GET['iframe']) {
            $page_magin = 160;
        } else {
            $page_magin = 0;
        }
        $this->assign('page_magin',$page_magin);
        $this->display();
    }

    public function add_my_applicat() {
        $application_id = I('application_id');
        $condition_application = array(
            'application_id' => $application_id
        );
        $application = M('Application_list')->where($condition_application)->find();
        if (empty($application)) {
            $this->error('添加的应用不存在！');
        }
        $property_id = $this->property['id'];
        $where = array(
            'application_id' => $application_id,
            'use_id' => $property_id,
            'from' => 1,
            'status' => 0,
        );
        $bind = M('Application_bind')->where($where)->find();
        if (!empty($bind)) {
            $url = '/property/property.iframe/property_enterprise_weixin';
            $msg = array(
                'message' => '添加成功！',
                'status' => 1,
                'url' => $url
            );
            $this->ajaxReturn($msg);
        }
        $data = array(
            'application_id' => $application_id,
            'from' => 1,
            'use_id' => $property_id,
            'add_time' => time()
        );
        $bind_id = M('Application_bind')->data($data)->add();
        if ($bind_id) {
            $url = 'property/property.iframe/property_enterprise_weixin';
            $msg = array(
                'message' => '已将该功能成功添加至我的应用，可前去体验！',
                'status' => 1,
                'url' => $url
            );
            $this->ajaxReturn($msg);
        } else {
            $this->error('添加失败！');
        }
    }

}