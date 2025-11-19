<style>
    .coupon-list li {
        line-height: 55px;
    }

    .periodic-icon-1 {
        background: url(./tpl/Merchant/default/static/diypage/images/mall_periodic_icon_1.png) no-repeat;
        position: absolute;
        float: left;
        left: 17px;
        width: 80px;
        height: 80px;
    }
    .periodic-icon-1-0 {
        top: 49px;
    }
    .periodic-icon-1-1 {
        top: 453px;
    }
    .periodic-icon-1-2 {
        top: 858px;
    }
    .periodic-icon-4 {
        background: url(./tpl/Merchant/default/static/diypage/images/mall_periodic_icon_2.png) no-repeat;
        position: absolute;
        float: left;
        top: 31px;
        width: 30px;
        height: 30px;
    }
    .periodic-icon-4-0 {
        left: 17px;
    }
    .periodic-icon-4-1 {
        left: 172px;
    }
    .periodic-icon-4-2 {
        left: 17px;
        top:285px;
    }
    .periodic-icon-5 {
        background: url(./tpl/Merchant/default/static/diypage/images/mall_periodic_icon_2.png) no-repeat;
        position: absolute;
        float: left;
        top: 43px;
        left: 17px;
        width: 30px;
        height: 30px;
    }
    .periodic-icon-5-0 {
        top: 44px;
    }
    .periodic-icon-5-1 {
        top: 180px;
    }
    .periodic-icon-5-2 {
        top: 317px;
    }
</style>

<script class="hide" type="text/html" id="al-activities">
    <div :class="[currentTheme]">
        <p class="al-title">{{config.title}}</p>
        <div class="al-container" :class="['al-display-style-'+config.list_display_style]">
<!--大图平铺-->
            <template v-for="(item,index) in list" v-if="config.list_display_style == 1">
                <div v-if="config.join_show && config.field_type != 'seckill_module' && config.field_type != 'periodic_module'" class="al-buy-btn-style-number-1">{{config.join_show_txt}}</div>
                <a class="al-item" :key="index">
                    <template v-if="config.field_type == 'periodic_module'">
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                        <div :class="['periodic-icon-1', 'periodic-icon-'+config.list_display_style+'-'+index]"></div>
                    </template>
                    <template v-else>
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                    </template>
                    <div class="al-board" v-if="config.time_count_show && config.field_type != 'periodic_module'" >
                        <div class="al-type-name"><p>{{config.field_type_name}}</p></div>
                        <template>
                            <div class="al-time-count" v-if="config.time_count_show">
                                <p><span :class="['time-count-down-' + config.list_display_style]" >抢购倒计时</span> <br><span :class="['time-count-down-time' + config.list_display_style]">00 时 00 分 00 秒</span></p>
                            </div>
                        </template>
                    </div>
                    <div v-if="config.name_show" class="al-good-name-1 textOmitted">
                        <p v-if="config.field_type != 'periodic_module'" class="al-buy-btn-style-goods-1"> {{config.goods_btn_name}}</p>
                        <p>此处显示商品名称，最多显示一行</p>
                    </div>
<!--                    <div v-if="config.desc_show" class="al-good-desc textOmitted">-->
<!--                        <p>此处显示商品描述，最多显示一行</p>-->
<!--                    </div>-->
                    <div v-if="config.field_type=='seckill_module' && config.remain_sku_show" class="al-remain-sku textOmitted">
                        <p>仅剩20件</p>
                    </div>
                    <div class="al-action-list">
                        <div class="al-price">
                            <p v-if="config.current_price_show" class="al-current-price">￥{{item.curPrice}} </p>
                            <p v-if="config.original_price_show && config.field_type != 'periodic_module'" class="al-original-price"> ￥{{item.price}}</p>
                        </div>
                        <div class="al-buy-btn" :class="['al-buy-btn-style-'+config.buy_btn_style]" v-if="config.buy_btn">
                            {{config.buy_btn_name}}
                        </div>
                    </div>
                </a>
            </template>
<!--左右滑动-->
            <div v-if="config.list_display_style == 2" style="overflow-x: scroll">
                <template v-for="(item,index) in list">
                    <a class="al-item" :key="index">
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                        <p v-if="config.field_type != 'periodic_module'" class="al-buy-btn-style-goods-2"> {{config.goods_btn_name}}</p>
                        <div v-if="config.name_show" class="al-good-name-2 textOmitted">
                            <p>此处显示商品名称...</p>
                        </div>
                        <div v-if="config.field_type=='seckill_module' && config.remain_sku_show" class="al-remain-sku textOmitted">
                            <p>仅剩20件</p>
                        </div>
<!--                        <div v-if="config.field_type!='seckill_module' && config.desc_show" class="al-good-desc textOmitted">-->
<!--                            <p>此处显示商品描述，最多显示一行</p>-->
<!--                        </div>-->
                        <div class="al-action-list">
                            <div class="al-price" style="margin-left: 4.53px;">
                                <p v-if="config.current_price_show" class="al-current-price">￥{{item.curPrice}} </p>
                                <p v-if="config.original_price_show && config.field_type != 'periodic_module'" class="al-original-price"> ￥{{item.price}}</p>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
<!--小图平铺 -->
            <template v-for="(item,index) in list" v-if="config.list_display_style == 4">
                <a class="al-item clearfix" :key="index">
                    <template v-if="config.field_type == 'periodic_module'">
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                        <div :class="['periodic-icon-4', 'periodic-icon-'+config.list_display_style+'-'+index]"></div>
                    </template>
                    <template v-else>
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                    </template>
                    <div v-if="config.join_show && config.field_type != 'seckill_module' && config.field_type != 'periodic_module'" class="al-buy-btn-style-number-4">{{config.join_show_txt}}</div>
                    <div class="al-board" v-if="config.time_count_show && config.field_type != 'periodic_module'" >
                        <template>
                            <div class="al-time-count" v-if="config.time_count_show">
                                <p><span :class="['time-count-down-' + config.list_display_style]" >距开始</span><span :class="['time-count-down-time' + config.list_display_style]">00:00:00</span></p>
                            </div>
                        </template>
                    </div>
                    <div v-if="config.name_show" class="al-good-name-4 textOmitted">
                        <p v-if="config.field_type != 'periodic_module'" class="al-buy-btn-style-goods-4"> {{config.goods_btn_name}}</p>
                        <p>此处显示商</p>
                    </div>
                    <div v-if="config.name_show" class="al-good-name-4 textOmitted" style="margin-top: -3px">
                        <p>品名称，最多显...</p>
                    </div>
<!--                    <div v-if="config.desc_show" class="al-good-desc textOmitted">-->
<!--                        <p>此处显示商品描述，...</p>-->
<!--                    </div>-->
                    <div v-if="config.field_type=='seckill_module' && config.remain_sku_show" class="al-remain-sku textOmitted">
                        <p>仅剩20件</p>
                    </div>
                    <div class="al-action-list">
                        <div class="al-price">
                            <p v-if="config.current_price_show" class="al-current-price">￥{{item.curPrice}}</p>
                            <p v-if="config.original_price_show && config.field_type != 'periodic_module'" class="al-original-price"> ￥{{item.price}}</p>
                        </div>
                        <div class="al-buy-btn" :class="['al-buy-btn-style-'+config.buy_btn_style]" v-if="config.buy_btn">
                            {{config.buy_btn_name}}
                        </div>
                    </div>
                </a>
            </template>
<!--详细列表-->
            <template v-for="(item,index) in list" v-if="config.list_display_style == 5">
                <a class="al-item clearfix" :key="index">
                    <div class="al-left">
                        <template v-if="config.field_type == 'periodic_module'">
                            <div class="al-main-img"
                                 :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                                 :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                            ></div>
                            <div :class="['periodic-icon-5', 'periodic-icon-'+config.list_display_style+'-'+index]"></div>
                        </template>
                        <template v-else>
                            <div class="al-main-img"
                                 :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                                 :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                            ></div>
                        </template>
                        <div v-if="config.join_show && config.field_type != 'seckill_module' && config.field_type != 'periodic_module'" class="al-buy-btn-style-number-5">{{config.join_show_txt}}</div>
                        <div class="al-board" v-if="config.time_count_show && config.field_type != 'periodic_module'">
                            <div class="al-time-count">
                                <p><span :class="['time-count-down-' + config.list_display_style]" >&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;距开始</span><span :class="['time-count-down-time' + config.list_display_style]">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;00:00:00</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="al-right">
                        <div class="al-action-list">
                            <div v-if="config.name_show" class="al-good-name-5 textOmitted">
                                <p v-if="config.field_type != 'periodic_module'" class="al-buy-btn-style-goods-4"> {{config.goods_btn_name}}</p>
                                <p>此处显示商</p>
                            </div>
                            <div v-if="config.name_show" class="al-good-name-5 textOmitted" style="margin-top: -3px">
                                <p>品名称，最多显...</p>
                            </div>
<!--                            <div v-if="config.desc_show" class="al-good-desc textOmitted">-->
<!--                                <p>此处显示商品描述，...</p>-->
<!--                            </div>-->
                            <div v-if="config.field_type=='seckill_module' && config.remain_sku_show" class="al-remain-sku textOmitted" style="position: absolute;top: 125px;">
                                <p>仅剩20件</p>
                            </div>
                            <div class="al-price" style="margin-left: 5.53px;">
                                <p v-if="config.current_price_show" class="al-current-price">￥{{item.curPrice}}</p>
                                <p v-if="config.original_price_show && config.field_type != 'periodic_module'" class="al-original-price"> ￥{{item.price}}</p>
                            </div>
                            <div>
                                <div class="al-buy-btn" :class="['al-buy-btn-style-'+config.buy_btn_style]" v-if="config.buy_btn"">
                                    {{config.buy_btn_name}}
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </template>
        </div></div>

</script>
<script class="hide" type="text/html" id="right-htmls">
    <div>
        <div class="form-horizontal">
            <div class="js-meta-region" style="margin-bottom:20px;">
                <div class="control-group"><label class="control-label" style="width: 119px;">选择活动：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <ul class="module-goods-list clearfix ui-sortable coupon-list js-activity-list"
                            name="activit_list" style="margin-left: 28px;">
                            <li class="sort" v-for="(act,index) in activities" :key="index">
                                <a :href="act.url" style="overflow: hidden;">
                                    <img  :price="act.price" :src="act.image" :alt="act.title" :title="act.title" width="50" height="50">
                                </a>
                                <a class="close-modal small hide" title="删除" @click="delActivity(act.id)">×</a>
                            </li>
                            <li><a href="javascript:void(0);" class="add-goods"><i class="icon-add"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="control-group"><label class="control-label" style="width: 119px;">标题名称：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <input type="text" maxlength="10" v-model="config.title">
                    </div>
                </div>
                <div class="control-group"><label class="control-label" style="width: 119px;">展示样式：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <label class="radio inline"><input type="radio" value="1" v-model="config.list_display_style">大图平铺</label>
                        <label v-if="config.field_type != 'periodic_module'" class="radio inline"><input type="radio" value="2" v-model="config.list_display_style">左右滑动</label>
                        <label class="radio inline"><input type="radio" value="4"  v-model="config.list_display_style">小图平铺</label>
                        <label class="radio inline"><input type="radio" value="5" v-model="config.list_display_style">详细列表</label>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" style="width: 119px;">显示内容：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <template v-if="config.field_type=='periodic_module'">
                            <label class="checkbox inline"><input type="checkbox" v-model="config.name_show" >商品名称</label>
<!--                            <label class="checkbox inline"><input type="checkbox" v-model="config.desc_show" >商品描述</label>-->
                            <label class="checkbox inline"><input type="checkbox" v-model="config.buy_btn" >购买按钮</label>
                        </template>
                        <template v-else>
                            <label v-if="config.list_display_style!=2" class="checkbox inline"><input type="checkbox" v-model="config.time_count_show">抢购倒计时</label>
                            <label v-if="config.field_type=='seckill_module'" class="checkbox inline"><input type="checkbox" v-model="config.remain_sku_show" >剩余库存</label>
                            <label v-if="config.field_type!='seckill_module' && config.list_display_style!=2" class="checkbox inline"><input type="checkbox" v-model="config.join_show" >{{config.join_number}}</label>
                            <label class="checkbox inline"><input type="checkbox" v-model="config.name_show" >商品名称</label>
<!--                            <label v-if="!(config.field_type =='seckill_module' && config.list_display_style==2)" class="checkbox inline"><input type="checkbox" v-model="config.desc_show" >商品描述</label>-->
                            <label class="checkbox inline"><input type="checkbox" v-model="config.current_price_show" >{{config.current_price_show_txt}}</label>
                            <label class="checkbox inline"><input type="checkbox" v-model="config.original_price_show" >{{config.original_price_show_txt}}</label>
                            <label  v-if="config.list_display_style!=2" class="checkbox inline"><input type="checkbox" v-model="config.buy_btn" >购买按钮</label>
                        </template>
                    </div>
                </div>

                <div class="control-group" v-if="config.buy_btn && config.list_display_style!=2"><label class="control-label" style="width: 119px;">购买样式：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <label class="radio inline"><input type="radio" value="1" v-model="config.buy_btn_style">方形线框</label>
                        <label class="radio inline"><input type="radio" value="2" v-model="config.buy_btn_style">方形实心</label>
                        <label class="radio inline"><input type="radio" value="3" v-model="config.buy_btn_style">圆角线框</label>
                        <label class="radio inline"><input type="radio" value="4" v-model="config.buy_btn_style">圆角实心</label>
                    </div>
                </div>
                <div v-if="config.list_display_style!=2" class="control-group">
                    <label class="control-label" style="width: 119px;">{{config.field_type_name}}按钮名称：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <input type="text" maxlength="4" v-model="config.buy_btn_name">
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" style="width: 119px;">其他设置：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <!--                        <label class="checkbox inline"><input type="checkbox" v-model="config.auto_increate">活动发布自动填充</label>-->
                        <label class="checkbox inline"><input type="checkbox" v-model="config.outsale_hide">隐藏已售罄的活动</label>
                        <label class="checkbox inline"><input type="checkbox" v-model="config.finish_hide">隐藏已结束的活动</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>