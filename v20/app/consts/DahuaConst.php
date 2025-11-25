<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      大华相关常量
	 */

	namespace app\consts;

	class DahuaConst
	{
        /** @var string 匹配模式 自动匹配同步 */
        const OPERATE_AUTO_SYN_DATA = 'auto_syn';
        /** @var string 匹配模式 手动匹配同步 */
        const OPERATE_HAND_SYN_DATA = 'hand_syn';



        /*** @var string 配置项缺失返回报错 2001*/
        const ERR_DH_NOT_CONFIGURED_CODE = 2001;
        /*** @var string 配置项缺失返回报错*/
        const ERR_DH_NOT_CONFIGURED_MESSAGE = '请前往 系统后台-智慧社区-社区配置-大华云睿进行配置';
        /*** @var string 缺少父组织编码 2002*/
        const ERR_DH_NOT_PORGCODE_CODE = 2002;
        /*** @var string 缺少父组织编码 返回报错*/
        const ERR_DH_NOT_PORGCODE_MESSAGE = '缺少父组织编码';
        /*** @var string 缺少组织编号 2003*/
        const ERR_DH_NOT_ORG_CODE_CODE = 2003;
        /*** @var string 缺少组织编号 返回报错*/
        const ERR_DH_NOT_ORG_CODE_MESSAGE = '缺少组织编号';
        /*** @var string 设备类型不存在 2044*/
        const ERR_DH_NOT_DEVICE_TYPE_CODE = 2044;
        /*** @var string 设备类型不存在 返回报错*/
        const ERR_DH_NOT_DEVICE_TYPE_MESSAGE = '设备类型不存在';
        /*** @var string 设备类型不存在 2044*/
        const ERR_DH_NOT_STORE_ID_CODE = 2004;
        /*** @var string 缺少场所ID 返回报错*/
        const ERR_DH_NOT_STORE_ID_MESSAGE = '缺少场所ID';
        /*** @var string 设备不存在 2005*/
        const ERR_DH_NOT_DEVICE_CODE = 2005;
        /*** @var string 设备不存在 返回报错*/
        const ERR_DH_NOT_DEVICE_MESSAGE = '设备不存在';
        /*** @var string 设备序列号不存在 2006*/
        const ERR_DH_NOT_DEVICE_ID_CODE = 2006;
        /*** @var string 设备序列号不存在 返回报错*/
        const ERR_DH_NOT_DEVICE_ID_MESSAGE = '设备序列号不存在';
        /*** @var string 编号不在范围内 2007*/
        const ERR_DH_NOT_RANGE_CODE = 2007;
        /*** @var string 编号不在范围内 返回报错*/
        const ERR_DH_NOT_RANGE_MESSAGE = '楼栋序号不在范围内';
        /*** @var string 人员id不存在 2008*/
        const ERR_DH_NOT_PERSON_FILED_ID_CODE = 2008;
        /*** @var string 编号不在范围内 返回报错*/
        const ERR_DH_NOT_PERSON_FILED_ID_MESSAGE = '人员id不存在';
        
        /**
         * @var string 场所组织不存在
         */
        const DH_NON_EXISTENT_STORE = 'MEM1001';
        /**
         * @var string 场所组织已经存在
         */
        const DH_IS_EXISTENT_STORE = 'MEM1002';
        /**
         * @var string 该视频直播已存在
         * 'LV1001'  '该视频直播已存在'
         * '500'     '该视频已在其他地方使用，请重新绑定设备后，再创建'
         */
        const DH_IS_EXISTENT_LIVE = ['LV1001','500','MP130007'];
        
        /** @var string 重复添加设备错误 */
        const DH_REPEAT_DEVICE_MESSAGE = '设备已被当前账号绑定';
        
        /****************** 针对表pigcms_fingerprint_device中access_type字段  *********************/
        /** @var string 大华NB指纹锁设备 */
        const DH_NB_FINGERPRINT_LOCK_DEVICE = 'dh_nb_lock';


        /** @var string 大华 redis tag */
        const DH_JOB_REDIS_TAG = 'DHInteractiveTag';
        /** @var string 大华 下发队列 redis cacheKey */
        const DH_TRAITS_REDIS_KEY = 'DHInteractive:traits:';
        /** @var string 大华 队列 redis cacheKey */
        const DH_JOB_REDIS_KEY = 'DHInteractive:job:';
        /** @var string 大华 添加人员 redis cacheKey */
        const DH_JOB_REDIS_PERSON_PROFILE_KEY = 'DHInteractive:personProfile:';
        /** @var string 大华 接口 redis cacheKey */
        const DH_REQUEST_REDIS_KEY = 'DHInteractive:request:';
        /** @var string 大华 异步授权 redis cacheKey */
        const DH_BATCH_REDIS_KEY = 'DHInteractive:batch:';
        /** @var integer 大华 接口 缓存时长 单位秒 */
        const DH_REQUEST_REDIS_TIMES = 5;
	}