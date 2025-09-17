<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Diymenu/class_edit')}" enctype="multipart/form-data" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">菜单名称</th>
				<td>
					<input type="text" class="input fl" name="title" size="20" placeholder="主菜单名称" validate="maxlength:20,required:true" value="{pigcms{$show.title}"/>
					<input type="hidden" name="id" value="{pigcms{$show.id}"/>
				</td>
			</tr>
			<tr>
				<th width="80">父级菜单</th>
				<td>
					<div class="mr15 l">
					<select name="pid" id="pid">
						<option selected="selected" value="0">请选择父菜单</option>
						<volist id="class" name="class">
							<option value="{pigcms{$class.id}" <if condition="$show['pid'] eq $class['id']">selected</if>>{pigcms{$class.title}</option>
						</volist>
					</select>
					<em class="notice_tips" tips="二级菜单需要选择父菜单"></em>
					</div>
				</td>
			</tr>
			<tr>
				<th width="80">菜单类型</th>
				<td>
					<div class="mr15 l">
					<select name="menu_type" class="menu_type">
						<option value="1" <if condition="$type eq 1">selected</if>>关键词回复菜单</option>
						<option value="2" <if condition="$type eq 2">selected</if>>url链接菜单</option>
                        <if condition="!empty($config['pay_wxapp_appid'])">
							<option value="3" <if condition="$type eq 3">selected</if>>平台小程序</option>
                        </if>
						<if condition="!empty($config['pay_wxapp_paotui_appid'])">
							<option value="4" <if condition="$type eq 4">selected</if>>跑腿小程序</option>
                        </if>
						<if condition="!empty($config['pay_wxapp_group_appid'])">
							<option value="5" <if condition="$type eq 5">selected</if>>社群小程序</option>
                        </if>
                        <option value="6" <if condition="$type eq 6">selected</if>>打开第三方小程序</option>
					</select>
					</div>
				</td>
			</tr>
			<tr <if condition="$type neq 1">style="display:none;"</if> class="keyword">
				<th width="80">关联关键词</th>
				<td><input type="text" class="input fl" name="keyword" style="width:200px;" placeholder="关联关键词" value="{pigcms{$show.keyword}"/></td>
			</tr>
			<tr <if condition="$type neq 2">style="display:none;"</if> class="url">
				<th width="80">外链接url</th>
				<td>
    				<input type="text" class="input fl" name="url" id="url" style="width:200px;" placeholder="外链接url" value="{pigcms{$show.url}"/>
    				<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'modules')" data-toggle="modal">从功能库选择</a>
				</td>
			</tr>
            <tr class="wxapp_open_wxapp" <if condition="empty($show['other_wxapp_appid'])">style="display: none;"</if> >
                <th width="80">选择第三方小程序</th>
                <td>
                   	<select name="wxapp_id" id="wxapp_id" tips="公众号自定义菜单跳转小程序，小程序的页面路径必须填写，且路径上不能带参数，建议填小程序的首页路径" style="width: 150px;">
	                   	<option value="">请选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $show['other_wxapp_appid']">selected</if> >{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">必填</span>
                </td>
            </tr>
			<tr  class="wxapp_page_path"  <if condition="empty($show['other_wxapp_appid'])">style="display: none;"</if> >
				<th width="80">小程序页面路径</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$show.other_wxapp_pagepath}"  placeholder="请输入小程序页面路径（必填）" tips="公众号自定义菜单跳转小程序，小程序的页面路径必须填写，且路径上不能带参数，建议填小程序的首页路径"/>
                <span style="position: relative;top: 2px;">必填</span>
                </td>
			</tr>

           <tr <if condition="$type neq 3">style="display:none;"</if> class="miniprogram">
                <th width="80">小程序页面：</th>
                <td>
                    <input type="text" class="input fl" name="pagepath" id="pagepath" style="width:200px;" placeholder="小程序的页面路径" value="{pigcms{$show.pagepath}"/>
                    <a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('pagepath', 'programs')" data-toggle="modal">从功能库选择</a>
                </td>
            </tr>
            <tr style="display:none;" class="wxsys">
                <th width="80">扩展菜单：</th>
                <td>
                    <div class="mr15 l">
                        <select name="wxsys">
                            <option value="">请选择..</option>
                            <volist name="wxsys" id="wxsys">
                                <option value="{pigcms{$wxsys}" <if condition="$wxsys eq $show['wxsys']">selected</if>>{pigcms{$wxsys}</option>
                            </volist>
                        </select>
                    </div>
                    <div class="system l"></div>
                </td>
            </tr>
			<tr>
				<th width="80">显示：</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$show['is_show'] eq 1">selected</if>"><span>是</span><input type="radio" name="is_show" value="1" <if condition="$show['is_show'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$show['is_show'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_show" value="0" <if condition="$show['is_show'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<tr>
				<th width="80">排序：</th>
				<td>
					<div class="mr15 l">
					<input id="sortid" class="input fl" name="sort" title="排序"  value="{pigcms{$show.sort}" type="text"></div>
					<div class="system l"></div>
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
$(function(){
	$('.menu_type').change(function(){
		var val = $(this).val();
        $('.wxapp_open_wxapp').hide();
        $('.wxapp_page_path').hide();
		if(val == 1){
			$('.keyword').css('display', '');
			$('.miniprogram').css('display','none');
			$('.url').css('display','none');
		} else if(val == 2) {
			$('.keyword').css('display','none');
			$('.miniprogram').css('display','none');
			$('.url').css('display', '');
		} else if(val == 3) {
			$('.keyword').css('display','none');
			$('.miniprogram').css('display','');
			$('.url').css('display','none');
		} else if(val == 4) {
			$('.keyword').css('display','none');
			$('.miniprogram').css('display','none');
			$('.url').css('display','none');
		}else if(val == 6) {
			$('.keyword').css('display','none');
			$('.miniprogram').css('display','none');
			$('.url').css('display','none');
            $('.wxapp_open_wxapp').show();
		}
	});
});

$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page_path").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page_path").hide();
	}
});

var pageDomId = '';
function addLink(domid,type){
	if(domid == 'pagepath'){
		art.dialog.data('domid', domid);
		art.dialog.open('?g=Admin&c=Link&a=insert&type='+type,{lock:true,title:'插入链接或关键词',width:800,height:500,yesText:'关闭',background: '#000',opacity: 0.45});
	}else{
		pageDomId = domid;
		parent.setLinkBases(window, 'platform', 'h5');
	}
}
//链接库回调方法
function getLinkBasesUrl(url){
	console.log('url', url);
	$('#' + pageDomId).val(url);
}
</script>
<include file="Public:footer"/>