<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
		<ul>
			<a href="javascript:;" class="on">社区管理APP IOS 打包资料填写</a>
		</ul>
	</div>
	<div class="page_tips" style="background-color:#2681f3;color:white;">
		自动打包功能公测中，打出来的包请自行严格测试后再上传到后台升级对外开放使用。若无法正常打包或打包出错，请联系在线客服进行排查解决。
	</div>
	<if condition="$autoPackInfo">
		<div class="page_tips">
			<if condition="$autoPackInfo['err_code'] eq 0">
			{pigcms{$autoPackInfo.err_msg}
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
					   <input type="text" class="input-text" name="village_manager_ios_name" id="village_manager_ios_name" value="{pigcms{$village_manager_ios_name}" size="80"  validate="required:true" tips=""/>
					   <span>6个中文字以内。安卓和IOS的名称可以一样，但是安卓（或IOS）不能和自己APP名称一样，例如安卓平台和安卓商家不能一致 ，安卓平台可以和IOS平台一致</span>
					</td>
				</tr>
				<tr>
					<th width="160">IOS 包名：</th>
					<td>
						<input type="text" class="input-text" name="village_manager_ios_bundleid" id="village_manager_ios_bundleid" value="{pigcms{$village_manager_ios_bundleid}" size="80" validate="required:true"/>
						<span>APP包名（bundle ID）全部用英文 不支持大写，需要用小写, 别用数字示例：以英文结合小写：如com.lifepass.pigcms（保证该包名的独一无二性）</span>
					</td>
				</tr>
				<tr>
					<th width="160">IOS Apple ID：</th>
					<td>
						<input type="text" class="input-text" name="village_manager_ios_appleid" id="village_manager_ios_appleid" value="{pigcms{$village_manager_ios_appleid}" size="80" validate="required:true"/>
						<span>每个项目唯一，iTunes connect创建应用时获取</span>
					</td>
				</tr>

				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">友盟 Appkey</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ument_open_appkey" id="village_manager_ument_open_appkey" value="{pigcms{$village_manager_ument_open_appkey}" size="80" validate="required:true"/>
					   <span>在友盟平台中获取</span>
					</td>
				</tr>
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">百度地图 Key</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_baidu_map_key" id="village_manager_baidu_map_key" value="{pigcms{$village_manager_baidu_map_key}" size="80" validate="required:true"/>
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
						   <input type="text" class="input-text" name="village_manager_google_map_key" id="village_manager_google_map_key" value="{pigcms{$village_manager_google_map_key}" size="80"/>
						   <span>在谷歌地图开放平台中获取，可不填</span>
						</td>
					</tr>
				</if>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">Appstore apiIssuer</th>
					<td>
					   <input type="text" class="input-text" name="applestore_apiIssuer" id="applestore_apiIssuer" value="{pigcms{$applestore_apiIssuer}" size="80"/>
					   <span>在apple开发者中心获取</span>
					</td>
				</tr>
				<tr>
					<th width="160">Appstore apiKey</th>
					<td>
					   <input type="text" class="input-text" name="applestore_apiKey" id="applestore_apiKey" value="{pigcms{$applestore_apiKey}" size="80"/>
					   <span>在apple开发者中心获取</span>
					</td>
				</tr>
				<tr>
					<th width="160">Appstore AuthKey</th>
					<td>
					   <input type="text" class="input-text" name="applestore_p8" id="applestore_p8" value="{pigcms{$applestore_p8}" readonly="readonly" size="80" />
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectFile"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传p8文件</a>&nbsp;&nbsp;&nbsp;
					   <span>AuthKey_*.p8 文件，在apple开发者中心获取</span>
					</td>
				</tr>
				<tr>
					<th width="160">Appstore TeamId</th>
					<td>
					   <input type="text" class="input-text" name="plat_applestore_teamId" id="plat_applestore_teamId" value="{pigcms{$plat_applestore_teamId}" size="80"/>
					   <span>在apple开发者中心获取</span>
					</td>
				</tr>
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">蒲公英 apikey</th>
					<td>
					   <input type="text" class="input-text" name="pgy_apikey" id="pgy_apikey" value="{pigcms{$pgy_apikey}" size="80"/>
					   <span>在蒲公英开发者中心获取，可不填。如果填写后，每次打包会尽量尝试自动提交一个测试版本到蒲公英平台</span>
					</td>
				</tr>
				
				
				<tr>
					<th width="160"></th>
					<td></td>
				</tr>
				<tr>
					<th width="160">APP LOGO</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_logo" id="village_manager_ios_logo" value="{pigcms{$village_manager_ios_logo}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1024" data-height="1024" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1024*1024的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1125*2436）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_1125_2436" id="village_manager_ios_start_pic_1125_2436" value="{pigcms{$village_manager_ios_start_pic_1125_2436}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1125" data-height="2436" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1125*2436的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（320*480）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_320_480" id="village_manager_ios_start_pic_320_480" value="{pigcms{$village_manager_ios_start_pic_320_480}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="320" data-height="480" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>320*480的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（640*960）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_640_960" id="village_manager_ios_start_pic_640_960" value="{pigcms{$village_manager_ios_start_pic_640_960}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="640" data-height="960" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>640*960的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（640*1136）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_640_1136" id="village_manager_ios_start_pic_640_1136" value="{pigcms{$village_manager_ios_start_pic_640_1136}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="640" data-height="1136" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>640*1136的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1242*2208）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_1242_2208" id="village_manager_ios_start_pic_1242_2208" value="{pigcms{$village_manager_ios_start_pic_1242_2208}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1242" data-height="2208" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1242*2208的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（750*1334）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_750_1334" id="village_manager_ios_start_pic_750_1334" value="{pigcms{$village_manager_ios_start_pic_750_1334}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="750" data-height="1334" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>750*1334的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（1242*2688）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_1242_2688" id="village_manager_ios_start_pic_1242_2688" value="{pigcms{$village_manager_ios_start_pic_1242_2688}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="1242" data-height="2688" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>1242*2688的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<tr>
					<th width="160">APP 启动图（828*1792）</th>
					<td>
					   <input type="text" class="input-text" name="village_manager_ios_start_pic_828_1792" id="village_manager_ios_start_pic_828_1792" value="{pigcms{$village_manager_ios_start_pic_828_1792}" size="80" readonly="readonly" validate="required:true"/>
					   <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" data-width="828" data-height="1792" style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传图片</a>&nbsp;&nbsp;&nbsp;<span>828*1792的PNG格式图片，不能有透明图片。</span>
					</td>
				</tr>
				<if condition="get_client_ip() eq '60.173.195.178' OR get_client_ip() eq '127.0.0.1' OR get_client_ip() eq '36.7.87.202'">
					<tr>
						<th width="160">技术确认可打包</th>
						<td>
							<span class="cb-enable"><label class="cb-enable <if condition="$village_manager_ios_package_check">selected</if>"><span>确定</span><input type="radio" name="village_manager_ios_package_check" value="1" <if condition="$village_manager_ios_package_check eq 1">checked="checked"</if>/></label></span>
							<span class="cb-disable"><label class="cb-disable <if condition="!$village_manager_ios_package_check">selected</if>"><span>暂未</span><input type="radio" name="village_manager_ios_package_check" value="0" <if condition="!$village_manager_ios_package_check">checked="checked"</if>/></label></span>
						</td>
					</tr>
				</if>
            </tbody>
        </table>
        <div class="btn" style="margin-top:20px;">
            <input type="submit" value="保存资料" class="button" />
			<if condition="$village_manager_ios_package_check">
				<input type="button" value="提交打包" class="button" onclick="window.open('{pigcms{:U('mobileAppUpdatePack',['type'=>'ios_village_manager'])}')"/>
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