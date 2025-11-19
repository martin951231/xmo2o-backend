<?php
include(WEB_PATH . 'cms/Common/common.php');

if (!function_exists('get_shop_url')) {

    /**
     * 获取快店、商城店铺详情页链接
     * @param $id
     * @param $type
     * @return string
     */
    function get_shop_url($id, $type)
    {
        if ($type == 'mall') {
            return C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=store&store_id=' . $id;
        } else if ($type == 'mall3') {
            return C('config.site_url') . PD('House_village')->base_url . 'pages/shopmall_third/store_home/index?store_id=' . $id;
        } else if($type == 'group'){
            return C('config.site_url') . '/packapp/platn/pages/store/v1/home/index?store_id='.$id;
        }else {
            return C('config.site_url') . '/wap.php?g=Wap&c=Shop&a=index#shop-' . $id;
        }
    }
}


if (!function_exists('get_order_detail_url')) {

    /**
     * 获取快店、商城订单详情页
     * @param $orderid
     * @param $type
     * @return string
     */
    function get_order_detail_url($orderid, $type)
    {
        if ($type == 'mall') {
            return C('config.site_url') . '/wap.php?g=Wap&c=Mall&a=status&from=mall_order_list&order_id=' . $orderid;
        } else {
            return C('config.site_url') . '/wap.php?g=Wap&c=Shop&a=status&order_id=' . $orderid;
        }
    }
}


if (!function_exists('get_order_list_url')) {

    /**
     * 获取快店、商城订单列表页
     * @param $type
     * @return string
     */
    function get_order_list_url($type)
    {
        if ($type == 'mall') {
            return C('config.site_url') . PD('House_village')->base_url . 'pages/my/my_order';
        } else {
            return C('config.site_url') . PD('House_village')->base_url . 'pages/my/my_order';
        }
    }
}

function getFirstCharter($str)
{
    loadSystemOrg('words');
    $string = (new words())->wordgetFirstCharter($str);
    return strtoupper(substr($string, 0, 1));
}