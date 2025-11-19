<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('MembershipCard/config')}" class="on">平台会员卡</a>|
            <a href="{pigcms{:U('MembershipCard/report')}">效果数据</a>|
            <a href="{pigcms{:U('MembershipCard/orders')}">订单列表</a>|
        </ul>
    </div>
<form id="myform" method="post" action="{pigcms{:U('MembershipCard/config')}" frame="true" refresh="true">
    <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <td width="100">会员卡名称</td>
            <td>
                <input type="text"  style="width:300px;" class="input fl" name="card_name" value="{pigcms{$config.card_name}"  validate="required:true" autocomplete="off" />
            </td>
        </tr>
        <tr class="">
            <td width="100" >价格</td>
            <td >
                <input type="text" class="input" name="price" value="{pigcms{$config.price}" validate="required:true,number:true,min:0.01" autocomplete="off"/>
            </td>
        </tr>
        <tr class="">
            <td width="100" >原价</td>
            <td >
                <input type="text" class="input" name="old_price" value="{pigcms{$config.old_price}" validate="required:true,number:true,min:0.01" autocomplete="off"/>
            </td>
        </tr>
        <tr>
            <td width="100">分享标题</td>
            <td>
                <input type="text"  style="width:300px;" class="input fl" name="share_title" value="{pigcms{$config.share_title}" autocomplete="off" />
            </td>
        </tr>
        <tr>
            <td width="100">分享副标题</td>
            <td>
                <input type="text"  style="width:300px;" class="input fl" name="share_sub_title" value="{pigcms{$config.share_sub_title}"  autocomplete="off" />
            </td>
        </tr>
        <tr>
            <td width="100">分享图片</td>
            <td>
                <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;推荐尺寸500*400
                <input type="hidden" class="input-image" name="share_image" id="pic_list" value="{pigcms{$config.share_image}">
                <ul class="image_product_list" id="avatar_detail">
                </ul>
                <ul class="avatar_list" id="avatar_show" >
                    <if condition="$config['share_image'] neq ''">
                        <li class="upload_pic_li" style="float:left;list-style:none;"><img src="{pigcms{$config.share_image|replace_file_domain}" style="width:250px;height:200px;margin-right:5px;"><br></li>
                    </if>
                </ul>
            </td>
        </tr>
        <!--<tr>
            <td width="100">会员卡背景图片</td>
            <td>
                <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;推荐尺寸355*190
                <input type="hidden" class="input-image" name="img_url" id="pic_list" value="{pigcms{$config.img_url}">
                <ul class="image_product_list" id="avatar_detail">
                </ul>
                <ul class="avatar_list" id="avatar_show" >
                    <if condition="$config['img_url'] neq ''">
                        <li class="upload_pic_li" style="float:left;list-style:none;"><img src="{pigcms{$config.img_url|replace_file_domain}" style="width:355px;height:190px;margin-right:5px;"><br></li>
                    </if>
                </ul>
            </td>
        </tr>-->
        <tr>
            <td width="100">有效天数</td>
            <td><input type="text" class="input fl" name="days" value="{pigcms{$config.days}"  validate="required:true,number:true,min:1" autocomplete="off" />&nbsp;&nbsp;<span>优惠券有效期与会员卡有效期同步</span></td>
        </tr>
        <tr>
            <td width="100">状态</td>
            <td colspan="2">
                <label><input type="radio" name="status" value="1"  <if condition="$config['status'] eq 1">checked="checked"</if>/><span>开启</span></label>&nbsp;&nbsp;&nbsp;
                <label><input type="radio" name="status" value="0" <if condition="$config['status'] eq 0">checked="checked"</if>/><span>关闭</span></label>
            </td>
        </tr>

        <tr>
            <td width="100">优惠类型</td>
            <td colspan="2">
                <label><input type="radio" class="discount_type" name="discount_type" value="1" <if condition="$config['discount_type'] eq 1">checked="checked"</if>/><span>仅赠送优惠券</span></label>&nbsp;&nbsp;&nbsp;
                <label><input type="radio" class="discount_type" name="discount_type" value="2" <if condition="$config['discount_type'] eq 2">checked="checked"</if>/><span>仅平台配送费优惠</span></label>&nbsp;&nbsp;&nbsp;
                <label><input type="radio" class="discount_type" name="discount_type" value="3" <if condition="$config['discount_type'] eq 3">checked="checked"</if>/><span>两者均优惠</span></label>
            </td>
        </tr>

        <!--优惠券配置-->
        <tr class="coupon_setting">
            <td width="100" colspan="2" style="font-weight: bold;color: rgba(0,0,0,.45)">优惠券设置</td>
        </tr>
        <tr class="coupon_setting">
            <td width="100">优惠券张数</td>
            <td><input type="text" class="input fl" name="coupon_num" value="{pigcms{$config.coupon_num}"  validate="required:true,number:true,min:1" autocomplete="off" /></td>
        </tr>
        <tr class="coupon_setting">
            <td width="100" >优惠券优惠金额</td>
            <td ><input type="text" class="input" name="coupon_price" value="{pigcms{$config.coupon_price}" validate="required:true,number:true,min:0.01" autocomplete="off"/></td>
        </tr>
        <tr class="coupon_setting">
            <td width="100" >优惠券门槛条件</td>
            <td ><input type="text" class="input" name="coupon_order_price" value="{pigcms{$config.coupon_order_price|default=0}" validate="required:true,number:true,min:0" autocomplete="off"/></label>
            </td>
        </tr>
        <tr class="coupon_setting">
            <td width="100">使用业务</td>
            <td colspan="2">
                <select name="coupon_cate">
                    <volist name="category" id="vo">
                        <if condition="$key eq 'shop'">
                            <option <if condition="$config['coupon_cate'] eq $key">selected</if> value="{pigcms{$key}">{pigcms{$vo}</option>
                        </if>
                    </volist>
                </select>
            </td>
        </tr>

        <!--平台配送费优惠-->
        <tr class="deliver_setting">
            <td width="100" colspan="2" style="font-weight: bold;color: rgba(0,0,0,.45)">平台配送费优惠设置</td>
        </tr>
        <tr class="deliver_setting">
            <td width="100">平台配送费优惠次数</td>
            <td><input type="text" class="input fl" name="deliver_num" value="{pigcms{$config.deliver_num}"  validate="required:true,number:true,min:1" autocomplete="off" /></td>
        </tr>
        <tr class="deliver_setting">
            <td width="100" >平台配送费优惠金额</td>
            <td ><input type="text" class="input" name="deliver_price" value="{pigcms{$config.deliver_price}" validate="required:true,number:true,min:0.01" autocomplete="off"/></td>
        </tr>
        <tr class="deliver_setting">
            <td width="100">使用业务</td>
            <td colspan="2">
                <select name="deliver_cate">
                    <volist name="category" id="vo">
                        <if condition="$key eq 'shop'">
                            <option <if condition="$config['deliver_cate'] eq $key">selected</if> value="{pigcms{$key}">{pigcms{$vo}</option>
                        </if>
                    </volist>
                </select>
            </td>
        </tr>
        <tr>
            <td width="100">续费提前几天</td>
            <td><input type="text" class="input fl" name="early_renewal" value="{pigcms{$config.early_renewal}"  validate="number:true,min:1" autocomplete="off" /></td>
        </tr>
        <tr>
            <td width="100">规则</td>
            <td><textarea name="buy_info" rows="6" cols="40">{pigcms{$config.buy_info}</textarea></td>
        </tr>


    </table>
    <div class="btn">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
    </div>
</form>
</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
    var ignoreValidate = ":hidden";

    KindEditor.ready(function(K){
        var editor = K.editor({
            allowFileManager : true
        });
        $('.J_selectImage').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=membershipCard";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url, title, width, height, border, align) {
                        var avatar_detail_length = $('#avatar_detail').find('li').length;
                        var li_length = upload_file_btn.siblings('.avatar_list').find('li').length+avatar_detail_length;
                        $('#avatar_show').show();

                        upload_file_btn.siblings('.input-image').val(url);

                        var html='<li class="upload_pic_li" style="float:left;list-style:none;"><img src="'+url+'" style="width:250px;height:200px;margin-right:5px;"><br></li>';
                        upload_file_btn.siblings('.avatar_list').html(html)
                        editor.hideDialog();


                    }
                });
            });
        });
    })


    $('input[name=discount_type]').on('change',function(){
        var discount_type = $(this).val();
        if(discount_type&1){
            $('.coupon_setting').show();
        }else{
            $('.coupon_setting').hide();
        }
        if(discount_type&2){
            $('.deliver_setting').show();
        }else{
            $('.deliver_setting').hide();
        }
    })

    $('.discount_type:checked').trigger('change');

</script>
<include file="Public:footer"/>

