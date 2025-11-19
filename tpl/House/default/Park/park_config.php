<include file="Public:header"/>

<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Index/index')}">社区管理</a>
			</li>
			<li class="active">基本信息设置</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">

		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul id="myTab" class="nav nav-tabs">
							<li class="active">
								<a href="#basicinfo" data-toggle="tab">基本设置</a>
							</li>
							<li>
								<a href="#txtstore" data-toggle="tab">月租车设置</a>
							</li>
							<li class="hide_box">
								<a href="#txtpwd" data-toggle="tab">储值车设置</a>
							</li>

                            <li class="hide_box">
								<a href="#bindweixin" data-toggle="tab">停车营销管理</a>
							</li>
						</ul>
					</div>
					<form id="edit_form" method="post" class="form-horizontal" action="{pigcms{:U('Park/park_config_edit')}" enctype="multipart/form-data">
						<div class="tab-content">
							<div class="tab-pane active park_config_box" id="basicinfo">

								<div class="form-group">
									<label class="col-sm-2"><label for="">是否开启智慧停车功能:</label></label>
									<div class="radio">
										<label style="padding-left:0px;"><input  name="park_versions" value="2" type="radio" class="ace changeParkVersions" <if condition="$village_park_config.park_versions eq 2">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
										<label style="padding-left:15px;padding-right:20px;"><input name="park_versions" value="1" type="radio" class="ace changeParkVersions" <if condition="$village_park_config.park_versions eq 1">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
										<span class="form_tips">需要开启后才能使用新的停车管理,没有开启,一切业务照旧</span>
									</div>
								</div>
                               <div class="form-group  park_sys_type_box" <if condition="$village_park_config.park_versions neq 2">style="display:none;"</if>>
                                    <label class="col-sm-2"><label for="">是否展示在用户端车场列表页:</label></label>
                                    <div class="radio">
                                        <label style="padding-left:0px;"><input  name="park_show" value="1" type="radio" class="ace " <if condition="$village_park_config.park_show eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                        <label style="padding-left:15px;padding-right:20px;"><input name="park_show" value="0" type="radio" class="ace " <if condition="$village_park_config.park_show eq 0">checked</if>/><span class="lbl" style="z-index: 1">关闭</span></label>
                                        <span class="form_tips">展示在用户端的车场列表页后，用户可选择车场</span>
                                    </div>
                                </div>
                                <if condition="$park_sys_type">
                                    <div class="form-group park_sys_type_box" <if condition="$village_park_config.park_versions neq 2">style="display:none;"</if>>
                                        <label class="col-sm-2"><label for="park_sys_type">停车设备类型:</label></label>
                                        <select class="col-xs-4 col-sm-4 col-md-2" name="park_sys_type" id="park_sys_type">
                                            <volist name="park_sys_type" id="vo">
                                                <option value="{pigcms{$vo.park_sys_type}" data-show="{pigcms{$vo.park_sys_type_box}" <if condition="$village_park_config['park_sys_type'] eq $vo['park_sys_type']">selected="selected"</if>>{pigcms{$vo['name']}</option>
                                            </volist>
                                        </select>
                                        <span class="form_tips">必选</span>
                                        <span class="form_tips device_type_syn" style="color: #1686ef;">选择开启智慧停车后必须选择停车设备类型</span>
                                    </div>
                                    <div class="d3_park"  <if condition="$village_park_config['park_sys_type_box'] neq '3' "> style="display: none" </if> >
                                            <div class="form-group" >
                                                <label class="col-sm-2"><label for="">停车场登记:</label></label>
                                                <div class="radio">
                                                    <label style="padding-left:0px;"><input  name="register_type" value="1" type="radio" class="ace " <if condition="$village_park_config.register_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">开启</span></label>
                                                    <label style="padding-left:15px;padding-right:20px;"><input name="register_type" value="0" type="radio" class="ace " <if condition="$village_park_config.register_type eq 0">checked</if>/><span class="lbl" style="z-index: 1">禁用</span></label>
                                                    <span class="form_tips">禁用后临时车可以直接入场无需登记</span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label class="col-sm-2"><label for="">临时车免登记时长:</label></label>
                                                <input class="col-sm-2" size="20" name="register_day" value="{pigcms{$village_park_config['register_day']}" type="text" style=""/>（天）
                                                <span class="form_tips">设置临时车登记后多少天之内不用再次登记可以直接入场，默认15天</span>
                                            </div>
                                    </div>
                                    <div class="form-group park_sys_type_set_box" <if condition="$village_park_config.park_sys_type_box neq 1">style="display:none;"</if>>
                                        <label class="col-sm-2"><label for="">厂商编号:</label></label>
                                        <input class="col-sm-4" size="20" name="union_id" value="{pigcms{$village_park_config['union_id']}" type="text" style=""/>
                                        <span class="form_tips">请先前往云平台注册车场之后，对应填写厂商编号</span>
                                    </div>
                                    <div class="form-group park_sys_type_set_box" <if condition="$village_park_config.park_sys_type_box neq 1">style="display:none;"</if>>
                                        <label class="col-sm-2"><label for="">车场编号:</label></label>
                                        <input class="col-sm-4" size="20" name="comid" value="{pigcms{$village_park_config['comid']}" type="text" style=""/>
                                        <span class="form_tips">请先前往云平台注册车场之后，对应填写车场编号</span>
                                    </div>
                                    <div class="form-group park_sys_type_set_box" <if condition="$village_park_config.park_sys_type_box neq 1">style="display:none;"</if>>
                                        <label class="col-sm-2"><label for="">车场秘钥:</label></label>
                                        <input class="col-sm-4" size="20" name="ckey" value="{pigcms{$village_park_config['ckey']}" type="text" style=""/>
                                        <span class="form_tips">请先前往云平台注册车场之后，对应填写车场秘钥</span>
                                    </div>

                                <!--todo  D5停车类型-->
                                <div class="d5_park" <if condition="($village_park_config.park_versions neq 2) or ($village_park_config.park_sys_type neq 'D5')">style="display:none;"</if>>
                                    <div class="form-group " >
                                        <label class="col-sm-2"><label for="">设备请求url:</label></label>
                                        <input class="col-sm-4" size="20" name="d5_url" value="{pigcms{$village_park_config['d5_url']}" type="text" placeholder="请输入设备请求url"/>
                                        <span class="form_tips">必填 D5停车场系统的请求连接，如 http://***，后面不用带/</span>
                                    </div>
                                    <div class="form-group " >
                                        <label class="col-sm-2"><label for="">设备账号名:</label></label>
                                        <input class="col-sm-4" size="20" name="d5_name" value="{pigcms{$village_park_config['d5_name']}" type="text" placeholder="请输入设备账号名"/>
                                        <span class="form_tips">必填 D5停车场系统的一个登录用户名，如 system</span>
                                    </div>
                                    <div class="form-group " >
                                        <label class="col-sm-2"><label for="">设备账号密码:</label></label>
                                        <input class="col-sm-4" size="20" name="d5_pass" value="{pigcms{$village_park_config['d5_pass']}" type="text" placeholder="请输入设备账号密码"/>
                                        <span class="form_tips">必填 D5停车场系统的一个登录密码,md5 加密</span>
                                    </div>
                                </div>
                                <!--todo  D6停车类型-->
                                <div class="d6_park" <if condition="($village_park_config.park_versions neq 2) or ($village_park_config.park_sys_type neq 'D6')">style="display:none;"</if>>
                                <div class="form-group " >
                                    <label class="col-sm-2"><label for="">车场ID:</label></label>
                                    <input name="d6_comid" value="{pigcms{$village_park_config['d6_comid']}" type="hidden" />
                                    <span id="d6_comid">{pigcms{$village_park_config['d6_comid']}</span>
                                    <span class="form_tips">停车场ID参数</span>
                                </div>
                            </div>


                                </if>
								<if condition="$park_system['last_upload_time']">
									<div class="form-group" id="">
										<label class="col-sm-2">车场最后通信时间</label>
										<span style="padding-top:4px;line-height:24px;">{pigcms{:date('Y-m-d H:i:s', $park_system['last_upload_time'])}</span>
										<button onclick="location.reload();" style="margin-left:15px;">刷新</button>
										<span class="form_tips">车场最后连接服务器的时间，可用于判断主机是否断网。正常情况下最少每5秒会通信一次。</span>
									</div>
								</if>
								<div class="form-group" id="">
									<label class="col-sm-2">停车场预付码:</label>
									<div style="padding-top:4px;line-height:24px;"><a href="?channel_id=1&spread=1&village_id={pigcms{$house_session.village_id}" class="see_qrcode">查看二维码</a></div>
								</div>
							</div>
							<div class="tab-pane" id="txtstore">
								<div class="form-group">
									<label class="col-sm-2"><label for="">该小区是否支持月租车:</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="is_park_month_type" value="1" type="radio" class="ace" <if condition="$village_park_config.is_park_month_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">是</span></label>
										<label style="padding-left:0px;"><input name="is_park_month_type" value="0" type="radio" class="ace" <if condition="$village_park_config.is_park_month_type eq 0">checked</if>/><span class="lbl" style="z-index: 1">否</span></label>
									</div>
								</div>
							</div>
							<div class="tab-pane" id="txtpwd">
								<div class="form-group">
									<label class="col-sm-2"><label for="">是否开启储值车功能:</label></label>
									<div class="radio">
										<label style="padding-left:0px;padding-right:20px;"><input name="is_temporary_park_type" value="1" type="radio" class="ace" <if condition="$village_park_config.is_temporary_park_type eq 1">checked</if>/><span class="lbl" style="z-index: 1">是</span></label>
										<label style="padding-left:0px;"><input name="is_temporary_park_type" value="0" type="radio" class="ace" <if condition="$village_park_config.is_temporary_park_type eq 0">checked</if>/><span class="lbl" style="z-index: 1">否</span></label>
										<!--										<span class="form_tips">开通后，物业费可自定义上缴月份</span>-->
									</div>
								</div>
								<div class="form-group" id="is_temporary_park_type">
									<label class="col-sm-2"><label for="">储值车最小储值金额(元):</label></label>
									<input class="col-sm-4" size="20" name="visitor_money" value="{pigcms{$village_park_config['visitor_money']}" type="text" style=""/>
									<span class="form_tips">只有支持储值车才有该设置</span>
								</div>

							</div>
                            <div class="tab-pane" id="bindweixin">
								<div class="form-group">
									<label class="col-sm-3" style="background-color:#D9EDF7" ><label for="">主要用于业主邀请好友时,可设置好友的收费标准</label></label>

								</div>
								<div class="form-group">
									<label class="col-sm-2"><label for="">业主邀请来访的好友:</label></label>
									<select name="is_friend_type" id="" style="width: 200px">
										<option value="A" <if condition="$village_park_config['is_friend_type'] eq 'A'">selected="selected"</if> >A</option>
										<option value="B" <if condition="$village_park_config['is_friend_type'] eq 'B'">selected="selected"</if> >B</option>
										<option value="C" <if condition="$village_park_config['is_friend_type'] eq 'C'">selected="selected"</if> >C</option><option value="C" <if condition="$village_park_config['is_friend_type'] eq 'D'">selected="selected"</if> >D</option>
									</select>
								</div>
							</div>

							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button type="submit" class="btn btn-info">
										<i class="ace-icon fa fa-check bigger-110"></i>
										保存
									</button>
								</div>
							</div>
						</div>

					</form>
				</div>
			</div>
			</div>
		</div>
	</div>
</div>




<style>

.BMap_cpyCtrl{display:none;}
input.ke-input-text {
background-color: #FFFFFF;
background-color: #FFFFFF!important;
font-family: "sans serif",tahoma,verdana,helvetica;
font-size: 12px;
line-height: 24px;
height: 24px;
padding: 2px 4px;
border-color: #848484 #E0E0E0 #E0E0E0 #848484;
border-style: solid;
border-width: 1px;
display: -moz-inline-stack;
display: inline-block;
vertical-align: middle;
zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;border:1px solid #ccc;}
.col-sm-1 { width: 14.333%;}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>    
<script>


	$(function(){
		var is_temporary_park_type = "{pigcms{$village_park_config['is_temporary_park_type']}";

		console.log(is_temporary_park_type);
		if(is_temporary_park_type == 1){
			$("#is_temporary_park_type").show();
		}else{
			$("#is_temporary_park_type").hide();
		}

        $('.changeParkVersions').click(function () {
            var park_version = $(this).val();
            $('.d5_park').hide();
            $('.d3_park').hide();
            if (1==park_version) {
                $('.park_sys_type_box').hide();
                $('.park_sys_type_set_box').hide();
            } else {
                $('.park_sys_type_box').show();
                $('#park_sys_type').change();
            }
            var type=$('#park_sys_type').val();
            if (type=="D3" && park_version==2){
                $('.d3_park').show();
            }else if(type=="D5" && park_version==2){
                $('.d5_park').show();
            }
        })
	})

    $('#park_sys_type').change(function () {
        $('.d5_park,.d6_park').hide();
        $('.d3_park').hide();
        var type=$('#park_sys_type').val();
        if (type=="D3"){
            $('.d3_park').show();
        }else if(type=="D5"){
            $('.d5_park').show();
        }else if(type=="D6"){
            $.post("{pigcms{:U('Park/get_d6_comid')}",{},function(r){
                if(r.status == 1){
                    $("input[name='d6_comid']").val(r.data)
                    $('#d6_comid').html(r.data)
                }
            })
            $('.d6_park').show();
        }
        else{
            $('.park_sys_type_set_box').hide();
        }
    })

   //  $('#park_sys_type').change();

	$('input[name="is_temporary_park_type"]').change(function(){
		if($(this).val()==1){
			$("#is_temporary_park_type").show();
		}else{
			$("#is_temporary_park_type").hide();
		}
	})

	function park_config(){
		window.location.href = "{pigcms{:U('park_config')}";
	}

	function preferential_list(){
		window.location.href = "{pigcms{:U('park_month_list')}";
	}

	function park_temporary_list(){
		window.location.href = "{pigcms{:U('park_temporary_list')}";
	}

	function vehicle_management(){
		window.location.href = "{pigcms{:U('Unit/vehicle_management')}";
	}


</script>
<script type="text/javascript">
var lock = 0, test;
KindEditor.ready(function(K){
	$('#edit_form').submit(function(){

		$.post("{pigcms{:U('Park/park_config_edit')}",$('#edit_form').serialize(),function(result){

			if(result.status == 1){
				window.location.href = "{pigcms{:U('Park/park_config')}";
			}else{
				$('#edit_form button[type="submit"]').prop('disabled',false).html('<i class="ace-icon fa fa-check bigger-110"></i>保存');
				alert(result.info);
			}
		})
		return false;
	});
});
$(document).ready(function(){

	$('.see_qrcode').live('click',function(){
		var url = '{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode_html&down=true&qrCon=';
		var qrcode_urls = "{pigcms{$qrcode_url}";
		var hou_url = qrcode_urls + $(this).attr('href');
		var qrcode_url = url+encodeURIComponent(hou_url);
		art.dialog.open(qrcode_url,{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("如需保存，鼠标右键点击图片另存为即可")}',
			padding: 0,
			width: 312,
			height: 312,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});

});


</script>

<include file="Public:footer"/>