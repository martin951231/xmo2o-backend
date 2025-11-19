<script type="text/html" id="time-count-down">
    <p>{{timeString}}</p>
</script>
<style>
    .coupon-list li {
        line-height: 55px;
    }
</style>

<script class="hide" type="text/html" id="al-activities">
    <div :class="[currentTheme]">
        <p class="al-title">{{config.title}}</p>
        <div class="al-container" :class="['al-display-style-'+config.list_display_style]">
            <template v-for="(item,index) in list" v-if="config.list_display_style == 1">
                <a class="al-item" :key="index">
                    <div class="al-main-img"
                         :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                         :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                    ></div>
                    <div class="al-board">
                        <div class="al-type-name"><p>{{config.field_type_name}}</p></div>
                        <template v-if="config.field_type=='presale_module'">
                            <div class="al-time-count">
                                <p>↘尾款立减15元</p>
                            </div>
                            <div v-if="config.presale_price_show" class="al-remain-sku">定金20元</div>
                        </template>
                        <template v-else>
                            <div v-if="config.remain_sku_show" class="al-remain-sku">剩余{{item.remain}}件</div>
                            <div class="al-time-count" v-if="config.time_count_show">
                                <time-count-down
                                    :start-time="item.startTime"
                                    :end-time="item.endTime"
                                ></time-count-down>
                            </div>
                        </template>
                    </div>
                    <div v-if="config.name_show" class="al-good-name textOmitted">
                        <p>{{item.title}}</p>
                    </div>
                    <div class="al-action-list">
                        <div class="al-price">
                            <p class="al-price-prefix">{{config.price_name}}:￥</p>
                            <p class="al-current-price">{{item.curPrice}} </p>
                            <p v-if="config.original_price_show" class="al-original-price"> ￥{{item.price}}</p>
                        </div>
                        <div class="al-buy-btn"
                             :class="['al-buy-btn-style-'+config.buy_btn_style]"
                        >
                            {{config.buy_btn_name}}
                        </div>
                    </div>
                </a>
            </template>
            <div v-if="config.list_display_style == 2" style="overflow-x: scroll">
                <template v-for="(item,index) in list">
                    <a class="al-item" :key="index">
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                        <div class="al-board">
                            <div class="al-type-name"><p>{{config.field_type_name}}</p></div>
                            <template v-if="config.field_type=='presale_module'">
                                <div class="al-time-count">
                                    <p>↘尾款立减15元</p>
                                </div>
                                <div v-if="config.presale_price_show" class="al-remain-sku">定金20元</div>
                            </template>
                            <template v-else>
                                <div v-if="config.remain_sku_show" class="al-remain-sku">剩余{{item.remain}}件</div>
                                <div class="al-time-count" v-if="config.time_count_show">
                                    <time-count-down
                                        :start-time="item.startTime"
                                        :end-time="item.endTime"
                                    ></time-count-down>
                                </div>
                            </template>
                        </div>
                        <div v-if="config.name_show" class="al-good-name textOmitted">
                            <p>{{item.title}}</p>
                        </div>
                        <div class="al-action-list">
                            <div class="al-price">
                                <p class="al-price-prefix">{{config.price_name}}:￥</p>
                                <p class="al-current-price">{{item.curPrice}} </p>
                                <p v-if="config.original_price_show" class="al-original-price"> ￥{{item.price}}</p>
                            </div>
                            <div class="al-buy-btn"
                                 :class="['al-buy-btn-style-'+config.buy_btn_style]"
                            >
                                {{config.buy_btn_name}}
                            </div>
                        </div>
                    </a>
                </template>
            </div>
            <div style="height: 300px;" v-if="config.list_display_style == 3">
                <div class="custom-image-swiper">
                    <div class="swiper-container">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide" v-for="(item,index) in [list[0]]" :key="index">
                                <a class="al-item" url="/pages/seckill/index">
                                    <div class="al-main-img"
                                         :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                                         :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                                    ></div>
                                    <div class="al-board">
                                        <div class="al-type-name"><p>{{config.field_type_name}}</p></div>
                                        <template v-if="config.field_type=='presale_module'">
                                            <div class="al-time-count">
                                                <p>↘尾款立减15元</p>
                                            </div>
                                            <div v-if="config.presale_price_show" class="al-remain-sku">定金20元</div>
                                        </template>
                                        <template v-else>
                                            <div v-if="config.remain_sku_show" class="al-remain-sku">剩余{{item.remain}}件</div>
                                            <div class="al-time-count" v-if="config.time_count_show">
                                                <time-count-down
                                                    :start-time="item.startTime"
                                                    :end-time="item.endTime"
                                                ></time-count-down>
                                            </div>
                                        </template>
                                    </div>
                                    <div v-if="config.name_show" class="al-good-name textOmitted">
                                        <p>{{item.title}}</p>
                                    </div>
                                    <div class="al-action-list">
                                        <div class="al-price">
                                            <p class="al-price-prefix">{{config.price_name}}:￥</p>
                                            <p class="al-current-price">{{item.curPrice}} </p>
                                            <p v-if="config.original_price_show" class="al-original-price"> ￥{{item.price}}</p>
                                        </div>
                                        <div class="al-buy-btn"
                                             :class="['al-buy-btn-style-'+config.buy_btn_style]"
                                        >
                                            {{config.buy_btn_name}}
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"><span class="swiper-pagination-switch swiper-active-switch"></span><span
                        class="swiper-pagination-switch"></span></div>
            </div>

            <template v-for="(item,index) in list" v-if="config.list_display_style == 4">
                <a class="al-item clearfix" :key="index">
                    <div class="al-main-img"
                         :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                         :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                    ></div>
                    <div class="al-board">
                        <div class="al-type-name"><p>{{config.field_type_name}}</p></div>
                        <template v-if="config.field_type=='presale_module'">
                            <div v-if="config.presale_price_show" class="al-remain-sku" style="margin-top: 6px;">定金20元</div>
                        </template>
                        <template v-else>
                            <div class="al-time-count" v-if="config.time_count_show">
                                <time-count-down
                                    :start-time="item.startTime"
                                    :end-time="item.endTime"
                                ></time-count-down>
                            </div>
                        </template>
                    </div>
                    <div v-if="config.name_show" class="al-good-name">
                        {{item.title}}
                    </div>
                    <div class="al-action-list">
                        <div class="al-price">
                            <p class="al-price-prefix">{{config.price_name}}:￥</p>
                            <p class="al-current-price">{{item.curPrice}} </p>
                            <p v-if="config.original_price_show" class="al-original-price"> ￥{{item.price}}</p>
                        </div>
                        <div>
                            <div v-if="config.remain_sku_show && config.field_type != 'presale_module'" class="al-remain-sku">剩余{{item.remain}}件</div>
                            <div class="al-buy-btn"
                                 :class="['al-buy-btn-style-'+config.buy_btn_style,config.field_type != 'presale_module'?'':'al-btn-buy-md',config.remain_sku_show?'':'al-btn-buy-md']"
                            >
                                {{config.buy_btn_name}}
                            </div>
                        </div>
                    </div>
                </a>
            </template>
            <template v-for="(item,index) in list" v-if="config.list_display_style == 5">
                <a class="al-item clearfix" :key="index">
                    <div class="al-left">
                        <div class="al-main-img"
                             :class="['al-main-image-size-style-'+config.main_image_size_style, 'al-main-image-fill-style-'+config.main_image_fill_style]"
                             :style="{background:'url('+item.img+') center no-repeat',backgroundSize:config.main_image_fill_style == 1 ? 'cover' : 'contain'}"
                        ></div>
                        <div class="al-board">
                            <template v-if="config.field_type=='presale_module'">
                                <div class="al-time-count" v-if="config.time_count_show">
                                    <p>↘尾款立减15元</p>
                                </div>
                            </template>
                            <template v-else>
                                <div class="al-time-count" v-if="config.time_count_show">
                                    <time-count-down
                                        :start-time="item.startTime"
                                        :end-time="item.endTime"
                                    ></time-count-down>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="al-right">
                        <div v-if="config.name_show" class="al-good-name">
                            {{item.title}}
                        </div>
                        <div class="al-action-list">
                            <div class="al-price">
                                <p class="al-price-prefix">{{config.price_name}}:￥</p>
                                <p class="al-current-price">{{item.curPrice}} </p>
                                <p v-if="config.original_price_show" class="al-original-price"> ￥{{item.price}}</p>
                            </div>
                            <div v-if="config.field_type=='presale_module'">
                                <div class="al-remain-sku" v-if="config.presale_price_show">定金15元</div>
                                <div class="al-buy-btn"
                                     :class="['al-buy-btn-style-'+config.buy_btn_style,config.presale_price_show?'':'al-btn-buy-lg']"
                                >
                                    {{config.buy_btn_name}}
                                </div>
                            </div>
                            <div v-else>
                                <div class="al-remain-sku" v-if="config.remain_sku_show">剩余{{item.remain}}件</div>
                                <div class="al-buy-btn"
                                     :class="['al-buy-btn-style-'+config.buy_btn_style,config.remain_sku_show?'':'al-btn-buy-lg']"
                                >
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
                        <label class="radio inline"><input type="radio" value="2" v-model="config.list_display_style">大图左右滑动</label>
                        <label class="radio inline"><input type="radio" value="3" v-model="config.list_display_style">大图轮播</label>
                        <label class="radio inline"><input type="radio" value="4" v-model="config.list_display_style">小图平铺</label>
                        <label class="radio inline"><input type="radio" value="5" v-model="config.list_display_style">详细列表</label>
                    </div>
                </div>
                <div class="control-group"><label class="control-label" style="width: 119px;">图片填充方式：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <label class="radio inline"><input type="radio" value="1" v-model="config.main_image_fill_style">填充</label>
                        <label class="radio inline"><input type="radio" value="2" v-model="config.main_image_fill_style">留白</label>
                    </div>
                </div>
                <div v-if="config.list_display_style==1 || config.list_display_style==2" class="control-group"><label class="control-label" style="width: 119px;" title="图片显示比例 在“大图平铺、大图左右滑动”情况下才有。其他字段所有展示样式下都一样。">图片显示比例：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <label class="radio inline"><input type="radio" value="1" v-model="config.main_image_size_style">3:2</label>
                        <label class="radio inline"><input type="radio" value="2" v-model="config.main_image_size_style">1:1</label>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label" style="width: 119px;">显示内容：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <template v-if="config.field_type=='presale_module'">
                            <label class="checkbox inline"><input type="checkbox" v-model="config.presale_price_show" >预售定金</label>
                        </template>
                        <template v-else>
                            <label class="checkbox inline"><input type="checkbox" v-model="config.time_count_show">{{config.field_type_name}}计时器</label>
                            <label class="checkbox inline"><input type="checkbox" v-model="config.remain_sku_show" >剩余库存</label>
                        </template>
                        <label class="checkbox inline"><input type="checkbox" v-model="config.name_show" >商品名称</label>
                        <label class="checkbox inline"><input type="checkbox" v-model="config.original_price_show" >{{config.field_type_name}}前商品原售价</label>
                    </div>
                </div>

                <div class="control-group"><label class="control-label" style="width: 119px;">购买样式：</label>
                    <div class="controls" style="margin-left: 104px;">
                        <label class="radio inline"><input type="radio" value="1" v-model="config.buy_btn_style">方形线框</label>
                        <label class="radio inline"><input type="radio" value="2" v-model="config.buy_btn_style">方形实心</label>
                        <label class="radio inline"><input type="radio" value="3" v-model="config.buy_btn_style">圆角线框</label>
                        <label class="radio inline"><input type="radio" value="4" v-model="config.buy_btn_style">圆角实心</label>
                    </div>
                </div>
                <div class="control-group"><label class="control-label" style="width: 119px;">{{config.field_type_name}}按钮名称：</label>
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