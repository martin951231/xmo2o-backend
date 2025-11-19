<include file="Public:header"/>
<div class="mainbox">
	<include file="nav"/>
    <form method="post" action="{pigcms{:U('System/Sms/storestaffApp')}">
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="" >
            <tbody>
			<tr>
				<th width="160">店员语音播报次数</th>
                <td>
					<input type="text" class="input-text" name="storestaff_app_voice_time" id="storestaff_app_voice_time" value="{pigcms{$config.storestaff_app_voice_time|default='6'}" size="15" validate="required:true" tips="收到语音通知时的语音播放次数"/>
					<span>（只能设置1-10）收到订单语音通知时的语音播放次数。数量越大，播放时间越久。店员打开APP查看通知，会直接停止播放，建议设置6次。</span>
                </td>
			</tr>
			<tr>
				<th width="160"></th>
                <td>
                </td>
			</tr>
			<tr>
                <th width="160">IOS包名：</th>
                <td>
                   <input type="text" class="input-text" name="storestaff_ios_package_name" id="storestaff_ios_package_name" value="{pigcms{$storestaff_ios_package_name}" size="60" validate="required:true" tips="请填写当前IOS包名："/>
                </td>
            </tr>
            <tr>
                <th width="160">IOS版本名称：</th>
                <td>
                    <input type="text" class="input-text" name="storestaff_ios_version" id="storestaff_ios_version" value="{pigcms{$storestaff_ios_version}" size="60" validate="required:true" tips="请填写当前IOS版本名称"/>
					<span>版本名称，x.x.xx 格式</span>
                    <if condition="$pack_return['storestaff_ios_appleid']">
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="get_appstore_version" class="btn btn-sm btn-success" style="background:#87b87f!important;border-color:#87b87f;color:#fff;cursor: pointer;">同步appstore版本和下载链接</a>
					</if>
                </td>
            </tr>
            <tr style="display:none;">
                <th width="160">IOS版本号：</th>
                <td>
                    <input type="text" class="input-text" name="storestaff_ios_version_code" id="storestaff_ios_version_code" value="{pigcms{$storestaff_ios_version_code}" size="60" validate="required:true" tips="请填写当前IOS版本号"/>
					<span>版本号，数字 格式</span>
                </td>
            </tr>
			<tr>
                <th width="160">IOS下载地址：</th>
                <td>
                    <input type="text" class="input-text" name="storestaff_ios_download_url" id="storestaff_ios_download_url" value="{pigcms{$storestaff_ios_download_url}" size="60" validate="required:true" tips="ios下载地址">
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{pigcms{:U('mobileAppUpdate', ['type'=>'ios_storestaff'])}" class="btn btn-sm btn-success" target="_blank" style="background:#87b87f!important;border-color:#87b87f;color:#fff;">有新版本（{pigcms{$new_version.ios_version_name}），去升级</a>
				</td>
            </tr>
			<tr>
				<th width="160"></th>
                <td>
                </td>
			</tr>
			<tr>
				<th width="160"></th>
                <td>
                </td>
			</tr>
			<tr>
                <th width="160">Android下载地址：</th>
                <td>
                    <input type="text" class="input-text" name="android_download_url" id="storestaff_android_download_url" value="{pigcms{$staff_android_url}" size="60" validate="required:true" tips="Android下载地址">
					<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传APP</a> 
                </td>
            </tr>
			<tr>
                <th width="160">Android包名：</th>
                <td>
                   <input type="text" class="input-text" name="storestaff_android_package_name" id="storestaff_android_package_name" value="{pigcms{$storestaff_android_package_name}" size="60" validate="required:true" tips="请填写当前Android包名：" />
                </td>
            </tr>
            <tr>
                <th width="160">Android版本名称：</th>
                <td>
                    <input type="text" class="input-text" name="android_version" id="storestaff_android_version" value="{pigcms{$staff_android_v}" size="60" validate="required:true" tips="请填写当前Android版本名称" readonly="readonly"/>
					<span>版本名称，x.x.xx 格式</span>
                </td>
            </tr>
            <tr>
                <th width="160">Android版本号：</th>
                <td>
                    <input type="text" class="input-text" name="android_version_code" id="storestaff_android_version_code" value="{pigcms{$staff_android_vcode}" size="60" validate="required:true" tips="请填写当前Android版本号" readonly="readonly"/>
					<span>版本号，数字 格式</span>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{pigcms{:U('mobileAppUpdate',['type'=>'android_storestaff'])}" class="btn btn-sm btn-success" target="_blank" style="background:#87b87f!important;border-color:#87b87f;color:#fff;">有新版本，{pigcms{$new_version.android_version_name}（{pigcms{$new_version.android_version_code}），去升级</a>
                </td>
            </tr>
            <tr>
                <th width="160">ICP备案号</th>
                <td>
                   <input type="text" class="input-text" name="storestaff_icp" id="storestaff_icp" value="{pigcms{$storestaff_icp}" size="60" tips="ICP备案号" />
                   <span>可不填写。放置于大陆的服务器，需要网站备案。</span>
                </td>
            </tr>
            <tr>
                <th width="160">Android版本描述：</th>
                <td><textarea name="android_version_desc" style="width:480px;height:160px;" id="storestaff_android_version_desc">{pigcms{$staff_android_vdesc}</textarea></td>
            </tr>
            <tr>
                <th width="160">隐私政策：</th>
                <td><textarea name="staff_privacy_policy" style="width:480px;height:300px;" id="staff_privacy_policy">{pigcms{:htmlspecialchars_decode($staff_privacy_policy,ENT_QUOTES)}</textarea></td>
            </tr>
            <tr>
                <th width="160">用户协议：</th>
                <td><textarea name="staff_user_agreement" style="width:480px;height:300px;" id="staff_user_agreement">{pigcms{:htmlspecialchars_decode($staff_user_agreement,ENT_QUOTES)}</textarea></td>
            </tr>
            </tbody>
        </table>
        <div class="btn" style="margin-top:20px;">
            <input type="submit" value="提交" class="button" />
            <input type="reset" value="取消" class="button" />
        </div>
		<input type="hidden" name="uploadApk" id="uploadApk" value="0"/>
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
	input[readonly]{
		background:#EFEFEF;
	}
</style>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
	KindEditor.ready(function(K){
        $('#get_appstore_version').click(function(){
			$.post("{pigcms{:U('ios_appstore_version',['appid' => $pack_return['storestaff_ios_appleid']])}", function(result){
				$('#storestaff_ios_version').val(result.info.version);
				$('#storestaff_ios_download_url').val(result.info.down_url);
                $('#storestaff_ios_package_name').val(result.info.bundleId);
			});
		});

		var site_url = "{pigcms{$config.site_url}";
		var editor = K.editor({
			allowFileManager : true
		});
		$('.J_selectImage').click(function(){
			var upload_file_btn = $(this);
			editor.uploadJson = "{pigcms{:U('Config/ajax_upload_apk')}&name="+upload_file_btn.siblings('.input-file').attr('name');
			editor.loadPlugin('insertfile', function(){
				editor.plugin.fileDialog({
					showRemote : false,
					clickFn : function(url, title, width, height, border, align) {
						if(url.indexOf('/') === 0){
							url = site_url + url;
						}
						$.post("{pigcms{:U('getAppInfo')}",{apk:url},function(res){
							console.log(res);
							if(typeof res == 'object'){
								if(res.status == 1){
									$('#uploadApk').val(1);
									$('#storestaff_android_package_name').val(res.info.apkPackage);
									$('#storestaff_android_version').val(res.info.apkVersionName);
									$('#storestaff_android_version_code').val(res.info.apkVersionCode);
									upload_file_btn.siblings('.input-text').val(url);
									editor.hideDialog();
								}else{
									alert(res.info);
									editor.hideDialog();
								}
							}else{
								alert('未知错误');
							}
						});
					}
				});
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',$('#Main_content',parent.document).scrollTop()+((screen.height-$('.ke-dialog').height())/2)+'px');
			},200);
		});
        
        window.editor = K.create('#staff_privacy_policy',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=app/privacy_policy"});
        window.editor2 = K.create('#staff_user_agreement',{pasteType : 1,uploadJson : "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=app/user_agreement"});
	});
</script>
<include file="Public:footer"/>