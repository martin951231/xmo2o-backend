<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/diy_field_modify')}" frame="true" refresh="true">
		<input type="hidden" name="id" value="{pigcms{$info['id']}"/>

			<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
				<tr>
					<th width="80">字段名称</th>
					<td><input type="text" class="input fl" name="name" id="name" size="25" value="{pigcms{$info['name']}" placeholder="" validate="maxlength:20,required:true" tips=""/></td>
				</tr>
				<tr>
					<th width="80">短标记(url)</th>
					<td><input type="text" class="input fl" name="url" id="url" size="25" value="{pigcms{$info['url']}" placeholder="英文或数字" validate="maxlength:20,required:true,en_num:true" tips="只能使用英文或数字，用于网址（url）中的标记！建议使用字段的拼音"/></td>
				</tr>
				<!--<tr>
					<th width="80">是否必填</th>
					<td>
						<span class="cb-enable"><label class="cb-enable  <php>if($info['iswrite']==1) echo 'selected';</php>"><span>是</span><input type="radio" name="iswrite" value="1" <php>if($info['iswrite']==1) echo 'checked="checked"';</php> /></label></span>
						<span class="cb-disable"><label class="cb-disable <php>if($info['iswrite']!=1) echo 'selected';</php>"><span>否</span><input type="radio" name="iswrite" value="0" <php>if($info['iswrite']!=1) echo 'checked="checked"';</php> /></label></span>
						<span style="margin-left: 30px;color: red">客户发布信息时决定此字段客户是否必须填写</span>
					</td>
				</tr>-->
				<tr>
					<th width="80">字段类型</th>
					<td>
						<select name="type" onchange="ShowTextarea(this.value);">
							<option value="1" <php>if($info['type']==1) echo 'selected="selected"';</php>>单文本框</option>
							<option value="2" <php>if($info['type']==2) echo 'selected="selected"';</php>>单选框</option>
							<option value="3" <php>if($info['type']==3) echo 'selected="selected"';</php>>复选框</option>
							<option value="4" <php>if($info['type']==4) echo 'selected="selected"';</php>>下拉框</option>
							<option value="5" <php>if($info['type']==5) echo 'selected="selected"';</php>>多文本框</option>
			
						</select>
					<span style="margin-left:10px; <php>if($info['type']!=1) echo 'display:none;'</php>" id="input_arr">属性：
						<select name="inarr" onchange="if(this.value==1){$('#input_unit').show();}else{$('#input_unit').hide();}">
						    <option value="0" <php>if($info['inarr']!=1) echo 'selected="selected"';</php>>无</option>
							<option value="1" <php>if($info['inarr']==1) echo 'selected="selected"';</php>>只填写数字</option>
						</select>&nbsp;&nbsp;
	   <select name="inunit" <php>if($info['inarr']!=1) echo 'style="display:none"';</php> id="input_unit">
			<option value="">请选择单位</option>
			<option value="g" <php>if($info['inunit']=='g') echo 'selected="selected"';</php>>g</option>
			<option value="kg" <php>if($info['inunit']=='kg') echo 'selected="selected"';</php>>kg</option>
			<option value="m" <php>if($info['inunit']=='m') echo 'selected="selected"';</php>>m</option>
			<option value="km" <php>if($info['inunit']=='km') echo 'selected="selected"';</php>>km</option>
			<option value="{pigcms{$config['Currency_txt']}" <php>if($info['inunit']== $config['Currency_txt']) echo 'selected="selected"';</php>>{pigcms{$config['Currency_txt']}</option>
			<option value="{pigcms{$config['Currency_txt']}/日" <php>if($info['inunit']== $config['Currency_txt'].'/日') echo 'selected="selected"';</php>>{pigcms{$config['Currency_txt']}/日</option>
			<option value="{pigcms{$config['Currency_txt']}/月" <php>if($info['inunit']== $config['Currency_txt'].'/月') echo 'selected="selected"';</php>>{pigcms{$config['Currency_txt']}/月</option>
			<option value="{pigcms{$config['Currency_txt']}/年" <php>if($info['inunit']== $config['Currency_txt'].'/年') echo 'selected="selected"';</php>>{pigcms{$config['Currency_txt']}/年</option>
			<option value="万" <php>if($info['inunit']=='万') echo 'selected="selected"';</php>>万</option>
			<option value="万{pigcms{$config['Currency_txt']}" <php>if($info['inunit']=='万'.$config['Currency_txt']) echo 'selected="selected"';</php>>万{pigcms{$config['Currency_txt']}</option>
			<option value="份" <php>if($info['inunit']=='份') echo 'selected="selected"';</php>>份</option>
			<option value="件" <php>if($info['inunit']=='件') echo 'selected="selected"';</php>>件</option>
			<option value="套" <php>if($info['inunit']=='套') echo 'selected="selected"';</php>>套</option>
			<option value="㎡" <php>if($info['inunit']=='㎡') echo 'selected="selected"';</php>>㎡</option>
			<option value="{pigcms{$config['Currency_txt']}/㎡" <php>if($info['inunit']==$config['Currency_txt'].'/㎡') echo 'selected="selected"';</php>>{pigcms{$config['Currency_txt']}/㎡</option>
		</select>
					</span>
					</td>

				</tr>
			  <tr id="valueoftype" <php>if($info['type']==1 || $info['type']==5 || $info['type']==6) echo 'style="display:none;"'</php>>
				  <th width="80">供选择值</th>
				  <td><textarea class="input fl" style="width:250px;height:80px;" name="valueoftype" placeholder ="必须写上这个字段类型供发布者选择的值一行算一个值">{pigcms{$optstr}</textarea></td>
			   </tr>
			   <if condition="$isfilter lt 5">
				    <tr id="is_show">
						<th width="80">是否作为筛选</th>
						<td id="changonoff">
								<span class="cb-enable">
								<label class="cb-enable <if condition="$info['isfilter'] eq '1'">selected</if>">
								<span>是</span>
								<input type="radio" name="isfilter" value="1" <if condition="$info['isfilter'] eq 1">checked="checked"</if>/></label>
								</span>
							<span class="cb-disable">
								<label class="cb-disable <if condition="$info['isfilter'] neq '1'">selected</if>"><span>否</span>
								<input type="radio" name="isfilter" value="0" <if condition="$info['isfilter'] neq 1">checked="checked"</if>/></label></span><span style="margin-left: 35px;color: red">最多只能设置4个字段
							</span>
						</td>
					</tr>
				    	
				<else/>
					<tr id="is_show"><th width="80">是否作为筛选</th><td id="changonoff"><span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="isfilter" value="0" checked="checked" /></label></span><span style="margin-left: 35px;color: red">最多只能设置4个字段(你已经添加4个了)</span></td></tr>
				</if>
			</table>
		<div class="btn hidden">
			<input type="submit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script type="text/javascript">
      $('#changonoff input[name="isfilter"]').click(function(){
			var vv=parseInt($(this).val());
			if(vv==1){
			  //$('#myonoffShow').show();
			}else{
			   $('#myonoffShow').hide();
			 }
	    });

	function ShowTextarea(vv){
	  vv=parseInt(vv);
	  if(vv==2 || vv==3 || vv==4){
	     $('#valueoftype').show();
	  }else{
	     $('#valueoftype').hide();
	  }
	  if(vv==1){
	    $('#input_arr').show();
	  }else{
	    $('#input_arr').hide();
	  }
	  if(vv==6){
	    $('#is_show').hide();
	    $('#valueoftype').hide();
	    $('#myonoffShow').hide();
	  }else{
	    $('#is_show').show();
	    $('#valueoftype').show();
	    //$('#myonoffShow').show();
	  }
	}
	</script>
<include file="Public:footer"/>