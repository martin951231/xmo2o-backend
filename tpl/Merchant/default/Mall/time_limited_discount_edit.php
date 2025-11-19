<include file="Public:header"/>

<div class="main-content">

    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tab-content">
                        <div class="grid-view">
                            <h2 style="margin-bottom: 20px">{pigcms{:L_('编辑限时优惠活动')}</h2>
                            <form enctype="multipart/form-data" class="myform"  method="post" >

                                <input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}">
                                <input type="hidden" name="id" value="{pigcms{$info.id}">
                                <div class="tab-content card_new" id="tab1" >
                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("活动时间")}:</label></label>
                                        <input type="text" class="input-text" name="start_date" id="start_date" style="width:160px;" value="{pigcms{$info.start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" />&nbsp;&nbsp;-&nbsp;&nbsp;
                                        <input type="text" class="input-text" name="end_date" id="end_date" style="width:160px;" value="{pigcms{$info.end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" />&nbsp;<font color="red">*</font>
                                        <span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
                                    </div>

                                    <div class="form-group flex flex-align">
                                        <label class="tiplabel"><label>{pigcms{:L_("活动周期")}:</label></label>
                                        <label class=" cycle_type">
                                            <volist name="week_arr" id="item">
                                                <if condition="in_array($key,$info['week_arr'])">
                                                    <label class="show_date fl active" data-value="{pigcms{$key}">{pigcms{$item}</label>
                                                 <else/>
                                                    <label class="show_date fl" data-value="{pigcms{$key}">{pigcms{$item}</label>
                                                </if>

                                            </volist>
                                            <input type="hidden" id="week" name="week" value="{pigcms{$info.week}">
                                        </label>&nbsp;&nbsp;{pigcms{:L_("的")}&nbsp;&nbsp;
                                        <input type="text" class="input-text" name="start_time" id="start_time" style="width:80px;" value="{pigcms{$info.start_time|substr=###,0,5}" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})" />&nbsp;&nbsp;{pigcms{:L_("至")}&nbsp;&nbsp;
                                        <input type="text" class="input-text" name="end_time" id="end_time" style="width:80px;" value="{pigcms{$info.end_time|substr=###,0,5}" onfocus="WdatePicker({isShowClear:false,dateFmt:'HH:mm'})" />&nbsp;<font color="red">*</font>
                                    </div>

                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("每单限购")}:</label></label>
                                            <input type="radio" name="limit_type" value="0" <if condition="$info['limit_type'] eq 0">checked="checked"</if>/>{pigcms{:L_("不限")}&nbsp;&nbsp;&nbsp;
                                            <label><input type="radio" name="limit_type" value="1" <if condition="$info['limit_type'] eq 1">checked="checked"</if> />{pigcms{:L_("自定义")}  &nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="input" name="limit_num" value="{pigcms{$info.limit_num}" autocomplete="off" />&nbsp;{pigcms{:L_("份")}&nbsp;</label>
                                    </div>

                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("库存类型")}:</label></label>
                                        <if condition="$in_progress">
                                            <input disabled type="radio" name="stock_type" value="0" <if condition="$info['stock_type'] eq 0">checked="checked"</if> onclick="change_stock_type(0)"/>{pigcms{:L_("每日库存")}&nbsp;&nbsp;&nbsp;&nbsp;
                                            <input disabled type="radio" name="stock_type" value="1" <if condition="$info['stock_type'] eq 1">checked="checked"</if> onclick="change_stock_type(1)"/>{pigcms{:L_("总库存")}
                                            <span style="margin-left: 10px;color: #ff0000">{pigcms{:L_("每日库存即每天自动置满固定量的库存；系统将会在每日凌晨0点开始更新，请在系统更新后查看相关每日库存回滚情况")}</span>
                                        <else/>
                                            <input type="radio" name="stock_type" value="0" <if condition="$info['stock_type'] eq 0">checked="checked"</if> onclick="change_stock_type(0)"/>{pigcms{:L_("每日库存")}&nbsp;&nbsp;&nbsp;&nbsp;
                                            <input type="radio" name="stock_type" value="1" <if condition="$info['stock_type'] eq 1">checked="checked"</if> onclick="change_stock_type(1)"/>{pigcms{:L_("总库存")}
                                            <span style="margin-left: 10px;color: #ff0000">{pigcms{:L_("每日库存即每天自动置满固定量的库存；总库存即固定的总库存，会不断减少，不会每天自动置满库存。如设置每日更新库存，则系统将会在每日凌晨0点开始更新，请在系统更新后查看相关每日库存回滚情况")}</span>
                                        </if>
                                    </div>
                                    <div class="form-group wx_coupon">
                                        <label class="tiplabel"><label>{pigcms{:L_("活动商品")}</label></label>

                                        <br/><br/>
                                        <table class="table table-striped table-bordered repWrap">
                                            <tr>
                                                <th>{pigcms{:L_("促销商品")}</th>
                                                <th>{pigcms{:L_("规格")}</th>
                                                <th>{pigcms{:L_("原价")}</th>
                                                <th>{pigcms{:L_("活动价")}</th>
                                                <th>{pigcms{:L_("折扣")}</th>
                                                <th class="stock_type_title">{pigcms{:L_("总库存(当前/原始)")}</th>
                                            </tr>

                                            <if condition="$info.is_spec eq 1">
                                                <volist name="info.spec_list" id="o" key="k">
                                                    <if condition="$k eq 1">
                                                        <tr>
                                                            <input type="hidden" name="spec_id[]" value="{pigcms{$o.id}"/>
                                                            <td rowspan="{pigcms{$info.spec_count}">
                                                                <div class="goods_pic"><img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="{pigcms{$info.image}"></div>
                                                            <div class="goods_name">{pigcms{$info.goods_name}</div></td>
                                                            <td>{pigcms{$o.str}</td>
                                                            <td>{pigcms{$o.price}</td>
                                                            <td>
                                                               <input type="text" name="limit_price[]" class="limit_price" value="{pigcms{$o.limit_price}">
                                                            </td>
                                                            <td class="red_color">
                                                                <php>
                                                                    if($o['price'] > 0){
                                                                        echo (getFormatNumber($o['limit_price']/$o['price'])*10).L_('折');
                                                                    }else{
                                                                        echo '-';
                                                                    }
                                                                </php>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="stock" name="stock[]" value="{pigcms{$o.stock}"/> /
                                                                <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$o.origin_stock}"/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
                                                            </td>
                                                        </tr>
                                                    <else/>
                                                        <tr>
                                                            <input type="hidden" name="spec_id[]" value="{pigcms{$o.id}"/>
                                                            <td>{pigcms{$o.str}</td>
                                                            <td>{pigcms{$o.limit_price}</td>
                                                            <td>
                                                               <input type="text" name="limit_price[]" class="limit_price" value="{pigcms{$o.limit_price}">
                                                            </td>
                                                            <td class="red_color">
                                                                <php>
                                                                    if($o['price'] > 0){
                                                                        echo (getFormatNumber($o['limit_price']/$o['price'])*10).L_('折');
                                                                    }else{
                                                                        echo '-';
                                                                    }
                                                                </php>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="stock" name="stock[]" value="{pigcms{$o.stock}"/> /
                                                                <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$o.origin_stock}"/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
                                                            </td>
                                                        </tr>
                                                    </if>
                                                </volist>
                                            <else/>
                                                <tr>
                                                    <td>
                                                        <div class="goods_pic"><img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="{pigcms{$info.image}"></div>
                                                        <div class="goods_name">{pigcms{$info.goods_name}</div></td>
                                                    <td>无</td>
                                                    <td>{pigcms{$info.price}</td>
                                                    <td>
                                                        <input type="text" name="limit_price[]" class="limit_price" value="{pigcms{$info.limit_price}">
                                                    </td>
                                                    <td class="red_color">
                                                        <php>
                                                            if($info['price'] > 0){
                                                                echo (getFormatNumber($info['limit_price']/$info['price'])*10).L_('折');
                                                            }else{
                                                                echo '-';
                                                            }
                                                        </php>
                                                    </td>
                                                    <td>
                                                        <input type="number" class="stock" name="stock[]" value="{pigcms{$info.stock}"/> /
                                                        <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$info.origin_stock}"/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
                                                    </td>
                                                </tr>
                                            </if>


                                        </table>
                                    </div>
                                    <div class="clearfix form-actions">
                                        <div class="col-md-offset-3 col-md-9" style="margin-left:0px">
                                            <button style="margin-left: 200px;" class="btn btn-info" type="button" onclick="check_stock()">
                                                <i class="ace-icon fa fa-check bigger-110"></i>
                                                {pigcms{:L_("保存")}
                                            </button>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/upyun.js"></script>
<script src="./static/js/cart/jscolor.js" type="text/javascript"></script>
<link rel="stylesheet" href="./static/kindeditor/themes/default/default.css"/>
<link rel="stylesheet" href="./static/kindeditor/plugins/code/prettify.css"/>
<style>

    .show_date.active {
        color: #067ADE;
        border: #067ADE 1px solid;
    }
    .show_date {
        background-color: white;
        color: #0a0a0a;
        border: #b6b4a4 1px solid;
        padding: 5px 10px;
        margin-bottom: 5px;
        cursor: pointer;
        margin-right: 5px;
    }
    .fl {
        float: left;
        display: inline;
    }
    .red_color{
        color: red;
        font-weight: bold
    }
    input[type=number]{
        width: 70px
    }

    .limit_price{
        width: 60px;
    }
    .goods_name{
        display: inline-block;
        max-width: 100px;
        word-break: break-word;
        vertical-align: middle
    }
    .goods_pic{
        display: inline-block
    }
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<link rel="stylesheet" href="./static/validate/dist/css/bootstrapValidator.css"/>
<script type="text/javascript" src="./static/validate/vendor/jquery/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="./static/validate/vendor/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="./static/validate/dist/js/bootstrapValidator.js"></script>
<script type="text/javascript">
</script>
<script type="text/javascript">
    //折扣联动
    $('.limit_price').on('blur', function () {
        var limit_price = parseFloat($(this).val());
        var old_price = parseFloat($(this).parent('td').prev().html());
        var ratio = 10;
        if (old_price > 0) {
            ratio = String(limit_price * 10 / old_price);
            if (ratio.indexOf('.') != -1) {
                ratio = parseFloat(ratio).toFixed(2);
            }
        }
        $(this).parent('td').next().html(ratio + "折")
    })

    // 点击选择日期
    function check_stock() {
        var submit = true;
        var stock_filled = true;
        $('.stock').each(function () {
            var stock = parseInt($(this).val());
            var origin_stock = parseInt($(this).siblings('.origin_stock').val());
            if (stock === '' || isNaN(stock) || origin_stock === '' || isNaN(origin_stock)) {
                stock_filled = false;
            }
            if(origin_stock != -1){
                if(stock == -1){
                    submit = false
                }else if (origin_stock < stock) {
                    submit = false
                }
            }
        })
        if (!stock_filled) {
            layer.alert('{pigcms{:L_("请填写库存")}');
            return false;
        }
        if (submit) {
            $('.myform').submit();
        } else {
            layer.alert('{pigcms{:L_("原始库存必须大于当前库存")}');
            return false;
        }
    }

    $('.show_date').click(function ()  {
        var value = $(this).data('value');
        var is_active = $(this).hasClass('active');
        if (is_active) {
            $(this).removeClass('active');
        }else{
            $(this).addClass('active');
        }
        var date_arr = [];
        var node = $(this).parent().children('label');
        node.each(function(i,val){
            var val_active = $(val).hasClass('active');
            if (val_active) {
                date_arr.push($(val).data('value'));
            }
        });
        date_arr = date_arr.join(',');
        $('#week').val(date_arr);
    });

    // 打开添加商品弹层
    $('.add_goods').on('click',function(){
        var savegood=$(this).attr('data-savegood');
        parent.layer.open({
            title: false,
            closeBtn: 0,
            type: 2,
            content: '{pigcms{$this->config['
            site_url']}/merchant.php?c=Shop&a=get_time_limited_discount_goods&store_id={pigcms{$_GET.store_id}&savegood='+savegood+'',
            area: ['950px', '650px'],
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    // 选择商品后回调方法
    function login_box_after(name,price,spec_value,index,savegood,goods_id,stock_num,image){
        //菜品不能重复
        console.log(typeof spec_value);
        console.log(spec_value);
        var goods_ids= $("input[name='goods_id[]'").val();
        var is_repeat=false;
        $.each($("input[name='goods_id[]'"),function (e) {
            if ($(this).val()==goods_id) {
                is_repeat = true;
            }
        })
        if (is_repeat) {
            layer.alert('{pigcms{:L_("不能添加重复商品")}');
            return false;
        }


        var spec_len = spec_value.length;
        var html = '';

        if (spec_len > 0) {
            for (var i = 0; i < spec_len; i++) {
                if (i == 0) {
                    html += '<tr class="tr_' + goods_id + '">\
                                 <th rowspan="' + spec_len + '">' + '<img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="' + image + '">' + name + '</th>\
                                 <td>' + spec_value[i].str + '</td>\
                                 <td>' + spec_value[i].price + '</td>\
                                 <td><input type="number" name="limit_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td class="red_color">0折</td>\
                                 <td><input type="number" name="stock[' + goods_id + '][]"/> / <input type="number" name="origin_stock[' + goods_id + '][]" /></td>\
                                 <td rowspan="' + spec_len + '"  onclick="del_row(' + goods_id + ')">删除</td>\
                             </tr>';
                    html += '<input type="hidden" name="spec_index[' + goods_id + '][]" value="' + spec_value[i].key + '"/>';
                } else {
                    html += '<tr class="tr_' + goods_id + '">\
                                 <td>' + spec_value[i].str + '</td>\
                                 <td>' + spec_value[i].price + '</td>\
                                 <td><input type="number" name="limit_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td class="red_color">0折</td>\
                                 <td><input type="number" name="stock[' + goods_id + '][]"/> / <input type="number" name="origin_stock[' + goods_id + '][]" /></td>\
                             </tr>';
                    html += '<input class="tr_' + goods_id + '" type="hidden" name="spec_index[' + goods_id + '][]" value="' + spec_value[i].key + '"/>';
                }
            }
        } else {
            html += '<tr class="tr_' + goods_id + '">\
                         <th><img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="' + image + '">' + name + '</th>\
                         <td>无</td>\
                         <td> ' + price + '</td>\
                         <td><input type="number" name="limit_price[' + goods_id + '][]" class="limit_price" value="' + price + '"></td>\
                         <td class="red_color">0折</td>\
                         <td><input type="number" name="stock[' + goods_id + '][]"/> / <input type="number" name="origin_stock[' + goods_id + '][]" /></td>\
                         <td onclick="del_row(' + goods_id + ')">删除</td>\
                    </tr>';
            html += '<input class="tr_' + goods_id + '" type="hidden" name="spec_index[' + goods_id + '][]" value=""/>';
        }
        html += '<input class="tr_' + goods_id + '" type="hidden" name="goods_id[]" value="' + goods_id + '"/>';
        $(".repWrap").append(html);
    }

    // 删除商品
    function del_row(goods_id) {
        $('.tr_' + goods_id).remove();
    }

    //切换库存类型
    function change_stock_type(type){
        if(type == 0){
            $('.stock_type_title').html('{pigcms{:L_("每日库存(当前/原始)")}');
        }else{
            $('.stock_type_title').html('{pigcms{:L_("总库存(当前/原始)")}');
        }
    }

</script>
<link rel="stylesheet" href="{pigcms{$static_path}css/card_new.css"/>
<style>
    #tab1 .tiplabel {
        margin-left: 20px;
        margin-right: 20px;
        min-width: 200px;!important
    }
</style>
<include file="Public:footer"/>