<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('add_label')}" enctype="multipart/form-data">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<input type="hidden" name="id" value="{pigcms{$_GET['id']}">
			<tr>
				<th width="80">名称</th>
				<td><input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$label.name}" placeholder="" validate="maxlength:4,required:true" tips=""/></td>
			</tr>
			
			<tr>
				<th width="80">标签类型</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$label.type eq 0 OR empty($label)">selected</if>"><span>文字</span><input type="radio" name="type" value="0" <if condition="$label.type eq 0">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$label.type eq 1">selected</if>"><span>图片</span><input type="radio" name="type" value="1" <if condition="$label.type eq 1">checked="checked"</if>/></label></span>
					&nbsp;&nbsp;&nbsp;选择文字，名称即是标签
				</td>
			</tr>
			
			<tr class="image" <if condition="empty($label) OR $label.type eq 0">style="display:none;"</if>>
				<th width="80">图片</th>
				<td><input type="file" class="input fl" name="image" style="width:200px;" placeholder="请上传图片" />&nbsp;&nbsp;&nbsp;&nbsp;图片比例100x100</td>
			</tr>
			<if condition="!($label) AND  $label.type eq 1">
			<tr class="image" >
				<th width="80">图片展示</th>
				<td style="line-height: 30px;"><img src="{pigcms{:file_domain()}{pigcms{$label.image}" style="width:50px;height:40px;"></td>
			</tr>
			</if>
			
			<tr class="text" <if condition="!empty($label) AND $label.type eq 1">style="display:none;"</if>>
				<th width="80">文字背景渐变颜色1</th>
				<td style="line-height: 40px;"><input type="hidden" class="color" name="color" id="color1" value="{pigcms{$label.color|default='#ff9804'}"/>	<div class="color-box color-box1"></div>点击选择颜色</td>
			</tr>
			
			<tr class="text" <if condition="!empty($label) AND $label.type eq 1">style="display:none;"</if>>
				<th width="80">文字背景渐变颜色2</th>
				<td style="    line-height: 40px;"><input type="hidden" class="color" name="color2"  id="color2" value="{pigcms{$label.color2|default='#fe3112'}">	<div class="color-box color-box2"></div>点击选择颜色</td>
			</tr>
			
			<tr class="text" <if condition="!empty($label) AND $label.type eq 1">style="display:none;"</if>>
				<th width="80">文字背景渐变效果</th>
				<td><div class="color-box2" style="background: linear-gradient(to bottom, {pigcms{$label.color|default='#ff9804'}, {pigcms{$label.color2|default='#fe3112'});" id="color_style">标签</div></td>
			</tr>
			
			
			
			<tr>
				<th width="80">标签状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$label.status eq 1 OR empty($label)">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$label.status eq 1 OR empty($label)">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$label.status eq 0 AND !empty($label)">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$label.status eq 0 AND !empty($label)">checked="checked"</if>/></label></span>
				</td>
			</tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<style>
	
		.color-box1 {

			float:left;

			width:30px;

			height:30px;

			margin:5px;

			border: 1px solid white;
			background-color: {pigcms{$label.color|default='#ff9804'};

		}
		
		.color-box2 {

			float:left;

			width:30px;

			height:30px;

			margin:5px;

			border: 1px solid white;
			background-color: {pigcms{$label.color2|default='#fe3112'};
		}
		#color_style{
			text-align:center;
			line-height:30px;
			color:#fff;
		}

	</style>
	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/colpick.js"></script>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/colpick.css?111" />

	

	<script>
		$(function(){
			$('input[name="type"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					$('.image').show();
					$('.text').hide();
				}else{
					$('.image').hide();
					$('.text').show();
				}
			});
			
			$('.color-box').colpick({

				colorScheme:'dark',

				layout:'rgbhex',

				color:'#ff8800',

				onSubmit:function(hsb,hex,rgb,el) {

					$(el).siblings('.color').val('#'+hex);
					$(el).css('background-color', '#'+hex);
					console.log($('#color1').val())
					console.log($('#color2').val())
					// $('#color_style').html($('#name').val())
					// $('#color_style').css('color','#fff')
					$('#color_style').css('background', 'linear-gradient(to bottom, '+$('#color1').val()+' , '+$('#color2').val()+')');
					// $('#color_style').css('background', 'background: linear-gradient(to bottom, #ff9804 , #1070e6)');
					$(el).colpickHide();
					

				}

			})
			
		})
	</script>
	
	
<include file="Public:footer"/>