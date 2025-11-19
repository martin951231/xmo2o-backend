<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
		<ul>
			<a href="javascript:;" class="on">店员APP Android 打包资料填写</a>
		</ul>
	</div>
	<div class="page_tips" style="background-color:#2681f3;color:white;">
		自动打包功能公测中，打出来的包请自行严格测试后再上传到后台升级对外开放使用。若无法正常打包或打包出错，请联系在线客服进行排查解决。
	</div>
	<if condition="$autoPackInfo">
		<div class="page_tips">
			<if condition="$autoPackInfo['err_code'] eq 0">
				{pigcms{$autoPackInfo.err_msg}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{pigcms{$autoPackInfo.download_url}" target="_blank" style="color:blue;font-weight:bold;">点击下载</a>
			<else/>
				{pigcms{$autoPackInfo.err_msg}
			</if>
		</div>
	</if>
    <form method="post" action="{pigcms{:U('mobileAppUpdateSave')}" id="myform">
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%">
            <tbody>
				<tr>
					<th width="160">APP 名称：</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_name" id="storestaff_android_name" value="{pigcms{$storestaff_android_name}" size="80"  validate="required:true" tips=""/>
					   <span>6个中文字以内。安卓和IOS的名称可以一样，但是安卓（或IOS）不能和自己APP名称一样，例如安卓平台和安卓商家不能一致 ，安卓平台可以和IOS平台一致</span>
					</td>
				</tr>
				<tr>
					<th width="160">Android 包名：</th>
					<td>
						<input type="text" class="input-text" name="storestaff_android_bundleid" id="storestaff_android_bundleid" value="{pigcms{$storestaff_android_bundleid}" size="80" validate="required:true"/>
						<span>APP包名，全部用英文 不支持大写，需要用小写, 别用数字示例：以英文结合小写：如com.lifepass.pigcms（保证该包名的独一无二性），包名不能包含 android 等安卓系统保留关键字。</span>
					</td>
				</tr>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">友盟 Appkey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_ument_open_android_appkey" id="storestaff_ument_open_android_appkey" value="{pigcms{$storestaff_ument_open_android_appkey}" size="80" validate="required:true"/>
					   <span>在友盟平台中获取</span>
					</td>
				</tr>
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">极光 Appkey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_jiguang_open_appkey" id="storestaff_jiguang_open_appkey" value="{pigcms{$storestaff_jiguang_open_appkey}" size="80" validate="required:true"/>
					   <span>在极光平台中获取</span>
					</td>
				</tr>
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">百度地图 Key</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_baidu_map_android_key" id="storestaff_baidu_map_android_key" value="{pigcms{$storestaff_baidu_map_android_key}" size="80" <if condition="$storestaff_android_md5">validate="required:true"</if>/>
					   <span>在百度地图开放平台中获取</span>
					</td>
				</tr>
				
				
				<if condition="isset($config['map_config'])">
					<tr>
						<th width="160"></th>
						<td></td>
					</tr>
					<tr>
						<th width="160">谷歌地图 Key</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_google_map_key" id="storestaff_google_map_key" value="{pigcms{$storestaff_google_map_key}" size="80"/>
						   <span>在谷歌地图开放平台中获取，可不填</span>
						</td>
					</tr>
				</if>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">消息推送方式</th>
					<td>
						<select id="storestaff_msg_push_type" name="storestaff_msg_push_type">
							<option value="">极光推送</option>
							<option value="umeng" <if condition="$storestaff_msg_push_type eq 'umeng'">selected="selected"</if>>友盟推送</option>
						</select>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】友盟推送 Message Secret</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_message_secret" id="storestaff_msg_umeng_message_secret" value="{pigcms{$storestaff_msg_umeng_message_secret}" size="80"/>
					   <span>在华为推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】华为推送 appId</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_huawei_appid" id="storestaff_msg_umeng_huawei_appid" value="{pigcms{$storestaff_msg_umeng_huawei_appid}" size="80"/>
					   <span>在华为推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】OPPO推送 appKey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_oppo_appkey" id="storestaff_msg_umeng_oppo_appkey" value="{pigcms{$storestaff_msg_umeng_oppo_appkey}" size="80"/>
					   <span>在OPPO推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】OPPO推送 appSecret</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_oppo_appsecret" id="storestaff_msg_umeng_oppo_appsecret" value="{pigcms{$storestaff_msg_umeng_oppo_appsecret}" size="80"/>
					   <span>在OPPO推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】VIVO推送 appid</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_vivo_appid" id="storestaff_msg_umeng_vivo_appid" value="{pigcms{$storestaff_msg_umeng_vivo_appid}" size="80"/>
					   <span>在VIVO推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】VIVO推送 appKey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_vivo_appkey" id="storestaff_msg_umeng_vivo_appkey" value="{pigcms{$storestaff_msg_umeng_vivo_appkey}" size="80"/>
					   <span>在VIVO推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】小米推送 appid</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_xiaomi_appid" id="storestaff_msg_umeng_xiaomi_appid" value="{pigcms{$storestaff_msg_umeng_xiaomi_appid}" size="80"/>
					   <span>在小米推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】小米推送 appKey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_xiaomi_appkey" id="storestaff_msg_umeng_xiaomi_appkey" value="{pigcms{$storestaff_msg_umeng_xiaomi_appkey}" size="80"/>
					   <span>在小米推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】魅族推送 appid</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_meizu_appid" id="storestaff_msg_umeng_meizu_appid" value="{pigcms{$storestaff_msg_umeng_meizu_appid}" size="80"/>
					   <span>在魅族推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr class="umeng_msg_row">
					<th width="160">【友盟】魅族推送 appkey</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_msg_umeng_meizu_appkey" id="storestaff_msg_umeng_meizu_appkey" value="{pigcms{$storestaff_msg_umeng_meizu_appkey}" size="80"/>
					   <span>在魅族推送开放平台获取，可不填</span>
					</td>
				</tr>
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				
				<tr>
					<th width="160">APP LOGO</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_logo" id="storestaff_android_logo" value="{pigcms{$storestaff_android_logo}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1024" data-height="1024" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1024*1024的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1500*2668）</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_start_pic_1500_2668" id="storestaff_android_start_pic_1500_2668" value="{pigcms{$storestaff_android_start_pic_1500_2668}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1500" data-height="2668" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1500*2668的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1125*2001）</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_start_pic_1125_2001" id="storestaff_android_start_pic_1125_2001" value="{pigcms{$storestaff_android_start_pic_1125_2001}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1125" data-height="2001" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1125*2001的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（750*1334）</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_start_pic_750_1334" id="storestaff_android_start_pic_750_1334" value="{pigcms{$storestaff_android_start_pic_750_1334}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="750" data-height="1334" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>750*1334的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（563*1001）</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_start_pic_563_1001" id="storestaff_android_start_pic_563_1001" value="{pigcms{$storestaff_android_start_pic_563_1001}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="563" data-height="1001" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>563*1001的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（375*667）</th>
					<td>
					   <input type="text" class="input-text" name="storestaff_android_start_pic_375_667" id="storestaff_android_start_pic_375_667" value="{pigcms{$storestaff_android_start_pic_375_667}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="375" data-height="667" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>375*667的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<if condition="get_client_ip() eq '60.173.195.178' OR get_client_ip() eq '127.0.0.1' OR get_client_ip() eq '36.7.87.202'">
					<tr>
						<th width="160">APP md5</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_md5" id="storestaff_android_md5" value="{pigcms{$storestaff_android_md5}" size="80"/>
						   <span> APP md5 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha1</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_sha1" id="storestaff_android_sha1" value="{pigcms{$storestaff_android_sha1}" size="80"/>
						   <span> APP sha1 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha256</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_sha256" id="storestaff_android_sha256" value="{pigcms{$storestaff_android_sha256}" size="80"/>
						   <span> APP sha256 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP storepass</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_storepass" id="storestaff_android_storepass" value="{pigcms{$storestaff_android_storepass}" size="80"/>
						   <span> APP storepass 值，可留空 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP alias</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_alias" id="storestaff_android_alias" value="{pigcms{$storestaff_android_alias}" size="80"/>
						   <span> APP alias 值，可留空 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP 密钥文件</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_keystore" id="storestaff_android_keystore" value="{pigcms{$storestaff_android_keystore}" readonly="readonly" size="80"/>
						   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectFile"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传密钥文件</a>&nbsp;&nbsp;&nbsp;
						   <span>jks文件或 keystore</span>
						</td>
					</tr>
				<else/>
					<tr>
						<th width="160">APP md5</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_md5" id="storestaff_android_md5" value="{pigcms{$storestaff_android_md5}" readonly="readonly" size="80"/>
						   <span> APP md5 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha1</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_sha1" id="storestaff_android_sha1" value="{pigcms{$storestaff_android_sha1}" readonly="readonly" size="80"/>
						   <span> APP sha1 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha256</th>
						<td>
						   <input type="text" class="input-text" name="storestaff_android_sha256" id="storestaff_android_sha256" value="{pigcms{$storestaff_android_sha256}" readonly="readonly" size="80"/>
						   <span> APP sha256 值，首次打包会自动生成 </span>
						</td>
					</tr>
				</if>
				
				
				<if condition="get_client_ip() eq '60.173.195.178' OR get_client_ip() eq '127.0.0.1' OR get_client_ip() eq '36.7.87.202'">
					<tr>
						<th width="160">技术确认可打包</th>
						<td>
							<span class="cb-enable"><label class="cb-enable <if condition="$storestaff_android_package_check">selected</if>"><span>确定</span><input type="radio" name="storestaff_android_package_check" value="1" <if condition="$storestaff_android_package_check eq 1">checked="checked"</if>/></label></span>
							<span class="cb-disable"><label class="cb-disable <if condition="!$storestaff_android_package_check">selected</if>"><span>暂未</span><input type="radio" name="storestaff_android_package_check" value="0" <if condition="!$storestaff_android_package_check">checked="checked"</if>/></label></span>
						</td>
					</tr>
				</if>
            </tbody>
        </table>
        <div class="btn" style="margin-top:20px;">
            <input type="submit" value="保存资料" class="button" />
			<if condition="$storestaff_android_package_check">
				<input type="button" value="提交打包" class="button" onclick="window.open('{pigcms{:U('mobileAppUpdatePack',['type'=>'android_storestaff'])}')"/>
			</if>
        </div>
    </form>
</div>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">

<style>
    .table_form{border:1px solid #ddd;}
    .tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
    .tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
    .tab_ul>li>a {
        position: relative;
        display: block;
        padding: 10px 15px;
        margin-right: 2px;
        line-height: 1.42857143;
        border: 1px solid transparent;
        border-radius: 4px 4px 0 0;
        padding: 7px 12px 8px;
        min-width: 100px;
        text-align: center;
    }
    .tab_ul>li>a, .tab_ul>li>a:focus {
        border-radius: 0!important;
        border-color: #c5d0dc;
        background-color: #F9F9F9;
        color: #999;
        margin-right: -1px;
        line-height: 18px;
        position: relative;
    }
    .tab_ul>li>a:focus, .tab_ul>li>a:hover {
        text-decoration: none;
        background-color: #eee;
    }
    .tab_ul>li>a:hover {
        border-color: #eee #eee #ddd;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #555;
        background-color: #fff;
        border: 1px solid #ddd;
        border-bottom-color: transparent;
        cursor: default;
    }
    .tab_ul>li>a:hover {
        background-color: #FFF;
        color: #4c8fbd;
        border-color: #c5d0dc;
    }
    .tab_ul>li:first-child>a {
        margin-left: 0;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #576373;
        border-color: #c5d0dc #c5d0dc transparent;
        border-top: 2px solid #4c8fbd;
        background-color: #FFF;
        z-index: 1;
        line-height: 18px;
        margin-top: -1px;
        box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #555;
        background-color: #fff;
        border: 1px solid #ddd;
        border-bottom-color: transparent;
        cursor: default;
    }
    .tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
        color: #576373;
        border-color: #c5d0dc #c5d0dc transparent;
        border-top: 2px solid #4c8fbd;
        background-color: #FFF;
        z-index: 1;
        line-height: 18px;
        margin-top: -1px;
        box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
    }
    .tab_ul:before,.tab_ul:after{
        content: " ";
        display: table;
    }
    .tab_ul:after{
        clear: both;
    }
	.ke-dialog-default{
		top:153px;
	}
	input[readonly]{
		background:#EFEFEF;
	}
</style>

<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">

	$('#storestaff_msg_push_type').change(function(){
		if($(this).val() == 'umeng'){
			$('.umeng_msg_row').show();
		}else{
			$('.umeng_msg_row').hide();
		}
	}).trigger('change');
	
	KindEditor.ready(function(K){
		var site_url = "{pigcms{$config.site_url}";
		var editor = K.editor({
			allowFileManager : true
		});
		$('.J_selectImage').click(function(){
			var upload_file_btn = $(this);
			editor.uploadJson = "{pigcms{:U('Sms/ajax_upload_pic')}&width=" + upload_file_btn.data('width') + "&height=" + upload_file_btn.data('height')+'&suffix=png';
			editor.loadPlugin('image', function(){
				editor.plugin.imageDialog({
					showRemote : false,
					clickFn : function(url, title, width, height, border, align) {
						if(url.indexOf('/') === 0){
							url = site_url + url;
						}
						upload_file_btn.siblings('.input-text').val(url);
						editor.hideDialog();
					}
				});
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
			},200);
		});
		
		$('.J_selectFile').click(function(){
			var upload_file_btn = $(this);
			editor.uploadJson = "{pigcms{:U('Sms/ajax_upload_zip')}";
			editor.loadPlugin('insertfile', function(){
				editor.plugin.fileDialog({
					showRemote : false,
					clickFn : function(url, title, width, height, border, align) {
						if(url.indexOf('/') === 0){
							url = site_url + url;
						}
						
						upload_file_btn.siblings('.input-text').val(url);
						editor.hideDialog();
					}
				});
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
			},200);
		});

        window.editor = K.create('#privacy_policy',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=app/privacy_policy"});
	});
</script>
<include file="Public:footer"/>