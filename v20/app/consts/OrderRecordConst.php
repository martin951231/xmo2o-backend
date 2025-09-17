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

    class OrderRecordConst
    {
        /** ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 业务来源  表-common_order_business_record-字段-business_source. ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ **/
        /**
         * 小区业务
         */
        const BUSINESS_SOURCE_VILLAGE = 'village';

        /**
         * 物业业务
         */
        const BUSINESS_SOURCE_PROPERTY = 'property';

        /**
         * 商家业务
         */
        const BUSINESS_SOURCE_MERCHANT = 'merchant';

        /**
         * 店铺业务
         */
        const BUSINESS_SOURCE_STORE = 'store';

        /**
         * 机器人业务
         */
        const BUSINESS_SOURCE_ROBOT = 'robot';
        /** ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 业务来源  表-common_order_business_record-字段-business_source. ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ **/




        /** ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 设备类型  表-common_order_business_record-字段-device_type. ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ **/
        /**
         * 人脸设备 关联表-house_face_device
         */
        const DEVICE_TYPE_FACE = 'face';

        /**
         * 监控设备 关联表-house_camera_device
         */
        const DEVICE_TYPE_CAMERA = 'camera';

        /**
         * 艾特充-充电桩设备 关联表-house_village_pile
         */
        const DEVICE_TYPE_PILE_HZ = 'pileHz';

        /**
         * 杂淘（kumike[酷米客]）-充电桩设备 关联表-house_village_pile_equipment
         */
        const DEVICE_TYPE_PILE = 'pile';

        /**
         * 工泰【分直流和交流】-汽车充电桩设备 关联表-house_new_pile_equipment
         */
        const DEVICE_TYPE_NEW_PILE = 'newPile';

        /**
         * 达闼-机器人设备 关联表-third_robot_device
         */
        const DEVICE_TYPE_ROBOT = 'robot';
        /** ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 设备类型  表-common_order_business_record-字段-device_type. ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ **/


        /** ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ 订单类型  表-common_order_business_record-字段-order_type. ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓ **/
        /**
         * 订单类型-杂淘（kumike[酷米客]）充电桩设备订单表 关联表-house_village_pile_pay_order
         */
        const ORDER_TYPE_PILE_TABLE = 'house_village_pile_pay_order';

        /** ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ 订单类型  表-common_order_business_record-字段-order_type. ↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑ **/

    }