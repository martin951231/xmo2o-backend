<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('House/property_add')}" frame="true" refresh="true">

		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">

            <tr>
                <th width="100">物业公司名称</th>
                <td><input type="text" class="input fl" name="property_name" size="30" placeholder="请输入物业公司名称" validate="maxlength:50,required:true"/></td>
            </tr>
            <tr>
                <th width="100">物业公司名称简称</th>
                <td><input type="text" class="input fl" name="property_short_name" size="30" placeholder="请输入物业公司名称简称" validate="maxlength:100,required:true"/></td>
            </tr>
			<tr >
				<th width="15%">所在区域</th>
				<td id="choose_cityarea" province_id="{pigcms{$village.province_id}" city_id="{pigcms{$village.city_id}" area_id="{pigcms{$village.area_id}" circle_id="-1" circle="-1"></td>
			</tr>
			<tr>
				<th width="100">物业联系地址</th>
				<td><input type="text" class="input fl" name="property_address" size="30" placeholder="请输入物业联系地址" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="100">物业联系电话</th>
				<td><input type="text" class="input fl" name="property_phone" size="20" placeholder="请输入物业联系电话" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="100">物业后台管理帐号</th>
				<td><input type="text" class="input fl" name="account" size="20" placeholder="请输入小区后台管理帐号" validate="maxlength:50,required:true"/></td>
			</tr>
			<tr>
				<th width="100">物业后台管理密码</th>
				<td><input type="text" class="input fl" name="password" size="20" placeholder="请输入物业后台管理密码" validate="maxlength:50,required:true,minlength:6"/></td>
			</tr>
<!--			<tr>-->
<!--				<th width="100">可添加小区数量</th>-->
<!--				<td><input type="text" class="input fl" name="village_num" size="20" placeholder="请输入小区数量" validate="maxlength:50,required:true"/></td>-->
<!--			</tr>-->
            <tr>
                <th>套餐功能选项</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="package_id" id="package_id" onchange="package(this.value)" style="width: 145px;">
                            <option value="0">请选择</option>
                            <volist name="package" id="vo">
                                <option value="{pigcms{$vo.package_id}">{pigcms{$vo['package_title']}</option>
                            </volist>
                        </select>
                        <em for="village_num" generated="true" class="error tips">必填项</em>
                    </div>
                </td>
            </tr>

            <tr <if condition="!$isSuiChuan"> style="display:none;" </if>>
                <th width="120">是否虚拟物业</th>
                <td>
					<span class="cb-enable"><label class="cb-enable" ><span>是</span>
					<input type="radio" name="is_virtual" value="1" /></label></span>
                    <span class="cb-disable " ><label class="cb-disable  selected"><span>否</span>
					<input type="radio" name="is_virtual" value="0" checked="checked"/></label></span>
                </td>
            </tr>

            <tr id="trs" style="display: none">
                <th>功能套餐购买周期</th>
                <td>
                    <input type="number" oninput="value=value.replace(/[^\d]/g,'')" min="1" class="input fl" name="pay_year" size="20" placeholder="请输入套餐购买周期" validate="maxlength:3"/>最多可购买<span id="years"></span>年
                </td>
            </tr>
            <tr>
                <th width="100">企微登录凭依手机号</th>
                <td>
                    <input type="text" class="input fl" name="login_phone" size="30" placeholder="请输入登录凭依手机号" tips="填写了登录凭依手机号，企业微信移动管理端登录后以此手机号为准进行账号匹配自动登录"/>
                </td>
            </tr>



            <tr <if condition="!$isVirtualSwitch"> style="display:none;" <else/> style="" </if>>
                <th width="120">是否虚拟物业</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable" ><span>是</span>
                    <input type="radio" name="is_virtual_switch" value="1" /></label></span>
                    <span class="cb-disable " ><label class="cb-disable  selected"><span>否</span>
                    <input type="radio" name="is_virtual_switch" value="0" checked="checked"/></label></span>
                    <em for="village_num" generated="true" class="error tips">选择是虚拟物业后请在下面选择【项目类型】</em>
                </td>
            </tr>
            <tr class="facilities_type_box" style="display: none;">
                <th>项目类型</th>
                <td>
                    <div class="form-group">
                        <select class="col-xs-4 col-sm-4 col-md-2" name="facilities_type" id="facilities_type" style="width: 145px;">
                            <volist name="facilities_type_switch" id="vo">
                                <option value="{pigcms{$vo.value}">{pigcms{$vo['label']}</option>
                            </volist>
                        </select>
                    </div>
                </td>
            </tr>
            
            
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>

    $('input[name="is_virtual_switch"]').click(function(){
        var is_virtual_switch = $(this).val();
        console.log('is_virtual_switch', is_virtual_switch)
        if (is_virtual_switch == 1) {
            $('.facilities_type_box').show();
            //1.获取当前文档的高度
            var docHeight = $(document).height();
            //2.获取当前浏览器窗口的高度
            var windowHeight = $(window).height();
            $(document).scrollTop(docHeight - windowHeight);
        } else {
            $('.facilities_type_box').hide();
        }
    })
    
    $(".cb-enable-integral").on('click',function(){
        $(".class-village-pay-integral").show(300);
    });
    
    $(".cb-disable-integral").on('click',function(){
        $(".class-village-pay-integral").hide(200);
    });
    
    $(".cb-enable-use-integral").on('click',function(){
        $(".class-village-pay-use-integral").show(300);
    });
    
    $(".cb-disable-use-integral").on('click',function(){
        $(".class-village-pay-use-integral").hide(200);
    });
    
    
    function addLink(domid,iskeyword){
        art.dialog.data('domid', domid);
        art.dialog.open('?g=Admin&c=Link&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
    }
    function package(id) {
        $.ajax({
            url: '{pigcms{:U("getPackageY")}',
            type: 'post',
            dataType: 'json',
            data: {
                'package_id':id
            },
            success: function(res){
                if(res.code == 1)
                {
                    $('#trs').attr('style','dispaly:');
                    $('#years').html(res.package_limit_num)
                }else{
                    $('#trs').hide();
                }
            }
        })
    }
</script>
<include file="Public:footer"/>