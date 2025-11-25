<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      设备相关常量
 */

namespace app\consts;

class DeviceConst
{
    /**
     * @var integer 海康设备类型 目前5
     */
    const HIK_DEVICE_TYPE = 5;
    
    /**
     * @var integer 西函门禁设备类型 目前31
     */
    const XI_HAN_DEVICE_TYPE = 31;

    /** 海康HIK 对接协议 以1开头 目前默认为【云牟外部独立协议】**/
    /**
     * @var string 标记设备品牌 '海康'
     */
    const HIK_BRAND_KEY = 'brand_haikang';

    /**
     * @var integer 标记协议 '云牟外部独立协议'
     */
    const HIK_YUNMO_WAIBU = 11;

    /**
     * @var string 对应协议名称 '云牟外部独立协议'
     */
    const HIK_YUNMO_WAIBU_TITLE = '云牟外部独立协议';

    /**
     * @var integer 标记协议 '海康云牟内部协议'
     */
    const HIK_YUNMO_NEIBU_SHEQU = 12;

    /**
     * @var string 对应协议名称 '海康云牟内部协议'
     */
    const HIK_YUNMO_NEIBU_SHEQU_TITLE = '海康云牟内部协议';

    /**
     * @var integer 标记协议 '6000C社区边缘'
     */
    const HIK_YUNMO_NEIBU_6000C = 14;

    /**
     * @var string 对应协议名称 '6000C社区边缘'
     */
    const HIK_YUNMO_NEIBU_6000C_TITLE = '6000C社区边缘';

    /**
     * @var integer 标记协议 '海康综合安防管理平台 v1.5.100 版本'
     */
    const HIK_ISC_V151 = 13;

    /**
     * @var string 对应协议名称 '海康综合安防管理平台 v1.5.100 版本'
     */
    const HIK_ISC_V151_TITLE = '海康综合安防管理平台 v1.5.100 版本';


    /**
     * @var integer 标记协议 '海康智慧社区管理平台V1.5.0'
     */
    const HIK_INFO_VISION_I_COMMUNITY = 15;

    /**
     * @var string 对应协议名称 '海康云牟内部协议'
     */
    const HIK_INFO_VISION_I_COMMUNITY_TITLE = '海康智慧社区管理平台V1.5.0';

    /**
     * ==============================================
     *  Hik Device Gateway 海康网关
     * ==============================================
     */
    /**
     * @var integer Hik Device Gateway 海康网关(平台)
     */
    const HIK_DEVICE_GATEWAY = 16;
    const HIK_DEVICE_GATEWAY_TITLE = 'Hik Device Gateway 海康网关(平台)';

    /**
     * @var 小区自己配置
     */
    const HIK_DEVICE_GATEWAY_CUSTOM = 17;
    const HIK_DEVICE_GATEWAY_CUSTOM_TITLE =  'Hik Device Gateway 海康网关(小区)';
    
    /**
     * @var integer 大华设备类型 目前88
     */
    const DH_DEVICE_TYPE = 88;

    /** 大华DH 对接协议 以3开头 目前默认为【大华h8900平台】**/
    /**
     * @var string 标记设备品牌 '大华'
     */
    const DH_BRAND_KEY = 'brand_dahua';

    /**
     * @var integer 标记协议 '大华云睿协议'
     */
    const  DH_YUNRUI = 31;

    /**
     * @var string 对应协议名称 '大华云睿协议'
     */
    const DH_YUNRUI_TITLE = '大华云睿协议';

    /**
     * @var integer 标记协议 '大华h8900平台'
     */
    const  DH_H8900 = 32;

    /**
     * @var string 对应协议名称 '大华h8900平台'
     */
    const DH_H8900_TITLE = '大华h8900平台';
    
    
    /**
     * @var string 设备类型  人脸
     */
    const DEVICE_TYPE_FACE                         = 'face';
    /**
     * @var string 设备类型  监控
     */
    const DEVICE_TYPE_CAMERA                       = 'camera';
    /**
     * @var string 设备类型  报警器
     */
    const DEVICE_TYPE_ALARM                       = 'alarm';
    /**                                            
     * @var string 设备类型  指纹                        
     */                                            
    const DEVICE_TYPE_FINGERPRINT                  = 'fingerprint';
    /**
     * @var string 设备类型  充电桩
     */
    const DEVICE_TYPE_CHARGE_PILE                  = 'charge_pile';
    /**
     * @var string 设备类型  智能快递柜
     */
    const DEVICE_TYPE_INTELLIGENT_EXPRESS_CABINET  = 'intelligent_express_cabinet';
    /**
     * @var string 设备类型  智慧停车
     */
    const DEVICE_TYPE_SMART_PARKING                = 'park';
    /**
     * @var string 设备类型  蓝牙门禁
     */
    const DEVICE_TYPE_BLUETOOTH_DOOR               = 'bluetooth_door';
    /**
     * @var string 设备类型  智能垃圾回收设备
     */
    const DEVICE_TYPE_GARBAGE_COLLECTION               = 'garbage_collection';


    // 表 face_user_bind_device 中 bind_type 类型 ↓
    /*** @var integer 记录大华组织根节点类型 */
    const DH_ORG_GROUP_TYPE              = 100;
    /**
     * @var integer 大华 物业 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_PROPERTY           = 101;
    /**
     * @var integer 大华 小区 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_VILLAGE            = 102;
    /**
     * @var integer 大华 楼栋 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_BUILD              = 99;
    /**
     * @var integer 大华 单元 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_UNIT               = 103;
    /**
     * @var integer 大华 楼层 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_FLOOR              = 104;
    /**
     * @var integer 大华 房屋 同步至设备方 相关信息记录
     */
    const DH_TO_CLOUD_ROOM               = 105;
    /**
     * @var int 大华  开门计划（以设备id为主导记录同步）
     */
    const DH_TIME_PLAN_CLOUD_DEVICE      = 106;

    /**
     * @var int 大华  人员（以uid为主导记录同步的人员基础信息）
     */
    const DH_UID_CLOUD_USER                    = 111;
    /**
     * @var int 大华  人员（以住户身份为主导记录同步）
     */
    const DH_PIG_CMS_ID_CLOUD_USER             = 112;
    /**
     * @var int 大华  人证信息（以uid身份为主导记录同步）
     */
    const DH_PERSON_IDENTITY_CLOUD_USER        = 113;

    /** @var int 大华  人员同步至人脸设备记录 */
    const DH_PERSON_TO_DEVICE_USER             = 114;

    /** @var int 大华  人员同步至人脸设备记录 */
    const DH_PERSON_TO_FINGERPRINT_DEVICE_USER = 115;
    
    /**
     * @var integer 海康 小区 同步至设备方 相关信息记录
     */
    const HK_TO_CLOUD_VILLAGE = 130;

    /**
     * @var integer 海康 楼栋同步至设备方 相关信息记录
     */
    const HK_TO_CLOUD_SINGLE  = 131;

    /**
     * @var integer 海康 单元同步至设备方 相关信息记录
     */
    const HK_TO_CLOUD_FLOOR   = 132;

    /**
     * @var integer 海康 房屋同步至设备方 相关信息记录
     */
    const HK_TO_CLOUD_ROOM    = 133;

    /**
     * @var int 海康  人员（以uid为主导记录同步的人员基础信息）
     */
    const HK_UID_CLOUD_USER   = 134;

    /**
     * @var int 海康  设置人员所属社区
     */
    const HK_UID_BIND_COMMUNITY = 135;

    /**
     * @var int 海康  设置人员所属户室
     */
    const HK_UID_BIND_ROOM     = 136;

    /**
     * @var integer 海康 设备确权
     */
    const HK_CONFIRM_DEVICE   = 140;

    /**
     * @var int 海康  给人员开通卡片
     */
    const HK_UID_BIND_CARD     = 150;

    /**
     * @var int Hik Device Gateway 海康网关(平台) 人员（小区身份id）
     */
    const Hk_GATEWAY_USER_BIND_PIGCMSID = 160;

    /**
     * @var int Hik Device Gateway 海康网关(平台) 人员卡号（小区身份IC卡号）
     */
    const Hk_GATEWAY_USER_BIND_IC_CARD = 161;

    /**
     * @var int Hik Device Gateway 海康网关(平台) 人员人脸图片
     */
    const Hk_GATEWAY_USER_BIND_FACE_IMAGE = 162;

    /**
     * @var int Hik Device Gateway 海康网关(平台) 二维码
     */
    const Hk_GATEWAY_USER_BIND_QRCODE_CARD = 163;

    /**
     * @var int Hik Device Gateway 海康网关(平台) 人员（小区身份id）
     */
    const HIK_YUNMO_WAIBU_BIND_QRCODE_CARD = 171;
    
    // 表 face_user_bind_device 中 bind_type 类型 ↑
    
    
    // 表 pigcms_camera_device_bind 中 bindType 类型 ↓
    /**
     * @var int 绑定的监控设备信息 
     */
    const BIND_CAMERA_DEVICE    = 1;
    //**↓↓↓↓↓↓ 对应数据表【device_bind_info】中 bind_type ↓↓↓↓↓↓**//
    /**
     * @var int 绑定的人脸设备信息
     */
    const BIND_FACE_DEVICE              = 2;
    /**
     * @var int 绑定的指纹设备信息
     */
    const BIND_FINGERPRINT_DEVICE       = 3;
    /**
     * @var int 绑定的报警设备信息
     */
    const BIND_ALARM_DEVICE              = 4;
    /**                                 
     * @var int 绑定的物业信息                 
     */                                 
    const BIND_PROPERTY                 = 20;
    /**                                 
     * @var int 绑定的小区信息                 
     */                                 
    const BIND_VILLAGE                  = 21;
    /**                                 
     * @var int 绑定的楼栋信息                 
     */                                 
    const BIND_BUILD                    = 22;
    /**                                 
     * @var int 绑定的单元信息                 
     */                                 
    const BIND_UNIT                     = 24;
    /**                                 
     * @var int 绑定的楼层信息                 
     */                                 
    const BIND_FLOOR                    = 25;
    /**                                 
     * @var int 绑定的房屋信息                 
     */                                 
    const BIND_ROOM                     = 26;
    /**                                 
     * @var int 绑定的计划信息                 
     */                                 
    const BIND_PLAN                     = 40;
    /**                                 
     * @var int 绑定的分组信息                 
     */                                 
    const BIND_GROUP                    = 41;
    /** 
     * @var int 绑定的人员人脸人卡同步信息 
     */
    const BIND_FACE_BIND_USER_BIND_CARD = 50;
    /** 
     * @var int 绑定的人员同步信息 
     */
    const BIND_USER                     = 51;
    /**                                 
     * @var int 绑定的人脸同步信息               
     */                                 
    const BIND_FACE                     = 52;
    /**                                 
     * @var int 绑定IC卡                   
     */                                 
    const BIND_IC_CARD                  = 53;
    /**                                 
     * @var int 绑定ID卡                   
     */                                 
    const BIND_ID_CARD                  = 54;
    /**
     * @var int 设置人员所属社区
     */
    const BIND_COMMUNITY_RELATION       = 55;
    /**
     * @var int 给人员开通卡片
     */
    const BIND_USER_OPEN_CARD           = 56;
    /**
     * @var int 下发人员到设备
     */
    const ISSUED_USER_TO_DEVICE         = 57;
    /**                                 
     * @var int 人员下发                    
     */                                 
    const BIND_USER_AUTH_ISSUED         = 61;
    /**                                 
     * @var int 记录同步步骤 
     */
    const BIND_DEVICE_STEP              = 33;
    //**↑↑↑↑↑↑ 对应数据表【device_bind_info】中 bind_type ↑↑↑↑↑↑**//
    //**↓↓↓↓↓↓ 对应数据表【device_bind_info】中 synStatus ↓↓↓↓↓↓**//
    // 一般大致同步路线 物业->小区(可能后续同步设备)->楼栋->单元(可能后续同步设备)->楼层->房屋->人员  同步设备是看具体需要在那一层后面，而且层级也不固定（有的没有或者加了层级） //
    /** @var int 同步物业信息成功 同步状态看 synStatusTxt 失败原因看 syn_reason  详细报错看 err_reason*/

    /** @var int 同步开始 */
    const BINDS_SYN_START                  = 1000;
    /** @var int 同步失败 */                   
    const BINDS_SYN_ERR                    = 1001;
    
    /** @var int 下发物业队列 */                 
    const BINDS_SYN_PROPERTY_QUEUE         = 1010;
    /** @var int 同步物业开始 */                 
    const BINDS_SYN_PROPERTY_START         = 1011;
    /** @var int 同步物业失败（未到三方） */
    const BINDS_SYN_PROPERTY_ERR           = 1012;
    
    /** @var int 下发小区队列 */                 
    const BINDS_SYN_VILLAGE_QUEUE          = 1020;
    /** @var int 同步小区开始 */                 
    const BINDS_SYN_VILLAGE_START          = 1021;
    /** @var int 同步小区失败（未到三方） */
    const BINDS_SYN_VILLAGE_ERR            = 1022;
    /** @var int 下发小区楼栋队列 */
    const BINDS_SYN_VILLAGE_BUILDS_QUEUE   = 1023;
    /** @var int 下发小区楼栋单个队列开始 */
    const BINDS_SYN_VILLAGE_BUILDS_START   = 1024;
    /** @var int 下发小区楼栋单个队列失败（未到三方） */
    const BINDS_SYN_VILLAGE_BUILDS_ERR     = 1025;
    /** @var int 下发小区楼栋下发单个队列结束 */
    const BINDS_SYN_VILLAGE_BUILDS_END     = 1026;
    
    /** @var int 下发同步楼栋队列 */
    const BINDS_SYN_BUILD_QUEUE            = 1030; 
    /** @var int 同步楼栋开始 */
    const BINDS_SYN_BUILD_START            = 1031;
    /** @var int 同步单元失败（未到三方） */
    const BINDS_SYN_BUILD_ERR              = 1032;
    /** @var int 下发同步楼栋的单元队列 */
    const BINDS_SYN_BUILD_UNIT_QUEUE       = 1033;
    /** @var int 下发同步楼栋的单元单个队列开始 */
    const BINDS_SYN_BUILD_UNITS_START      = 1034;
    /** @var int 下发同步楼栋的单元单个队列失败（未到三方） */
    const BINDS_SYN_BUILD_UNITS_ERR        = 1035;
    /** @var int 下发同步楼栋的单元单个队列结束 */
    const BINDS_SYN_BUILD_UNITS_END        = 1036;


    /** @var int 下发同步单元队列 */
    const BINDS_SYN_UNIT_QUEUE             = 1040;
    /** @var int 同步单元开始 */ 
    const BINDS_SYN_UNIT_START             = 1041;
    /** @var int 同步单元失败（未到三方） */           
    const BINDS_SYN_UNIT_ERR               = 1042;
    /** @var int 下发同步单元的房屋队列 */            
    const BINDS_SYN_UNIT_ROOMS_QUEUE       = 1043;
    /** @var int 下发同步单元的房间单个队列开始 */
    const BINDS_SYN_UNIT_ROOMS_START       = 1044;
    /** @var int 下发同步单元的房间单个队列失败（未到三方） */
    const BINDS_SYN_UNIT_ROOMS_ERR         = 1045;
    /** @var int 下发同步单元的房间单个队列结束 */
    const BINDS_SYN_UNIT_ROOMS_END         = 1046;
    
    /** @var int 下发同步房屋队列 */
    const BINDS_SYN_ROOM_QUEUE             = 1050;
    /** @var int 同步房屋开始 */
    const BINDS_SYN_ROOM_START             = 1051;
    /** @var int 同步单元失败（未到三方） */
    const BINDS_SYN_ROOM_ERR               = 1052;
    
                                           
    /** @var int 下发设备队列 */                 
    const BINDS_SYN_DEVICE_QUEUE           = 1110;
    /** @var int 同步设备开始 */                 
    const BINDS_SYN_DEVICE_START           = 1111;
    /** @var int 同步设备失败（未到三方） */
    const BINDS_SYN_DEVICE_ERR             = 1112;
    /** @var int 获取直播流失败（未到三方） */
    const DEVICE_CREATE_LIVE_ERR           = 1113;
    /** @var int 设备一键同步队列 */
    const DEVICE_ALL_SYN_QUEUE             = 1115;
    /** @var int 同步设备楼栋开始 */
    const DEVICE_ALL_SYN_BUILD_START       = 1116;
    /** @var int 同步设备楼栋失败（未到三方） */
    const DEVICE_ALL_SYN_BUILD_ERR         = 1117;


    /** @var int 住户同步队列 */
    const BIND_USER_ALL_SYN_QUEUE          = 1310;
    /** @var int 住户同步失败（未到三方） */
    const BIND_USER_ALL_SYN_ERR            = 1311;
    /** @var int 住户同步开始 */
    const BIND_USER_ALL_SYN_START          = 1312;
    /** @var int 住户同步设备队列 */
    const BIND_USER_TO_DEVICE_SYN_QUEUE    = 1313;
    /** @var int 住户同步设备开始 */
    const BIND_USER_TO_DEVICE_SYN_START    = 1314;
    /** @var int 住户同步设备错误 */
    const BIND_USER_TO_DEVICE_SYN_ERR      = 1315;
    /** @var int 设置住户所属社区错误 */
    const BIND_USER_TO_COMMUNITY_RELATION_ERR  = 1316;
    /** @var int 设置人员所属户室错误 */
    const BIND_USER_TO_ROOM_RELATION_ERR   = 1317;
    /** @var int 人员开通卡片失败 */
    const BIND_USER_TO_OPEN_CARD_ERR       = 1318;


    /** @var int 设备自动确权开始（目前主要用于海康内部应用） */
    const DEVICE_AUTO_CONFIRM_START        = 1400;
                                           
    const BINDS_SYN_PROPERTY_SUCCESS       = 10100;
    /** @var int 同步小区信息成功 */               
    const BINDS_SYN_VILLAGE_SUCCESS        = 10200;
    /** @var int 同步楼栋信息成功 */               
    const BINDS_SYN_BUILD_SUCCESS          = 10300;
    /** @var int 同步单元信息成功 */               
    const BINDS_SYN_UNIT_SUCCESS           = 10400;
    /** @var int 同步楼层信息成功 */               
    const BINDS_SYN_FLOOR_SUCCESS          = 10500;
    /** @var int 同步房屋信息成功 */               
    const BINDS_SYN_ROOM_SUCCESS           = 10600;
    /** @var int 同步房屋信息成功 */               
    const BINDS_SYN_PERSON_SUCCESS         = 10700;
    /** @var int 同步设备信息成功 */               
    const BINDS_SYN_DEVICE_SUCCESS         = 10800;
    /** @var int 同步通道信息成功 */
    const BINDS_SYN_DEVICE_CHANNEL_SUCCESS = 10801;
    /** @var int 获取直播流成功 */
    const DEVICE_CREATE_LIVE_SUCCESS       = 10802;
    /** @var int 同步计划信息成功 */
    const BINDS_SYN_PLAN_SUCCESS           = 10900;
    /** @var int 同步组信息成功 */                
    const BINDS_SYN_GROUP_SUCCESS          = 11100;
    /** @var int 设备自动确权成功（目前主要用于海康内部应用） */
    const DEVICE_AUTO_CONFIRM_SUCCESS      = 11200;
    /** @var int 同步物业信息成功 同步状态看 synStatusTxt 失败原因看 syn_reason  详细报错看 err_reason */
    const BINDS_SYN_PROPERTY_FAIL          = 40100;
    /** @var int 同步小区信息失败 */               
    const BINDS_SYN_VILLAGE_FAIL           = 40200;
    /** @var int 同步楼栋信息失败 */               
    const BINDS_SYN_BUILD_FAIL             = 40300;
    /** @var int 同步单元信息失败 */               
    const BINDS_SYN_UNIT_FAIL              = 40400;
    /** @var int 同步楼层信息失败 */               
    const BINDS_SYN_FLOOR_FAIL             = 40500;
    /** @var int 同步房屋信息失败 */               
    const BINDS_SYN_ROOM_FAIL              = 40600;
    /** @var int 同步房屋信息失败 */               
    const BINDS_SYN_PERSON_FAIL            = 40700;
    /** @var int 同步设备信息失败 */               
    const BINDS_SYN_DEVICE_FAIL            = 40800;
    /** @var int 同步通道信息失败 */
    const BINDS_SYN_DEVICE_CHANNEL_FAIL    = 40801;
    /** @var int 获取直播流失败 */
    const DEVICE_CREATE_LIVE_FAIL          = 40802;
    /** @var int 同步计划信息失败 */               
    const BINDS_SYN_PLAN_FAIL              = 40900;
    /** @var int 同步组信息失败 */
    const BINDS_SYN_GROUP_FAIL             = 41100;
    /** @var int 设备自动确权失败（目前主要用于海康内部应用） */
    const DEVICE_AUTO_CONFIRM_FAIL         = 41200;
    /** @var int 人员同步失败 */
    const BIND_USER_FAIL                  = 42000;
    //**↑↑↑↑↑↑ 对应数据表中 synStatus ↑↑↑↑↑↑**//
    
    /**
     * @var string 设备触发一键同步
     */
    const DEVICE_ALL_SYN_USERS_TO_CLOUD_AND_DEVICE = 'deviceSynAllUsersCloud';


    /** @var int 人脸开门 */
    const DEVICE_FACE_OPEN             = 1;
    /** @var int 远程开门 */               
    const DEVICE_GATE_OPEN             = 2;
    /** @var int IC卡刷卡开门 */            
    const DEVICE_IC_CARD_OPEN          = 3;
    /** @var int 人证开门 证件开门 */          
    const DEVICE_CERTIFICATES_OPEN     = 10;
    /** @var int 特指身份证开门 */            
    const DEVICE_ID_CARD_PERSON_OPEN   = 11;
    /** @var int 蓝牙开门 */               
    const DEVICE_BLUETOOTH_OPEN        = 21;
    /** @var int 二维码开门 */              
    const DEVICE_QR_CODE_OPEN          = 31;
    /** @var int 密码开门 */               
    const DEVICE_PASS_WORD_OPEN        = 41;
    /** @var int 动态密码开锁 */
    const DEVICE_DYNAMIC_PASSWORD_OPEN = 42;
    /** @var int 指纹开门 */
    const DEVICE_FINGERPRINT_OPEN      = 45;
    /** @var int 健康码开门 */              
    const DEVICE_HEALTH_CODE_OPEN      = 61;
    /** @var int 健康码开门 */
    const DEVICE_ERR_HEALTH_CODE_OPEN  = 62;
    /** @var int 其他设备操作 */
    const DEVICE_OTHER_OPEN            = 44;
    /** @var int 组合认证刷卡加密码开锁 */
    const DEVICE_IC_CARD_AND_PASS_WORD_OPEN   = 70;
    /** @var int 组合认证刷卡加指纹开锁 */
    const DEVICE_IC_CARD_AND_FINGERPRINT_OPEN = 71;
    /** @var int 组合认证刷卡加指纹加密码开锁 */
    const DEVICE_IC_CARD_AND_FINGERPRINT_AND_PASS_WORD_OPEN = 72;
    /** @var int 组合认证指纹加密码开锁 */
    const DEVICE_FINGERPRINT_AND_PASS_WORD_OPEN = 73;
    /** @var int 组合认证人脸加指纹开锁 */
    const DEVICE_FACE_AND_FINGERPRINT_OPEN      = 74;
    /** @var int 组合认证人脸加密码开锁 */
    const DEVICE_FACE_AND_PASS_WORD_OPEN        = 75;
    /** @var int 组合认证人脸加刷卡开锁 */
    const DEVICE_FACE_AND_IC_CARD_OPEN          = 76;
    /** @var int 组合认证人脸加密码加指纹开锁 */
    const DEVICE_FACE_AND_PASS_WORD_AND_IC_CARD_OPEN   = 77;
    /** @var int 组合认证人脸加刷卡加指纹开锁 */
    const DEVICE_FACE_AND_IC_CARD_AND_FINGERPRINT_OPEN = 78;
    /** @var int 组合认证工号加指纹开锁 */
    const DEVICE_WORK_NUM_AND_FINGERPRINT_OPEN         = 79;
    /** @var int 组合认证工号加指纹加密码开锁 */
    const DEVICE_WORK_NUM_AND_FINGERPRINT_AND_PASS_WORD_OPEN = 80;
    /** @var int 组合认证工号加人脸开锁 */
    const DEVICE_WORK_NUM_AND_FACE_OPEN = 81;
    /** @var int 组合认证 */
    const DEVICE_COMBINED_CERTIFICATION = 90;

    /** @var int 一个人员同时可存在的人脸图片数上限 */
    const USER_MAX_FACE_IMG_NUM = 3;
    
    /** @var int pc端后台上传人脸图片宽度 */
    const PC_UPLOAD_FACE_IMG_WIDTH = 295;
    /** @var int pc端后台上传人脸图片高度 */
    const PC_UPLOAD_FACE_IMG_HIGH  = 413;
    
    // 下发状态
    /** @var int 状态 1成功  */
    const STATE_SUCCESS        = 1;
    /** @var int 状态 2失败  */
    const STATE_FAILED         = 2;
    /** @var int 状态 3下发中  */
    const STATE_PROCESS        = 3;
    /** @var int 状态 4同步删除成功  */
    const STATE_DELETE_SUCCESS = 4;
    /** @var int 状态 5同步删除失败  */
    const STATE_DELETE_FAILED  = 5;
    /** @var int 状态 0未下发  */
    const STATE_NOT_DOWN       = 0;
    
    /** @var int 记录用户下发同步上线条数 */
    const LIMIT_USER_AUTH_RECORD = 30;


    // 下发类型
    /**
     * @var string 下发类型  人员
     */
    const DEVICE_AUTH_TYPE_PERSON   = 'person';
    /**
     * @var string 下发类型  卡片
     */
    const DEVICE_AUTH_TYPE_CARD     = 'card';
    /**
     * @var string 下发类型  人脸
     */
    const DEVICE_AUTH_TYPE_FACE     = 'face';
    /**
     * @var string 下发类型  指纹
     */
    const DEVICE_AUTH_TYPE_FINGER   = 'finger';
    /**
     * @var string 下发类型  密码
     */
    const DEVICE_AUTH_TYPE_PASSWORD = 'password';
    
    
    /** @var int 垃圾回收类型设备类型值*/
    const GARBAGE_COLLECTION_BIND_TYPE = 31;    
    /** @var string 垃圾回收 redis tag */
    const GARBAGE_COLLECTION_JOB_REDIS_TAG = 'GarbageInteractiveTag';
    /** @var string  垃圾回收 下发队列 redis cacheKey */
    const GARBAGE_COLLECTION_TRAITS_REDIS_KEY = 'GarbageInteractive:traits:';
    /** @var string 垃圾回收 队列 redis cacheKey */
    const GARBAGE_COLLECTION_JOB_REDIS_KEY = 'GarbageInteractive:job:';
    /**
     * @var string 下发类型  机器人
     */
    const DEVICE_AUTH_TYPE_THIRD_ROBOT = 'third_robot';
    
    //用户绑定人脸
    const FACE_DEVICE_USER_JOB_REDIS_TAG = 'FaceDeviceUserInteractiveTag';
    const FACE_DEVICE_USER_TRAITS_REDIS_KEY = 'FaceDeviceUserInteractive:traits:';
    const FACE_DEVICE_USER_JOB_REDIS_KEY = 'FaceDeviceUserInteractive:job:';

}