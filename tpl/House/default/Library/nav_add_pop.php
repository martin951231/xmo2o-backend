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
	<form id="myform" method="post" action="{pigcms{:U('Library/nav_amend_pop')}" enctype="multipart/form-data">
		  <input type="hidden" name="id" value="{pigcms{$detail.id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$detail.name}" size="20" placeholder="请输入名称" validate="maxlength:50,required:true"/></td>
			</tr>
            <tr>
            <th width="80">活动日期</th>
                <td>
                    <input type="text" name="start_time" id="start_time" autocomplete="off" class="input-text" value="{pigcms{$detail.start_time}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="开始日期" validate="maxlength:50,required:true"/>
                    &nbsp;—&nbsp;
                    <input type="text" name="end_time"  id="end_time" autocomplete="off" class="input-text" value="{pigcms{$detail.end_time}"  style="height:30px;" onfocus="WdatePicker({isShowClear:false,readOnly:false,dateFmt:'yyyy-MM-dd'})" placeholder="结束日期"/>&nbsp;&nbsp;&nbsp;&nbsp;
                    <span class="form_tips"></span>
                </td>
                </tr>

			<tr>
				<th width="80">现图</th>

				<td>
					<img src="{pigcms{$detail['img']}" style="width:150px;height:150px;" class="view_msg"/>
				</td>
			</tr>
			<tr>
				<th width="80">图标</th>
				<td><input type="file" class="input fl" name="img" style="width:200px;" placeholder="请上传图片" tips="不修改请不上传！上传新图片，老图片会被自动删除！" onchange="preview(this);"  <if condition="empty($detail) OR empty($detail['img'])">validate="maxlength:200,required:true" </if> /> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;建议尺寸:586X734支持扩展名:png，jpg</td>
			</tr>
			<tr>
				<th width="80">链接地址</th>
				<td>
					<input type="text" class="input fl" name="url" id="url" value="{pigcms{$detail.url}" style="width:200px;" placeholder="请填写链接地址" validate="maxlength:200,required:true"/>
					<?php if(!C('butt_open')){ ?>
						<if condition="$now_category['cat_type'] neq 1">
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 0)" data-toggle="modal">从功能库选择</a>
							<else />
							<a href="#modal-table" class="btn btn-sm btn-success" onclick="addLink('url', 'on_deving', 1)" data-toggle="modal">从功能库选择</a>
						</if>
					<?php } ?>
				</td>
			</tr>


			<tr>
				<th width="80">是否开启</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$detail['status'] eq 1">selected</if>"><span>开启</span><input type="radio" name="status" value="1" <if condition="$detail['status'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$detail['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$detail['status'] eq 0">checked="checked"</if>/></label></span>
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

</script>
<include file="Publics:footer"/>