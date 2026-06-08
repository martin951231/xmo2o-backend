<?php
/**
 * LinkService.php
 * 链接库service
 * Create on 2020/11/14 11:58
 * Created by zhumengqun
 */

namespace app\common\model\service;

use app\common\model\db\AppointCategory;
use app\common\model\db\ClassifyCategory;
use app\common\model\db\GiftCategory;
use app\common\model\db\GoodsCategory;
use app\common\model\db\GoodsWholesaleCategory;
use app\common\model\db\GroupCategory;
use app\common\model\db\MicroPageDecorate;
use app\common\model\db\PortalActivityCat;
use app\common\model\db\PortalArticleCat;
use app\common\model\db\ShopCategory;
use app\common\model\db\Special;
use app\common\model\db\SystemNewsCategory;
use app\common\model\db\VillageGroupCategory;
use app\common\model\db\MerchantCategory;
use app\foodshop\model\db\MealStoreCategory;
use app\grow_grass\model\service\GrowGrassCategoryService;
use app\mall\model\db\MallCategory;
use app\atlas\model\db\AtlasCategory;
use app\merchant\model\db\MerchantStore;
use app\community\model\service\HouseVillageService;

class LinkService
{
    public function __construct()
    {
        if (cfg('system_type') == 'village') {
            $this->base_url = '/packapp/village/';
        } else {
            $this->base_url = '/packapp/plat/';
        }

        $this->listArr = ['gymk', 'wmsy', 'wmdd', 'wmflym', 'wmzt', 'scsy', 'sczt', 'scflym', 'schdym', 'xbscsy', 'xbsczt', 'xbscflym', 'xbschdym', 'cysy', 'cydd', 'cyflym', 'xbcysy', 'xbcyflym', 'xbcydd', 'tgsy', 'tgdpsy', 'tglj', 'yysy', 'yyfl', 'yydd', 'sqtgsy', 'sqtgfl', 'jfhgsy', 'jfhgfl', 'ptkbsy', 'ptkbfl', 'flxx', 'yszc', 'pfsc', 'mhgn', 'yxhd', 'atlas', 'ymlb', 'ptgj', 'yxgj', 'gnym','diyPageCategory','growGrassIndex','growGrassCategory','marriage', 'xbtgfllj','scenic','sport','employeeCard','newsCenic', 'ticketAppoint','giftMallCommonPage','yyjslb','yhxy'];
    }

    public function getLinkCategory($source, $source_id, $type)
    {
        if ($source == 'merchant') {
            $catArr = [
                [
                    'label' => 'diyPage',
                    'txt' => '微页面'
                ],
                [
                    'label' => 'matchingTools',
                    'txt' => '配套工具'
                ],
                [
                    'label' => 'marketingTools',
                    'txt' => '营销工具'
                ],
                [
                    'label' => 'functionPage',
                    'txt' => '功能页面'
                ],
            ];
        } elseif ($source == 'store') {
            $catArr = [
                [
                    'label' => 'diyPage',
                    'txt' => '微页面'
                ],
                [
                    'label' => 'matchingTools',
                    'txt' => '配套工具'
                ],
                [
                    'label' => 'marketingTools',
                    'txt' => '营销工具'
                ],
                [
                    'label' => 'functionPage',
                    'txt' => '功能页面'
                ],
            ];
        } else {
            $catArr = [
                [
                    'label' => 'commonPages',
                    'txt' => '常用页面'
                ],
                [
                    'label' => 'commonModules',
                    'txt' => '公用模块'
                ],
                [
                    'label' => 'storeCategory',
                    'txt' => '店铺分类'
                ],
                [
                    'label' => 'waimaiBusiness',
                    'txt' => cfg('shop_alias_name')
                ],
                [
                    'label' => 'newMallBusiness',
                    'txt' => cfg('mall_alias_name_new')
                ],
                [
                    'label' => 'newFoodshopBusiness',
                    'txt' => cfg('meal_alias_name')
                ],
                [
                    'label' => 'groupBusiness',
                    'txt' => cfg('group_alias_name')
                ],
                [
                    'label' => 'bookingBusiness',
                    'txt' => cfg('appoint_alias_name')
                ],
                [
                    'label' => 'communityGroupBuying',
                    'txt' => cfg('village_group_alias')
                ],
                [
                    'label' => 'scenic',
                    'txt' => cfg('life_tools_scenic_alias_name')
                ],
                [
                    'label' => 'growGrass',
                    'txt' => cfg('grow_grass_alias')
                ],
                [
                    'label' => 'platformNews',
                    'txt' => '平台快报'
                ],
                [
                    'label' => 'diyPage',
                    'txt' => '微页面'
                ],
                [
                    'label' => 'otherBusiness',
                    'txt' => '其他业务'
                ]
            ];


            //积分商城
            if(cfg('gift_alias_name')){
                $catArr[]=[
                    'label' => 'giftMall',
                    'txt' => cfg('gift_alias_name')
                ];
            }


            $pluginLabels = (new \app\common\model\db\Plugin())->where(['status' => 1, 'show' => 1])->column('plugin_label');
            $showMatch = [
                'waimaiBusiness' => in_array('shop', $pluginLabels),
                'newMallBusiness' => in_array('mall', $pluginLabels),
                'newFoodshopBusiness' => in_array('dining', $pluginLabels),
                'groupBusiness' => in_array('group', $pluginLabels),
                'bookingBusiness' => in_array('appoint', $pluginLabels),
                'communityGroupBuying' => in_array('villagegroup', $pluginLabels),
                'growGrass' => in_array('grow_grass', $pluginLabels),
                'giftMall' => in_array('scoremall', $pluginLabels),
                'scenic'=> in_array('life_tools', $pluginLabels),
            ];
            foreach($catArr as $key => $value){
				//删除老餐饮
				if(cfg('delete_foodshop_v1') && $value['label'] == 'foodshopBusiness'){
					unset($catArr[$key]);
				}
				if(cfg('delete_foodshop_v1') && $value['label'] == 'newFoodshopBusiness'){
					$catArr[$key]['txt'] = cfg('meal_alias_name');
				}

				//删除老商城
				if(cfg('delete_mall_v1') && $value['label'] == 'mallBusiness'){
					unset($catArr[$key]);
				}
				if(cfg('delete_mall_v1') && $value['label'] == 'newMallBusiness'){
					$catArr[$key]['txt'] = cfg('mall_alias_name_new');
				}

                //删除积分换购
                if($value['label'] == 'pointsExchange'){
					unset($catArr[$key]);
                    continue;
				}

                //根据插件判断功能库是否显示
                if(isset($showMatch[$value['label']]) && !$showMatch[$value['label']]){
                    unset($catArr[$key]);
                    continue;
                }
			}

            //问卷调查
            $plugin = (new \app\common\model\db\Plugin())->where(['status' => 1, 'show' => 1, 'plugin_label' => 'questionnaire_survey'])->find();
            if ($plugin) {
                $catArr[] = [
                    'label' => 'questionnaireSurvey',
                    'txt' => $plugin['plugin_name']
                ];
            }

            //社群活动分类
            $social_activity_plugin = (new \app\common\model\db\Plugin())->where(['status' => 1, 'show' => 1, 'plugin_label' => 'social_activity'])->find();
			if($social_activity_plugin){
                $catArr[] = [
                    'label' => 'socialActivity',
                    'txt' => $social_activity_plugin['plugin_name']
                ];
            }
            $catArr = array_values($catArr);
        }
        return $catArr;
    }

    public function getLinkContent($label, $systemUser)
    {
        if (empty($label)) {
            throw new \think\Exception('参数错误');
        }
        switch ($label) {
            case 'commonPages':
                $arr = $this->getcommonPages();
                break;
            case 'commonModules':
                $arr = $this->getCommonModules();
                break;
            case 'waimaiBusiness':
                $arr = $this->getWaimaiBusiness();
                break;
            case 'mallBusiness':
                $arr = $this->getMallBusiness($systemUser);
                break;
            case 'newMallBusiness':
                $arr = $this->getNewMallBusiness($systemUser);
                break;
            case 'foodshopBusiness':
                $arr = $this->getFoodshopBusiness();
                break;
            case 'newFoodshopBusiness':
                $arr = $this->getNewFoodshopBusiness();
                break;
            case 'groupBusiness':
                $arr = $this->getGroupBusiness();
                break;
            case 'bookingBusiness':
                $arr = $this->getBookingBusiness();
                break;
            case 'communityGroupBuying':
                $arr = $this->getCommunityGroupBuying();
                break;
            case 'pointsExchange':
                $arr = $this->getPointsExchange();
                break;
            case 'platformNews':
                $arr = $this->getPlatformNews();
                break;
            case 'otherBusiness':
                $arr = $this->getOtherBusiness();
                break;
            case 'diyPage':
                $arr = $this->getDiyPage();
                break;
            case 'matchingTools':
                $arr = $this->getMatchingTools();
                break;
            case 'marketingTools':
                $arr = $this->getMarketingTools();
                break;
            case 'functionPage':
                $arr = $this->getFunctionPage();
                break;
            case 'storeCategory':
                $arr = $this->storeCategory();
                break;
            case 'giftMall': //积分商城
                $arr = $this->giftMall();
                break;
            case 'scenic': //景区
                $arr = $this->getScenic();
                break;
            default:
                $arr = $this->$label();
                break;
        }
        return $arr;
    }

    /**
     * @param $label
     * @param $keyword
     * 获取列表内容
     */
    public function getList($label, $systemUser, $keyword = '', $page = '', $pageSize = '', $source = 'platform', $source_id = 0, $store_id = 0)
    {
        if (empty($label)) {
            throw new \think\Exception('label参数缺失');
        }
        // foreach ($this->listArr as $key => $val) {
        //     if ($val == $label) {
        //         $res = $this->$label($systemUser, $keyword, $page, $pageSize, $source, $source_id, $store_id);
        //         break;
        //     }
        // }

        //判断$label方法是否存在
        if (!method_exists($this, $label)) {
            throw new \think\Exception('未找到该分类('.$label.')');
        }
        $res = $this->$label($systemUser, $keyword, $page, $pageSize, $source, $source_id, $store_id);

        return $res;

    }

    private function storeCategory()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '分类装修页面', 'label' => 'diyPageCategory']]
            ]
        ];
        return $arr;
    }

    private function diyPageCategory()
    {
        $arr = [
            'add_link' => false,//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类名称', 'label' => 'flym']],
                'body' => $this->getMerchantCategory()
            ]
        ];
        return $arr;
    }

    /**
     *店铺装修页面分类
     */
    public function getMerchantCategory()
    {
        $list = (new MerchantCategory())->getCategory();
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                $item = [
                    'cat_id' => $val['cat_id'],
                    'cat_fid' => $val['cat_fid'],
                    'url' => cfg('site_url') . '/packapp/platn/pages/store/v1/classifyPrimary/index?source_id=' . $val['cat_id'],
                    'flym' => [
                        'image' => '',
                        'txt' => $val['cat_name'],
                        'url' => '',
                    ]
                ];
                $farr[] = $item;//一级
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    $items = array();
                    $where=[['cat_fid','=',$val1['cat_id']],['cat_status','=',1]];
                    $arr=(new MerchantCategory())->getSome($where,true,'cat_sort DESC, cat_id DESC')->toArray();
                    foreach ($arr as $k=>$v){
                        $item1 = [
                            'cat_id' => $v['cat_id'],
                            'cat_fid' => $v['cat_fid'],
                            'url' => cfg('site_url') . '/packapp/platn/pages/store/v1/classifySecondary/index?source_id=' . $v['cat_id'],
                            'flym' => [
                                'image' => '',
                                'txt' => $v['cat_name'],
                                'url' => '',
                            ]
                        ];
                        $items[] = $item1;//一级
                    }
                    $farr[$key]['children'] = $items;
                }
            }
        }
        return $farr;

    }

    private function getcommonPages()
    {
        $platIndex =  cfg('site_url') . '/wap.php?g=Wap&c=Home&a=index&no_house=1';
        $platImage = cfg('site_url') . '/v20/public/static/common/link/ptsy.png';
        $myIndex =  get_base_url('pages/plat_menu/my');
        $myImage = cfg('site_url') . '/v20/public/static/common/link/ptgrzx.png';

        if(in_array(cfg('single_system_type'),['shopping_mall'])){
            $platIndex = get_base_url('pages/shoppingMall/index');
            $platImage = cfg('site_url') . '/v20/public/static/common/link/mall_index.png';
            $myIndex = get_base_url('pages/shoppingMall/my');
            $myImage = cfg('site_url') . '/v20/public/static/common/link/mall_my.png';
        }

        $arr = [
            'style' => 'btn',
            'btn_list' => [
                ['icon' => 'iconshouye1', 'image' => $platImage, 'txt' => '平台首页', 'url' => $platIndex],
                ['icon' => 'icondingdan', 'image' => cfg('site_url') . '/v20/public/static/common/link/ptgrdd.png', 'txt' => '平台个人订单', 'url' => get_base_url('pages/my/my_order') ],
                ['icon' => 'iconlingquanzhongxin', 'image' => cfg('site_url') . '/v20/public/static/common/link/wdyhq.png', 'txt' => '我的优惠券', 'url' => get_base_url('pages/coupon/myCoupon')],
                ['icon' => 'iconrili', 'image' => cfg('site_url') . '/v20/public/static/common/link/lqzx.png', 'txt' => '领券中心', 'url' => get_base_url('pages/coupon/index')],
                ['icon' => 'iconiconfront-', 'image' => $myImage, 'txt' => '平台个人中心', 'url' => $myIndex],
                ['icon' => 'iconqiandao', 'image' => cfg('site_url') . '/v20/public/static/common/link/qdzx.png', 'txt' => '签到中心', 'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=sign'],
                ['icon' => 'iconhuiyuan', 'image' => cfg('site_url') . '/v20/public/static/common/link/ptyhhyk.png', 'txt' => '平台优惠会员卡', 'url' => get_base_url('pages/membership/platMembership')],
//                ['icon' => 'iconqiandao', 'image' => cfg('site_url') . '/v20/public/static/common/link/zhaopin.png', 'txt' => '求职首页', 'url' => cfg('site_url') . '/packapp/project_employment/pages/index/index'],
            ],
        ];
        return $arr;
    }

    private function getCommonModules()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '公用模块', 'label' => 'gymk']]//空和一个的时候不显示
            ]
        ];
        return $arr;
    }

    private function gymk()
    {
        $service_house_village = new HouseVillageService();
        $app_version = isset($_POST['app_version'])&&$_POST['app_version']?intval($_POST['app_version']):0;
        $deviceId = isset($_POST['Device-Id'])&&$_POST['Device-Id']?trim($_POST['Device-Id']):0;
        $param = [
            'pagePath' => 'pages/village/my/',
            'isAll' => true,
        ];
        $pagesMyUrl = $service_house_village->villagePagePath($app_version,$deviceId,$param);
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 30,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/my/message/list',0),
                        'xzym' => [
                            'image' => '',
                            'txt' => '用户消息中心',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/my/message/list',0)
                        ]
                    ],
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/packapp/merchant/index.html',
                        'xzym' => [
                            'image' => '',
                            'txt' => '商家中心',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/packapp/merchant/index.html'
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/packapp/storestaff/index.html',
                        'xzym' => [
                            'image' => '',
                            'txt' => '店员中心',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/packapp/storestaff/index.html'
                        ]
                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/packapp/deliver/index.html',
                        'xzym' => [
                            'image' => '',
                            'txt' => '配送中心',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/packapp/deliver/index.html'
                        ]
                    ],
                    [
                        'cat_id' => 4,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') .  '/packapp/deliver_web/register.html',
                        'xzym' => [
                            'image' => '',
                            'txt' => '配送员注册',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') .  '/packapp/deliver_web/register.html'
                        ]
                    ],
                    [
                        'cat_id' => 5,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Invitation&a=datelist',
                        'xzym' => [
                            'image' => '',
                            'txt' => '交友',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Invitation&a=datelist'
                        ]
                    ],
                    [
                        'cat_id' => 6,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Third_recharge&a=mobile_recharge',
                        'xzym' => [
                            'image' => '',
                            'txt' => '手机充值',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Third_recharge&a=mobile_recharge'
                        ]
                    ],
                    [
                        'cat_id' => 7,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=City_car&a=index',
                        'xzym' => [
                            'image' => '',
                            'txt' => '扫码挪车',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=City_car&a=index'
                        ]
                    ],
                    [
                        'cat_id' => 8,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Hotel&a=index',
                        'xzym' => [
                            'image' => '',
                            'txt' => '酒店',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Hotel&a=index'
                        ]
                    ],
                    [
                        'cat_id' => 9,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/live_and_video/live/viewer/viewer_list',
                        'xzym' => [
                            'image' => '',
                            'txt' => '直播',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/live_and_video/live/viewer/viewer_list'
                        ]
                    ],
                    [
                        'cat_id' => 10,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Ride&a=ride_list&plat=1',
                        'xzym' => [
                            'image' => '',
                            'txt' => '顺风车',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Ride&a=ride_list&plat=1'
                        ]
                    ],
                    [
                        'cat_id' => 11,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Workerstaff&a=index',
                        'xzym' => [
                            'image' => '',
                            'txt' => cfg('appoint_worker_name') ? (cfg('appoint_worker_name') . '中心') : '技师中心',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Workerstaff&a=index'
                        ]
                    ],
                    [
                        'cat_id' => 12,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Crowdsourcing&a=index',
                        'xzym' => [
                            'image' => '',
                            'txt' => '众包',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Crowdsourcing&a=index'
                        ]
                    ],
                    [
                        'cat_id' => 13,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Service&a=index',
                        'xzym' => [
                            'image' => '',
                            'txt' => '服务快派',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Service&a=index'
                        ]
                    ],
                    [
                        'cat_id' => 14,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Service&a=paotui',
                        'xzym' => [
                            'image' => '',
                            'txt' => '跑腿',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Service&a=paotui'
                        ]
                    ],
                    [
                        'cat_id' => 15,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=plat',
                        'xzym' => [
                            'image' => '',
                            'txt' => '客服联系用户入口',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=plat'
                        ]
                    ],
                    [
                        'cat_id' => 16,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=user',
                        'xzym' => [
                            'image' => '',
                            'txt' => '用户联系客服入口',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=user'
                        ]
                    ],
                    [
                        'cat_id' => 17,
                        'cat_fid' => 0,
                        'url' => $pagesMyUrl . 'villagelist',
                        'xzym' => [
                            'image' => '',
                            'txt' => cfg('house_name') . '列表',
                            'url' => ''
                        ],
                        'yllj' => [
                            'image' => '',
                            'txt' => '预览链接',
                            'url' => $pagesMyUrl . 'villagelist'
                        ]
                    ]
                ]
            ],
        ];
        return $arr;

    }

    private function getWaimaiBusiness()
    {
        $alias = cfg('shop_alias_name') ? cfg('shop_alias_name') : '外卖';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => $alias . '首页', 'label' => 'wmsy'], ['txt' => $alias . '订单', 'label' => 'wmdd'], ['txt' => '分类页面', 'label' => 'wmflym'], ['txt' => $alias . '专题', 'label' => 'wmzt']]
            ]
        ];
        return $arr;
    }

    private function wmsy()
    {
        $alias = cfg('shop_alias_name') ? cfg('shop_alias_name') : '外卖';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/shop_index/index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/shop_index/index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function wmdd()
    {
        $alias = cfg('shop_alias_name') ? cfg('shop_alias_name') : '外卖';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=shop_order_list',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '订单',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=shop_order_list',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function wmflym()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_131',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类名称', 'label' => 'flym']],
                'body' => $this->getWaimaiCategory()
            ]
        ];
        return $arr;
    }

    public function wmzt($systemUser, $keyword, $page, $pageSize)
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_152',//为空时不显示跳转按钮
            'show_search' => true,//为空不展示搜索按钮
            'page_bar' => true,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '专题名称', 'label' => 'ztmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->sepcial(1, $systemUser, $keyword, $page, $pageSize)
            ]
        ];
        return $arr;
    }

    private function getMallBusiness($systemUser)
    {
        $alias = cfg('mall_alias_name') ? cfg('mall_alias_name') : '商城';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => $alias . '首页', 'label' => 'scsy'], ['txt' => '活动页面', 'label' => 'schdym'], ['txt' => '分类页面', 'label' => 'scflym'], ['txt' => $alias . '专题', 'label' => 'sczt']]
            ]
        ];
        return $arr;
    }

    private function scsy()
    {
        $alias = cfg('mall_alias_name') ? cfg('mall_alias_name') : '商城';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function schdym()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择活动', 'label' => 'xzhd'], ['txt' => '活动名称', 'label' => 'hdmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=group_goods_list',
                        'xzhd' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '拼团活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=group_goods_list',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=seckill_goods_list',
                        'xzhd' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '秒杀活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=seckill_goods_list',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=cut_goods_list',
                        'xzhd' => [
                            'txt' => 3,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '砍价活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=cut_goods_list',
                            'image' => ''
                        ]
                    ],
                ]
            ]
        ];
        return $arr;
    }

    private function scflym()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_188',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类页面', 'label' => 'flym']],
                'body' => $this->oldMallCategory()
            ]
        ];
        return $arr;
    }

    private function sczt($systemUser, $keyword, $page, $pageSize)
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_317',//为空时不显示跳转按钮
            'show_search' => true,//为空不展示搜索按钮
            'page_bar' => true,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '专题名称', 'label' => 'ztmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->sepcial(3, $systemUser, $keyword, $page, $pageSize)
            ]
        ];
        return $arr;
    }

    private function getNewMallBusiness($systemUser)
    {
        $alias = cfg('mall_alias_name_new') ? cfg('mall_alias_name_new') : '新版商城';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => $alias . '首页', 'label' => 'xbscsy'], ['txt' => '活动页面', 'label' => 'xbschdym'], ['txt' => '分类页面', 'label' => 'xbscflym']]
            ]
        ];
        return $arr;
    }

    private function xbscsy()
    {
        $alias = cfg('mall_alias_name_new') ? cfg('mall_alias_name_new') : '新版商城';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/shopmall_index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/shopmall_index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function xbschdym()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择活动', 'label' => 'xzhd'], ['txt' => '活动名称', 'label' => 'hdmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/makeGroup',
                        'xzhd' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '拼团活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/makeGroup',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/flashSale',
                        'xzhd' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '秒杀活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/flashSale',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/bargaining',
                        'xzhd' => [
                            'txt' => 3,
                            'url' => '',
                            'image' => ''
                        ],
                        'hdmc' => [
                            'txt' => '砍价活动',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/shopmall_third/bargaining',
                            'image' => ''
                        ]
                    ],
                ]
            ]
        ];
        return $arr;
    }

    private function xbscflym()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/mall/platform.mallGoodsCategory/goodsCategoryList',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类页面', 'label' => 'flym']],
                'body' => $this->newMallCategory1()
            ]
        ];
        return $arr;
    }

    private function getFoodshopBusiness()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => $alias . '首页', 'label' => 'cysy'], ['txt' => '分类页面', 'label' => 'cyflym'], ['txt' => $alias . '订单', 'label' => 'cydd']]
            ]
        ];
        return $arr;
    }

    private function cysy()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Foodshop&a=index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Foodshop&a=index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function cyflym()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_18',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类页面', 'label' => 'flym']],
                'body' => $this->foodshopCategory('old')
            ]
        ];
        return $arr;
    }

    private function cydd()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=foodshop_order_list',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '订单',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=foodshop_order_list',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function getNewFoodshopBusiness()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [
                    [
                        'txt' => $alias . '首页',
                        'label' => 'xbcysy'
                    ],
                    [
                        'txt' => '分类页面',
                        'label' => 'xbcyflym'
                    ],
                    [
                        'txt' => $alias . '订单',
                        'label' => 'xbcydd'
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function xbcysy()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/foodshop/index/index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/foodshop/index/index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function xbcyflym()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/foodshop/platform.storecategory/list',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类页面', 'label' => 'flym']],
                'body' => $this->foodshopCategory('new')
            ]
        ];
        return $arr;
    }

    private function xbcydd()
    {
        $alias = cfg('meal_alias_name') ? cfg('meal_alias_name') : '餐饮';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为false不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],//头部菜单 label和txt的数组形式
                'body' => [//头部下的内容
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . $this->base_url . 'pages/foodshop/order/orderList',//一行的url选项 有值则该行能选中
                        'xzym' => [//头部xzym的label对应的内容
                            'txt' => 1,//有文字则展示文字
                            'url' => '',//图片，有链接则跳转
                            'image' => ''//有图片则展示
                        ],
                        'ymmc' => [//头部ymmc对应的内容
                            'txt' => $alias . '订单',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [//头部预览链接对应的内容
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . $this->base_url . 'pages/foodshop/order/orderList',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function getGroupBusiness()
    {
        $alias = cfg('group_alias_name') ? cfg('group_alias_name') : '团购优选';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => cfg('group_index_name') ? cfg('group_index_name') : '团购优选', 'label' => 'tgsy'], ['txt' => $alias . '店铺首页', 'label' => 'tgdpsy'], ['txt' => $alias . '链接', 'label' => 'tglj'], ['txt' => '新版' . $alias . '分类链接', 'label' => 'xbtgfllj']]
            ]
        ];
        return $arr;
    }

    private function tgsy()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '分类名称', 'label' => 'flmc']],
                'body' => $this->getGroupIndex(2, cfg('group_index_name'))
            ]
        ];
        return $arr;
    }

    private function tgdpsy()
    {
        $alias = cfg('group_alias_name') ? cfg('group_alias_name') : '团购优选';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->getGroupIndex(1, $alias . '店铺首页')
            ]
        ];
        return $arr;
    }

    private function tglj()
    {
        $alias = cfg('group_alias_name') ? cfg('group_alias_name') : '团购优选';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Group&a=around',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '附近' . $alias,
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Group&a=around',
                            'image' => ''
                        ]
                    ],

                    ['cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=group_order_list',
                        'xzym' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '订单',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Group&a=around',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=group_collect',
                        'xzym' => [
                            'txt' => 3,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '收藏',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=group_collect',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 4,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Group&a=navigation',
                        'xzym' => [
                            'txt' => 4,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '导航',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Group&a=navigation',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 5,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=apply',
                        'xzym' => [
                            'txt' => 5,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '注册成为团长',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=apply',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function getBookingBusiness()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '预约首页', 'label' => 'yysy'], ['txt' => '预约分类', 'label' => 'yyfl'], ['txt' => '预约订单', 'label' => 'yydd'],['txt' => '技师列表', 'label' => 'yyjslb']]
            ]
        ];
        return $arr;
    }

    private function yysy()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Appoint&a=index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '预约首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Appoint&a=index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function yyjslb(){
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/appoint/worker/list'),
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '技师列表',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/appoint/worker/list'),
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function yyfl()
    {
        $arr = [
            'add_link' => '/v20/public/platform/#/common/platform.iframe/menu_57',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->appointCategory()
            ]
        ];
        return $arr;
    }

    private function yydd()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=appoint_order_list',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '预约订单',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=appoint_order_list',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function getCommunityGroupBuying()
    {
        $alias = cfg('village_group_alias') ? cfg('village_group_alias') : '社区团购';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '常用页面', 'label' => 'sqtgsy'], ['txt' => '分类页面', 'label' => 'sqtgfl']]
            ]
        ];
        return $arr;
    }

    private function sqtgsy()
    {
        $alias = cfg('village_group_alias') ? cfg('village_group_alias') : '社区团购';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=index',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=apply',
                        'xzym' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '申请团长',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Village_group&a=apply',
                            'image' => ''
                        ]
                    ]

                ]
            ]
        ];
        return $arr;
    }

    private function sqtgfl()
    {
        $arr = [
            'add_link' => '/v20/public/platform/#/common/platform.iframe/menu_376',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->villageGroupCategory()
            ]
        ];
        return $arr;
    }

    private function getPointsExchange()
    {
        $alias = cfg('gift_alias_name') ? cfg('gift_alias_name') : '积分商城';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => $alias . '换购首页', 'label' => 'jfhgsy'], ['txt' => $alias . '分类', 'label' => 'jfhgfl']]
            ]
        ];
        return $arr;
    }

    private function jfhgsy()
    {
        $alias = cfg('gift_alias_name') ? cfg('gift_alias_name') : '积分商城';
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Recruit&a=score_package',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => $alias . '换购首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Recruit&a=score_package',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function jfhgfl()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_136',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->pointCategory()
            ]
        ];
        return $arr;
    }

    private function getPlatformNews()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '平台快报首页', 'label' => 'ptkbsy'], ['txt' => '平台快报分类', 'label' => 'ptkbfl']]
            ]
        ];
        return $arr;
    }

    private function ptkbsy()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Systemnews&a=index',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '平台快报首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Systemnews&a=index',
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function ptkbfl()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->newsInfo()
            ]
        ];
        return $arr;
    }

    private function getScenic()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [
                    ['txt' => '常用页面', 'label' => 'newsCenic']
                ]
        ]];

        if(customization('life_tools')){
          $arr['list']['left'][] = ['txt' => '体育健身', 'label' => 'sport'];
           $arr['list']['left'][] = ['txt' => '员工卡列表', 'label' => 'employeeCard'];
           $arr['list']['left'][] = ['txt' => '门票预约', 'label' => 'ticketAppoint'];
        }
        return $arr;
    }

    private function getOtherBusiness()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [
                    ['txt' => '分类信息', 'label' => 'flxx'],
                    ['txt' => '隐私政策', 'label' => 'yszc'],
                    ['txt' => '用户协议', 'label' => 'yhxy'],
                    ['txt' => '批发市场', 'label' => 'pfsc'],
                    ['txt' => '门户功能', 'label' => 'mhgn'],
                    ['txt' => '营销活动', 'label' => 'yxhd'],
                    ['txt' => '图文管理', 'label' => 'atlas'],
                    // ['txt' => '结婚助手', 'label' => 'marriage'],
                    //['txt' => '景区', 'label' => 'scenic']
                ]
            ]];
        if(cfg('single_system_type') == 'shopping_mall'){
            $arr['list']['left'][] = ['txt' => '商场工具', 'label' => 'shoppingMallTools'];
        }elseif(cfg('single_system_type') == 'member'){
            $arr['list']['left'][] = ['txt' => '会员工具', 'label' => 'shoppingMallTools'];
        }
        return $arr;
    }

    private function employeeCard(){
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => get_base_url('/pages/lifeTools/pocket/list'),
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '员工卡列表',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('/pages/lifeTools/pocket/list'),
                            'image' => ''
                        ]
                    ],
                ]
            ]
        ];
        return $arr;
    }

    private function sport(){
        $linkArr = [
            [
                'url' => get_base_url('/pages/lifeTools/sports/index'),
                'txt' => '体育首页',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/sports/catList?type=1'),
                'txt' => '体育课程分类列表',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/sports/catList?type=2'),
                'txt' => '体育场馆分类列表',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/information/list?type=sports'),
                'txt' => '体育资讯列表',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/match/list'),
                'txt' => '赛事列表',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/sports/activity/list'),
                'txt' => '运动约战',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/sports/activity/list?showSelf=1'),
                'txt' => '我的约战',
            ],
        ];
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => []
            ],
        ];
        foreach($linkArr as $key => $value){
            $arr['list']['body'][] = [
                'cat_id' => $key + 1,
                'cat_fid' => 0,
                'url' => $value['url'],
                'xzym' => [
                    'image' => '',
                    'txt' => $value['txt'],
                    'url' => ''
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => $value['url'],
                ]
            ];
        }
        return $arr;
    }

    private function newsCenic(){
        $linkArr = [
            [
                'url' => get_base_url('/pages/lifeTools/scenic/index'),
                'txt' => '景区首页',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/information/list?type=scenic'),
                'txt' => '景区资讯',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/help/helpList'),
                'txt' => '寻人求助',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/advice/list'),
                'txt' => '投诉建议',
            ],
            [
                'url' => get_base_url('/pages/lifeTools/scenic/ticketBook?type=scenic&showTabs=false'),
                'txt' => '景区列表',
            ],

        ];
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => []
            ],
        ];
        foreach($linkArr as $key => $value){
            $arr['list']['body'][] = [
                'cat_id' => $key + 1,
                'cat_fid' => 0,
                'url' => $value['url'],
                'xzym' => [
                    'image' => '',
                    'txt' => $value['txt'],
                    'url' => ''
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => $value['url'],
                ]
            ];
        }
        return $arr;
    }

    private function ticketAppoint(){
        $linkArr = [
            [
                'url' => get_base_url('/pages/lifeTools/scenic/ticketBook'),
                'txt' => '门票预约',
            ],
        ];
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => []
            ],
        ];
        foreach($linkArr as $key => $value){
            $arr['list']['body'][] = [
                'cat_id' => $key + 1,
                'cat_fid' => 0,
                'url' => $value['url'],
                'xzym' => [
                    'image' => '',
                    'txt' => $value['txt'],
                    'url' => ''
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => $value['url'],
                ]
            ];
        }
        return $arr;
    }

    // 景区
    private function scenic(){
        $linkArr = [
            [
                'url' => cfg('site_url').'/wap.php?g=Wap&c=Scenic_index&a=index',
                'txt' => '景区首页',
            ],
        ];
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => []
            ],
        ];
        foreach($linkArr as $key => $value){
            $arr['list']['body'][] = [
                'cat_id' => $key + 1,
                'cat_fid' => 0,
                'url' => $value['url'],
                'xzym' => [
                    'image' => '',
                    'txt' => $value['txt'],
                    'url' => ''
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => $value['url'],
                ]
            ];
        }
        return $arr;
    }

    private function getDiyPage()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '页面列表', 'label' => 'ymlb']]
            ]];
        return $arr;
    }

    private function getMatchingTools()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '页面列表', 'label' => 'ptgj']]
            ]];
        return $arr;
    }

    private function getMarketingTools()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '页面列表', 'label' => 'yxgj']]
            ]];
        return $arr;
    }

    private function getFunctionPage()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '页面列表', 'label' => 'gnym']]
            ]];
        return $arr;
    }

    private function flxx()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_38',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->classifyCategory()
            ]
        ];
        return $arr;
    }

    private function yszc()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Login&a=privacy_policy',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '平台APP隐私政策',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Login&a=privacy_policy',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/packapp/storestaff/agreement.html',
                        'xzym' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '店员APP隐私政策',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/packapp/storestaff/agreement.html',
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?c=Login&a=mer_privacy_policy',
                        'xzym' => [
                            'txt' => 3,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '商家APP隐私政策',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?c=Login&a=mer_privacy_policy',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 4,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/packapp/deliver/agreement.html',
                        'xzym' => [
                            'txt' => 4,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '配送APP隐私政策',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/packapp/deliver/agreement.html',
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 5,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Login&a=village_privacy_policy',
                        'xzym' => [
                            'txt' => 5,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '社区管理APP隐私政策',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Login&a=village_privacy_policy',
                            'image' => ''
                        ]

                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function pfsc()
    {
        $arr = [
            'add_link' => cfg('site_url') . '/v20/public/platform/#/common/platform.iframe/menu_253',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->marketCategory()
            ]
        ];
        return $arr;
    }

    private function mhgn()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->actInfo()
            ]
        ];
        return $arr;
    }

    private function yxhd()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->yxhdInfo()
            ]
        ];
        return $arr;
    }

    private function atlas()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->atlasInfo()
            ]
        ];
        return $arr;
    }

    private function marriage(){
        $linkArr = [
            [
                'url' => get_base_url('/pages/marriage/v1/industryMaster/index', true),
                'txt' => '业内高手',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/toolList/index', true),
                'txt' => '结婚工具',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/wedPlan/index', true),
                'txt' => '结婚计划',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/userBudget/index', true),
                'txt' => '结婚预算',
            ],
            [
                'url' => get_base_url('/pages/marriage/luckyDay'),
                'txt' => '黄道吉日',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/strategy/index', true),
                'txt' => '结婚宝典',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/userHomepage/index', true),
                'txt' => '个人主页',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/joinCondition/index', true),
                'txt' => '入驻条件说明',
            ],
            [
                'url' => get_base_url('/pages/marriage/v1/marriageRegistry/index', true),
                'txt' => '婚姻登记处',
            ],
        ];
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => []
            ],
        ];
        foreach($linkArr as $key => $value){
            $arr['list']['body'][] = [
                'cat_id' => $key + 1,
                'cat_fid' => 0,
                'url' => $value['url'],
                'xzym' => [
                    'image' => '',
                    'txt' => $value['txt'],
                    'url' => ''
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => $value['url'],
                ]
            ];
        }
        return $arr;
    }

    private function ymlb($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->ymlbInfo($systemUser, $keyword, $page, $pageSize, $source, $source_id)
            ]
        ];
        return $arr;
    }

    private function ptgj($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0, $store_id = 0)
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->ptgjInfo($systemUser, $keyword, $page, $pageSize, $source, $source_id, $store_id)
            ]
        ];
        return $arr;
    }

    private function yxgj($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->yxgjInfo($systemUser, $keyword, $page, $pageSize, $source, $source_id)
            ]
        ];
        return $arr;
    }

    private function gnym($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $this->gnymInfo($systemUser, $keyword, $page, $pageSize, $source, $source_id)
            ]
        ];
        return $arr;
    }
    //处理类函数

    /**
     * @return mixed
     * 外卖分类
     */
    public function getWaimaiCategory()
    {
        $items = array();
        $list = (new ShopCategory())->getCategory();
        //全部分类
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => cfg('site_url') . $this->base_url . 'pages/shop_new/shopType/shopType?cat_url=all&title=' . urlencode('全部分类'),
            'flym' => [
                'txt' => '全部分类',
                'url' => '',
                'image' => ''
            ]
        ];
        $f_num = 1;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . $this->base_url . 'pages/shop_new/shopType/shopType?cat_url=' . $val['cat_url'] . '&title=' . urlencode($val['cat_name']),
                        'flym' => [
                            'txt' => $val['cat_name'],
                            'url' => '',
                            'image' => ''
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . $this->base_url . 'pages/shop_new/shopType/shopType?cat_url=' . $val['cat_url'] . '&title=' . urlencode($val['cat_name']),
                        'flym' => [
                            'txt' => $val['cat_name'],
                            'url' => '',
                            'image' => ''
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }

        return array_merge($items, $farr);
    }

    /**
     * @param $type
     * @param array $systemUser
     * @return array
     * 各种老版专题
     */
    public function sepcial($type, $systemUser, $keyword, $page, $pageSize)
    {
        $where = [['type', '=', $type], ['status', '=', 1]];
        if ($keyword !== '') {
            array_push($where, ['name', 'like', '%' . $keyword . '%']);
        }
        /*if ($systemUser['area_id']) {
            $now_area = (new Area())->getOne(['area_id' => $systemUser['area_id']]);
            if ($now_area['area_type'] == 3) {
                $where = "0";
            } elseif ($now_area['area_type'] == 2) {
                $city_id = $this->$systemUser['area_id'];
                $province_id = $now_area['area_pid'];
                $where['province_id'] = $province_id;
                $where['city_id'] = $city_id;
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->$systemUser['area_id'];
                $where['province_id'] = $province_id;
            }
        }*/
        $list = (new Special())->getSpecial($where, $page, $pageSize);
        $items = array();
        $num = 0;
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                if ($type == 1) {
                    $url = cfg('site_url') . $this->base_url . 'pages/shop_new/shopSpecial/shopSpecial?id=' . $val['pigcms_id'];
                } else if ($type == 3) {
                    $url = cfg('site_url') . '/wap.php?g=Wap&c=Special&a=index&id=' . $val['pigcms_id'];
                }
                $num++;
                $item = [
                    'cat_id' => $val['pigcms_id'],
                    'cat_fid' => 0,
                    'url' => $url,
                    'xzym' => [
                        'txt' => $num,
                        'url' => '',
                        'image' => '',
                    ],
                    'ztmc' => [
                        'txt' => $val['name'],
                        'url' => '',
                        //'image' => $val['image'],
                        'image' => '',//杨宁宁说不要图片
                    ],
                    'yllj' => [
                        'txt' => '预览链接',
                        'url' => $url,
                        'image' => '',
                    ]
                ];
                $items['list'][] = $item;
            }
            $items['count'] = (new Special())->getCount($where);
        }
        return $items;
    }

    /**
     *老版商城分类
     */
    public function oldMallCategory()
    {
        $items = array();
        $list = (new GoodsCategory())->getCategory();
        //全部分类
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=index',
            'flym' => [
                'image' => '',
                'txt' => '全部分类',
                'url' => ''
            ]
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['id'],
                        'cat_fid' => $val['fid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=goods_list&cat_id=0&cat_fid=' . $val['id'],
                        'flym' => [
                            // 'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['id'],
                        'cat_fid' => $val['fid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Mall&a=goods_list&cat_id=' . $val['id'] . '&cat_fid=' . $val['fid'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);

    }

    /**
     *新版商城专题
     */
    public function newMallSepcial()
    {

    }

    /**
     * 新版商城分类
     */
    public function newMallCategory()
    {
        $cateService = new MallCategory();
        $order = [
            'sort' => 'DESC',
            'cat_id' => 'ASC'
        ];
        $where = [['status', '=', 1],['is_del', '=',0]];
        $list = $cateService->getCategoryByCondition($where, $order);
        $farr = array();
        $carr = array();
        $garr = array();
        $f_num = 0;
        $c_num = 0;
        $g_num = 0;
        if (!empty($list)) {
            //分离出子数组和父数组
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent=' . $val['cat_name'] . '&cat_first=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else if ($cateService->getCategoryByCondition(['cat_id' => $val['cat_fid']], $order)[0]['cat_fid'] == 0) {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent=' . $val['cat_name'] . '&cat_first=' . $val['cat_fid'] . '&cat_second=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                } else {
                    $first_id = $cateService->getCategoryByCondition(['cat_id' => $val['cat_fid']], $order)[0]['cat_fid'];
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent=' . $val['cat_name'] . '&cat_first=' . $first_id . '&cat_second=' . $val['cat_fid'] . '&cat_three=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $garr[] = $item;//二级
                }
            }
            foreach ($carr as $key => $val1) {
                foreach ($garr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $g_num++;
                        //$val2['flmc']['image'] = $g_num;
                        $carr[$key]['children'][] = $val2;
                    }
                }
                $g_num = 0;
            }
            foreach ($farr as $key => $val1) {
                foreach ($carr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $c_num++;
                        //$val2['flmc']['image'] = $c_num;
                        $farr[$key]['children'][] = $val2;
                    }
                }
                $c_num = 0;
            }
            return $farr;
        }
    }


    /**
     * 新版商城分类
     */
    public function newMallCategory1()
    {
        $cateService = new MallCategory();
        $order = [
            'sort' => 'DESC',
            'cat_id' => 'ASC'
        ];
        $where = [['status', '=', 1],['is_del', '=',0]];
        $list = $cateService->getCategoryByCondition($where, $order);
        $farr = array();
        $carr = array();
        $garr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['level'] == 1) {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$val['cat_name'].'&cat_first=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;
                } else if ($val['level'] == 2) {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$val['cat_name'].'&cat_second=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/shopmall_third/searchresult?searchcontent='.$val['cat_name'].'&cat_three=' . $val['cat_id'],
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $garr[] = $item;
                }
            }
            foreach ($carr as $key => $val1) {
                foreach ($garr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $carr[$key]['children'][] = $val2;
                    }
                }
            }
            foreach ($farr as $key => $val1) {
                foreach ($carr as $val2) {
                    if ($val1['cat_id'] == $val2['cat_fid']) {
                        $farr[$key]['children'][] = $val2;
                    }
                }
            }
            return $farr;
        } else {
            return [];
        }
    }

    /**
     * 餐饮分类(老版和新版的平台分类是用的同一个，只是跳转链接不同，用type标识)
     */
    public function foodshopCategory($type)
    {
        $items = array();
        $list = (new MealStoreCategory())->getCategoryByCondition(['cat_status' => 1], 'cat_sort DESC');
        if ($type == 'old') {
            $url = cfg('site_url') . '/wap.php?g=Wap&c=Foodshop&a=index';
        } else if ($type == 'new') {
            $url = cfg('site_url') . $this->base_url . 'pages/foodshop/search/searchList';
        }
        //全部分类
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => $url,
            'flym' => [
                'image' => '',
                'txt' => '全部分类',
                'url' => ''
            ]
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($type == 'old') {
                    $url = cfg('site_url') . '/wap.php?g=Wap&c=Foodshop&a=index&cat_url=' . $val['cat_url'];
                } else if ($type == 'new') {
                    $url = cfg('site_url') . $this->base_url . 'pages/foodshop/search/searchList?cat_id=' . $val['cat_id'];
                }
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flym' => [
                            // 'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flym' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    /**
     * type:group_alias_name 店铺首页,group_index_name 首页
     *
     */
    public function getGroupIndex($type, $groupName)
    {
        $items = array();
        $list = (new GroupCategory())->getCategory();
        if ($type == 1) {
            $indexUrl = cfg('site_url') . '/wap.php?g=Wap&c=Group&a=index';
        } else if ($type == 2) {
            $indexUrl = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=index';
        }
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => $indexUrl,
            'flmc' => [
                'image' => '',
                'txt' => $groupName,
                'url' => '',
            ]
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($type == 1) {
                    $url = cfg('site_url') . '/wap.php?g=Wap&c=Group&a=index&cat_url=' . $val['cat_url'];
                } else if ($type == 2) {
                    if ($val['cat_fid'] == 0) {
                        $url = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=category&cat_id=' . $val['cat_id'] . '&page_num=1';
                    } else {
                        $url = cfg('site_url') . '/wap.php?g=Wap&c=Groupnew&a=two_category&cat_id=' . $val['cat_id'] . '&page_num=2';
                    }
                }
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    /**
     * 预约分类
     */
    public function appointCategory()
    {
        $items = array();
        $list = (new AppointCategory())->getCategory();
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Appoint&a=category',
            'flmc' => [
                'image' => '',
                'txt' => '全部分类',
                'url' => ''
            ]
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => get_base_url('pages/appoint/category?cat_id='.$val['cat_id']),
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Appoint&a=two_category&cat_id=' . $val['cat_id'],
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    /**
     * 社区团购分类
     */
    public function villageGroupCategory()
    {
        $list = (new VillageGroupCategory())->getCategory();
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['fid'],
                        'url' => cfg('site_url') . '/packapp/community_group/index.html#/classify?cat_id=' . $val['cat_id'],
                        'flmc' => [
                            // 'image' => $val['bg_img'] ? cfg('site_url') . $val['bg_img'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['fid'],
                        'url' => cfg('site_url') . '/packapp/community_group/index.html#/classify_goods?cat_son_id=' . $val['cat_id'] . '&cat_id=' . $val['fid'],
                        'flmc' => [
                            //'image' => $val['bg_img'] ? cfg('site_url') . $val['bg_img'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return $farr;
    }

    /**
     * @return array
     * 积分商城分类
     */
    public function pointCategory()
    {
        $items = array();
        $list = (new GiftCategory())->getCategory();
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Gift&a=index',
            'flmc' => [
                'image' => '',
                'txt' => '全部分类',
                'url' => ''
            ]
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Gift&a=gift_list&cat_id=' . $val['cat_id'],
                        'flmc' => [
                            // 'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                }
                /*else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Appoint&a=two_category&cat_id=' . $val['cat_id'],
                        'flmc' => [
                            'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                               $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                            $num = 0;
                    }
                }
            }*///积分商城的用户端暂不支持二级分类（二级处理暂时注释）
            }
        }
        return array_merge($items, $farr);
    }

    /**
     * 平台快报信息
     */
    public function newsInfo()
    {
        $model = new SystemNewsCategory;
        $list = $model->getCategory(['status' => 1], 'sort DESC');
        $items = array();
        if (!empty($list)) {
            foreach ($list as $key => $val) {
                $sonList = $model->getSonInfo(['snc.id' => $val['id'], 'sn.status' => 1, 'snc.status' => 1], 'sn.sort DESC');
                if (!empty($sonList)) {
                    $item = array();
                    foreach ($sonList as $k => $v) {
                        $item[] = [
                            'cat_id' => $k + 1,
                            'cat_fid' => $key + 1,
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Systemnews&a=news&id=' . $v['id'],
                            'flmc' => [
                                'image' => '',
                                'txt' => $v['title'],
                                'url' => ''
                            ]
                        ];
                    }
                }
                $items[$key] = [
                    'cat_id' => $key + 1,
                    'cat_fid' => 0,
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=Systemnews&a=index&category_id=' . $val['id'],
                    'flmc' => [
                        'image' => '',
                        'txt' => $val['name'],
                        'url' => ''
                    ]
                ];
                if (!empty($item)) {
                    $items[$key]['children'] = $item;
                }
                unset($item);

            }
        }
        return $items;
    }

    /**
     * 分类信息分类
     */
    public function classifyCategory()
    {
        $items = array();
        $list = (new ClassifyCategory())->getCategory();
        $items = [
            [
                'cat_id' => 0,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Classify&a=index',
                'flmc' => [
                    'image' => '',
                    'txt' => '分类信息首页',
                    'url' => ''
                ]
            ],
            [
                'cat_id' => -1,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/lifeTools/buildingSale/list',
                'flmc' => [
                    'image' => '',
                    'txt' => '房产销售',
                    'url' => ''
                ]
            ],
        ];
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fcid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cid'],
                        'cat_fid' => $val['fcid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Classify&a=index&cid=' . $val['cid'],
                        'flmc' => [
                            // 'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cid'],
                        'cat_fid' => $val['fcid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Classify&a=Lists&cid=' . $val['cid'],
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    /**
     * 批发市场分类
     */
    public function marketCategory()
    {
        $items = array();
        $list = (new GoodsWholesaleCategory())->getCategory();
        $items[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/packapp/merchant/market_list.html',
            'flmc' => [
                'image' => '',
                'txt' => '市场首页',
                'url' => ''
            ]
        ];
        // $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fid'] == 0) {
                    //$f_num++;
                    $item = [
                        'cat_id' => $val['id'],
                        'cat_fid' => $val['fid'],
                        'url' => '',
                        'flmc' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['id'],
                        'cat_fid' => $val['fid'],
                        'url' => cfg('site_url') . '/packapp/merchant/market_category_list.html?cat_fid=' . $val['fid'] . '&cat_id=' . $val['id'],
                        'flmc' => [
                            //'image' => $val['image'] ? cfg('site_url') . '/' . $val['image'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    public function actInfo()
    {
        $items = [
            [
                'cat_id' => 1,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Portal&a=index',
                'flmc' => [
                    'image' => '',
                    'txt' => '  门户首页',
                    'url' => ''
                ]
            ],
            [
                'cat_id' => 2,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/index.php?g=Wap&c=Portal&a=tieba',
                'flmc' => [
                    'image' => '',
                    'txt' => '门户贴吧',
                    'url' => ''
                ]
            ],
            [
                'cat_id' => 3,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/index.php?g=Wap&c=Portal&a=yellow',
                'flmc' => [
                    'image' => '',
                    'txt' => '门户黄页',
                    'url' => ''
                ]
            ],
            [
                'cat_id' => 4,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/index.php?g=Wap&c=Portal&a=activity',
                'flmc' => [
                    'image' => '',
                    'txt' => '门户活动',
                    'url' => ''
                ],
                'children' => $this->portalActivityCategory()
            ],
            [
                'cat_id' => 5,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/index.php?g=Wap&c=Portal&a=article',
                'flmc' => [
                    'image' => '',
                    'txt' => '门户资讯',
                    'url' => '',
                ],
                'children' => $this->portalArticleCategory()
            ]
        ];
        return $items;
    }

    /**
     * 门户活动分类
     */
    public function portalActivityCategory()
    {
        $list = (new PortalActivityCat())->getCategory();
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fcid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cid'],
                        'cat_fid' => $val['fcid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Portal&a=activity&cid=' . $val['cid'],
                        'flmc' => [
                            //'image' => $val['img'] ? cfg('site_url') . $val['img'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                }
                /* else {
                     $item = [
                         'cat_id' => $val['cid'],
                         'cat_fid' => $val['fcid'],
                         'url' => cfg('site_url') . '/packapp/merchant/market_category_list.html?cat_fid=' . $val['fid'] . '&cat_id=' . $val['id'],
                         'flmc' => [
                             'image' => $val['img'] ? cfg('site_url') . $val['img'] : '',
                             'txt' => $val['cat_name'],
                             'url' => '',
                         ]
                     ];
                     $carr[] = $item;//二级
                 }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                               $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                            $num = 0;
                    }
                }*///门户活动用户端暂不支持二级分类
            }
        }
        return $farr;
    }

    /**
     * 门户资讯分类
     */
    public function portalArticleCategory()
    {
        $list = (new PortalArticleCat())->getCategory();
        $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['fcid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cid'],
                        'cat_fid' => $val['fcid'],
                        'url' => cfg('site_url') . '/wap.php?g=Wap&c=Portal&a=article&cid=' . $val['cid'],
                        'flmc' => [
                            'image' => '',
                            'txt' => $val['cat_name'],
                            'url' => ''
                        ]
                    ];
                    $farr[] = $item;//一级
                }
                /* else {
                     $item = [
                         'cat_id' => $val['cid'],
                         'cat_fid' => $val['fcid'],
                         'url' => cfg('site_url') . '/packapp/merchant/market_category_list.html?cat_fid=' . $val['fid'] . '&cat_id=' . $val['id'],
                         'flmc' => [
                             'image' => '',
                             'txt' => $val['cat_name'],
                             'url' => '',
                         ]
                     ];
                     $carr[] = $item;//二级
                 }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                               $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                            $num = 0;
                    }
                }*///门户活动用户端暂不支持二级分类
            }
        }
        return $farr;
    }

    public function yxhdInfo()
    {
        $data = array(
            'bargain' => '砍价',
            'seckill' => '秒杀',
            'crowdfunding' => '众筹',
            'cutprice' => '降价拍',
            'lottery' => '大转盘',
            'red_packet' => '微信红包',
            'guajiang' => '刮刮卡',
            'jiugong' => '九宫格',
            'luckyFruit' => '幸运水果机',
            'goldenEgg' => '砸金蛋',
            'voteimg' => '图文投票',
            'custom' => '万能表单',
            'card' => '微贺卡', // 暂时不显示渠道二维码
            'game' => '微游戏',
            'live' => '微场景',
            'research' => '微调研',
            'forum' => '讨论小区',
            'autumn' => '中秋吃月饼活动',
            'helping' => '微助力',
            'donation' => '募捐',
            'coinTree' => '摇钱树',
            'collectword' => '集字游戏',
            'sentiment' => '谁是情圣',
            'frontPage' => '我要上头条',
            'test' => '趣味测试',
            'punish' => '惩罚台',
            'shakeLottery' => '摇一摇',
            'youSetDiscount' => '优惠接力',
            'popularity' => '人气冲榜',
            'problem' => '一战到底',
            'seniorScene' => '微场景',
            'auction' => '微拍卖'
        );
        $data =[]; //关闭营销系统的活动
        $items = array();
        $f_num = 0;
        foreach ($data as $index => $val) {
            $f_num++;
            $item = [
                'cat_id' => $f_num,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Wxapp&a=index&cat_url=' . $index,
                'xzym' => [
                    'txt' => $f_num,
                    'url' => '',
                    'image' => ''
                ],
                'ymmc' => [
                    'txt' => $val,
                    'url' => '',
                    'image' => ''
                ],
                'yllj' => [
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=Wxapp&a=index&cat_url=' . $index,
                    'image' => ''
                ]
            ];
            $items[] = $item;
        }
        $items[] = [
            'cat_id' => $f_num+1,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Wapactivity&a=index',
            'xzym' => [
                'txt' => $f_num+1,
                'url' => '',
                'image' => ''
            ],
            'ymmc' => [
                'txt' => '限时活动',
                'url' => '',
                'image' => ''
            ],
            'yllj' => [
                'txt' => '预览链接',
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Wapactivity&a=index',
                'image' => ''
            ]
        ];
        $items[] = [
            'cat_id' => $f_num + 2,
            'cat_fid' => 0,
            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Activity&a=index',
            'xzym' => [
                'txt' => $f_num + 2,
                'url' => '',
                'image' => ''
            ],
            'ymmc' => [
                'txt' => '商家微活动',
                'url' => '',
                'image' => ''
            ],
            'yllj' => [
                'txt' => '预览链接',
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Activity&a=index',
                'image' => ''
            ]
        ];
        return $items;
    }



     private function xbtgfllj()
    {
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'cat',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'flmc']],
                'body' => $this->getNewGroupIndex()
            ]
        ];
        return $arr;
    }
/**新版团购分类链接
     * @return array
     */
    public function getNewGroupIndex()
    {

        $list = (new GroupCategory())->getCategory();

        $cat_names = [];
        foreach ($list as $_value)
        {
            if ($_value['cat_fid'] == 0) {
                $cat_names[$_value['cat_id']]['cat_name'] = $_value['cat_name'];
            }
        }
        $f_num = 0;
        $num = 0;
        $farr = array();
        $group_alias_name = cfg('group_alias_name') ? cfg('group_alias_name') : '团购';
        $farr[] = [
            'cat_id' => 0,
            'cat_fid' => 0,
            'url' => get_base_url('pages/group/index/home?currentPage=index'),
            'flmc' => [
                'image' => '',
                'txt' => '新版'.$group_alias_name.'首页',
                'url' => '',
            ]
        ];
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    $url = get_base_url('pages/group/index/catPage?cat_fid='. $val['cat_id'] . '&cat_name=' . urlencode($cat_names[$val['cat_id']]['cat_name']) );
                } else {
                    if(isset($cat_names[$val['cat_fid']])){
                        $url = get_base_url('pages/group/index/list?cat_fid='. $val['cat_fid'] . '&cat_name=' . urlencode($cat_names[$val['cat_fid']]['cat_name']) .'&cat_id='.$val['cat_id'].'&type=store');
                    }else{
                        $url = "";
                    }
                }
                if ($val['cat_fid'] == 0) {
                    $f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => $url,
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',//杨宁宁说不要图片
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return  $farr;
    }
    /**
     * 图文管理分类
     */
    public function atlasInfo()
    {
        $items = array();
        $merchantlist = (new MerchantCategory())->getCategory();
        $categorylist = (new AtlasCategory())->getAtlasCategoryList(['cat_status' => 1], 'cat_sort DESC, cat_id DESC');
        $list = array_merge($merchantlist, $categorylist);
        // print_r($merchantlist);
        // print_r($categorylist);
        // print_r($list);
        // $items[] = [
        //     'cat_id' => 0,
        //     'cat_fid' => 0,
        //     'url' => cfg('site_url') . '/pages/store/v1/home/index',
        //     'flmc' => [
        //         'image' => '',
        //         'txt' => '店铺首页',
        //         'url' => ''
        //     ]
        // ];
        // $f_num = 0;
        $num = 0;
        $farr = array();
        if (!empty($list)) {
            foreach ($list as $val) {
                if ($val['cat_fid'] == 0) {
                    //$f_num++;
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => '',
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $farr[] = $item;//一级
                } else {
                    $item = [
                        'cat_id' => $val['cat_id'],
                        'cat_fid' => $val['cat_fid'],
                        'url' => cfg('site_url') . '/packapp/platn/pages/imageText/v1/category/index?cat_id=' . $val['cat_id'],
                        'flmc' => [
                            //'image' => $val['cat_pic'] ? cfg('site_url') . '/' . $val['cat_pic'] : '',
                            'image' => '',
                            'txt' => $val['cat_name'],
                            'url' => '',
                        ]
                    ];
                    $carr[] = $item;//二级
                }
            }
            if (!empty($farr)) {
                foreach ($farr as $key => $val1) {
                    if (!empty($carr)) {
                        foreach ($carr as $val2) {
                            if ($val1['cat_id'] == $val2['cat_fid']) {
                                $num++;
                                //$val2['flmc']['image'] = $num;
                                $farr[$key]['children'][] = $val2;
                            }
                        }
                        $num = 0;
                    }
                }
            }
        }
        return array_merge($items, $farr);
    }

    public function ymlbInfo($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        $list = (new MicroPageDecorate())->getSome(['source' => $source, 'source_id' => $source_id]);
        $items = array();
        $f_num = 0;
        if (!empty($list)) {
            foreach ($list as $index => $val) {
                $f_num++;
                $item = [
                    'cat_id' => $f_num,
                    'cat_fid' => 0,
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/customPage?pageId=' . $val['id'] . "&source=" . $source . "&source_id=" . $source_id,
                    'ymmc' => [
                        'image' => '',
                        'txt' => $val['page_title'],
                        'url' => cfg('site_url') . '/packapp/plat/pages/customPage/customPage?pageId=' . $val['id'] . "&source=" . $source . "&source_id=" . $source_id
                    ],
                    'yllj' => [
                        'image' => '',
                        'txt' => '预览链接',
                        'url' => cfg('site_url') . '/packapp/plat/pages/customPage/customPage?pageId=' . $val['id'] . "&source=" . $source . "&source_id=" . $source_id
                    ]
                ];
                $items[] = $item;
            }
        }
        return $items;
    }

    public function ptgjInfo($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0, $store_id = 0)
    {
        //根据店铺id获取商家id
        if ($source == 'store') {
            $mer_id = (new MerchantStore())->getOne(['store_id' => $source_id]) ? (new MerchantStore())->getOne(['store_id' => $source_id])['mer_id'] : 0;
        } else {
            $mer_id = $source_id;
        }
        $items = [
            [
                'cat_id' => 1,
                'cat_fid' => 0,
                'url' => 'scan',
                'flmc' => [
                    'image' => '',
                    'txt' => '扫一扫（仅商家小程序首页生效）',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '扫一扫（仅商家小程序首页生效）',
                    'url' => '',
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => '',
                ]
            ],
            [
                'cat_id' => 2,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake',
                'flmc' => [
                    'image' => '',
                    'txt' => '餐饮-自取店铺列表',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '餐饮-自取店铺列表',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake',
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=selftake',
                ]

            ],
            [
                'cat_id' => 3,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList',
                'flmc' => [
                    'image' => '',
                    'txt' => '餐饮-店铺列表',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '餐饮-店铺列表',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList',
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList',
                ]
            ],
            [
                'cat_id' => 4,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book',
                'flmc' => [
                    'image' => '',
                    'txt' => '餐饮-在线预定店铺列表',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '餐饮-在线预定店铺列表',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book',
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=book',
                ]
            ],
            [
                'cat_id' => 5,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse',
                'flmc' => [
                    'image' => '',
                    'txt' => '餐饮-通用码店铺列表',
                    'url' => '',
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '餐饮-通用码店铺列表',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse',
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/foodshop/merchant/storeList?dining_type=inhouse',
                ]
            ],
            [
                'cat_id' => 6,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=pay&mer_id=' . $mer_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '买单',
                    'url' => '',
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '买单',
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=pay&mer_id=' . $mer_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=My&a=pay&mer_id=' . $mer_id,
                ]
            ],
            [
                'cat_id' => 7,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id='.$store_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '用户联系商家客服入口',
                    'url' => '',
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '用户联系商家客服入口',
                    'url' => cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id='.$store_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?c=Kefu&a=contactStore&store_id='.$store_id,
                ]
            ],
            [
                'cat_id' => 8,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=store&store_id='.$store_id.'&mer_id='.$mer_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '商家客服联系用户入口',
                    'url' => '',
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '商家客服联系用户入口',
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=store&store_id='.$store_id.'&mer_id='.$mer_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?g=Wap&c=Kefu&a=store&store_id='.$store_id.'&mer_id='.$mer_id,
                ]
            ]
        ];
        return $items;
    }

    public function yxgjInfo($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        //根据店铺id获取商家id
        if ($source == 'store') {
            $mer_id = (new MerchantStore())->getOne(['store_id' => $source_id]) ? (new MerchantStore())->getOne(['store_id' => $source_id])['mer_id'] : 0;
        } else {
            $mer_id = $source_id;
        }
        $items = [
            [
                'cat_id' => 1,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $mer_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '会员卡',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '会员卡',
                    'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $mer_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id=' . $mer_id,
                ]
            ],
            [
                'cat_id' => 2,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/coupon/storeCoupon?mer_id=' . $mer_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '领券中心',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '领券中心',
                    'url' => cfg('site_url') . '/packapp/plat/pages/coupon/storeCoupon?mer_id=' . $mer_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/coupon/storeCoupon?mer_id=' . $mer_id,
                ]
            ],
        ];

        if(customization('open_merchant_wxapp_score_plugin') == 1){
            $items[] = [
                'cat_id' => 3,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/wap.php?c=My&a=scoreMall&mer_id=' . $mer_id,
                'flmc' => [
                    'image' => '',
                    'txt' => '商家会员卡积分商城',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '商家会员卡积分商城',
                    'url' => cfg('site_url') . '/wap.php?c=My&a=scoreMall&mer_id=' . $mer_id,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/wap.php?c=My&a=scoreMall&mer_id=' . $mer_id,
                ]
            ];
        }
        return $items;
    }

    public function gnymInfo($systemUser, $keyword, $page, $pageSize, $source = 'platform', $source_id = 0)
    {
        $sourceUrl = '';
        if($source == 'store' && $source_id){
            $sourceUrl = '?source=store&source_id=' . $source_id;
        }else if($source == 'merchant' && $source_id){
            $sourceUrl = '?source=merchant&source_id=' . $source_id;
        }
        $items = [
            [
                'cat_id' => 1,
                'cat_fid' => 0,
                'url' => '主页',
                'flmc' => [
                    'image' => '',
                    'txt' => '主页',
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/index' . $sourceUrl,
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '主页（商家小程序）',
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/index' . $sourceUrl,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/index' . $sourceUrl,
                ]
            ],
            [
                'cat_id' => 2,
                'cat_fid' => 0,
                'url' => cfg('site_url') . '/packapp/plat/pages/customPage/my' . $sourceUrl,
                'flmc' => [
                    'image' => '',
                    'txt' => '个人中心',
                    'url' => ''
                ],
                'ymmc' => [
                    'image' => '',
                    'txt' => '个人中心（商家小程序）',
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/my' . $sourceUrl,
                ],
                'yllj' => [
                    'image' => '',
                    'txt' => '预览链接',
                    'url' => cfg('site_url') . '/packapp/plat/pages/customPage/my' . $sourceUrl,
                ]
            ],
        ];
        return $items;
    }

    public function growGrass()
    {
        $alias = cfg('grow_grass_alias')?:'种草';
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [
                    ['txt' => $alias . '页面', 'label' => 'growGrassIndex'],
                    ['txt' => $alias . '话题', 'label' => 'growGrassCategory']
                ]
            ]
        ];
        return $arr;
    }

    public function growGrassIndex()
    {
        $pages = [
            ['name' => '首页', 'url' => get_base_url('pages/wantToBuy/v1/home/index', true)],
            ['name' => '搜索页', 'url' => get_base_url('pages/wantToBuy/v1/search/index', true)],
            ['name' => '话题广场', 'url' => get_base_url('pages/wantToBuy/v1/topicSquare/index', true)],
        ];
        $body = [];
        foreach ($pages as $k => $p) {
            $body[] = [
                'cat_id' => $k,
                'cat_fid' => 0,
                'url' => $p['url'],
                'ymmc' => [
                    'txt' => $p['name'],
                    'url' => '',
                    'image' => ''
                ],
                'yllj' => [
                    'txt' => '预览链接',
                    'url' => $p['url'],
                    'image' => ''
                ]
            ];
        }
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $body
            ]
        ];
        return $arr;
    }

    public function growGrassCategory($systemUser, $keyword, $page, $pageSize, $source, $source_id)
    {
        $where = [['is_del', '=', 0]];
        if ($keyword) {
            $where[] = ['name', 'like', '%' . $keyword . '%'];
        }
        $lists = (new GrowGrassCategoryService())->getCategoryList($where, $page, $pageSize);
        $items = ['list' => [], 'count' => 0];
        if ($lists) {
            foreach ($lists['list'] as $key => $val) {
                $url = get_base_url('pages/wantToBuy/v1/topicDetail/index?category_id=' . $val['category_id'], true);
                $item = [
                    'cat_id' => $val['category_id'],
                    'cat_fid' => 0,
                    'url' => $url,
                    'xzym' => [
                        'txt' => $val['category_id'],
                        'url' => '',
                        'image' => '',
                    ],
                    'ztmc' => [
                        'txt' => $val['name'],
                        'url' => '',
                        'image' => '',
                    ],
                    'yllj' => [
                        'txt' => '预览链接',
                        'url' => $url,
                        'image' => '',
                    ]
                ];
                $items['list'][] = $item;
            }
            $items['count'] = $lists['count'];
        }

        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => true,//为空不展示搜索按钮
            'page_bar' => true,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '专题名称', 'label' => 'ztmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => $items
            ]
        ];
        return $arr;
    }


    private function giftMall()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '常用功能', 'label' => 'giftMallCommonPage']]
            ]
        ];
        return $arr;
    }

    public function giftMallCommonPage(){
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'],  ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 0,
                        'cat_fid' => 0,
                        'url' =>cfg('site_url') . '/wap.php?g=Wap&c=Gift&a=index',
                        'xzym' => [
                            'txt' => cfg('gift_alias_name').'首页',
                            'url' => '',
                            'image' => '',
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?g=Wap&c=Gift&a=index',
                            'image' => '',
                        ]
                    ],
                ]
            ]
        ];
        return $arr;
    }


    private function yhxy()
    {
        $arr = [
            'add_link' => '', //为空时不显示跳转按钮
            'show_search' => false, //为空不展示搜索按钮
            'page_bar' => false, //是否有分页
            'type' => 'list', //列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=plat',
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '平台APP用户协议',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=plat',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=storestaff',
                        'xzym' => [
                            'txt' => 2,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '店员APP用户协议',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=storestaff',
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=merchant',
                        'xzym' => [
                            'txt' => 3,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '商家APP用户协议',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=merchant',
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 4,
                        'cat_fid' => 0,
                        'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=deliver',
                        'xzym' => [
                            'txt' => 4,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '配送APP用户协议',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => cfg('site_url') . '/wap.php?c=Login&a=user_agreement&type=deliver',
                            'image' => ''
                        ]

                    ]
                ]
            ]
        ];
        return $arr;
    }

    //问卷调查功能库入口
    private function questionnaireSurvey()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '问卷列表', 'label' => 'questionnaireSurveyLists']]
            ]
        ];
        return $arr;
    }

    //问卷调查列表
    private function questionnaireSurveyLists($systemUser, $keyword, $page, $pageSize, $source, $source_id)
    {
        $where = [['is_del', '=', 0]];
        if ($keyword) {
            $where[] = ['name', 'like', '%' . $keyword . '%'];
        }
        $lists = (new \app\survey\model\SurveyForm())->where('is_del', 0)
            ->append(['status_text'])
            ->order('create_time desc')
            ->paginate($pageSize)
            ->toArray();
        $items = ['list' => [], 'count' => $lists['total']];
        foreach ($lists['data'] as $val) {
            $url = get_base_url('pages/activity/questionnaire/form?form_id=' . $val['id']);
            $item = [
                'cat_id' => $val['id'],
                'cat_fid' => 0,
                'url' => $url,
                'title' => [
                    'txt' => $val['title'],
                    'url' => '',
                    'image' => '',
                ],
                'valid_data' => [
                    'txt' => $val['end_time'],
                    'url' => '',
                    'image' => '',
                ],
                'status' => [
                    'txt' => $val['status_text'],
                    'url' => '',
                    'image' => '',
                ],
                'yllj' => [
                    'txt' => '预览链接',
                    'url' => $url,
                    'image' => '',
                ]
            ];
            $items['list'][] = $item;
        }

        $arr = [
            'add_link' => '', //为空时不显示跳转按钮
            'show_search' => true, //为空不展示搜索按钮
            'page_bar' => true, //是否有分页
            'type' => 'list', //列表=list  多级分类=cat
            'list' => [
                'head' => [
                    ['txt' => '问卷名称', 'label' => 'title'],
                    ['txt' => '有效期限', 'label' => 'valid_data'],
                    ['txt' => '问卷状态', 'label' => 'status'],
                    ['txt' => '预览链接', 'label' => 'yllj']
                ],
                'body' => $items
            ]
        ];
        return $arr;
    }

    //社群活动分类
    private function socialActivity()
    {
        $arr = [
            'style' => 'list',
            'list' => [
                'left' => [['txt' => '社群活动首页', 'label' => 'socialActivityHome'], ['txt' => '社群活动分类', 'label' => 'socialActivityLists']]
            ]
        ];
        return $arr;
    }

    private function socialActivityHome(){
        $arr = [
            'add_link' => '',//为空时不显示跳转按钮
            'show_search' => false,//为空不展示搜索按钮
            'page_bar' => false,//是否有分页
            'type' => 'list',//列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '选择页面', 'label' => 'xzym'], ['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                    [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/socialActivity/index'),
                        'xzym' => [
                            'txt' => 1,
                            'url' => '',
                            'image' => ''
                        ],
                        'ymmc' => [
                            'txt' => '社群活动首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/socialActivity/index'),
                            'image' => ''
                        ]
                    ]
                ]
            ]
        ];
        return $arr;
    }

    private function socialActivityLists($systemUser, $keyword, $page, $pageSize, $source, $source_id)
    {
        $where = [['status', '=', 1]];
        if ($keyword) {
            $where[] = ['name', 'like', '%' . $keyword . '%'];
        }
        $lists = (new \app\social_activity\model\db\SocialActivityCategory())->where($where)
            ->append(['status_text'])
            ->order('id desc')
            ->paginate($pageSize)
            ->toArray();
        $items = ['list' => [], 'count' => $lists['total']];
        foreach ($lists['data'] as $val) {
            $url = get_base_url('pages/socialActivity/search?category_id=' . $val['id']);
            $item = [
                'cat_id' => $val['id'],
                'cat_fid' => 0,
                'url' => $url,
                'title' => [
                    'txt' => $val['name'],
                    'url' => '',
                    'image' => '',
                ],
                'yllj' => [
                    'txt' => '预览链接',
                    'url' => $url,
                    'image' => '',
                ]
            ];
            $items['list'][] = $item;
        }

        $arr = [
            'add_link' => '', //为空时不显示跳转按钮
            'show_search' => true, //为空不展示搜索按钮
            'page_bar' => true, //是否有分页
            'type' => 'list', //列表=list  多级分类=cat
            'list' => [
                'head' => [
                    ['txt' => '活动分类', 'label' => 'title'],
                    ['txt' => '预览链接', 'label' => 'yllj']
                ],
                'body' => $items
            ]
        ];
        return $arr;
    }

    private function shoppingMallTools()
    {
        $arr = [
            'add_link' => '', //为空时不显示跳转按钮
            'show_search' => false, //为空不展示搜索按钮
            'page_bar' => false, //是否有分页
            'type' => 'list', //列表=list  多级分类=cat
            'list' => [
                'head' => [['txt' => '页面名称', 'label' => 'ymmc'], ['txt' => '预览链接', 'label' => 'yllj']],
                'body' => [
                     [
                        'cat_id' => 1,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/index'),
                        'ymmc' => [
                            'txt' => '首页',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/index'),
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 2,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/order'),
                        'ymmc' => [
                            'txt' => '拍照积分列表',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/order'),
                            'image' => ''
                        ]
                    ],
                    [
                        'cat_id' => 3,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/activity/list'),
                        'ymmc' => [
                            'txt' => '商场活动列表',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/activity/list'),
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 4,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/parking/parkPayment'),
                        'ymmc' => [
                            'txt' => '停车缴费',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/parking/parkPayment'),
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 5,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/parking/myCar'),
                        'ymmc' => [
                            'txt' => '我的车辆',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/parking/myCar'),
                            'image' => ''
                        ]

                    ],
                    [
                        'cat_id' => 6,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/guide'),
                        'ymmc' => [
                            'txt' => '商场导购',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/guide'),
                            'image' => ''
                        ],
                    ],
                    [
                        'cat_id' => 7,
                        'cat_fid' => 0,
                        'url' => get_base_url('pages/shoppingMall/connectWiFi'),
                        'ymmc' => [
                            'txt' => 'WIFI列表',
                            'url' => '',
                            'image' => ''
                        ],
                        'yllj' => [
                            'txt' => '预览链接',
                            'url' => get_base_url('pages/shoppingMall/connectWiFi'),
                            'image' => ''
                        ],
                    ]
                ]
            ]
        ];
        return $arr;
    }
}