<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Adver/adver_amend')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$now_adver.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">广告名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$now_adver.name}" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>
			<tr>
				<th width="80">广告副标题</th>
				<td><input type="text" class="input fl" name="sub_name" size="20" value="{pigcms{$now_adver.sub_name}" placeholder="请输入副标题" validate="required:true"/></td>
			</tr>
			<if condition="($many_city eq 1 AND !in_array($now_category['cat_key'],array('wap_gift_adver','levelad'))) OR C('customization.open_view_multi_city') eq 1">
                <if condition="$is_area_many_city neq 1 ">
                    <tr id="adver_currency">
                        <th width="15%">通用广告</th>
                        <td width="35%" class="radio_box">
                            <span class="cb-enable"><label class="cb-enable <if condition="$now_adver['city_id'] eq 0">selected</if>"><span>通用</span><input id="yes" type="radio" name="currency" value="1" <if condition="$now_adver['city_id'] eq 0">checked="checked"</if> /></label></span>
                            <span class="cb-disable"><label class="cb-disable <if condition="$now_adver['city_id'] neq 0">selected</if>"><span>不通用</span><input id="no" type="radio" name="currency" value="2" <if condition="$now_adver['city_id'] neq 0">checked="checked"</if> /></label></span>
                            <if condition="C('customization.open_view_multi_city') eq 1">
                            <em for="currency" generated="true" class="error tips" style="position: relative;color: red;font-weight: bold;">选择不通用时请在列表页配置指定区域</em>
                            </if>
                        </td>
                    </tr>
                <else/>
                    <input id="no" type="hidden" name="currency" value="2" checked="checked" />
                </if>
            <if condition="C('customization.open_view_multi_city') neq 1">
				<tr id="adver_region" <if condition="$now_adver['city_id'] eq 0 or $is_area_many_city eq 1">style="display:none;"</if>>
					<th width="15%">所在区域</th>
					<td width="85%" colspan="3" id="choose_cityareass" province_idss="{pigcms{$now_adver['province_id']}" city_idss="{pigcms{$now_adver['city_id']}"></td>
				</tr>
            </if>
			</if>
			<tr>
				<th width="80">广告现图</th>
				<td><img src="{pigcms{:file_domain()}/upload/adver/{pigcms{$now_adver.pic}" style="width:260px;height:80px;" class="view_msg"/></td>
			</tr>
			<tr>
				<th width="80">广告图片</th>
				<td><input type="file" class="input fl" name="pic" style="width:200px;" placeholder="请上传图片" tips="不修改请不上传！上传新图片，老图片会被自动删除！"/></td>
			</tr>
			<if condition="!in_array($now_category['cat_key'],array('wap_gift_adver','wap_foodshop_index_top'))">
			<tr>
				<th width="80">背景颜色</th>
				<td><input type="text" class="input fl" name="bg_color" value="{pigcms{$now_adver.bg_color}" id="choose_color" style="width:120px;" placeholder="可不填写" tips="请点击右侧按钮选择颜色，用途为如果图片尺寸小于屏幕时，会被背景颜色扩充，主要为首页使用。"/>&nbsp;&nbsp;<a href="javascript:void(0);" id="choose_color_box_edit" style="line-height:28px;">点击选择颜色</a></td>
			</tr>
			</if>
            <if condition="$now_category['cat_key'] neq 'community_index_guide'">
                <tr>
                    <th width="80">链接地址</th>
                    <td>
                        <input type="text" class="input fl" name="url" id="url" value="{pigcms{$now_adver.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:800,required:true"/>
                       	<?php if(!C('butt_open')){ ?>
                            <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 0, 0)" data-toggle="modal">从功能库选择</a>
                        <?php } ?>
                    </td>
                </tr>
            </if>
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
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $now_adver['wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr style="display: none;" class="wxapp_open_wxapp wxapp_page">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$now_adver.wxapp_page}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
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
	                   	<option value="1" <if condition="$now_adver['app_open_type'] eq 1">selected="selected"</if>>打开其他小程序</option>
	                   	<option value="2" <if condition="$now_adver['app_open_type'] eq 2">selected="selected"</if>>打开其他app</option>
                   </select>
                </td>
            </tr>
			<tr class="app_url app_open_app">
				<th width="80">选择苹果APP</th>
				<td>
                   	<select name="ios_app_name" id="ios_app_name" tips="">
	                   	<option value="">选择苹果APP</option>
	                   	<volist name='app_list' id='vo'>
	                   		<option value="{pigcms{$vo.url_scheme}" <if condition="$vo['url_scheme'] eq $now_adver['ios_app_name']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
			</tr>
			<tr class="ios_app_url app_open_app">
				<th width="80">苹果APP下载地址</th>
				<td><input type="text" class="input fl" name="ios_app_url" id="ios_app_url" style="width:200px;" value="{pigcms{$now_adver.ios_app_url}"  placeholder="请输入苹果APP下载地址" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP包名</th>
				<td><input type="text" class="input fl" name="android_app_name" style="width:200px;" value="{pigcms{$now_adver.android_app_name}"  placeholder="请输入安卓APP包名" tips=""/></td>
			</tr>
			<tr class="app_open_app">
				<th width="80">安卓APP下载地址</th>
				<td><input type="text" class="input fl" name="android_app_url" style="width:200px;" value="{pigcms{$now_adver.android_app_url}"  placeholder="请输入安卓APP下载地址" tips=""/></td>
			</tr>
            <tr class="app_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="app_wxapp_id" id="app_wxapp_id" tips="请选择一个想要打开的其他小程序">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $now_adver['app_wxapp_id']">selected</if>>{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr class="app_open_wxapp" style="display: none;">
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="app_wxapp_page" id="app_wxapp_page" style="width:200px;" value="{pigcms{$now_adver.app_wxapp_page}" placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<th width="80">广告排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$now_adver.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">广告状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_adver['status'] eq 1">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$now_adver['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_adver['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$now_adver['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<if condition="!in_array($now_category['cat_key'],array('wap_gift_adver'))">
			<tr>
				<th width="80">以通用广告来补全广告位</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$now_adver['complete'] eq 1">selected</if>"><span>是</span><input type="radio" name="complete" value="1" <if condition="$now_adver['complete'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$now_adver['complete'] eq 0">selected</if>"><span>否</span><input type="radio" name="complete" value="0" <if condition="$now_adver['complete'] eq 0">checked="checked"</if>/></label></span>
					<em tips="当设置过城市广告时，城市广告数量不够总数量，是否使用通用广告来补全城市广告位的数量。" class="notice_tips"></em>
				</td>
			</tr>
			</if>

            <if condition="$_GET['cat_key']=='app_index_top' && $customization['life_tools'] eq 1">
                <tr>
                    <th>指定员工可见</th>
                    <td>
                        <tr>
                            <td colspan="2">
                                <input class="default" type="button" value="添加" onclick="addLabel()"/>
                            </td>
                        </tr>
                <if condition="$now_adver['assigns_staff']">
                    <volist name='assigns_staff' id='staff'>
                            <tr id="append">
                                <td>
                                    <select name="selectMer[]" class='selectMer' style="width:250px" onchange="selectProvince(this)">
                                        <!--<option name="" value="" selected="selected">请选择商家</option>-->
                                        <volist name='cardLableMerList' id='merList'>
                                            <option value="{pigcms{$merList.mer_id}" <if condition="$staff['mer_id'] eq $merList['mer_id']">selected="selected"</if>>{pigcms{$merList.name}</option>
                                            <!--<option value="{pigcms{$merList.mer_id}">{pigcms{$merList.name}</option>-->
                                        </volist>
                                    </select>
                                </td>
                                <td>
                                    <volist name='staff.all_ids' id='staf_ids'>
                                            <label>{pigcms{$staf_ids.name}<input type="checkbox" name="label_{pigcms{$staf_ids.mer_id}[]"
                                                                                 value="{pigcms{$staf_ids.id}" onchange="checkLabel(this,1)"
                                                <if condition="$staf_ids['checked']">checked="checked"</if>>
                                            </label>
                                    </volist>
                                </td>
                            </tr>
                    </volist>
                    <else/>
                    <tr id="append">
                        <td>
                            <select name="selectMer[]" class='selectMer' style="width:250px" onchange="selectProvince(this)">
                                <option name="" value="" selected="selected">请选择商家</option>
                                <volist name='cardLableMerList' id='merList'>
                                    <option value="{pigcms{$merList.mer_id}">{pigcms{$merList.name}</option>
                                </volist>
                            </select>
                        </td>
                    </tr>
                </if>
                    </td>
            </tr>
            </if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script>
    function addLabel() {
        $('#append').after("<tr>" +
            "                            <td>" +
            "                                <select name='selectMer[]' style='width:250px' onchange='selectProvince(this)' class='selectMer' >" +
            "                                    <option name='' value='' selected='selected'>请选择商家</option>" +
            "                                    <volist name='cardLableMerList' id='merList'>" +
            "                                        <option value='{pigcms{$merList.mer_id}'>{pigcms{$merList.name}</option>" +
            "                                    </volist>" +
            "                                </select>" +
            "                            </td>" +
            "                        </tr>");
    }

    var selectArr=[];
    function selectProvince(obj) {
        var param={};
        param.mer_id=$(obj).val();
        param.ids = []
        selectArr.push(param);
        $.ajax({
            url : "{pigcms{:U('ajax_getLabel')}",
            async: false,//这一步是非常重要的，作用是设置为同步执行
            dataType : "json",
            data:{'mer_id':$(obj).val()},
            type : "post",
            success: function (data) {
                if(data.length>0) {
                    $(obj).parent().siblings().html("");
                    var Html="";
                    $.each(data,function(i,item){
                        Html+="<label>"+item.name+
                            " <input type='checkbox' name='label_"+item.mer_id+"[]' value='"+item.id+"' onchange='checkLabel(this,"+$(obj).val()+")'/>"+"</label>";
                    });
                    var append="<td id='"+$(obj).val()+"'>"+Html+"</td>";
                        /*"<td><input class='default' type='button' value='移除' onclick='delLabel("+$(obj).val()+")'/></td>"*/
                    $(obj).parent().after(append);
                }
            },
            error:function(data){
                console.log(data)
            }
        });
    }

    function delLabel(mer_id) {
        $("#"+mer_id).remove();
    }

    function checkLabel(obj,mer_id) {
        // alert($(obj).val());
        //console.log(mer_id)
        if($(obj).is(":checked")){
            selectArr.map(value => {
                if(value.mer_id == mer_id) {
                    value.ids.push($(obj).val())
                }
                return  value
            })
        }else{
            selectArr.map(value => {
                if(value.mer_id == mer_id) {
                    value.ids.splice($.inArray($(obj).val(),value.ids),1);
                }
                return  value
            })
        }
        //$("input[name='assigns_staff']").val(htmlEncode(JSON.stringify(selectArr)))
        //$("input[name='assigns_staff[]']").val(selectArr)
    }

    function htmlEncode(str) {
        str = str.replace(/s+/ig, '');
        str = str.replace(/&/g, '');
        str = str.replace(/</g, '');
        str = str.replace(/>/g, '');
        str = str.replace(/(?:t| |v|r)*n/g, '<br />');
        str = str.replace(/t/g, '    ');
        str = str.replace(/x22/g, '"');
        str = str.replace(/"/g, "");
        return str;
    }
</script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
    var pageDomId = ''
    function addLink(domid, iskeyword, type){
        pageDomId = domid;
        art.dialog.data('domid', domid);
        parent.setLinkBases(window, 'platform', 'h5');
    }
    //链接库回调方法
    function getLinkBasesUrl(url){
        console.log('url', url);
        $('#' + pageDomId).val(url);
    }
var is_area_many_city = <?php echo $is_area_many_city ?>;
$("#yes").click(function(){
	$("#adver_region").hide();
})
$("#no").click(function(){
    if(is_area_many_city==0){
        $("#adver_region").show();
    }
})
$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})
$("#wxapp_id").trigger('change');

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

$("#ios_app_name").change(function(){
	if ($("#ios_app_name").val()) {
		$(".ios_app_url").show();
	}else{
		$("#ios_app_url").val('');
		$(".ios_app_url").hide();
	}
})
$("#ios_app_name").trigger('change');

$('#choose_color_box_edit').click(function(){
        window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'choose_color',function(color){
            console.log('data', color);
            $("#choose_color").val(color?color: '');
            $("#choose_color").css("background-color",color?color: 'inherit');
        })
    });
</script>
<script>
	var admin_area_id = {pigcms{$system_session.area_id};
    if((admin_area_id > 0) && (is_area_many_city != 1)){
        $("#adver_currency").hide();
        $("#no").trigger('click');
    }
</script>
<include file="Public:footer"/>