<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Shop/add_shop_center_type_img')}" enctype="multipart/form-data" ">
		<input type="hidden" name="id" value="{pigcms{$_GET['id']}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">前台广告显示模式</th>
				<td>
					<select name="type" class="valid" id="group_main_page_center_type">
						<!-- <option value="1" <if condition="$slider.type eq 1">selected</if>>1图 纯图片模式</option>
						<option value="2" <if condition="$slider.type eq 2">selected</if>>2图 纯图片模式</option> -->
						<option value="3" <if condition="$slider.type eq 3">selected</if>>3图 纯图片模式</option>
						<option value="4" <if condition="$slider.type eq 4">selected</if>>4图 纯图片模式</option>
						<option value="5" <if condition="$slider.type eq 5">selected</if>>5图 纯图片模式</option>
						<!--option value="-3" <if condition="$slider.type eq -3">selected</if>>3图 图文模式</option>
						<option value="-4" <if condition="$slider.type eq -4">selected</if>>4图 图文模式</option>
						<option value="-5" <if condition="$slider.type eq -5">selected</if>>5图 图文模式</option-->
					</select>
				</td>
			</tr>
			
			<tr>
				<th width="80">导航名称</th>
				<td><input type="text" class="input fl" name="name" size="20" value="{pigcms{$slider.name}" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr id="sub_title" <if condition="$slider['type'] gt 0 OR !isset($slider['type'])">style="display:none"</if>>
				<th width="80">导航副标题</th>
				<td><input type="text" class="input fl" name="sub_name" size="20" value="{pigcms{$slider.sub_name}"  placeholder="请输入名称" validate="maxlength:20"/></td>
			</tr>
			<tr>
				<th width="80">导航图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:180px;" value="{pigcms{$slider.pic}"   placeholder="请上传图片" />
				<em tips="纯图片模式下建议尺寸：
				 1图 纯图模式，尺寸是750*230。2图 纯图模式，第一、第二张图均是206*97。3图 纯图模式，第一张图280*270，第二、第三张图均是280*135。4图 图文模式，四张图均是350*240。5图 图文模式，第一、第二张图均是350*240，第三、第四、第五张图均是230*260。" class="notice_tips"></em></td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
				<input type="text" class="input fl" name="url" id="url" style="width:180px;" placeholder="请填写链接地址"  value="{pigcms{$slider.url}" validate="maxlength:200,required:true,url:true"/>
				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
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
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $slider['wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr style="display: none;" class="wxapp_open_wxapp wxapp_page">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$slider.wxapp_page}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
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
	                   	<option value="1" <if condition="$slider['app_open_type'] eq 1">selected="selected"</if>>打开其他小程序</option>
	                   	<option value="2" <if condition="$slider['app_open_type'] eq 2">selected="selected"</if>>打开其他app</option>
                   </select>
                </td>
            </tr>
			<tr class="app_url app_open_app">
				<th width="80">选择苹果APP</th>
				<td>
                   	<select name="ios_app_name" id="ios_app_name" tips="">
	                   	<option value="">选择苹果APP</option>
	                   	<volist name='app_list' id='vo'>
	                   		<option value="{pigcms{$vo.url_scheme}" <if condition="$vo['url_scheme'] eq $slider['ios_app_name']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
			</tr>
			<tr class="ios_app_url app_open_app">
				<th width="80">苹果APP下载地址</th>
				<td><input type="text" class="input fl" name="ios_app_url" id="ios_app_url" style="width:200px;" value="{pigcms{$slider.ios_app_url}"  placeholder="请输入苹果APP下载地址" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP包名</th>
				<td><input type="text" class="input fl" name="android_app_name" style="width:200px;" value="{pigcms{$slider.android_app_name}"  placeholder="请输入安卓APP包名" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP下载地址</th>
				<td><input type="text" class="input fl" name="android_app_url" style="width:200px;" value="{pigcms{$slider.android_app_url}"  placeholder="请输入安卓APP下载地址" tips=""/></td>
			</tr>
			<tr>
				<th width="80">导航排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;"  value="{pigcms{$slider.sort}" validate="maxlength:10,required:true,number:true"/>
				<em tips="数值越高，排序靠前" class="notice_tips"></em>
				</td>
			</tr>
            <tr class="app_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="app_wxapp_id" id="app_wxapp_id" tips="请选择一个想要打开的其他小程序">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $slider['app_wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr class="app_open_wxapp" style="display: none;">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="app_wxapp_page" id="app_wxapp_page" style="width:200px;" value="{pigcms{$slider.app_wxapp_page}" placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>

            <if condition="$user_level neq 1">
                <tr>
                    <th width="15%">所有区域通用</th>
                    <td width="35%" class="radio_box">
                        <span class="cb-enable"><label class="cb-enable <if condition="$slider['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$slider['city_id'] eq 0">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$slider['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$slider['city_id'] neq 0">checked="checked"</if> /></label></span>
                    </td>
                </tr>
			<else/>
				<tr style="display:none;">
                    <th width="15%">所有区域通用</th>
                    <td width="35%" class="radio_box">
						<input id="no" type="radio" name="currency" value="2" checked="checked"/>
                    </td>
                </tr>
            </if>
			<tr id="adver_region" <if condition="$slider['city_id'] eq 0 && $user_level neq 1">style="display: none;"</if> >
				<th width="15%">绑定区域</th>
				<td width="85%" colspan="3" id="choose_cityareass" province_idss="{pigcms{$slider.province_id}" city_idss="{pigcms{$slider.city_id}"></td>
			</tr>


            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效日期</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$slider['effect_date_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="effect_date_type" value="1" <if condition="$slider['effect_date_type'] neq 2">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$slider['effect_date_type'] eq 2">selected</if>"><span>自定义日期</span><input type="radio" name="effect_date_type" value="2" <if condition="$slider['effect_date_type'] eq 2">checked="checked"</if> /></label></span>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择日期</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_date" style="width:120px;" id="d4311"  value="{pigcms{$slider.start_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})"/>-
                    <input type="text" class="input-text" name="end_date" style="width:120px;" id="d4311" value="{pigcms{$slider.end_date}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd'})" validate="required:true"/>
                </td>
            </tr>
            <tr class="effect_date_tr">
                <th width="80">选择星期</th>
                <td colspan="2">
                    <input type="checkbox" name="week[]" value="0" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'0') !== false)">checked</if>>&nbsp;星期日&nbsp;
                    <input type="checkbox" name="week[]" value="1" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'1') !== false)">checked</if>>&nbsp;星期一&nbsp;
                    <input type="checkbox" name="week[]" value="2" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'2') !== false)">checked</if>>&nbsp;星期二&nbsp;
                    <input type="checkbox" name="week[]" value="3" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'3') !== false)">checked</if>>&nbsp;星期三&nbsp;
                    <input type="checkbox" name="week[]" value="4" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'4') !== false)">checked</if>>&nbsp;星期四&nbsp;
                    <input type="checkbox" name="week[]" value="5" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'5') !== false)">checked</if>>&nbsp;星期五&nbsp;
                    <input type="checkbox" name="week[]" value="6" <if condition="($_GET['id'] lt 1) or ($slider['effect_date_type'] eq 1) or (strpos($slider['week'],'6') !== false)">checked</if>>&nbsp;星期六
                </td>
            </tr>
            <tr>
                <th width="80"><span style="color: #FF0000">*</span>生效时间段</th>
                <td class="radio_box">
                    <span class="cb-enable"><label class="cb-enable <if condition="$slider['effect_time_type'] neq 2">selected</if>"><span>全时段</span><input type="radio" name="effect_time_type" value="1" <if condition="$slider['effect_time_type'] neq 2">checked="checked"</if>/></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$slider['effect_time_type'] eq 2">selected</if>" ><span>自定义时间段</span><input type="radio" name="effect_time_type" value="2" <if condition="$slider['effect_time_type'] eq 2">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr class="effect_time_tr">
                <th width="80">选择时间</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4312"  value="{pigcms{$slider.start_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})"/>-
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4312" value="{pigcms{$slider.end_time}" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'HH:mm'})" validate="required:true"/>
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
<style type="text/css">
    .effect_date_tr,.effect_time_tr{
        display: none;
    }
</style>
<script>
    var ignoreValidate = ":hidden";

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

    $(function () {
        $("#group_main_page_center_type").change(function () {
            if ($(this).val() > 0) {
                $('#sub_title').hide();
            } else {
                $('#sub_title').show();
            }
        })
    });

    var pageDomId = ''
    function addLink(domid, iskeyword, type){
        pageDomId = domid;
        art.dialog.data('domid', domid);
        parent.setLinkBases(window, 'platform', 'h5');
    }
    //链接库回调方法
    function getLinkBasesUrl(url){
        $('#' + pageDomId).val(url);
    }

    $("#yes").click(function () {
        $("#adver_region").hide();
    });

    $("#no").click(function () {
        $("#adver_region").show();
    });

    $("#wxapp_id").change(function () {
        if ($("#wxapp_id").val()) {
            $(".wxapp_page").show();
        } else {
            $("#wxapp_page").val('');
            $(".wxapp_page").hide();
        }
    });

    $("#wxapp_id").trigger('change');
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