<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      三方导入数据常量
	 */

	namespace app\consts;

	class thirdImportDataConst
	{
        /** @var int 房产表导入数据类型 */
        const THIRD_YWYL_HOUSE_DATA      = 1;
        /** @var int 业主表导入数据类型 */
        const THIRD_YWYL_USER_DATA       = 2;
        /** @var int 收费表导入数据类型 */
        const THIRD_YWYL_CHARGE_DATA     = 3;

        /*** 出售状态 1正常居住 2出售中 3空关（未入住） 4停用中 5 出售中 **/
        /** @var int 正常居住 */
        const USER_STATUS_LIVE           = 1;
        /** @var int 空关（未入住） */          
        const USER_STATUS_NOT_LIVE       = 2;
        /** @var int 出租中 */              
        const USER_STATUS_LEASE          = 3;
        /** @var int 停用中 */              
        const USER_STATUS_STOP           = 4;
        /** @var int 出售中 */              
        const USER_STATUS_SELL           = 5;
        /** @var int 空置 */               
        const USER_STATUS_VACANT         = 6;
        /** @var int 装修 */               
        const USER_STATUS_RENOVATION     = 7;
        /** @var int 未收房 */
        const USER_STATUS_UNCHECKED_ROOM = 8;

        // 出售 空关 入住 出租 装修 停用 空置 未收房
        /***房产状态 1出售 2空关（未入住） 3入住  4空置 5出租 6停用 7装修 8未收房  **/
        /** @var int 房产状态 1出租 */
        const HOUSE_STATUS_SELL           = 1;
        /** @var int 房产状态 2空关（未入住） */
        const HOUSE_STATUS_NOT_LIVE       = 2;
        /** @var int 房产状态 3入住 */          
        const HOUSE_STATUS_LIVE           = 3;
        /** @var int 房产状态 4空置 */          
        const HOUSE_STATUS_VACANT         = 4;
        /** @var int 房产状态 5出租 */          
        const HOUSE_STATUS_LEASE          = 5;
        /** @var int 房产状态 6停用 */          
        const HOUSE_STATUS_STOP           = 6;
        /** @var int 房产状态 7装修 */          
        const HOUSE_STATUS_RENOVATION     = 7;
        /** @var int 房产状态 8未收房 */         
        const HOUSE_STATUS_UNCHECKED_ROOM = 8;

        /***
         * 房产类型 
         *  0未设置 1多层 2小高层 3高层 4别墅 5排屋 
         *  6储藏室 7自行车库 8写字楼 9商铺 10商场 
         *  11会所 12办公用房 13保姆房 14酒店 15其它 
         *  16车库 17车位 18广告位 *
         */
        /** @var int 房产类型 0未设置 */
        const HOUSE_TYPE_NOT_SET           = 0;
        /** @var int 房产类型 1多层  */
        const HOUSE_TYPE_MULTI_STOREY      = 1;
        /** @var int 房产类型 2小高层  */
        const HOUSE_TYPE_SMALL_HEIGHT_RISE = 2;
        /** @var int 房产类型 3高层  */
        const HOUSE_TYPE_HEIGHT_RISE       = 3;
        /** @var int 房产类型 4别墅  */
        const HOUSE_TYPE_VILLA             = 4;
        /** @var int 房产类型 5排屋  */
        const HOUSE_TYPE_TOWNHOUSE         = 5;
        /** @var int 房产类型 6储藏室  */
        const HOUSE_TYPE_STOREROOM         = 6;
        /** @var int 房产类型 7自行车库  */
        const HOUSE_TYPE_BICYCLE_PARKING   = 7;
        /** @var int 房产类型 8写字楼  */
        const HOUSE_TYPE_OFFICE_BUILDING   = 8;
        /** @var int 房产类型 9商铺  */
        const HOUSE_TYPE_SHOPS             = 9;
        /** @var int 房产类型 10商场  */
        const HOUSE_TYPE_MARKET            = 10;
        /** @var int 房产类型 11会所  */
        const HOUSE_TYPE_CLUB              = 11;
        /** @var int 房产类型 12办公用房  */
        const HOUSE_TYPE_OFFICE_SPACE      = 12;
        /** @var int 房产类型 13保姆房  */
        const HOUSE_TYPE_NANNY_ROOM        = 13;
        /** @var int 房产类型 14酒店  */
        const HOUSE_TYPE_HOTEL             = 14;
        /** @var int 房产类型 15其它  */
        const HOUSE_TYPE_OTHER             = 15;
        /** @var int 房产类型 16车库  */
        const HOUSE_TYPE_GARAGE            = 16;
        /** @var int 房产类型 17车位  */
        const HOUSE_TYPE_PARKING_LOT       = 17;
        /** @var int 房产类型 18广告位  */
        const HOUSE_TYPE_ADSENSE           = 18;
        
        /*** 
         * 房产性质
         * 1商品房 2经济适用房 3房改房 
         */
        /** @var int 房产类型 1商品房 */
        const HOUSE_TYPE_COMMERCIAL_HOUSING = 1;
        /** @var int 房产类型 2经济适用房 */
        const HOUSE_TYPE_AFFORDABLE_HOUSING = 2;
        /** @var int 房产类型 3房改房 */
        const HOUSE_TYPE_HOUSING_REFORM     = 3;
        
        
        /*** handle_status 处理状态 ***/
        /** @var int 未处理 */
        const HANDLE_STATUS_NOT        = 0;
        /** @var int 处理中 */
        const HANDLE_STATUS_PROCESSING = 1;
        /** @var int 处理成功 */
        const HANDLE_STATUS_SUCCESS    = 2;
        /** @var int 处理失败 */
        const HANDLE_STATUS_FAIL       = 3;
        /** @var int 删除 */
        const HANDLE_STATUS_DELETE     = 4;
        
        
        /**** member_relation 与户主关系 ***/
        /** @var int 业主 */
        const HOUSEHOLDER_RELATIONSHIP_OWNER    = 0;
        /** @var int 家属 */                      
        const HOUSEHOLDER_RELATIONSHIP_FAMILY   = 1;
        /** @var int 租客 */                      
        const HOUSEHOLDER_RELATIONSHIP_TENANT   = 2;
        /** @var int 配偶 */                      
        const HOUSEHOLDER_RELATIONSHIP_SPOUSE   = 11;
        /** @var int 父母 */                      
        const HOUSEHOLDER_RELATIONSHIP_PARENT   = 12;
        /** @var int 子女 */
        const HOUSEHOLDER_RELATIONSHIP_CHILDREN = 13;
        /** @var int 亲朋好友 */
        const HOUSEHOLDER_RELATIONSHIP_FRIENDS  = 15;

        /**** member_sex 性别 ***/
        /** @var int 未知 */
        const MEMBER_SEX_UNKNOWN = 0;
        /** @var int 男 */
        const MEMBER_SEX_MALE    = 1;
        /** @var int 女 */
        const MEMBER_SEX_FEMALE  = 2;


        /**** member_card_type 证件类型 ***/
        /** 
         * 居民身份证、临时身份证、户口簿、居民身份证（台）
         * 军官证、警官证、士兵证、军事学院证、军官退休证、文职干部证、文职干部退休证、离休干部荣誉证、武警警官证、军队学员证、军队文职干部证、军队离退休干部证和军队职工证
         * 护照、港澳台同胞来往通行证、港澳同胞回乡证、港澳居民来往内地通行证、中华人民共和国来往港澳通行证、台湾居民来往大陆通行证、大陆居民往来台湾通行证、
         * 边民出入境通行证、外国人永久居留证、外国人居留证、外国人出入境证、外交官证、领事馆证、
         * 海员证
         * 其他
         */
        /** @var int 无 */
        const CARD_TYPE_NOTHING                                                           = 0;
        /** @var int 居民身份证 */
        const CARD_TYPE_RESIDENT_ID_CARD                                                  = 1;
        /** @var int 临时身份证 */                                                             
        const CARD_TYPE_TEMPORARY_ID_CARD                                                 = 2;
        /** @var int 户口簿 */                                                               
        const CARD_TYPE_RESIDENCE_BOOKLET                                                 = 3;
        /** @var int 居民身份证（台） */                                                          
        const CARD_TYPE_RESIDENT_ID_CARD_TAIWAN                                           = 4;
        /** @var int 军官证 */                                                               
        const CARD_TYPE_CERTIFICATE_OF_OFFICERS                                           = 5;
        /** @var int 警官证 */                                                               
        const CARD_TYPE_POLICE_OFFICER_CERTIFICAT                                         = 6;
        /** @var int 士兵证 */                                                               
        const CARD_TYPE_SOLDIER_ID                                                        = 7;
        /** @var int 军事学院证 */                                                             
        const CARD_TYPE_MILITARY_ACADEMY_CERTIFICATE                                      = 8;
        /** @var int 军官退休证 */                                                             
        const CARD_TYPE_OFFICER_RETIREMENT_CERTIFICATE                                    = 9;
        /** @var int 文职干部证 */                                                             
        const CARD_TYPE_CIVILIAN_CADRE_CERTIFICATE                                        = 10;
        /** @var int 文职干部退休证 */                                                           
        const CARD_TYPE_RETIREMENT_CERTIFICATE_FOR_CIVILIAN_CADRES                        = 11;
        /** @var int 离休干部荣誉证 */                                                           
        const CARD_TYPE_HONORARY_CERTIFICATE_FOR_RETIRED_CADRES                           = 12;
        /** @var int 武警警官证 */                                                             
        const CARD_TYPE_ARMED_POLICE_OFFICER_CERTIFICATE                                  = 13;
        /** @var int 军队学员证 */                                                             
        const CARD_TYPE_MILITARY_STUDENT_CERTIFICATE                                      = 14;
        /** @var int 军队文职干部证 */                                                           
        const CARD_TYPE_MILITARY_CIVILIAN_CADRE_CERTIFICATE                               = 15;
        /** @var int 军队离退休干部证和军队职工证 */
        const CARD_TYPE_MILITARY_RETIRED_CADRE_CERTIFICATE_AND_MILITARY_STAFF_CERTIFICATE = 16;
        /** @var int 护照 */
        const CARD_TYPE_PASSPORT                                                          = 17;
        /** @var int 港澳台同胞来往通行证 */
        const CARD_TYPE_TRAVEL_PERMIT_FOR_COMPATRIOTS_FROM_HONG_KONG_MACAO_AND_TAIWAN     = 18;
        /** @var int 港澳同胞回乡证 */
        const CARD_TYPE_HOME_VISITING_CERTIFICATE_FOR_HONG_KONG_AND_MACAO_COMPATRIOTS     = 19;
        /** @var int 港澳居民来往内地通行证 */
        const CARD_TYPE_MAINLAND_TRAVEL_PERMIT_FOR_HONG_KONG_AND_MACAO_RESIDENTS          = 20;
        /** @var int 中华人民共和国来往港澳通行证 */
        const CARD_TYPE_PERMIT_OF_THE_PEOPLES_REPUBLIC_OF_CHINA_TO_HONG_KONG_AND_MACAO    = 21;
        /** @var int 台湾居民来往大陆通行证 */
        const CARD_TYPE_TRAVEL_PASSES_FOR_TAIWAN_RESIDENTS_TO_ENTER_OR_LEAVE_THE_MAINLAND = 22;
        /** @var int 大陆居民往来台湾通行证 */
        const CARD_TYPE_MAINLAND_RESIDENTS_TRAVEL_PERMIT_TO_TAIWAN                        = 23;
        /** @var int 边民出入境通行证 */
        const CARD_TYPE_FRONTIER_EXIT_ENTRY_PERMIT                                        = 24;
        /** @var int 外国人永久居留证 */
        const CARD_TYPE_FOREIGNERS_PERMANENT_RESIDENCE_PERMIT                             = 25;
        /** @var int 外国人居留证 */
        const CARD_TYPE_ALIEN_RESIDENCE_PERMIT                                            = 26;
        /** @var int 外国人出入境证 */
        const CARD_TYPE_ALIEN_EXIT_ENTRY_PERMIT                                           = 27;
        /** @var int 外交官证 */
        const CARD_TYPE_DIPLOMATIC_CERTIFICATE                                            = 28;
        /** @var int 领事馆证 */
        const CARD_TYPE_CONSULATE_CARD                                                    = 29;
        /** @var int 海员证 */
        const CARD_TYPE_SEAMANS_CARD                                                      = 30;
        /** @var int 其他 */
        const CARD_TYPE_OTHER                                                             = 31;
        
        
        
        
        /** @var string 三方EXCEL业主信息导入 */
        const THIRD_YWYL_OWNER_EXCEL = 'third_ywyl_owner_excel';
        
	}