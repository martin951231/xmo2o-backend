<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('company_edit')}" enctype="multipart/form-data" frame="true" refresh="true">
		<input type="hidden" name="company_id" value="{pigcms{$detail.company_id}">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="15%">企业名称</th>
				<td><input type="text" class="input fl" name="company_name" size="20" validate="maxlength:20,required:true" tips="企业名称" value="{pigcms{$detail['company_name']}" /></td>
			</tr>
			<tr>
				<th width="15%">法人姓名</th>
				<td><input type="text" class="input fl" name="legal_person_name" size="20" validate="maxlength:20,required:true" tips="法人姓名" value="{pigcms{$detail['legal_person_name']}"/></td>
			</tr>
			<tr>
				<th width="15%">手机号</th>
				<td><input type="text" class="input fl" name="phone" size="20" validate="maxlength:20,required:true" tips="手机号" value="{pigcms{$detail['phone']}"/></td>
			</tr>
			<tr>
				<th width="15%">身份证号</th>
				<td><input type="text" class="input fl" name="ID_number" size="20" validate="maxlength:20,required:true" tips="身份证号" value="{pigcms{$detail['ID_number']}"/></td>
			</tr>
			<tr>
				<th width="15%">是否隐藏</th>
				<td >
					<span class="cb-enable">
						<label class="cb-enable <if condition="$detail['is_hide'] eq 1">selected</if>">
							<span>是</span>
							<input type="radio" name="is_hide" value="1" <if condition="$detail['is_hide'] eq 1">checked="checked</if>">
						</label>
					</span>
					<span class="cb-disable">
						<label class="cb-disable  <if condition="$detail['is_hide'] eq 0">selected</if>">
							<span>否</span>
							<input type="radio" name="is_hide" value="0"  <if condition="$detail['is_hide'] eq 0">checked="checked</if>">
						</label>
					</span>
				</td>
			</tr>
			<tr>
				<th width="15%">公司地址</th>
				<td width="35%"><input type="text" class="input fl" size="50"  readonly="readonly" name="address" id="adress" validate="required:true" value="{pigcms{$detail['address']}" placeholder="请在下方地图中选择位置" /></td>
			</tr>
				
			<tr>
				<th width="15%">经纬度</th>
				<td width="35%" class="radio_box"><input class="input fl" size="50" name="long_lat" id="long_lat" type="text" readonly="readonly" validate="required:true" value="{pigcms{$detail['lng']},{pigcms{$detail['lat']}" /></td>
			</tr>
			<tr>
				<th width="15%">店铺面积</th>
				<td><input type="text" class="input fl" name="acreage" size="20" validate="maxlength:20,required:true" tips="店铺面积" value="{pigcms{$detail['acreage']}"/></td>
			</tr>
			<tr >
				<th width="80">营业执照</th>
				<td>
					<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;
	                <input type="hidden" class="input-image" name="license_pic" id="pic_list" value="<if condition="$detail['license_pic_v'] neq ''">{pigcms{$detail['license_pic_v']}</if>">
	                <ul class="image_product_list" id="license_pic_detail">

	                </ul>
	                <ul class="license_pic_list" id="license_pic_detail_show" >
	                <if condition="$detail.license_pic neq ''">
	                    <volist name="detail.license_pic" id="vo">
	                        <li  class="upload_pic_li1" style="float:left;list-style:none;"><img src="{pigcms{:file_domain().$vo}" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del_license(this,{pigcms{$i})">[删除]</a></li>
	                    </volist>
	                </if>
	                </ul>
            	</td>
				
				
			</tr>
			<tr>
				<th width="15%">店铺环境照片</th>
				<td>
					<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage2" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >上传图片</a>&nbsp;&nbsp;
	                <input type="hidden" class="input-image" name="company_pic" id="pic_list" value="<if condition="$detail['company_pic_v'] neq ''">{pigcms{$detail['company_pic_v']}</if>">
	                <ul class="image_product_list" id="company_pic_detail">

	                </ul>
	                <ul class="company_pic_list" id="company_pic_detail_show" >
	                <if condition="$detail.company_pic neq ''">
	                    <volist name="detail.company_pic" id="vo">
	                        <li  class="upload_pic_li2" style="float:left;list-style:none;"><img src="{pigcms{:file_domain().$vo}" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del_company(this,{pigcms{$i})">[删除]</a></li>
	                    </volist>
	                </if>
	                </ul>
            	</td>
			</tr>
		<!-- 	<tr>
				<th width="15%">状态</th>
				<td width="35%"> 
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="status" <if condition="1 eq $detail['status']">checked="checked"</if> value="1"/> 通过</label>
                        <label style="float:left;width:60px" class="checkbox_status"><input type="radio" class="input_radio" name="status" <if condition="1 neq $detail['status']">checked="checked"</if> value="2" /> 不通过</label>
                </td>
			</tr>	
			<tr class="reason">
				<th width="15%">原因</th>
				<td width="35%">
                   <input type="text" class="input fl" name="reason" value="{pigcms{$detail.reason}" placeholder="审核原因"/>
                </td>
			</tr>			 -->
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>

	<div class="modal-content" style="width:100%;">
		<div class="modal-header no-padding" style="width:100%;">
			<div class="table-header" style="padding-left:10px;">拖动红色图标，经纬度框内将自动填充经纬度。</div>
		</div>
		<div class="modal-body no-padding" style="width:100%;">
			<form id="map-search" style="margin:10px;">
				<input id="map-keyword" type="textbox" style="width:300px;border:1px solid #ccc;height:24px;line-height:24px;padding-left:6px;" placeholder="尽量填写城市、区域、街道名"/>
				<input type="submit" value="搜索" class="button" style="margin-left:0px;"/>
			</form>
			<div style="width:750px;height:400px;margin-left:10px;" id="cmmap"></div>
		</div>
	</div>
	<script type="text/javascript">
		var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}";
		var default_lng = 117.2285309;
		var default_lat = 31.8291397;
	</script>
	<if condition="$default_city['area_lng']">
		<script type="text/javascript">
			default_lng = {pigcms{$default_city.area_lng};
			default_lat = {pigcms{$default_city.area_lat};
		</script>
	</if>
    <if condition="$config['map_config'] eq 'google' AND $config['google_map_ak']">
        <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places,drawing&key={pigcms{$config.google_map_ak}&language=en"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/map_google.js"></script>
    <else />
		<script type="text/javascript" src="https://api.map.baidu.com/api?ak={pigcms{$config.baidu_map_ak_web}&v=2.0&s=1" charset="utf-8"></script>
		<script type="text/javascript" src="https://api.map.baidu.com/library/DrawingManager/1.4/src/DrawingManager_min.js"></script>
		<script type="text/javascript">var polygonMap = 1;</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/map.js"></script>
    </if>

	<script type="text/javascript">
		$('input[name=status]').click(function (argument) {
			if ($(this).val()==2) {
				$('.reason').show();
			}else{
				$('.reason').hide();
			}
		})
		$('input[name=status]').trigger('click');
	</script>

	<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
	<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>

	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
			editor = K.create('#content', {
				filterMode: false,
				resizeType : 1,
				allowPreviewEmoticons : false,
				allowImageUpload : true,
				uploadJson : '/admin.php?g=System&c=Upyun&a=kindedtiropic',
				items : ['source', 'fontname', 'fontsize','subscript','superscript','indent','outdent','|', 'forecolor', 'hilitecolor', 'bold', 'italic', 'underline','hr',
				 '|', 'justifyleft', 'justifycenter', 'justifyright', 'insertorderedlist',
				'insertunorderedlist','link', 'unlink','image','media','diyTool']
			});
            content_editor = editor;
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
            $('.J_selectImage').click(function(){
                var upload_file_btn = $(this);
                editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=recruit";
                editor.loadPlugin('image', function(){
                    editor.plugin.imageDialog({
                        showRemote : false,
                        clickFn : function(url, title, width, height, border, align) {
                            var license_pic_detail_length = $('#license_pic_detail').find('li').length;
                            var li_length = upload_file_btn.siblings('.license_pic_list').find('li').length+license_pic_detail_length;
                                $('#license_pic_detail_show').show();
                                if(li_length>1){
                                    alert('最多上传2个图片！');
                                }else{
                                    var image_url = upload_file_btn.siblings('.input-image').val();
                                    console.log(image_url)
                                    if(li_length==0){
                                        upload_file_btn.siblings('.input-image').val(url);
                                    }else{
                                        upload_file_btn.siblings('.input-image').val(image_url+';'+url);
                                    }
                                    var html='<li class="upload_pic_li1" style="float:left;list-style:none;"><img src="'+url+'" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del_license(this,'+(li_length+1)+')">[删除]</a></li>';
                                    upload_file_btn.siblings('.license_pic_list').append(html)
                                    editor.hideDialog();
                                }

                        }
                    });
                });
            });

            $('.J_selectImage2').click(function(){
                var upload_file_btn = $(this);
                editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=recruit";
                editor.loadPlugin('image', function(){
                    editor.plugin.imageDialog({
                        showRemote : false,
                        clickFn : function(url, title, width, height, border, align) {
                            var company_pic_detail_length = $('#company_pic_detail').find('li').length;
                            var li_length = upload_file_btn.siblings('.company_pic_list').find('li').length+company_pic_detail_length;
                                $('#company_pic_detail_show').show();
                                if(li_length>5){
                                    alert('最多上传6个图片！');
                                }else{
                                    var image_url = upload_file_btn.siblings('.input-image').val();
                                    console.log(image_url)
                                    if(li_length==0){
                                        upload_file_btn.siblings('.input-image').val(url);
                                    }else{
                                        upload_file_btn.siblings('.input-image').val(image_url+';'+url);
                                    }
                                    var html='<li class="upload_pic_li2" style="float:left;list-style:none;"><img src="'+url+'" style="width:90px;height:50px;margin-right:5px;"><br><a href="javascript:void(0)" onclick="del_company(this,'+(li_length+1)+')">[删除]</a></li>';
                                    upload_file_btn.siblings('.company_pic_list').append(html)
                                    editor.hideDialog();
                                }

                        }
                    });
                });
            });
		});
		function del_license(obj,id){
			var image_list = $(obj).parents('.license_pic_list').siblings('.input-image').val();
			image_list =  image_list.split(';')
			
			
			var tmp_img_list= 
			image_list.splice(id-1,1);
			tmp_img_list = image_list.join(';');
			$(obj).parents('.license_pic_list').siblings('.input-image').val(tmp_img_list)
			
			console.log(tmp_img_list);
			$(obj).closest('.upload_pic_li1').remove();
		}
		function del_company(obj,id){
			var image_list = $(obj).parents('.company_pic_list').siblings('.input-image').val();
			image_list =  image_list.split(';')
			
			
			var tmp_img_list= 
			image_list.splice(id-1,1);
			tmp_img_list = image_list.join(';');
			$(obj).parents('.company_pic_list').siblings('.input-image').val(tmp_img_list)
			
			console.log(tmp_img_list);
			$(obj).closest('.upload_pic_li2').remove();
		}
	</script>
<include file="Public:footer"/>