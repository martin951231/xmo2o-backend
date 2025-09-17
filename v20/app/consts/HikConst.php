<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      海康相关常量
	 */
	namespace app\consts;

	class HikConst
	{
        /**
         * @var integer 海康（云眸外部应用） 访客二维码
         */
        const HIK_WAI_BU_VISITOR_QR_CODE  = 40;
        
        /**
         * @var integer 海康（云眸内应用） 访客二维码
         */
        const HIK_BEI_BU_VISITOR_QR_CODE  = 41;
        

        /*** 
         * @var string 配置项缺失返回报错 1006
         */
        const ERR_MESSAGE_NOT_CONFIGURED_CODE        = 1006;
        /*** 
         * @var string 配置项缺失返回报错
         */
        const ERR_MESSAGE_NOT_CONFIGURED             = '请前往 系统后台-智慧社区-社区配置-云眸内部应用进行配置';

        /*** 
         * @var string 海康云眸的社区ID 缺失返回报错 1007
         */
        const ERR_MESSAGE_NOT_COMMUNITY_ID_CODE      = 1007;
        /*** 
         * @var string 海康云眸的社区ID 缺失返回报错
         */
        const ERR_MESSAGE_NOT_COMMUNITY_ID           = '海康云眸社区ID不存在';

        /*** 
         * @var string 楼栋编号 缺失返回报错 1008
         */
        const ERR_MESSAGE_NOT_BUILDINGS_NUMBER_CODE  = 1008;
        /*** 
         * @var string 楼栋编号 缺失返回报错
         */
        const ERR_MESSAGE_NOT_BUILDINGS_NUMBER       = '楼栋编号缺少';

        /*** 
         * @var string 楼栋ID 缺失返回报错 1009
         */
        const ERR_MESSAGE_NOT_BUILDINGS_ID_CODE      = 1009;
        /*** 
         * @var string 楼栋ID 缺失返回报错
         */
        const ERR_MESSAGE_NOT_BUILDINGS_ID           = '楼栋ID缺少';

        /*** 
         * @var string 单元编号 缺失返回报错 1010
         */
        const ERR_MESSAGE_UNIT_NUMBER_CODE           = 1010;
        /*** 
         * @var string 单元编号 缺失返回报错
         */
        const ERR_MESSAGE_UNIT_NUMBER                = '单元编号缺少';

        /*** 
         * @var string 单元ID 缺失返回报错 1011
         */
        const ERR_MESSAGE_UNIT_ID_CODE               = 1011;
        /*** 
         * @var string 单元ID 缺失返回报错
         */
        const ERR_MESSAGE_UNIT_ID                    = '单元ID缺少';

        /*** 
         * @var string 户室编号 缺失返回报错 1011
         */
        const ERR_MESSAGE_ROOM_NUMBER_CODE           = 1011;
        /*** 
         * @var string 户室编号 缺失返回报错
         */
        const ERR_MESSAGE_ROOM_NUMBER                = '户室编号缺少';

        /*** 
         * @var string 楼层 缺失返回报错 1012
         */
        const ERR_MESSAGE_FLOOR_NUMBER_CODE          = 1012;
        /*** 
         * @var string 楼层 缺失返回报错
         */
        const ERR_MESSAGE_FLOOR_NUMBER               = '楼层缺少';

        /*** 
         * @var string 设备序列号不存在 1013
         */
        const ERR_HIK_NOT_DEVICE_ID_CODE             = 1013;
        /*** 
         * @var string 设备序列号不存在 返回报错
         */
        const ERR_HIK_NOT_DEVICE_ID_MESSAGE          = '设备序列号不存在';

        /*** 
         * @var string 设备类型不存在 1014
         */
        const ERR_HIK_NOT_DEVICE_TYPE_CODE           = 1014;
        /*** 
         * @var string 设备类型不存在 返回报错
         */
        const ERR_HIK_NOT_DEVICE_TYPE_MESSAGE        = '设备类型不存在';

        /*** 
         * @var string 设备类型不存在 1015
         */
        const ERR_HIK_NOT_COMMUNITY_ID_CODE          = 1015;
        /*** 
         * @var string 设备类型不存在 返回报错
         */
        const ERR_HIK_NOT_COMMUNITY_ID_MESSAGE       = '设备类型不存在';

        /*** 
         * @var string 缺少社区ID 1016
         */
        const ERR_DH_NOT_PARENT_ID_CODE              = 1016;
        /*** 
         * @var string 缺少社区ID 返回报错
         */
        const ERR_DH_NOT_PARENT_ID_MESSAGE           = '缺少社区ID';


        /**
         * @var string 更新社区不存在需要走添加逻辑
         */
        const HIK_NON_EXISTENT_COMMUNITIES  = '511000';
        /**
         * @var string 该社区下楼栋已存在，无法添加(楼栋名重复)
         */
        const HIK_EXISTENT_BUILD_BAN_ADD    = '511001';
        /**
         * @var string 楼栋编号已经存在
         */
        const HIK_EXISTENT_BUILD_NUMBER     = '511004';
        /**
         * @var string 楼栋不存在
         */
        const HIK_NON_EXISTENT_BUILD        = '511005';
        /**
         * @var string 该楼栋下单元已存在，无法添加(单元名重复)
         */
        const HIK_EXISTENT_UNIT_BAN_ADD     = '511007';
        /**
         * @var string 单元编号已经存在(单元编号重复)
         */
        const HIK_EXISTENT_UNIT_NUMBER      = '511008';
        /**
         * @var string 单元不存在
         */
        const HIK_NON_EXISTENT_UNIT         = '511006';
        /**
         * @var string 当前房屋已存在
         */
        const HIK_EXISTENT_ROOM_NUMBER      = '511092';
        /**
         * @var string 单元下户室名称已存在  单元下户室名称需唯一
         */
        const HIK_EXISTENT_ROOM_BAN_ADD     = '511152';
        /**                                 
         * @var string 该设备已被添加              
         */                                 
        const HIK_EXISTENT_DEVICE           = '511157';
        /**                                 
         * @var string 设备存在高风险需要确权          
         */                                 
        const HIK_HIGH_RISK_TO_CONFIRMATION = '511193';
        /**
         * @var string 该人员不存在
         */
        const HIK_NON_EXISTENT_PERSON       = '511046';
        /**
         * @var string 卡号已被使用
         */
        const HIK_IC_CARD_EXISTENT          = '511106';

        /**
         * @var string 加密未开启，无需关闭.
         */
        const HIK_ENCRYPTION_NOT_ENABLED_NOT_CLOSE    = '510213';



        /** @var string 海康 redis tag */
        const HIK_JOB_REDIS_TAG = 'HikInteractiveTag';
        /** @var string 海康 下发队列 redis cacheKey */
        const HIK_TRAITS_REDIS_KEY = 'HikInteractive:traits:';
        /** @var string 海康 队列 redis cacheKey */
        const HIK_JOB_REDIS_KEY = 'HikInteractive:job:';
        /** @var string 海康 事件 redis cacheKey */
        const HIK_EVENT_REDIS_KEY = 'HikInteractive:event:';
        /** @var string 海康 接口 redis cacheKey */
        const HIK_REQUEST_REDIS_KEY = 'HikInteractive:request:';
        /** @var integer 海康 消息id 缓存时长 单位秒 */
        const HIK_CONSUMER_ID_REDIS_TIMES = 15;
        /** @var integer 海康 消息2次拉取间隔 单位秒 */
        const HIK_CONSUMER_REPEATED_TIMES = 10;
        /** @var integer 海康 接口 缓存时长 单位秒 */
        const HIK_REQUEST_REDIS_TIMES = 120;

        /** @var string 海康智慧社区管理平台 redis tag */
        const HIK_COMMUNITY_JOB_REDIS_TAG = 'HikCommunityInteractiveTag';
        /** @var string 海康智慧社区管理平台 下发队列 redis cacheKey */
        const HIK_COMMUNITY_TRAITS_REDIS_KEY = 'HikCommunityInteractive:traits:';
        /** @var string 海康智慧社区管理平台 队列 redis cacheKey */
        const HIK_COMMUNITY_JOB_REDIS_KEY = 'HikCommunityInteractive:job:';

        /** @var string Hik Device Gateway 海康网关 redis tag */
        const HIK_DEVICE_GATE_WAY_JOB_REDIS_TAG = 'HikDeviceGateWayInteractiveTag';
        /** @var string Hik Device Gateway 海康网关 下发队列 redis cacheKey */
        const HIK_DEVICE_GATE_WAY_TRAITS_REDIS_KEY = 'HikDeviceGateWayInteractive:traits:';
        /** @var string Hik Device Gateway 海康网关 redis cacheKey */
        const HIK_DEVICE_GATE_WAY_JOB_REDIS_KEY = 'HikDeviceGateWayInteractive:job:';
        

        //***************************消息类型**********************************//
        /** 注：6000C场景，不支持边缘侧门禁权限下发状态订阅消息和广告下发状态订阅消息 */
        
        /** @var string community_message_community            社区订阅消息 社区资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_COMMUNITY          = 'community_message_community';
        /** @var string community_message_building             楼栋订阅消息  楼栋资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_BUILDING           = 'community_message_building';
        /** @var string community_message_unit                 单元订阅消息  单元资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_UNIT               = 'community_message_unit';
        /** @var string community_message_room                 户室订阅消息  房屋资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_ROOM               = 'community_message_room';
        /** @var string community_message_person               人员订阅消息 人员资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_PERSON             = 'community_message_person';
        /** @var string community_message_device               设备订阅消息 设备资料变动的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_DEVICE             = 'community_message_device';
        /** @var string community_message_access_state         门禁权限下发状态订阅消息 门禁权限下发状态的推送消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE       = 'community_message_access_state';
        /** @var string community_message_visitor_access       访客门禁权限下发状态订阅消息 访客门禁权限下发状态订阅消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_VISITOR_ACCESS     = 'community_message_visitor_access';
        /** @var string community_event_access                 门禁事件订阅消息 门禁事件订阅的推送消息 */
        const HIK_EVENT_COMMUNITY_EVENT_ACCESS               = 'community_event_access';
        /** @var string community_event_alarm                  报警事件订阅消息 报警事件订阅的推送消息 */
        const HIK_EVENT_COMMUNITY_EVENT_ALARM                = 'community_event_alarm';
        /** @var string community_event_intercom               对讲事件订阅消息 对讲事件订阅的推送消息 */
        const HIK_EVENT_COMMUNITY_EVENT_INTERCOM             = 'community_event_intercom';
        /** @var string community_message_advert_state         广告下发状态订阅消息	广告下发状态订阅消息 */
        const HIK_EVENT_COMMUNITY_ADVERT_STATE               = 'community_message_advert_state';
        /** @var string community_message_audit_state          房屋审核通知消息 房屋审核通知订阅消息 */
        const HIK_EVENT_COMMUNITY_MESSAGE_AUDIT_STATE        = 'community_message_audit_state';
        /** @var string community_event_parking_passVehicle    停车场车辆出入通知消息 停车场车辆出入通知订阅消息 */
        const HIK_EVENT_COMMUNITY_EVENT_PARKING_PASS_VEHICLE = 'community_event_parking_passVehicle';
        
        // 消息码对照表
        /** @var integer 人脸权限下发 */
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE           = 10019;
        /** @var integer 指纹权限下发 */                                         
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FINGER         = 10020;
        /** @var integer 卡号权限下发 */                                         
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_CARD           = 10021;
        /** @var integer 访客人脸下发 */                                         
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_VISITOR        = 10022;
        /** @var integer 访客二维码下发 */                                        
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_VISITOR_QR     = 10023;
        /** @var integer 动态密码权限下发 */                                       
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_ACTIVE_PASS    = 10024;
        /** @var integer 人脸权限删除 */                                         
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE_DELETE    = 10027;
        /** @var integer 人员权限下发的全量状态 */                                    
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_PERSON_ALL     = 10028;
        /** @var integer 人员权限删除成功 */
        const HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_SUCCESS_PERSON_DELETE = 10029;
        
	}