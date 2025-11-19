<?php
$base_url = D('House_village')->base_url;
return [
    array(
        'hover_pic_path' => C('config.site_url')."/static/images/house/index2.png",
        'name' => '首页',
        'pic_path' => C('config.site_url')."/static/images/house/index.png",
        'url' => C('config.site_url') . $base_url . "pages/village_menu/index",
		'page_path' => '/pages/village_menu/index',
		'page_url' => '/pages/village_menu/index',
		'is_tab' => true,
        'original_page' => 'village_menu_index'
    ),
    array(
        'hover_pic_path' => C('config.site_url')."/static/images/house/convenient2.png",
        'name' => '便民',
        'pic_path' => C('config.site_url')."/static/images/house/convenient.png",
        'url' => C('config.site_url').$base_url."pages/village_menu/convenient",
		'page_path' => '/pages/village_menu/convenient',
		'page_url' => '/pages/village_menu/convenient',
		'is_tab' => true,
        'original_page' => 'village_menu_convenient'
    ),
    array(//社区团购
        'hover_pic_path' => C('config.site_url')."/static/images/house/village_group2.png",
        'name' => C('config.village_group_alias'),
        'pic_path' => C('config.site_url')."/static/images/house/village_group.png",
        'url' => C('config.site_url')."/wap.php?g=Wap&c=Village_group&a=index",
		'is_tab' => false,
        'original_page' => ''
    ),
    array(
        'hover_pic_path' => C('config.site_url')."/static/images/house/lt2.png",
        'name' => '邻里',
        'pic_path' => C('config.site_url')."/static/images/house/lt.png",
        'url' => C('config.site_url').$base_url."pages/village_menu/resident",
		'page_path' => '/pages/village_menu/resident',
		'page_url' => '/pages/village_menu/resident',
		'is_tab' => true,
        'original_page' => 'village_menu_resident'
    ),
    array(
        'hover_pic_path' => C('config.site_url')."/static/images/house/my2.png",
        'name' => '我的',
        'pic_path' => C('config.site_url')."/static/images/house/my.png",
        'url' => C('config.site_url').$base_url."pages/village_menu/my",
		'page_path' => '/pages/village_menu/my',
		'page_url' => '/pages/village_menu/my',
		'is_tab' => true,
        'original_page' => 'village_menu_my'
    ),
];