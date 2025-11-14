<include file="Publics:header"/>
<style>
	.frame_form .input{
		height:30px;
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
</style>
<if condition="$row_type neq single AND $yingshan AND $jsdhwy_new_wap_index lt 1">
	<div style="margin-left: 10px;color: #9a9999">
		<span> 说明：</span>
		<p>1.角标最多只能输入四位</p>
		<p>2.角标只填写角标名称时，默认背景是红色</p>
	</div>
</if>
	<form id="myform" method="post" action="{pigcms{:U('Library/nav_amend_new')}" enctype="multipart/form-data">
		<input type="hidden" name="id" value="{pigcms{$detail.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$detail.name}" size="20" placeholder="请输入名称" validate="maxlength:20,required:true"/></td>
			</tr>


			<tr>
				<th width="80">现图</th>

				<td>
<!--					<if condition="strpos($detail['img'],'static/') === false">-->
<!--						<img src="{pigcms{:file_domain()}/upload/service/{pigcms{$detail['img']}" style="width:150px;height:150px;" class="view_msg"/>-->
<!--						<else/>-->
<!--						<img src="{pigcms{:file_domain()}{pigcms{$detail['img']}" style="width:150px;height:150px;" class="view_msg"/>-->
<!--					</if>-->
					<img src="{pigcms{$detail['img']}" style="width:150px;height:150px;" class="view_msg"/>
				</td>
			</tr>
			<tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="img" style="width:200px;" placeholder="请上传图片" tips="不修改请不上传！上传新图片，老图片会被自动删除！" onchange="preview(this);" /></td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
					<input type="text" class="input fl" name="url" id="url" value="{pigcms{$detail.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:2000,required:true"/>
					<?php if(!C('butt_open')){ ?>
						<if condition="$now_category['cat_type'] neq 1">
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 0)" data-toggle="modal">从功能库选择</a>
							<else />
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 1)" data-toggle="modal">从功能库选择</a>
						</if>
					<?php } ?>
				</td>
			</tr>

            <tr class="wxapp_open_wxapp">
                <th width="80">打开其他小程序</th>
                <td>
                   	<select name="wxapp_id" id="wxapp_id" tips="请选择一个想要打开的其他小程序，目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。">
	                   	<option value="">选择小程序</option>
	                   	<volist name='wxapp_list' id='vo'>
	                   		<option value="{pigcms{$vo.appid}" <if condition="$vo['appid'] eq $detail['wxapp_id']">selected</if> >{pigcms{$vo.name}</option>
	                   	</volist>
                   </select>
                   <span style="position: relative;top: 2px;">选填</span>
                </td>
            </tr>
			<tr  class="wxapp_open_wxapp wxapp_page"  <if condition="empty($detail['wxapp_id'])">style="display: none;"</if> >
				<th width="80">小程序页面</th>
				<td><input type="text" class="input fl" name="wxapp_page" id="wxapp_page" style="width:200px;" value="{pigcms{$detail.wxapp_page}"  placeholder="请输入小程序页面路径" tips="即打开另一个小程序时进入的页面路径，如果为空则打开首页；另一个小程序的页面路径请联系该小程序的技术人员询要；目前仅支持用户在平台小程序首页和{pigcms{$config.shop_alias_name}首页中打开其他小程序。"/></td>
			</tr>
			<tr>
				<th width="80">排序</th>
				<td><input type="text" class="input fl" name="sort" style="width:80px;" value="{pigcms{$detail.sort}" validate="maxlength:10,required:true,number:true"/></td>
			</tr>
			<tr>
				<th width="80">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$detail['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1" <if condition="$detail['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$detail['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$detail['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>

			<if condition="$row_type neq single AND $yingshan AND $jsdhwy_new_wap_index lt 1">
				<tr>
					<th width="80">角标名称</th>
					<td><input type="text" class="input fl" id="mark_name" name="mark_name" value="{pigcms{$detail.mark_name}" onblur="if(value.length>4) value=value.slice(0,4);change_mark_box()" placeholder="请输入名称"/></td>
				</tr>

				<tr>
					<th width="80">角标背景颜色</th>
					<td>
						<input type="text" readonly class="input fl" name="mark_bg" id="mark_bg" value="{pigcms{$detail.mark_bg}" style="width:120px;height:35px;" placeholder="不可填写" />&nbsp;&nbsp;
						<a href="javascript:void(0);" id="mark_bg_color" style="line-height:28px;">点击选择颜色</a>
					</td>
				</tr>

				<tr class="show_box">
					<th width="80">角标效果示例</th>
					<td>
						<span class='mark_box'>
							<img src="{pigcms{$detail['img']}">
							<i class='mark'>红包</i>
						</span>
					</td>
				</tr>
			</if>

		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>


<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/upyun.js"></script>
<script>
  var img_src="{pigcms{$detail['img']}";

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
      $('.mark_box').find('.mark').text(mark_name);
      $('.mark_box').find('.mark').css({'background-color':mark_bg});
      $('.mark_box').find('.mark').show();
      $('.mark_box').find('img').attr('src',img_src);
      $('.show_box').show();
    }else{
      $('.mark_box').find('.mark').hide();
      $('.show_box').hide();
    }
  }
  change_mark_box();

  $('#mark_bg_color').click(function(){
    window.showTopColorPanel('Openadd',$(this).offset().top,$(this).height(),$(this).offset().left,'mark_bg',function(color){
      $("#mark_bg").val(color);
      var cssText = $("#mark_bg").attr("style") + ";background-color:"+ color +" !important;";
      $("#mark_bg").css("cssText",cssText);
      change_mark_box();
    })
  });

$("#wxapp_id").change(function(){
	if ($("#wxapp_id").val()) {
		$(".wxapp_page").show();
	}else{
		$("#wxapp_page").val('');
		$(".wxapp_page").hide();
	}
})
</script>
<include file="Publics:footer"/>