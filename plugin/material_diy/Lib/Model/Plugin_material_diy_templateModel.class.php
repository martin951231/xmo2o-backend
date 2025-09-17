<?php

class Plugin_material_diy_templateModel extends Model
{
    // 按钮点击选择
    public $add_material_btn = [
        '标题文本',
        '单行文本',
        '多行文本',
        '单元选择框',
        '业主姓名选择框',
        '业主联系方式框',
        '业主身份选择',
        '时间选择框',
        '办公时间选择框',
        '房间户型选择框'
    ];
    // 对应区块信息-可以添加不可以删除
    public $template_type_arr = array(
        'title_txt' => '标题文本',
        'txt' => '单行文本',
        'mul_txt' => '多行文本',
        'floor_choose' => '单元选择框',
        'owner_choose' => '业主姓名选择框',
        'owner_phone' => '业主联系方式框',
        'owner_identity' => '业主身份选择',
        'date_choose' => '时间选择框',
        'time_choose' => '办公时间选择框',
        'room_choose' => '房间户型选择框',
    );

    // 对应的移动区块信息
    public $template_tip = array(
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#4f81bd', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'title_txt', // 区分类型
            'type_text'=> '标题文本', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'txt', // 区分类型
            'type_text'=> '单行文本', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'mul_txt', // 区分类型
            'type_text'=> '多行文本', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'floor_choose', // 区分类型
            'type_text'=> '单元选择框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'owner_choose', // 区分类型
            'type_text'=> '业主姓名选择框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'owner_phone', // 区分类型
            'type_text'=> '业主联系方式框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态
            'type'=> 'owner_identity', // 区分类型
            'type_text'=> '业主身份选择', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态 1 选中状态
            'type'=> 'date_choose', // 区分类型
            'type_text'=> '日期选择框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮 1 显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态 1 选中状态
            'type'=> 'time_choose', // 区分类型
            'type_text'=> '办公时间选择框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮 1 显示关闭按钮
        ),
        array(
            'width'=> 120, // 默认宽度
            'height'=> 30, // 默认高度
            'top'=> 20,  // 所处top位置
            'left'=> 340, // 所处left位置
            'draggable'=> 1, // 是否可以拖拽 1 可以
            'resizable'=> 1, // 是否可以缩放 1 可以
            'minw'=> 60, // 可缩放下最小宽度
            'minh'=> 20, // 可缩放下最小高度
            'parentLim'=> 1, // 是否限制在父级区域中移动 1 默认限制
            'zIndex'=> 1, // 浮动层级
            'backgroundColor'=> '#b8cce4', // 背景颜色
            'color'=> '#ffffff', // 字体颜色
            'active'=> 0, // 是否处于被缩放选中状态 1 选中状态
            'type'=> 'room_choose', // 区分类型
            'type_text'=> '房间户型选择框', // 对应区块名称
            'is_close'=> 0 // 是否显示关闭按钮  与 active 处于同样状态   前端选中时候自动显示关闭按钮 1 显示关闭按钮
        )
    );

    public $owner_identity_type = [
        '业主',
        '家属',
        '租客'
    ];

    // 对应文件类型
    // 图片，音频，  视频 ， excel文件, txt文本, ppt, word, pdf, 安卓安装包
    public $all_type = array(
        'image/png', 'image/x-png', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/x-icon',
        'audio/mp3',
        'application/octet-stream', 'video/mp4','video/x-ms-wmv',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/pdf',
        'application/vnd.android.package-archive',
        'application/x-tar', 'application/x-rar', 'application/x-zip-compressed','application/zip',
        'image/bmp'
    );
    // 图片，excel文件, ppt, word, pdf
    public $now_all_type = array(
        'image/png', 'image/x-png', 'image/jpg', 'image/jpeg', 'image/pjpeg', 'image/gif', 'image/x-icon',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.ms-powerpoint',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/pdf'
    );
    // 对应文件后准
    public $all_exts = array(
        'jpg', 'jpeg', 'png', 'gif',
        'mp3', 'ico', 'mp4', 'wmv',
        'xls', 'xlsx', 'txt', 'ppt', 'jnt', 'doc', 'docx', 'rtf', 'pdf', 'chm', 'pptx',
        'apk', 'ipa',
        '7z', 'tar', 'wim', 'zip', 'rar',
        'bmp'
    );
    public $now_all_exts = array(
        'jpg', 'jpeg', 'png', 'gif','ico',
        'xls', 'xlsx', 'txt', 'jnt', 'doc', 'docx', 'rtf', 'pdf'
    );
    // 图片文件的后缀
    public $image_exts = array(
        'jpg', 'jpeg', 'png', 'gif'
    );
    // 图片文件的所有类型
    public $image_type = array(
        'image/png', 'image/x-png', 'image/jpg', 'image/jpeg',
        'image/pjpeg', 'image/gif', 'image/x-icon'
    );

    // 填写模板对应状态
    public $diy_tatus_txt = [
        0 => '待审核',
        1 => '已通过',
        3 => '已拒绝',
        4 => '已删除',
    ];
    
    public function get_room_types(){
        $room_types=array();
        $room_types[]=array('type_id'=>'10','type_name'=>'单间配套','choose_info'=>'单间配套','msg'=>'单间配套');
        $room_types[]=array('type_id'=>'101','type_name'=>'一室一厅','choose_info'=>'一室一厅','msg'=>'一室一厅');
        $room_types[]=array('type_id'=>'201','type_name'=>'两室一厅','choose_info'=>'两室一厅','msg'=>'两室一厅');
        $room_types[]=array('type_id'=>'301','type_name'=>'三室一厅','choose_info'=>'三室一厅','msg'=>'三室一厅');
        return $room_types;
    }
    //公租房工作流程功能绑定标识 办理入住、电子合同、办理退租
    public function get_dealwith_flow(){
        $flow_types=array();
        $flow_types[]=array('use_type_id'=>'1','type_name'=>'办理入住');
        $flow_types[]=array('use_type_id'=>'2','type_name'=>'电子合同');
        $flow_types[]=array('use_type_id'=>'3','type_name'=>'办理退租');
        return $flow_types;
    }
    // 默认模板（如果数据库没有区这里）
    public function get_default_template_info() {

        $str='a:27:{i:0;a:18:{s:5:"width";s:3:"793";s:6:"height";s:2:"63";s:3:"top";s:1:"2";s:4:"left";s:1:"4";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"28";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:4:"true";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:4:"true";s:7:"key_num";s:1:"0";s:5:"value";s:24:"小区装饰装修申请";}i:1;a:18:{s:5:"width";s:3:"114";s:6:"height";s:3:"193";s:3:"top";s:2:"69";s:4:"left";s:1:"4";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"15";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"1";s:5:"value";s:9:"申请人";}i:2;a:18:{s:5:"width";s:3:"123";s:6:"height";s:2:"65";s:3:"top";s:2:"68";s:4:"left";s:3:"121";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"224";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"2";s:5:"value";s:12:"业主姓名";}i:3;a:18:{s:5:"width";s:3:"123";s:6:"height";s:2:"62";s:3:"top";s:3:"135";s:4:"left";s:3:"121";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"252";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"3";s:5:"value";s:15:"业主手机号";}i:4;a:18:{s:5:"width";s:3:"123";s:6:"height";s:2:"63";s:3:"top";s:3:"200";s:4:"left";s:3:"121";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"293";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"4";s:5:"value";s:12:"业主身份";}i:5;a:18:{s:5:"width";s:3:"141";s:6:"height";s:2:"63";s:3:"top";s:2:"69";s:4:"left";s:3:"452";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"19";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"5";s:5:"value";s:15:"业主身份证";}i:6;a:18:{s:5:"width";s:3:"142";s:6:"height";s:2:"63";s:3:"top";s:3:"135";s:4:"left";s:3:"451";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"347";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"6";s:5:"value";s:12:"业主住址";}i:7;a:18:{s:5:"width";s:3:"123";s:6:"height";s:2:"63";s:3:"top";s:3:"266";s:4:"left";s:3:"121";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:1:"6";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"8";s:5:"value";s:12:"公司名称";}i:8;a:18:{s:5:"width";s:3:"123";s:6:"height";s:2:"63";s:3:"top";s:3:"332";s:4:"left";s:3:"121";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"381";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"9";s:5:"value";s:18:"负责人手机号";}i:9;a:18:{s:5:"width";s:3:"141";s:6:"height";s:2:"62";s:3:"top";s:3:"267";s:4:"left";s:3:"452";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:1:"7";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"10";s:5:"value";s:15:"公司负责人";}i:10;a:18:{s:5:"width";s:3:"141";s:6:"height";s:2:"61";s:3:"top";s:3:"333";s:4:"left";s:3:"452";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"424";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"11";s:5:"value";s:21:"负责人身份证号";}i:11;a:18:{s:5:"width";s:3:"113";s:6:"height";s:3:"129";s:3:"top";s:3:"266";s:4:"left";s:1:"4";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"11";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"12";s:5:"value";s:12:"装修公司";}i:12;a:18:{s:5:"width";s:3:"241";s:6:"height";s:2:"62";s:3:"top";s:3:"398";s:4:"left";s:1:"3";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"386";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"13";s:5:"value";s:12:"开工日期";}i:13;a:18:{s:5:"width";s:3:"140";s:6:"height";s:2:"62";s:3:"top";s:3:"398";s:4:"left";s:3:"452";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:1:"7";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"14";s:5:"value";s:12:"竣工日期";}i:14;a:18:{s:5:"width";s:3:"794";s:6:"height";s:2:"73";s:3:"top";s:3:"464";s:4:"left";s:1:"3";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"464";s:15:"backgroundColor";s:7:"#4f81bd";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:9:"title_txt";s:9:"type_text";s:12:"标题文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"15";s:5:"value";s:33:"装饰装修施工项目及内容";}i:15;a:17:{s:5:"width";s:3:"201";s:6:"height";s:2:"63";s:3:"top";s:2:"69";s:4:"left";s:3:"596";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"63";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"17";}i:16;a:17:{s:5:"width";s:3:"202";s:6:"height";s:2:"62";s:3:"top";s:3:"135";s:4:"left";s:3:"596";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"65";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"21";}i:17;a:17:{s:5:"width";s:3:"199";s:6:"height";s:2:"63";s:3:"top";s:3:"266";s:4:"left";s:3:"249";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"49";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"23";}i:18;a:17:{s:5:"width";s:3:"201";s:6:"height";s:2:"61";s:3:"top";s:3:"267";s:4:"left";s:3:"596";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:2:"57";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"24";}i:19;a:17:{s:5:"width";s:3:"199";s:6:"height";s:2:"60";s:3:"top";s:3:"399";s:4:"left";s:3:"248";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"393";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:11:"date_choose";s:9:"type_text";s:15:"日期选择框";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"25";}i:20;a:17:{s:5:"width";s:3:"200";s:6:"height";s:2:"62";s:3:"top";s:3:"398";s:4:"left";s:3:"597";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"412";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:11:"date_choose";s:9:"type_text";s:15:"日期选择框";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"26";}i:21;a:17:{s:5:"width";s:3:"200";s:6:"height";s:2:"61";s:3:"top";s:3:"332";s:4:"left";s:3:"597";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"452";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"27";}i:22;a:17:{s:5:"width";s:3:"198";s:6:"height";s:2:"62";s:3:"top";s:3:"333";s:4:"left";s:3:"249";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:1:"6";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:3:"txt";s:9:"type_text";s:12:"单行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"28";}i:23;a:17:{s:5:"width";s:3:"793";s:6:"height";s:3:"586";s:3:"top";s:3:"541";s:4:"left";s:1:"4";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"495";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:7:"mul_txt";s:9:"type_text";s:12:"多行文本";s:8:"is_close";s:5:"false";s:7:"key_num";s:2:"29";}i:24;a:17:{s:5:"width";s:3:"199";s:6:"height";s:2:"64";s:3:"top";s:2:"68";s:4:"left";s:3:"248";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"497";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:12:"owner_choose";s:9:"type_text";s:21:"业主姓名选择框";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"0";}i:25;a:17:{s:5:"width";s:3:"199";s:6:"height";s:2:"62";s:3:"top";s:3:"135";s:4:"left";s:3:"249";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"499";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:11:"owner_phone";s:9:"type_text";s:21:"业主联系方式框";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"0";}i:26;a:17:{s:5:"width";s:3:"199";s:6:"height";s:2:"61";s:3:"top";s:3:"201";s:4:"left";s:3:"248";s:9:"draggable";s:1:"1";s:9:"resizable";s:1:"1";s:4:"minw";s:2:"60";s:4:"minh";s:2:"20";s:9:"parentLim";s:1:"1";s:6:"zIndex";s:3:"501";s:15:"backgroundColor";s:7:"#b8cce4";s:5:"color";s:7:"#ffffff";s:6:"active";s:5:"false";s:4:"type";s:14:"owner_identity";s:9:"type_text";s:18:"业主身份选择";s:8:"is_close";s:5:"false";s:7:"key_num";s:1:"0";}}';
//        $str=array(
//            array(
//                'width' => '793',
//                'height' => '63',
//                'top' => '2',
//                'left' => '4',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '28',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'true',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'true',
//                'key_num' => '0',
//                'value' => '小区装饰装修申请',
//            ),
//            array(
//                'width' => '114',
//                'height' => '193',
//                'top' => '69',
//                'left' => '4',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '15',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '1',
//                'value' => '申请人',
//            ),
//            array(
//                'width' => '123',
//                'height' => '65',
//                'top' => '68',
//                'left' => '121',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '224',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '2',
//                'value' => '业主姓名',
//            ),
//            array(
//                'width' => '123',
//                'height' => '62',
//                'top' => '135',
//                'left' => '121',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '252',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '3',
//                'value' => '业主手机号',
//            ),
//            array(
//                'width' => '123',
//                'height' => '63',
//                'top' => '200',
//                'left' => '121',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '293',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '4',
//                'value' => '业主身份',
//            ),
//            array(
//                'width' => '141',
//                'height' => '63',
//                'top' => '69',
//                'left' => '452',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '19',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '5',
//                'value' => '业主身份证',
//            ),
//            array(
//                'width' => '142',
//                'height' => '63',
//                'top' => '135',
//                'left' => '451',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '347',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '6',
//                'value' => '业主住址',
//            ),
//            array(
//                'width' => '123',
//                'height' => '63',
//                'top' => '266',
//                'left' => '121',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '6',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '8',
//                'value' => '公司名称',
//            ),
//            array(
//                'width' => '123',
//                'height' => '63',
//                'top' => '332',
//                'left' => '121',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '381',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '9',
//                'value' => '负责人手机号',
//            ),
//            array(
//                'width' => '141',
//                'height' => '62',
//                'top' => '267',
//                'left' => '452',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '7',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '10',
//                'value' => '公司负责人',
//            ),
//            array(
//                'width' => '141',
//                'height' => '61',
//                'top' => '333',
//                'left' => '452',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '424',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '11',
//                'value' => '负责人身份证号',
//            ),
//            array(
//                'width' => '113',
//                'height' => '129',
//                'top' => '266',
//                'left' => '4',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '11',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '12',
//                'value' => '装修公司',
//            ),
//            array(
//                'width' => '241',
//                'height' => '62',
//                'top' => '398',
//                'left' => '3',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '386',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '13',
//                'value' => '开工日期',
//            ),
//            array(
//                'width' => '140',
//                'height' => '62',
//                'top' => '398',
//                'left' => '452',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '7',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '14',
//                'value' => '竣工日期',
//            ),
//            array(
//                'width' => '794',
//                'height' => '73',
//                'top' => '464',
//                'left' => '3',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '464',
//                'backgroundColor' => '#4f81bd',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'title_txt',
//                'type_text' => '标题文本',
//                'is_close' => 'false',
//                'key_num' => '15',
//                'value' => '装饰装修施工项目及内容',
//            ),
//            array(
//                'width' => '201',
//                'height' => '63',
//                'top' => '69',
//                'left' => '596',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '63',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '17',
//            ),
//            array(
//                'width' => '202',
//                'height' => '62',
//                'top' => '135',
//                'left' => '596',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '65',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '21',
//            ),
//            array(
//                'width' => '199',
//                'height' => '63',
//                'top' => '266',
//                'left' => '249',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '49',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '23',
//            ),
//            array(
//                'width' => '201',
//                'height' => '61',
//                'top' => '267',
//                'left' => '596',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '57',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '24',
//            ),
//            array(
//                'width' => '199',
//                'height' => '60',
//                'top' => '399',
//                'left' => '248',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '393',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'date_choose',
//                'type_text' => '日期选择框',
//                'is_close' => 'false',
//                'key_num' => '25',
//            ),
//            array(
//                'width' => '200',
//                'height' => '62',
//                'top' => '398',
//                'left' => '597',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '412',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'date_choose',
//                'type_text' => '日期选择框',
//                'is_close' => 'false',
//                'key_num' => '26',
//            ),
//
//            array(
//                'width' => '199',
//                'height' => '60',
//                'top' => '399',
//                'left' => '248',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '393',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'time_choose',
//                'type_text' => '时间选择框',
//                'is_close' => 'false',
//                'key_num' => '25',
//            ),
//            array(
//                'width' => '200',
//                'height' => '62',
//                'top' => '398',
//                'left' => '597',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '412',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'time_choose',
//                'type_text' => '时间选择框',
//                'is_close' => 'false',
//                'key_num' => '26',
//            ),
//
//            array(
//                'width' => '200',
//                'height' => '61',
//                'top' => '332',
//                'left' => '597',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '452',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '27',
//            ),
//            array(
//                'width' => '198',
//                'height' => '62',
//                'top' => '333',
//                'left' => '249',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '6',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'txt',
//                'type_text' => '单行文本',
//                'is_close' => 'false',
//                'key_num' => '28',
//            ),
//            array(
//                'width' => '793',
//                'height' => '586',
//                'top' => '541',
//                'left' => '4',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '495',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'mul_txt',
//                'type_text' => '多行文本',
//                'is_close' => 'false',
//                'key_num' => '29',
//            ),
//            array(
//                'width' => '199',
//                'height' => '64',
//                'top' => '68',
//                'left' => '248',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '497',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'owner_choose',
//                'type_text' => '业主姓名选择框',
//                'is_close' => 'false',
//                'key_num' => '0',
//            ),
//            array(
//                'width' => '199',
//                'height' => '62',
//                'top' => '135',
//                'left' => '249',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '499',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'owner_phone',
//                'type_text' => '业主联系方式框',
//                'is_close' => 'false',
//                'key_num' => '0',
//            ),
//            array(
//                'width' => '199',
//                'height' => '61',
//                'top' => '201',
//                'left' => '248',
//                'draggable' => '1',
//                'resizable' => '1',
//                'minw' => '60',
//                'minh' => '20',
//                'parentLim' => '1',
//                'zIndex' => '501',
//                'backgroundColor' => '#b8cce4',
//                'color' => '#ffffff',
//                'active' => 'false',
//                'type' => 'owner_identity',
//                'type_text' => '业主身份选择',
//                'is_close' => 'false',
//                'key_num' => '0',
//            )
//        );
        $default_template_info = [
            'template_title' => '装修申请单',
            'template_img' => '/static/material_diy/img/decoration_application.png',
            'template_width' => '800px',
            'template_content' => $str,
            'from' => 'default',
            'from_id' => '0',
            'sort' => '100',
            'template_status' => 0
        ];
        $template_id =  M('Plugin_material_diy_template')->where(['template_status' => 0, 'from' => 'default'])->getField('template_id');
        if (!$template_id) {
            $default_template_info['add_time'] = time();
            $template_id = M('Plugin_material_diy_template')->data($default_template_info)->add();
            if ($template_id) {
                $default_template_info['template_id'] = $template_id;
            }
        }
        return $default_template_info;
    }

    public function template_detail($template_id, $is_write=false) {
        $where_template = array(
            'template_id' => $template_id
        );
        $template_info = $this->where($where_template)->find();
        if (empty($template_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 解析下时间
        if ($template_info['add_time']>0) {
            $template_info['add_time_txt'] = date('Y-m-d H:i:s',$template_info['add_time']);
        }
        if ($template_info['last_time']>0) {
            $template_info['last_time_txt'] = date('Y-m-d H:i:s',$template_info['last_time']);
        }
        if ($template_info['template_content']) {
            $template_info['template_content'] = unserialize($template_info['template_content']);
            $zIndexMax = 1;
            $zIndexMaxArr = [];
            foreach ($template_info['template_content'] as &$item) {
                $item['width'] = intval($item['width']);
                $item['height'] = intval($item['height']);
                $item['top'] = intval($item['top']);
                $item['left'] = intval($item['left']);
                $item['minw'] = intval($item['minw']);
                $item['minh'] = intval($item['minh']);
                if ($item['zIndex']>1000) {
                    $item['zIndex'] = ceil($item['zIndex']/10);
                } else {
                    $item['zIndex'] = intval($item['zIndex']);
                }
                $zIndexMaxArr[] = $item['zIndex'];
//                $item['zIndex'] = intval($item['zIndex']);
            }
            $zIndexMax = max($zIndexMaxArr);
            if (!$zIndexMax) {
                $zIndexMax = 1;
            }
            $template_info['zIndexMax'] = $zIndexMax;
        }
        if ($template_info['template_img'] && strpos($template_info['template_img'],'/static/material_diy/img/') !== false) {
            $template_info['template_img_path'] = C('config.site_url') . $template_info['template_img'];
        } elseif ($template_info['template_img']) {
            $template_info['template_img_path'] = replace_file_domain($template_info['template_img']);
        }

        if ($is_write && $template_info['from'] == 'house' && $template_info['from_id']>0) {
            $template_info['template_type_arr'] = $this->template_type_arr;
        }

        return $template_info;
    }

    public function template_list($where=array(), $page=1, $page_size=20, $order='add_time DESC') {
        if (!$where['template_status']) {
            if ($where['_string']) {
                $where['_string'] .= ' AND template_status <> 4';
            } else {
                $where['template_status'] = array('neq', 4);
            }
        }
        if ($where['_string']) {
            $where['_string'] .= "  OR ( `from`='default' AND  template_status <> 4)";
        }
        
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $this->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'add_time DESC';
        }
        $list = $this->where($where)->limit($limit)->order($order)->select();
        if (empty($list)) {
            $list = array();
            if ($list[0]['from']!='default') {
                $template_info = $this->get_default_template_info();
                array_unshift($list, $template_info);
            }
        } else {
            $site_url = C('config.site_url');
//            $url = $site_url.'/wap.php?c=My&a=shop_order_before&template_id=';
            if ($list[0]['from']!='default') {
                $template_info = $this->get_default_template_info();
                array_unshift($list, $template_info);
            }
            // 关于来源处理
            $returnArr = build_redirect_info();
            $whereStr=" ((`from`='{$returnArr['from']}'  AND  from_id={$returnArr['from_id']}) OR `from`='default') AND template_status<>4 AND template_type >0";
            $bind_use_type_arr=array();
            $bind_use_type=$this->where($whereStr)->field('template_type')->select();
            if(!empty($bind_use_type)){
                foreach($bind_use_type as $utv){
                    if(!empty($utv['template_type']) && !in_array($utv['template_type'],$bind_use_type_arr)){
                        $bind_use_type_arr[]=$utv['template_type'];
                    }
                }
                $bind_use_type_arr=array_unique($bind_use_type_arr);
            }
            $base_url = D('House_village')->base_url;
            $dealwith_flow=$this->get_dealwith_flow();
            $dealwith_flow_arr=array();
            foreach ($dealwith_flow as $kk=>$fvv){
                $dealwith_flow_arr[$fvv['use_type_id']]=$fvv['type_name'];
                $dealwith_flow[$kk]['isbind']=false;
                if(in_array($fvv['use_type_id'],$bind_use_type_arr)){
                    $dealwith_flow[$kk]['isbind']=true;
                }
            }
            foreach ($list as &$val) {
                $val['use_type_id_str']='';
                if(isset($val['template_type']) && !empty($val['template_type']) && isset($dealwith_flow_arr[$val['template_type']])){
                    $val['use_type_id_str']=$dealwith_flow_arr[$val['template_type']];
                }
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if ($val['last_time']>0) {
                    $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                }
                if ($val['template_content']) {
                    $val['template_content'] = unserialize($val['template_content']);
                }
                if ($val['template_img']) {
                    $val['template_img_path'] = replace_file_domain($val['template_img']);
                }
                if ('default' == $val['from']) {
                    $val['qrcode_url'] = '';
                } else {
//                    $url = $site_url.$base_url."pages/village/materialDiy/materialList?template_id={$val['template_id']}&from={$val['from']}&from_id={$val['from_id']}";
                    $url= $site_url .str_replace('plugin.php', 'wap.php', U('Wap/Renovation/user_know', ['template_id'=>$val['template_id']]));
                    $val['qrcode_url'] = $site_url . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($url);
                }
            }
        }
        $unitRentalArr=array('id'=>'112041','module'=>'UnitRental','status'=>1,'show'=>1);
        $unitRental=M('House_menu_new')->where($unitRentalArr)->find();
        $is_unit_rental=0;
        if($unitRental){
            $is_unit_rental=1;
        }
        array_unshift($dealwith_flow,array('use_type_id'=>'0','type_name'=>'请选择','isbind'=>false));
        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        $data['dealwith_flow'] = $dealwith_flow;
        $data['bind_use_type_arr'] = $bind_use_type_arr;
        $data['is_unit_rental'] = $is_unit_rental;
        return $data;
    }


    public function template_write_detail($diy_id, $is_write=false, $is_api=false) {
        $where_diy = array(
            'id' => $diy_id
        );
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $diy_value_info = $d_plugin_material_diy_value->where($where_diy)->find();
        if (empty($diy_value_info)) {
            returnCode(31, [], '获取对象不存在！');
        }

        // 解析下时间
        if ($diy_value_info['add_time']>0) {
            $diy_value_info['add_time_txt'] = date('Y-m-d H:i:s',$diy_value_info['add_time']);
        }
        if ($diy_value_info['last_time']>0) {
            $diy_value_info['last_time_txt'] = date('Y-m-d H:i:s',$diy_value_info['last_time']);
        }
        if ($diy_value_info['write_time']>0) {
            $diy_value_info['write_time_txt'] = date('Y-m-d H:i:s',$diy_value_info['write_time']);
        }
        if ($diy_value_info['check_time']>0) {
            $diy_value_info['check_time_txt'] = date('Y-m-d H:i:s',$diy_value_info['check_time']);
        }
        if (isset($diy_value_info['diy_tatus'])) {
            $diy_value_info['diy_tatus_txt'] = $this->diy_tatus_txt[$diy_value_info['diy_tatus']];
        }
        if ($diy_value_info['diy_value']) {
            $diy_value_info['diy_value'] = unserialize($diy_value_info['diy_value']);
            $zIndex_arr = [];
            foreach ($diy_value_info['diy_value'] as $key=>&$item) {
                $item['width'] = intval($item['width']);
                $item['height'] = intval($item['height']);
                $item['top'] = intval($item['top']);
                $item['left'] = intval($item['left']);
                $item['minw'] = intval($item['minw']);
                $item['minh'] = intval($item['minh']);
                if ($item['zIndex']>1000) {
                    $item['zIndex'] = ceil($item['zIndex']/10);
                } else {
                    $item['zIndex'] = intval($item['zIndex']);
                }
                $zIndex_arr[] = intval($item['zIndex']);
                if ($is_api) {
                    unset($diy_value_info['diy_value'][$key]['draggable']);
                    unset($diy_value_info['diy_value'][$key]['resizable']);
                    unset($diy_value_info['diy_value'][$key]['minw']);
                    unset($diy_value_info['diy_value'][$key]['minh']);
                    unset($diy_value_info['diy_value'][$key]['parentLim']);
                    unset($diy_value_info['diy_value'][$key]['backgroundColor']);
                    unset($diy_value_info['diy_value'][$key]['color']);
                    unset($diy_value_info['diy_value'][$key]['active']);
                    unset($diy_value_info['diy_value'][$key]['is_close']);
                    unset($diy_value_info['diy_value'][$key]['key_num']);
                    if (!in_array($item['type'],['mul_txt', 'txt', 'date_choose','time_choose'])) {
                        // 用户前端 不是单行文本、多行文本、日期选择的全部替换为单行文本
                        $item['type'] = 'txt';
                        $item['type_text'] = $this->template_type_arr[$item['type']];
                    }
                }
            }
            $zIndex_min = min($zIndex_arr);
            $diy_value_info['zIndex_min'] = $zIndex_min;
        }
        if ($diy_value_info['template_img'] && strpos($diy_value_info['template_img'],'/static/material_diy/img/') !== false) {
            $diy_value_info['template_img_path'] = C('config.site_url') . $diy_value_info['template_img'];
        } elseif ($diy_value_info['template_img']) {
            $diy_value_info['template_img_path'] = replace_file_domain($diy_value_info['template_img']);
        }

        if ($is_write && $diy_value_info['from'] == 'house' && $diy_value_info['from_id']>0) {
            $diy_value_info['template_type_arr'] = $this->template_type_arr;
        }
        if ($is_api) {
            $diy_value_info['is_write'] = false;
        } else {
            $diy_value_info['is_write'] = true;
        }

        return $diy_value_info;
    }

    public function write_template_list($where=array(), $page=1, $page_size=20, $order='add_time DESC') {
        if (!$where['diy_tatus']) {
            $where['diy_tatus'] = array('neq', 4);
        }
        $d_plugin_material_diy_value = M('Plugin_material_diy_value');
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $d_plugin_material_diy_value->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'add_time DESC';
        }
        $list = $d_plugin_material_diy_value->where($where)->limit($limit)->field('id, template_id, title, template_img, add_time, from, from_id, from_type, from_name, diy_tatus, diy_reason, check_time, write_time, uid')->order($order)->select();

        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if ($val['last_time']>0) {
                    $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                }
                if ($val['write_time']>0) {
                    $val['write_time_txt'] = date('Y-m-d H:i:s',$val['write_time']);
                }
                if ($val['check_time']>0) {
                    $val['check_time_txt'] = date('Y-m-d H:i:s',$val['check_time']);
                }
                if (isset($val['diy_tatus'])) {
                    $val['diy_tatus_txt'] = $this->diy_tatus_txt[$val['diy_tatus']];
                }
//                if ($val['diy_value']) {
//                    $val['diy_value'] = unserialize($val['diy_value']);
//                }
                if ($val['template_img']) {
                    $val['template_img_path'] = replace_file_domain($val['template_img']);
                }
            }
        }

        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }

    public function write_file_list($where=array(), $page=1, $page_size=20, $order='add_time DESC') {
        if (!$where['file_status']) {
            $where['file_status'] = array('neq', 4);
        }
        if ($where['diy_id']>0) {
            $d_plugin_material_diy_value = M('Plugin_material_diy_value');
            $diy_value_info = $d_plugin_material_diy_value->where(array('id' => $where['diy_id']))->find();
        }


        $d_plugin_material_diy_file = M('Plugin_material_diy_file');
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $d_plugin_material_diy_file->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'add_time DESC';
        }
        $list = $d_plugin_material_diy_file->where($where)->limit($limit)->order($order)->select();

        // 图片文件的后缀
        $image_exts = $this->image_exts;
        // 图片文件的所有类型
        $image_type = $this->image_type;
        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                $val['is_image'] = false;
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                if ($val['last_time']>0) {
                    $val['last_time_txt'] = date('Y-m-d H:i:s',$val['last_time']);
                }
                if ($val['diy_value']) {
                    $val['diy_value'] = unserialize($val['diy_value']);
                }
                if ($val['file_url']) {
                    $val['file_url_path'] = replace_file_domain($val['file_url']);
                }
                if (in_array($val['file_type'], $image_type)) {
                    $val['is_image'] = true;
                }
                if (in_array($val['file_suffix'], $image_exts)) {
                    $val['is_image'] = true;
                }
                if ($diy_value_info && $diy_value_info['title']) {
                    $val['title'] = $diy_value_info['title'];
                }
            }
        }

        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }

    public function write_remark_list($where=array(), $page=1, $page_size=20, $order='add_time DESC') {
        $d_plugin_material_diy_remark = M('Plugin_material_diy_remark');
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $d_plugin_material_diy_remark->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'add_time DESC';
        }
        $list = $d_plugin_material_diy_remark->where($where)->limit($limit)->order($order)->select();

        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
            }
        }

        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }

    public function material_diy_deposit_list($where=array(), $page=1, $page_size=20, $order='a.pay_time DESC, deposit_id DESC') {
        $where_bind = [];
        if (isset($where['name'])) {
            $where_bind['name'] = $where['name'];
            unset($where['name']);
        }
        if (isset($where['phone'])) {
            $where_bind['phone'] = $where['phone'];
            unset($where['phone']);
        }
        if (isset($where['room_addrss'])) {
            $where_bind['room'] = $where['room_addrss'];
            unset($where['room_addrss']);
        }
        if (!isset($where['is_refund'])) {
            $where['is_refund'] = 1;
        }

        if ($where_bind) {
            $d_house_village_user_vacancy = M('House_village_user_vacancy');
            $pigcms_id_arr = $d_house_village_user_vacancy->where($where_bind)->getField('pigcms_id', true);
            if (!$pigcms_id_arr || !$pigcms_id_arr[0]) {
                $pigcms_id_arr = [];
            }
            $where['a.pigcms_id'] = ['in', $pigcms_id_arr];
        }
        $d_house_village_deposit = M('House_village_deposit');
        $limit = (($page-1)*$page_size).','.$page_size;
        $count = $d_house_village_deposit
            ->join('as a LEFT JOIN '.C('DB_PREFIX') . 'plugin_material_diy_deposit AS b ON b.deposit_id = a.deposit_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX') . 'house_village_user_vacancy AS c ON (c.pigcms_id = a.pigcms_id AND c.village_id = a.village_id)')
            ->where($where)->count();
        $total_page = ceil($count/$page_size);
        if (empty($order)) {
            $order = 'a.pay_time DESC, deposit_id DESC';
        }
        $list = $d_house_village_deposit
            ->join('as a LEFT JOIN '.C('DB_PREFIX') . 'plugin_material_diy_deposit AS b ON b.deposit_id = a.deposit_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX') . 'house_village_user_vacancy AS c ON c.pigcms_id = a.pigcms_id')
            ->field('c.uid,c.usernum,c.name,c.phone,c.single_id,c.floor_id,c.layer_id,c.room,a.*')
            ->where($where)->limit($limit)->order($order)->select();
        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                $val['address'] = D('House_village')->get_single_floor_room($val['single_id'], $val['floor_id'], $val['layer_id'], $val['pigcms_id'], $val['village_id']);
                //$val['address'] = PD('House_village')->get_single_floor_room($val['single_id'], $val['floor_id'], $val['layer_id'], $val['pigcms_id'], $val['village_id']);
            }
        }
        $data = array();
        $data['count'] = $count;
        $data['total_page'] = $total_page;
        $data['list'] = $list;
        return $data;
    }

    public function material_diy_bind_deposit_list($where=array(), $order='b.add_time DESC, b.bind_id DESC') {
        $d_plugin_material_diy_deposit = M('Plugin_material_diy_deposit');
        $list = $d_plugin_material_diy_deposit
            ->join('as b LEFT JOIN '.C('DB_PREFIX') . 'house_village_deposit AS a ON b.deposit_id = a.deposit_id')
            ->join(' LEFT JOIN '.C('DB_PREFIX') . 'house_village_user_vacancy AS c ON c.pigcms_id = a.pigcms_id')
            ->field('c.uid,c.usernum,c.name,c.phone,c.single_id,c.floor_id,c.layer_id,c.room,a.*,b.bind_id, b.diy_id')
            ->where($where)->order($order)->select();
        if (empty($list)) {
            $list = array();
        } else {
            foreach ($list as &$val) {
                if ($val['add_time']>0) {
                    $val['add_time_txt'] = date('Y-m-d H:i:s',$val['add_time']);
                }
                $val['address'] = D('House_village')->get_single_floor_room($val['single_id'], $val['floor_id'], $val['layer_id'], $val['pigcms_id'], $val['village_id']);
                //$val['address'] = PD('House_village')->get_single_floor_room($val['single_id'], $val['floor_id'], $val['layer_id'], $val['pigcms_id'], $val['village_id']);
            }
        }
        $data = array();
        $data['list'] = $list;
        return $data;
    }

    /**
     * 文件上传
     * @param $uid  int 操作用户uid 为对应id
     * @param $is_file
     * @param $other_info  array 相关信息
     * @param $path string 路径
     * @param array $param 参数
     * @return array
     */
    public function file_handle($uid, $path,  $param = array('size' => 50), $is_file=true, $other_info = array())
    {
        import("ORG.Net.UploadFile");
        $upload = new UploadFile();
        $upload->maxSize = $param['size'] * 1024 * 1024 ;
        // 全部文件的后缀
        $all_exts = $this->now_all_exts;
        // 图片文件的后缀
        $image_exts = $this->image_exts;
        // 图片，excel文件, ppt, word, pdf
        $all_type = $this->now_all_type;
        // 图片文件的所有类型
        $image_type = $this->image_type;
        // 判断给予所需要的条件
        if ($is_file) {
            $upload->allowTypes = $all_type;
            $upload->allowExts = $all_exts;
        } else {
            $upload->allowTypes = $image_type;
            $upload->allowExts = $image_exts;
        }
        $upload->saveRule = 'uniqid_rand';
        isset($param['thumb']) && $upload->thumb = $param['thumb'];
        isset($param['imageClassPath']) && $upload->imageClassPath = $param['imageClassPath'];
        isset($param['thumbPrefix']) && $upload->thumbPrefix = $param['thumbPrefix'];
        isset($param['thumbMaxWidth']) && $upload->thumbMaxWidth = $param['thumbMaxWidth'];
        isset($param['thumbMaxHeight']) && $upload->thumbMaxHeight = $param['thumbMaxHeight'];
        isset($param['thumbRemoveOrigin']) && $upload->thumbRemoveOrigin = $param['thumbRemoveOrigin'];

        $img_mer_id = sprintf("%09d", $uid);

        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);

        $upload_dir = "./upload/{$path}/{$rand_num}/";

        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }

        $upload->savePath = $upload_dir;// 设置附件上传目录

        if (!$upload->upload()) {// 上传错误提示错误信息
            return array('error' => 1, 'message' => $upload->getErrorMsg());
        } else {// 上传成功 获取上传文件信息
            $msg = array();
            $files = $upload->getUploadFileInfo();
            $msg['file_id_arr'] = array();
            //  处理统一标记
            $add_time = time();
            $site = C('config.site_url');
            foreach ($files as $file) {
                $msg['url'][$file['key']] = substr($file['savepath'] . $file['savename'], 1);
//                // 做一下图片内容安全检测
//                if (in_array($file['type'], $image_type)) {
//                    $url = $site . $msg['url'][$file['key']];
//                    $check = PD('House')->imgSecCheck($url);
//                    if ($check['errcode'] != 0) {
//                        $real_url = "{$_SERVER['DOCUMENT_ROOT']}{$msg['url'][$file['key']]}";
//                        // 不合规的不安全内容进行本地清除
//                        unlink($real_url);
//                        return array('error' => 1, 'message' => L_('图片内容含有违法违规内容'));
//                    }
//                }
                $msg['title'][$file['key']] = $rand_num . ',' . $file['savename'];
                if (in_array(strtolower($file['type']),$image_type,true)) {
                    $imageSizeArr = getimagesize($file['savepath'].$file['savename']);
                    $file_extra = serialize(array(
                        'img_width'=>intval($imageSizeArr[0]),
                        'img_height'=>intval($imageSizeArr[1]),
                        'size'=>$file['size']
                    ));
                } else {
                    $file_extra = serialize(array(
                        'size'=>$file['size']
                    ));
                }
                $add_data = array(
                    'diy_id' => 0,
                    'file_uid' => $uid,
                    'ip' => get_client_ip(),
                    'add_time' => $add_time,
                    'file_url' => $msg['url'][$file['key']],
                    'file_remark'=>$file['name'],
                    'file_suffix' => $file['extension'],
                    'file_name' => $file['savename'],
                    'file_type' => $file['type'],
                    'file_extra' => $file_extra
                );
                if ($other_info && $other_info['from']) {
                    $add_data['from'] = $other_info['from'];
                }
                if ($other_info && $other_info['from_id']) {
                    $add_data['from_id'] = $other_info['from_id'];
                }
                if ($other_info && $other_info['from_type']) {
                    $add_data['from_type'] = $other_info['from_type'];
                }
                if ($other_info && $other_info['from_name']) {
                    $add_data['from_name'] = $other_info['from_name'];
                }
                $file_id = M('Plugin_material_diy_file')->add($add_data);
                $msg['file_id'] = $file_id;
                $msg['file_url'] = $msg['url'][$file['key']];
                $msg['file_id_arr'][] = $file_id;
                $msg['file_url_arr'][] = $msg['url'][$file['key']];
            }
            $msg['error'] = 0;
            return $msg;
        }
    }

    //todo 办公时间读取
    public function get_work_time($village_id,$type=0){
        $work_time = D('House_village')->where(array('village_id'=>$village_id))->getField('work_time');
        $options=[
            'start'=>'08:00',
            'end'=>'18:00',
            'step'=>'00:05'
        ];
        if($work_time){
            $work_time=explode('-',$work_time);
            $options['start']=$work_time[0];
            $options['end']=$work_time[1];
        }
        if($type == 1){
            $options['step']='5';
        }
        return $options;
    }

    //todo 校验办公时间合法性
    public function check_work_time($village_id,$data){
        $rr=[];
        foreach ($data as $v){
            if($v['type'] == 'time_choose'){
                $rr[]=$v;
            }
        }
        $is_true=true;
        if(!empty($rr)){
            $work_time = D('House_village')->where(array('village_id'=>$village_id))->getField('work_time');
            if(empty($work_time)){
                returnCode(11, [], '请先配置办公时间！');
            }
            $work_time=explode('-',$work_time);
            foreach ($rr as $v){
                if($v['value'] < $work_time[0] || $v['value'] > $work_time[1]){
                    returnCode(11, [], '当前办公时间'.$v['value'].'已失效，请重新选择（'.$work_time[0].'-'.$work_time[1].'）或刷新页面');
                    $is_true=false;
                    break;
                }
            }
        }
        if(!$is_true){
            exit();
        }
    }

    //todo 校验是否可重复提交公租房申请
    public function check_add_apply_record($param){
        $where=[
            'village_id'=>$param['village_id'],
            'template_id'=>$param['template_id'],
            'uid'=>$param['uid'],
            'flow_status'=>array('neq',11),
            'del_time'=>0
        ];
//        if(intval(C('config.is_material_diy')) > 0 && $param['pigcms_id']){
//            $where['pigcms_id']=$param['pigcms_id'];
//        }
        $info=M('House_public_rental_apply_record')->where($where)->getField('number');
        if($info){
            return ['error'=>false,'msg'=>'该模板您已经提交过！'.PHP_EOL.'编号:['.$info.']'];
        }else{
            return ['error'=>true,'msg'=>'ok'];
        }
    }

    //todo 校验电子合同提交申请
    public function check_add_contract($param){
        $where=[
            'from'=>'house',
            'from_id'=>$param['village_id'],
            'template_id'=>$param['template_id'],
            'uid'=>$param['uid'],
            'diy_tatus'=>array('neq',4)
        ];
        $info=M('Plugin_material_diy_value')->where($where)->getField('id');
        if($info){
            return ['error'=>false,'msg'=>'该模板您已经提交过！'];
        }else{
            return ['error'=>true,'msg'=>'ok'];
        }
    }

    //todo 电子合同同步更新用户
    public function syn_public_rental($param){
        $where=[
            'type'=>1,
            'village_id'=>$param['village_id'],
            'uid'=>$param['uid'],
            'flow_status'=>array('gt',11),
            'del_time'=>0
        ];
        $data=[
            'contract_template_id'=>$param['template_id'],
            'contract_value_id'=>$param['value_id'],
        ];
        $info=M('House_public_rental_apply_record')->where($where)->data($data)->save();
        return $info;
    }

    //todo 后端操作 切换新模板 填写记录
    public function contract_relation_rental_record($param){
        $Plugin_material_diy_template=M('Plugin_material_diy_template');
        $House_public_rental_apply_record=M('House_public_rental_apply_record');
        $Plugin_material_diy_value=M('Plugin_material_diy_value');
        if(!$param['record_id']){
            return true;
        }
        $template_info = $Plugin_material_diy_template->where(['template_id'=>$param['template_id'],'template_type'=>2])->field('template_id')->find();
        if(!$template_info){
            return true;
        }
        $uid=$House_public_rental_apply_record->where(['id'=>$param['record_id']])->getField('uid');
        if($uid){
            $Plugin_material_diy_value->where(['id'=>$param['value_id']])->data(['uid'=>$uid])->save();
            $House_public_rental_apply_record->where(['id'=>$param['record_id']])->data([
                'contract_template_id'=>$template_info['template_id'],
                'contract_value_id'=>$param['value_id']
            ])->save();
        }

        return true;

    }

    //todo 公租房 写入申请记录
    public function add_apply_record($param){
        $time=time();
        $data=[
            'number'=>'HZSQ-'.date('YmdHis').createRandomStr(5, true),
            'village_id'=>$param['village_id'],
            'template_id'=>$param['template_id'],
            'value_id'=>$param['value_id'],
            'uid'=>$param['uid'],
            'pigcms_id'=>$param['pigcms_id'],
            'flow_status'=>10,
            'add_time'=>$time,
            'update_time'=>$time,
            'del_time'=>0
        ];
        $id=M('House_public_rental_apply_record')->data($data)->add();
        $rr=[
            'id'=>$id,
            'type'=>'add_apply'
        ];
        invoke_v20_service('\app\community\model\service\HousePublicRentalApplyService/addApplyV20',$rr);
        return $id;
    }


    //todo 校验添加退租申请
    public function check_add_renting($param){
        $where=[
            'village_id'=>$param['village_id'],
            'uid'=>$param['uid'],
            'flow_status'=>array('gt',20),
            'del_time'=>0
        ];
        $apply_info=M('House_public_rental_apply_record')->where($where)->order('id desc')->find();
        if(!$apply_info){
            return ['error'=>false,'msg'=>'您暂未添加入住申请，暂不可提交','data'=>[]];
        }
        $where=[
            'village_id'=>$param['village_id'],
            'template_id'=>$param['template_id'],
            'uid'=>$param['uid'],
            'apply_record_id'=>$apply_info['id'],
            'flow_status'=>array('neq',11),
            'del_time'=>0
        ];
        $info=M('House_public_rental_renting_record')->where($where)->order('id desc')->getField('number');
        if($info){
            return ['error'=>false,'msg'=>'该模板您已经提交过！'.PHP_EOL.'编号:['.$info.']','data'=>[]];
        }
        return ['error'=>true,'msg'=>'ok','data'=>$apply_info];

    }

    /**
     * 公租房 添加退租申请
     * @author:zhubaodi
     * @date_time: 2022/8/8 8:53
     */
    public function add_cancel_rental_record($param){
        $time=time();
        $apply_record_info=$param['apply_record_info'];
        $data=[
            'number'=>'TZBH-'.date('YmdHis').createRandomStr(5, true),
            'village_id'=>$param['village_id'],
            'apply_record_id'=>$apply_record_info['id'],
            'single_id'=>$apply_record_info['single_id'],
            'floor_id'=>$apply_record_info['floor_id'],
            'layer_id'=>$apply_record_info['layer_id'],
            'vacancy_id'=>$apply_record_info['vacancy_id'],
            'template_id'=>$param['template_id'],
            'value_id'=>$param['value_id'],
            'uid'=>$param['uid'],
            'pigcms_id'=>$param['pigcms_id'],
            'add_time'=>$time,
            'update_time'=>$time,
            'del_time'=>0
        ];
        $id=M('House_public_rental_renting_record')->data($data)->add();
        $rr=[
            'id'=>$id,
            'type'=>'add_renting'
        ];
        invoke_v20_service('\app\community\model\service\HousePublicRentalApplyService/addApplyV20',$rr);
        return $id;
    }


    //todo 审核同步公租房
    public function syn_apply_record($param){
        $time=time();
        $House_public_rental_apply_record=M('House_public_rental_apply_record');
        if(!$param['diy_tatus'] || !in_array($param['diy_tatus'],[1,3])){
            return true;
        }
        $where=[
            'village_id'=>$param['village_id'],
            'template_id'=>$param['template_id'],
            'value_id'=>$param['value_id'],
            'flow_status'=>10
        ];
        $data=[
            'flow_status'=>($param['diy_tatus'] == 1) ? 12 : 11,
            'apply_time'=>$time,
            'update_time'=>$time
        ];
        $info=$House_public_rental_apply_record->where($where)->field('id')->find();
        if(!$info){
            return true;
        }
        $House_public_rental_apply_record->where(['id'=>$info['id']])->data($data)->save();
        $rr=[
            'id'=>$info['id'],
            'type'=>'examine_apply'
        ];
        invoke_v20_service('\app\community\model\service\HousePublicRentalApplyService/addApplyV20',$rr);
        return true;
    }
}