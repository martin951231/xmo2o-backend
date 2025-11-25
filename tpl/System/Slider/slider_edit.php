<include file="Public:header"/>
<style type="text/css">
.color-box{
    float: left;
    width: 30px;
    height: 30px;
    margin: 5px;
    border: 1px solid white;
    background-color: #ff0080;
}
.mark_box{
	display:block;
	position: relative;
}
.mark_box img{
	width:36px;
	height: 36px;
}
.mark_box i.mark{
	display: block;
    position: absolute;
    line-height: 16px;
    top: -6px;
    background: #f00;
    color: #fff;
    padding: 0 5px;
    left: 18px;
    border-radius: 10px;
    border-bottom-left-radius: 0;
    font-size: 11px;
    display: none;
}
.effect_date_tr,.effect_time_tr{
    display: none;
}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Slider/slider_amend',array('from_page'=>$_GET['from_page']))}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$now_slider.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">导航名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$now_slider.name}" size="20" placeholder="建议4字以内" validate="maxlength:20,required:true"/></td>
			</tr>
			<if condition="$many_city eq 1 ">
                <if condition="$is_area_many_city neq 1 ">
                    <tr id="adver_currency">
                        <th width="15%">通用广告</th>
                        <td width="35%" class="radio_box">
                            <span class="cb-enable"><label class="cb-enable <if condition="$now_slider['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$now_slider['city_id'] eq 0">checked="checked"</if> /></label></span>
                            <span class="cb-disable"><label class="cb-disable <if condition="$now_slider['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$now_slider['city_id'] neq 0">checked="checked"</if> /></label></span>
                            <if condition="C('customization.open_view_multi_city') eq 1">
                            <em for="currency" generated="true" class="error tips" style="position: relative;color: red;font-weight: bold;">选择不通用时请在列表页配置指定区域</em>
                            </if>
                        </td>
                    </tr>
                <else/>
                    <input id="no" type="hidden" name="currency" value="2" checked="checked" />
                </if>

            <if condition="C('customization.open_view_multi_city') neq 1">
				<tr id="adver_region" <if condition="$now_slider['city_id'] eq 0 or $is_area_many_city eq 1">style="display:none;"</if>>
					<th width="15%">所在区域</th>
					<td width="85%" colspan="3" id="choose_cityareass" province_idss="{pigcms{$now_slider['province_id']}" city_idss="{pigcms{$now_slider['city_id']}"></td>
				</tr>
            </if>
			</if>
			<if condition="$now_slider['pic']">
				<tr>
					<th width="80">导航现图</th>
					<td><img src="{pigcms{:file_domain()}/upload/slider/{pigcms{$now_slider.pic}" style="width:80px;height:80px;" class="view_msg"/></td>
				</tr>
			</if>
			<tr>
				<th width="80">导航图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传图片" tips="不修改请不上传！上传新图片，老图片会被自动删除！" onchange="preview(this);" /></td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
				<input type="text" class="input fl" name="url" id="url" value="{pigcms{$now_slider.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:800,required:true"/>
				<if condition="$now_category['cat_type'] neq 1">
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0, 0)" data-toggle="modal">从功能库选择</a>
				<else />
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url',0, 1)" data-toggle="modal">从功能库选择</a>
				</if>
				</td>
			</tr>
            <tr>
                <th width="80">小程序中想要打开</th>
                <td>
                   	<select name="wxapp_open_type" id="wxapp_open_type">
	                   	<option value="1" selected="selected">打开其他小程序</option>
	                   	<!-- <option value="2">打开其他app</option> -->
                   </select>
                </td>
            </tr>
            <tr class="wxapp_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="wxapp_id" id="wxapp_id" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $now_slider['wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr style="display: none;" class="wxapp_open_wxapp wxapp_page">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$now_slider.wxapp_page}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<td colspan="2">
				打开其他APP
				</td>
			</tr>
            <tr>
                <th width="80">APP中想要打开</th>
                <td>
                   	<select name="app_open_type" id="app_open_type">
	                   	<!--<option value="1" <if condition="$now_slider['app_open_type'] eq 1">selected="selected"</if>>打开其他小程序</option>-->
	                   	<option value="2" <if condition="$now_slider['app_open_type'] eq 2">selected="selected"</if>>打开其他app</option>
                   </select>
                </td>
            </tr>
			<tr class="app_url app_open_app">
				<th width="80">选择苹果APP</th>
				<td>
                   	<select name="ios_app_name" id="ios_app_name" tips="">
	                   	<option value="">选择苹果APP</option>
	                   	<volist name='app_list' id='vo'>
	                   		<option value="{pigcms{$vo.url_scheme}" <if condition="$vo['url_scheme'] eq $now_slider['ios_app_name']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
			</tr>
			<tr class="ios_app_url app_open_app">
				<th width="80">苹果APP下载地址</th>
				<td><input type="text" class="input fl" name="ios_app_url" id="ios_app_url" style="width:200px;" value="{pigcms{$now_slider.ios_app_url}"  placeholder="请输入苹果APP下载地址" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP包名</th>
				<td><input type="text" class="input fl" name="android_app_name" style="width:200px;" value="{pigcms{$now_slider.android_app_name}"  placeholder="请输入安卓APP包名" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP下载地址</th>
				<td><input type="text" class="input fl" name="android_app_url" style="width:200px;" value="{pigcms{$now_slider.android_app_url}"  placeholder="请输入安卓APP下载地址" tips=""/></td>
			</tr>
            <tr class="app_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="app_wxapp_id" id="app_wxapp_id" tips="请选择一个想要打开的其他小程序">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $now_slider['app_wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr class="app_open_wxapp" style="display: none;">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="app_wxapp_page" id="app_wxapp_page" style="width:200px;" value="{pigcms{$now_slider.app_wxapp_page}" placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<th width="80">导航排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$now_slider.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">导航状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_slider['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_slider['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_slider['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$now_slider['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<if condition="in_array($now_category['cat_key'],array('waimai_slider','wap_slider','wap_shop_slider','wap_foodshop_slider'))">
				<tr>
					<th width="80">角标名称</th>
					<td><input type="text" class="input fl" name="mark_name" id="mark_name" size="25" value="{pigcms{$now_slider.mark_name}" placeholder="最多输入4个字符" validate="maxlength:4,required:false" tips=""  onblur="change_mark_box();"  /></td>
				</tr>
				<tr>
					<th width="80">角标背景颜色</th>
					<td style="line-height: 40px;">
						<div class="color-box color-box1" style="background-color:{pigcms{$now_slider.mark_bg|default='#ff0000'};"></div>点击选择颜色
						<input type="hidden" class="color" name="mark_bg" id="mark_bg" value="{pigcms{$now_slider.mark_bg|default='#ff0000'}"/>
					</td>
				</tr>
				<tr class="show_box">
					<th width="80">角标效果示例</th>
					<td>
						<span class='mark_box'>
							<img src="https://hf.pigcms.com/upload/slider/2015/08/55cc6b1f816fd.png" >
							<i class='mark'>红包</i>
						</span>
					</td>
				</tr>
			</if>

            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效日期</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_slider['effect_date_type'] eq 1">selected</if>"><span>全时段</span><input type="radio" name="effect_date_type" value="1" <if condition="$now_slider['effect_date_type'] eq 1">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_slider['effect_date_type'] eq 2">selected</if>"><span>自定义日期</span><input type="radio" name="effect_date_type" value="2" <if condition="$now_slider['effect_date_type'] eq 2">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择日期</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_date" style="width:120px;" id="d4311"  value="{pigcms{$now_slider.start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
                    <input type="text" class="input-text" name="end_date" style="width:120px;" id="d4311" value="{pigcms{$now_slider.end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true"/>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择星期</th>
                <td colspan="2">
                    <input type="checkbox" name="week[]" value="0" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'0') !== false)">checked</if>>&nbsp;星期日&nbsp;
                    <input type="checkbox" name="week[]" value="1" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'1') !== false)">checked</if>>&nbsp;星期一&nbsp;
                    <input type="checkbox" name="week[]" value="2" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'2') !== false)">checked</if>>&nbsp;星期二&nbsp;
                    <input type="checkbox" name="week[]" value="3" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'3') !== false)">checked</if>>&nbsp;星期三&nbsp;
                    <input type="checkbox" name="week[]" value="4" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'4') !== false)">checked</if>>&nbsp;星期四&nbsp;
                    <input type="checkbox" name="week[]" value="5" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'5') !== false)">checked</if>>&nbsp;星期五&nbsp;
                    <input type="checkbox" name="week[]" value="6" <if condition="($now_slider['effect_date_type'] eq 1) or (strpos($now_slider['week'],'6') !== false)">checked</if>>&nbsp;星期六
                </td>
            </tr>
            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效时间段</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$now_slider['effect_time_type'] eq 1">selected</if>"><span>全时段</span><input type="radio" name="effect_time_type" value="1" <if condition="$now_slider['effect_time_type'] eq 1">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$now_slider['effect_time_type'] eq 2">selected</if>" ><span>自定义时间段</span><input type="radio" name="effect_time_type" value="2" <if condition="$now_slider['effect_time_type'] eq 2">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr class="effect_time_tr">
                <th width="80">选择时间</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4312"  value="{pigcms{$now_slider.start_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>-
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4312" value="{pigcms{$now_slider.end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" validate="required:true"/>
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
<script type="text/javascript" src="{pigcms{$static_path}js/colpick.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/colpick.css?111" />
<script>
    var ignoreValidate = ":hidden";
    var is_area_many_city = <?php echo $is_area_many_city ?>;

    $('input[name=effect_date_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_date_tr').show();
        } else {
            $('.effect_date_tr').hide();
        }
    })

    $('input[name=effect_time_type]').on('click', function () {
        if ($(this).val() == 2) {
            $('.effect_time_tr').show();
        } else {
            $('.effect_time_tr').hide();
        }
    })
    $('input[name=effect_date_type]:checked').trigger('click');
    $('input[name=effect_time_type]:checked').trigger('click');

$("#yes").click(function () {
	$("#adver_region").hide();
})

$("#no").click(function () {
    if(is_area_many_city==0) {
        $("#adver_region").show();
    }
})

var admin_area_id = {pigcms{$system_session.area_id};
if((admin_area_id > 0) && (is_area_many_city != 1)){
	$("#adver_currency").hide();
	$("#no").trigger('click');
}

$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})
$("#wxapp_id").trigger('change');

var pageDomId = '';
function addLink(domid,iskeyword, type){
	pageDomId = domid;
	art.dialog.data('domid', domid);
	if (type == 1) {
		art.dialog.open('?g=Admin&c=LinkPC&a=insert&iskeyword='+iskeyword,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	} else {
		parent.setLinkBases(window, 'platform', 'h5');
	}
}
//链接库回调方法
function getLinkBasesUrl(url){
	console.log('url', url);
	$('#' + pageDomId).val(url);
}

</script>
<script type="text/javascript">
$('.color-box').colpick({
	colorScheme:'dark',
	layout:'rgbhex',
	color:'#ff8800',
	onSubmit:function(hsb,hex,rgb,el) {

		$(el).siblings('.color').val('#'+hex);
		$(el).css('background-color', '#'+hex);
		$(el).colpickHide();
		change_mark_box();
	}
});
var img_src="{pigcms{$now_slider.pic}";
if(img_src){
	img_src="{pigcms{:file_domain()}/upload/slider/"+img_src;
}
function preview(file){ 
	if (file.files && file.files[0]){  
		var reader = new FileReader();  
		reader.onload = function(evt){
			img_src=evt.target.result;
			change_mark_box();
		}    
		reader.readAsDataURL(file.files[0]);  
	}else{
		img_src=file.value;
		change_mark_box();
	}
}
function change_mark_box(){
	var mark_name=$('#mark_name').val();
	var mark_bg=$('#mark_bg').val();
	if(mark_name!='' && mark_bg!='' && img_src!=''){
		$('.mark_box').find('.mark').text(mark_name).css({'background-color':mark_bg}).show();
		$('.mark_box').find('img').attr('src',img_src);
		$('.show_box').show();	
	}else{
		$('.mark_box').find('.mark').hide();
		$('.show_box').hide();
	}
}
change_mark_box();

$("#ios_app_name").change(function(){
	if ($("#ios_app_name").val()) {
		$(".ios_app_url").show();
	}else{
		$("#ios_app_url").val('');
		$(".ios_app_url").hide();
	}
})
$("#ios_app_name").trigger('change');


// APP中想要打开i切换
$("#app_open_type").change(function () {
    if ($("#app_open_type").val() == 1) {
    	// 打开小程序
        $(".app_open_wxapp").show();
        $(".app_open_app").hide();
    } else {
    	// 打开app
        $(".app_open_app").show();
        $(".app_open_wxapp").hide();
    }
})
$("#app_open_type").trigger('change');

$("#app_wxapp_id").change(function () {
    if ($("#app_wxapp_id").val()) {
        $(".app_wxapp_page").show();
    } else {
        $("#app_wxapp_page").val('');
        $(".app_wxapp_page").hide();
    }
})
$("#app_wxapp_id").trigger('change');
</script>
<include file="Public:footer"/>