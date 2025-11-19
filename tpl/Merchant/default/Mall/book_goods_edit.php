<include file="Public:header"/>

<div class="main-content">

    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div class="tab-content">
                        <div class="grid-view">
                            <h2 style="margin-bottom: 20px">{pigcms{:L_('创建预售活动')}</h2>
                            <form enctype="multipart/form-data" class="myform" method="post" >

                                <input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}">
                                <input type="hidden" name="id" value="{pigcms{$info.id}">
                                <div class="tab-content card_new" id="tab1" >
                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("活动时间")}:</label></label>
                                        <input type="text" class="input-text" name="start_time" id="start_time" style="width:160px;" value="{pigcms{$info.start_time}" placeholder="{pigcms{:L_("点击设置开始时间")}" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm:ss'})" />&nbsp;&nbsp;-&nbsp;&nbsp;
                                        <input type="text" class="input-text" name="end_time" id="end_time" style="width:160px;" value="{pigcms{$info.end_time}" placeholder="{pigcms{:L_("点击设置结束时间")}" value="" />&nbsp;<font color="red">*</font>
                                        <span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
                                    </div>

                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("尾款支付类型")}:</label></label>
                                        <input type="radio" disabled  name="rest_pay_type" value="1" <if condition="$info['rest_pay_type'] eq '1'" >checked="checked"</if>/>{pigcms{:L_("固定时间")}&nbsp;&nbsp;&nbsp;
                                        <input type="radio" disabled  name="rest_pay_type" value="2"  <if condition="$info['rest_pay_type'] eq '2'" >checked="checked"</if>/>{pigcms{:L_("定金支付后")}  &nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>
 
                                    <div class="form-group rest_pay_type_1">
                                        <label class="tiplabel"><label>{pigcms{:L_("尾款支付时间")}:</label></label>
                                        <input type="text" disabled class="input-text" name="rest_start_time" id="rest_start_time" style="width:160px;" value="{pigcms{$info.rest_start_time}"   placeholder="{pigcms{:L_("点击设置开始时间")}" />&nbsp;&nbsp;-&nbsp;&nbsp;
                                        <input type="text"  disabled class="input-text" name="rest_end_time" id="rest_end_time" style="width:160px;" value="{pigcms{$info.rest_end_time}"  placeholder="{pigcms{:L_("点击设置结束时间")}"/>&nbsp;<font color="red">*</font>
                                        <span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
                                    </div>

                                    <div class="form-group rest_pay_type_2">
                                        <label class="tiplabel"><label>{pigcms{:L_("尾款支付时间")}:</label></label>
                                        
                                        定金支付后<input type="text" readonly  style="width: 50px;" class="input" name="rest_day"  autocomplete="off" placeholder="必须为整数" value="{pigcms{$info.rest_day}" oninput="value=value.replace(/[^\d]/g,'')" />&nbsp;{pigcms{:L_("天")}支付尾款&nbsp;
                                    </div>

                                    <div class="form-group">
                                        <label class="tiplabel"><label>{pigcms{:L_("发货类型")}:</label></label>
                                        <input type="radio" disabled name="send_goods_type" value="1" <if condition="$info['send_goods_type'] eq '1'">checked="checked"</if>/>{pigcms{:L_("固定时间")}&nbsp;&nbsp;&nbsp;
                                        <input type="radio" disabled name="send_goods_type" value="2" <if condition="$info['send_goods_type'] eq '2'">checked="checked"</if>/>{pigcms{:L_("尾款支付后")}  &nbsp;&nbsp;&nbsp;&nbsp;
                                    </div>

                                    <div class="form-group send_goods_type_1">
                                        <label class="tiplabel"><label>{pigcms{:L_("发货时间")}:</label></label>
                                        <input type="text" disabled class="input-text" name="send_goods_date" id="send_goods_date" style="width:160px;" placeholder="{pigcms{:L_("点击设置发货时间")}" value="{pigcms{$info.send_goods_date}"  />
                                        <span class="tip">({pigcms{:L_("开始时间最早为当天")})</span>
                                    </div>

                                    <div class="form-group send_goods_type_2">
                                        <label class="tiplabel"><label>{pigcms{:L_("发货时间")}:</label></label>
                                        尾款支付后<input type="text" readonly style="width: 50px;" class="input" name="send_goods_day" autocomplete="off" placeholder="必须为整数" value="{pigcms{$info.send_goods_day}" oninput="value=value.replace(/[^\d]/g,'')" />&nbsp;{pigcms{:L_("天")}发货&nbsp;
                                    </div>
                                    <div class="form-group wx_coupon">
                                        <!--
                                        <label class="tiplabel"><label>{pigcms{:L_("活动商品")}</label></label>
                                        <a href="javascript:;" class="btn btn-sm btn-success add_goods">{pigcms{:L_("选择商品")}</a>&nbsp;-->
                                        <br/><br/>
                                        <table class="table table-striped table-bordered repWrap">
                                            <tr>
                                                <th>{pigcms{:L_("促销商品")}</th>
                                                <th>{pigcms{:L_("规格")}</th>
                                                <th>{pigcms{:L_("原价")}</th>
                                                <th>{pigcms{:L_("定金")}</th>
                                                <th>{pigcms{:L_("优惠金额")}</th>
                                                <th class="stock_type_title">{pigcms{:L_("总库存(当前/原始)")}</th>
                                            </tr>
                                            <if condition="!empty($info['spec_list'])">
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
                                                               <input type="text" name="bargin_price[]" class="limit_price" value="{pigcms{$o.bargin_price}" readonly>
                                                            </td>
                                                            <td class="red_color">
                                                                <input onblur="update_ratio($(this))" type="number" step="0.01" name="discount_price[]" class="limit_price" value="{pigcms{$o.discount_price}" readonly/>
                                                                </php>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="stock" name="stock[]" value="{pigcms{$o.stock}" readonly/> /
                                                                <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$o.origin_stock}" readonly/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
                                                            </td>
                                                        </tr>
                                                    <else/>
                                                        <tr>
                                                            <input type="hidden" name="spec_id[]" value="{pigcms{$o.id}"/>
                                                            <td>{pigcms{$o.str}</td>
                                                            <td>{pigcms{$o.price}</td>
                                                            <td>
                                                               <input type="text" name="bargin_price[]" class="limit_price" value="{pigcms{$o.bargin_price}" readonly>
                                                            </td>
                                                            <td class="red_color">
                                                                <input onblur="update_ratio($(this))" type="number" step="0.01" name="discount_price[]" class="limit_price" value="{pigcms{$o.discount_price}" readonly/>
                                                                </php>
                                                            </td>
                                                            <td>
                                                                <input type="number" class="stock" name="stock[]" value="{pigcms{$o.stock}" readonly /> /
                                                                <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$o.origin_stock}" readonly/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
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
                                                        <input type="text" name="limit_price[]" class="limit_price" value="{pigcms{$info.bargin_price}" readonly>
                                                    </td>
                                                    <td class="red_color">
                                                        {pigcms{$info.discount_price}
                                                        <!--php>
                                                            if($info['price'] > 0){
                                                                echo (getFormatNumber($info['limit_price']/$info['price'])*10).L_('折');
                                                            }else{
                                                                echo '-';
                                                            }
                                                        </php>-->
                                                    </td>
                                                    <td>
                                                        <input type="number" class="stock" name="stock[]" value="{pigcms{$info.stock}" readonly/> /
                                                        <input type="number" class="origin_stock" name="origin_stock[]" value="{pigcms{$info.origin_stock}" readonly/><span style="color: red;margin-left: 5px">-1 代表无限量</span>
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
   /* var rest_start_time=$("input[name='rest_start_time']").val();
    var rest_end_time=$("input[name='rest_end_time']").val();
    if(rest_start_time){
        var addtime = getYMDHMS (rest_start_time);
        $("input[name='rest_start_time']").val(addtime);
    }

    if(rest_end_time){
        var end_time = getYMDHMS (rest_end_time);
        alert(end_time);
        $("input[name='rest_end_time']").val(end_time);
    }
//时间戳转换格式化时间
    function getYMDHMS(date) {
        var date = new Date(date);
        var YY = date.getFullYear() + '-';
        var MM = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
        var DD = (date.getDate() < 10 ? '0' + (date.getDate()) : date.getDate());
        var hh = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':';
        var mm = (date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes()) + ':';
        var ss = (date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds());
        return YY + MM + DD +" "+hh + mm + ss;
    }*/

    //-----------尾款时间--------
    function rest_pay_type_show(){
        var rest_pay_type = $('input[name="rest_pay_type"]:checked ').val();
        if(rest_pay_type==2){
           $('.rest_pay_type_1').hide();
           $('.rest_pay_type_2').show();
        }else{
           $('.rest_pay_type_1').show();
           $('.rest_pay_type_2').hide();
        }
    }
    $("input[name='rest_pay_type'").change(function(){
        rest_pay_type_show();
    });
    rest_pay_type_show();
    //-----------发货时间--------
    function send_goods_show(){
        var send_goods_type = $('input[name="send_goods_type"]:checked ').val();
        if(send_goods_type==2){
           $('.send_goods_type_1').hide();
           $('.send_goods_type_2').show();
        }else{
           $('.send_goods_type_1').show();
           $('.send_goods_type_2').hide();
        }
    }
    $("input[name='send_goods_type'").change(function(){
        send_goods_show();
    });
    send_goods_show();

    function check_stock() {
        var tt=0;

        if($("#start_time").val()=="" || $("#end_time").val()==""){
            tt=1;
            layer.alert('{pigcms{:L_("活动时间不能为空")}');
        }
        var rest_pay_type = $('input[name="rest_pay_type"]:checked ').val();
        if(rest_pay_type==1){
            if($("#rest_start_time").val()=="" || $("#rest_end_time").val()==""){
                tt=1;
                layer.alert('{pigcms{:L_("尾款支付时间不能为空")}');
            }
        }else{
            if($("input[name='rest_day']").val()<=0 || $("input[name='rest_day']").val()==''){
                tt=1;
                layer.alert('{pigcms{:L_("支付尾款天数要大于0")}');
            }
        }

        var send_goods_type = $('input[name="send_goods_type"]:checked ').val();
        if(send_goods_type==1){
            if($("#send_goods_date").val()==""){
                tt=1;
                layer.alert('{pigcms{:L_("发货时间不能为空")}');
            }
        }else{
            if($("input[name='send_goods_day']").val()<=0 || $("input[name='send_goods_day']").val()==''){
                tt=1;
                layer.alert('{pigcms{:L_("支付尾款后发货天数要大于0")}');
            }
        }

        $("input[name^='bargin_price']").each(function(i, el) {
            if($(this).val()==0 || $(this).val()==""){
                tt=1;
                layer.alert('{pigcms{:L_("定金不能为零")}');
            }
        });

        if(tt==1){
            tt=0;
            return false;
        }

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

    // 点击选择日期
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
            site_url']}/merchant.php?c=Mall&a=get_time_limited_discount_goods&store_id={pigcms{$_GET.store_id}&savegood='+savegood+'',
            area: ['950px', '650px'],
            shade: 0.2,
            cancel: function () {
            },
            move: false
        });
    })

    // 选择商品后回调方法
    function login_box_after(name,price,spec_value,index,savegood,goods_id,stock_num,image){
        //alert(spec_value);
        //console.log(spec_value);
        //菜品不能重复
        //console.log(typeof spec_value);
        //console.log(spec_value);
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
                                 <td rowspan="' + spec_len + '">' + '<div class="goods_pic"><img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="' + image + '"></div><div class="goods_name">' + name + '</div></td>\
                                 <td>' + spec_value[i].str + '</td>\
                                 <td>' + spec_value[i].price + '</td>\
                                 <td><input onblur="update_ratio($(this))" type="number" step="0.01" name="bargin_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td class="red_color"><input onblur="update_ratio($(this))" type="number" step="0.01" name="discount_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td><input type="number" class="stock" name="stock[' + goods_id + '][]" value="10000"/> / <input type="number" class="origin_stock" name="origin_stock[' + goods_id + '][]" value="10000"/><span style="color: red;margin-left: 5px">-1 代表无限量</span></td>\
                                 <td style="cursor: pointer;" rowspan="' + spec_len + '"  onclick="del_row(' + goods_id + ')"><a href="javascript:void(0)">删除</a></td>\
                             </tr>';
                    html += '<input type="hidden" name="spec_index[' + goods_id + '][]" value="' + spec_value[i].key + '"/>';
                } else {
                    html += '<tr class="tr_' + goods_id + '">\
                                 <td>' + spec_value[i].str + '</td>\
                                 <td>' + spec_value[i].price + '</td>\
                                 <td><input onblur="update_ratio($(this))" type="number" step="0.01" name="bargin_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td class="red_color"><input onblur="update_ratio($(this))" type="number" step="0.01" name="discount_price[' + goods_id + '][]" class="limit_price" value="' + spec_value[i].price + '"/></td>\
                                 <td><input type="number" class="stock" name="stock[' + goods_id + '][]" value="10000"/> / <input type="number" class="origin_stock" name="origin_stock[' + goods_id + '][]" value="10000"/><span style="color: red;margin-left: 5px">-1 代表无限量</span></td>\
                             </tr>';
                    html += '<input class="tr_' + goods_id + '" type="hidden" name="spec_index[' + goods_id + '][]" value="' + spec_value[i].key + '"/>';
                }
            }
        } else {
            html += '<tr class="tr_' + goods_id + '">\
                         <td><div class="goods_pic"><img style="width: 50px;height: 50px;margin-right: 10px;border-radius: 4px" src="' + image + '"></div><div class="goods_name">' + name + '</div></td>\
                         <td>无</td>\
                         <td> ' + price + '</td>\
                         <td><input onblur="update_ratio($(this))" type="number" step="0.01" name="bargin_price[' + goods_id + '][]" class="limit_price" value="' + price + '"></td>\
                         <td class="red_color"><input onblur="update_ratio($(this))" type="number" step="0.01" name="discount_price[' + goods_id + '][]" class="limit_price" value="' + price + '"/></td>\
                         <td><input type="number" class="stock" name="stock[' + goods_id + '][]" value="10000"/> / <input type="number" class="origin_stock" name="origin_stock[' + goods_id + '][]" value="10000"/> <span style="color: red;margin-left: 5px">-1 代表无限量</span></td>\
                         <td style="cursor: pointer;" onclick="del_row(' + goods_id + ')"><a href="javascript:void(0)">删除</a></td>\
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
   /*发货时间*/
   $("#send_goods_date").click(function () {
       var rest_pay_type = $('input[name="rest_pay_type"]:checked ').val();
       var rest_end_time;
       if(rest_pay_type==1){
           rest_end_time=$("#rest_end_time").val();
       }else {
           rest_end_time=$("#send_goods_date").val();
       }
       WdatePicker({
           el: this, //点击对象id，一般可省略el
           lang: 'auto', //语言选择，一般用auto
           dateFmt: 'yyyy-MM-dd', //时间显示格式，年月日 时分秒，年月日就是yyyy-MM-dd
           minDate:rest_end_time, //最小日期
           readOnly: true, //是否只读
           isShowClear: false, //是否显示“清空”按钮
       })
   })
   /*活动结束时间*/
   $("#end_time").click(function () {
       WdatePicker({
           el: this, //点击对象id，一般可省略el
           lang: 'auto', //语言选择，一般用auto
           dateFmt: 'yyyy-MM-dd HH:mm:ss', //时间显示格式，年月日 时分秒，年月日就是yyyy-MM-dd
           minDate:$("#start_time").val(), //最小日期
           readOnly: true, //是否只读
           isShowClear: false, //是否显示“清空”按钮
       })
   })

   /*活动结束时间*/
   $("#rest_start_time").click(function () {
       WdatePicker({
           el: this, //点击对象id，一般可省略el
           lang: 'auto', //语言选择，一般用auto
           dateFmt: 'yyyy-MM-dd HH:mm:ss', //时间显示格式，年月日 时分秒，年月日就是yyyy-MM-dd
           minDate:$("#start_time").val(), //最小日期
           readOnly: true, //是否只读
           isShowClear: false, //是否显示“清空”按钮
       })
   })

   $("#rest_end_time").click(function () {
       WdatePicker({
           el: this, //点击对象id，一般可省略el
           lang: 'auto', //语言选择，一般用auto
           dateFmt: 'yyyy-MM-dd HH:mm:ss', //时间显示格式，年月日 时分秒，年月日就是yyyy-MM-dd
           minDate:$("#rest_start_time").val(), //最小日期
           readOnly: true, //是否只读
           isShowClear: false, //是否显示“清空”按钮
       })
   })

   /*end_time*/
    /**折扣联动
    function update_ratio(obj) {
        var limit_price = parseFloat(obj.val());
        var old_price = parseFloat(obj.parent('td').prev().html());
        var ratio = 10;
        if (old_price > 0) {
            ratio = String(limit_price * 10 / old_price);
            if (ratio.indexOf('.') != -1) {
                ratio = parseFloat(ratio).toFixed(2);
            }
        }
        obj.parent('td').next().html(ratio + "折")
    }**/

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