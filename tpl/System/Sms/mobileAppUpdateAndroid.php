<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
		<ul>
			<a href="javascript:;" class="on">平台APP Android 打包资料填写</a>
		</ul>
	</div>
    <form method="post" action="{pigcms{:U('mobileAppUpdateSave')}">
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="myform">
            <tbody>
				<tr>
					<th width="160">APP 名称：</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_name" id="plat_android_name" value="{pigcms{$plat_android_name}" size="60"  validate="required:true" tips=""/>
					   <span>6个中文字以内。安卓和IOS的名称可以一样，但是安卓（或IOS）不能和自己APP名称一样，例如安卓平台和安卓商家不能一致 ，安卓平台可以和IOS平台一致</span>
					</td>
				</tr>
				<tr>
					<th width="160">Android 包名：</th>
					<td>
						<input type="text" class="input-text" name="plat_android_bundleid" id="plat_android_bundleid" value="{pigcms{$plat_android_bundleid}" size="60" validate="required:true"/>
						<span>APP包名，全部用英文 不支持大写，需要用小写, 别用数字示例：以英文结合小写：如com.lifepass.pigcms（保证该包名的独一无二性）</span>
					</td>
				</tr>
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">微博应用 Appid</th>
					<td>
					   <input type="text" class="input-text" name="plat_weibo_open_appid" id="plat_weibo_open_appid" value="{pigcms{$plat_weibo_open_appid}" size="60"/>
					   <span>在微博开放平台中获取，可不填</span>
					</td>
				</tr>
				<tr>
					<th width="160">微博应用 Appkey</th>
					<td>
					   <input type="text" class="input-text" name="plat_weibo_open_appkey" id="plat_weibo_open_appkey" value="{pigcms{$plat_weibo_open_appkey}" size="60"/>
					   <span>在微博开放平台中获取，可不填</span>
					</td>
				</tr>
				<tr>
					<th width="160">微博应用 AppSecret</th>
					<td>
					   <input type="text" class="input-text" name="plat_weibo_open_appsecret" id="plat_weibo_open_appsecret" value="{pigcms{$plat_weibo_open_appsecret}" size="60"/>
					   <span>在微博开放平台中获取，可不填</span>
					</td>
				</tr>
				
				<if condition="isset($config['map_config'])">
					<tr>
						<th width="160"></th>
						<td></td>
					</tr>
					<tr>
						<th width="160">FaceBook应用 Appid</th>
						<td>
						   <input type="text" class="input-text" name="plat_facebook_open_appid" id="plat_facebook_open_appid" value="{pigcms{$plat_facebook_open_appid}" size="60"/>
						   <span>在FaceBook开放平台中获取，可不填</span>
						</td>
					</tr>
					<tr>
						<th width="160">FaceBook应用 Appkey</th>
						<td>
						   <input type="text" class="input-text" name="plat_facebook_open_appkey" id="plat_facebook_open_appkey" value="{pigcms{$plat_facebook_open_appkey}" size="60"/>
						   <span>在FaceBook开放平台中获取，可不填</span>
						</td>
					</tr>
				</if>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">友盟 Appkey</th>
					<td>
					   <input type="text" class="input-text" name="plat_ument_open_android_appkey" id="plat_ument_open_android_appkey" value="{pigcms{$plat_ument_open_android_appkey}" size="60" validate="required:true"/>
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
					   <input type="text" class="input-text" name="plat_jiguang_open_appkey" id="plat_jiguang_open_appkey" value="{pigcms{$plat_jiguang_open_appkey}" size="60" validate="required:true"/>
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
					   <input type="text" class="input-text" name="plat_baidu_map_android_key" id="plat_baidu_map_android_key" value="{pigcms{$plat_baidu_map_android_key}" size="60" validate="required:true"/>
					   <span>在百度地图开放平台中获取</span>
					</td>
				</tr>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">百度语音 appId</th>
					<td>
					   <input type="text" class="input-text" name="plat_baidu_voice_android_appid" id="plat_baidu_voice_android_appid" value="{pigcms{$plat_baidu_voice_android_appid}" size="60" validate="required:true"/>
					   <span>在百度开放平台中获取</span>
					</td>
				</tr>
				<tr>
					<th width="160">百度语音 appKey</th>
					<td>
					   <input type="text" class="input-text" name="plat_baidu_voice_android_appkey" id="plat_baidu_voice_android_appkey" value="{pigcms{$plat_baidu_voice_android_appkey}" size="60" validate="required:true"/>
					   <span>在百度开放平台中获取</span>
					</td>
				</tr>
				<tr>
					<th width="160">百度语音 secretKey</th>
					<td>
					   <input type="text" class="input-text" name="plat_baidu_voice_android_secretkey" id="plat_baidu_voice_android_secretkey" value="{pigcms{$plat_baidu_voice_android_secretkey}" size="60" validate="required:true"/>
					   <span>在百度开放平台中获取</span>
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
						   <input type="text" class="input-text" name="plat_google_map_key" id="plat_google_map_key" value="{pigcms{$plat_google_map_key}" size="60"/>
						   <span>在谷歌地图开放平台中获取，可不填</span>
						</td>
					</tr>
				</if>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">APP LOGO</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_logo" id="plat_android_logo" value="{pigcms{$plat_android_logo}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1024" data-height="1024" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1024*1024的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1500*2668）</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_start_pic_1500_2668" id="plat_android_start_pic_1500_2668" value="{pigcms{$plat_android_start_pic_1500_2668}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1500" data-height="2668" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1500*2668的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1125*2001）</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_start_pic_1125_2001" id="plat_android_start_pic_1125_2001" value="{pigcms{$plat_android_start_pic_1125_2001}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1125" data-height="2001" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1125*2001的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（750*1334）</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_start_pic_750_1334" id="plat_android_start_pic_750_1334" value="{pigcms{$plat_android_start_pic_750_1334}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="750" data-height="1334" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>750*1334的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（563*1001）</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_start_pic_563_1001" id="plat_android_start_pic_563_1001" value="{pigcms{$plat_android_start_pic_563_1001}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="563" data-height="1001" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>563*1001的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（375*667）</th>
					<td>
					   <input type="text" class="input-text" name="plat_android_start_pic_375_667" id="plat_android_start_pic_375_667" value="{pigcms{$plat_android_start_pic_375_667}" size="60" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="375" data-height="667" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>375*667的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<if condition="get_client_ip() eq '60.173.195.178' OR get_client_ip() eq '127.0.0.1'">
					<tr>
						<th width="160">APP md5</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_md5" id="plat_android_md5" value="{pigcms{$plat_android_md5}" size="60"/>
						   <span> APP md5 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha1</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_sha1" id="plat_android_sha1" value="{pigcms{$plat_android_sha1}" size="60"/>
						   <span> APP sha1 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha256</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_sha256" id="plat_android_sha256" value="{pigcms{$plat_android_sha256}" size="60"/>
						   <span> APP sha256 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP storepass</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_storepass" id="plat_android_storepass" value="{pigcms{$plat_android_storepass}" size="60"/>
						   <span> APP storepass 值，可留空 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP alias</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_alias" id="plat_android_alias" value="{pigcms{$plat_android_alias}" size="60"/>
						   <span> APP alias 值，可留空 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP 密钥文件</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_keystore" id="plat_android_keystore" value="{pigcms{$plat_android_keystore}" readonly="readonly" size="60"/>
						   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectFile"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传密钥文件</a>&nbsp;&nbsp;&nbsp;
						   <span>jks文件或 keystore</span>
						</td>
					</tr>
				<else/>
					<tr>
						<th width="160">APP md5</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_md5" id="plat_android_md5" value="{pigcms{$plat_android_md5}" readonly="readonly" size="60"/>
						   <span> APP md5 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha1</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_sha1" id="plat_android_sha1" value="{pigcms{$plat_android_sha1}" readonly="readonly" size="60"/>
						   <span> APP sha1 值，首次打包会自动生成 </span>
						</td>
					</tr>
					<tr>
						<th width="160">APP sha256</th>
						<td>
						   <input type="text" class="input-text" name="plat_android_sha256" id="plat_android_sha256" value="{pigcms{$plat_android_sha256}" readonly="readonly" size="60"/>
						   <span> APP sha256 值，首次打包会自动生成 </span>
						</td>
					</tr>
				</if>
				
				
				<if condition="get_client_ip() eq '60.173.195.178' OR get_client_ip() eq '127.0.0.1'">
					<tr>
						<th width="160">技术确认可打包</th>
						<td>
							<span class="cb-enable"><label class="cb-enable <if condition="$plat_android_package_check">selected</if>"><span>确定</span><input type="radio" name="plat_android_package_check" value="1" <if condition="$plat_android_package_check eq 1">checked="checked"</if>/></label></span>
							<span class="cb-disable"><label class="cb-disable <if condition="!$plat_android_package_check">selected</if>"><span>暂未</span><input type="radio" name="plat_android_package_check" value="0" <if condition="!$plat_android_package_check">checked="checked"</if>/></label></span>
						</td>
					</tr>
				</if>
            </tbody>
        </table>
        <div class="btn" style="margin-top:20px;">
            <input type="submit" value="保存资料" class="button" />
			<if condition="$plat_android_package_check">
				<input type="button" value="提交打包" class="button" onclick="window.open('{pigcms{:U('mobileAppUpdatePack',['type'=>'android_plat'])}')"/>
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
	KindEditor.ready(function(K){
		var site_url = "{pigcms{$config.site_url}";
		var editor = K.editor({
			allowFileManager : true
		});
		$('.J_selectImage').click(function(){
			var upload_file_btn = $(this);
			editor.uploadJson = "{pigcms{:U('Sms/ajax_upload_pic')}&width=" + upload_file_btn.data('width') + "&height=" + upload_file_btn.data('height');
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