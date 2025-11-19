<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - 小区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
		<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
        <script src="{pigcms{$static_path}layer/layer.js"></script>
        <link rel="stylesheet" href="{pigcms{$static_path}layer/css/layui.css"  media="all">
	</head>
<style type="text/css">
	.jqstooltip {
		position: absolute;
		left: 0px;
		top: 0px;
		visibility: hidden;
		background: rgb(0, 0, 0) transparent;
		background-color: rgba(0, 0, 0, 0.6);
		filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
		-ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
		color: white;
		font: 10px arial, san serif;
		text-align: left;
		white-space: nowrap;
		padding: 5px;
		border: 1px solid white;
		z-index: 10000;
	}

	.jqsfield {
		color: white;
		font: 10px arial, san serif;
		text-align: left;
	}

	.statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
		display: none;
	}

	#shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
		{
		font-size: 12px;
		color: black;
		display: none;
		width: 100%;
	}
	.fl{ float:left;}
	.fr{ float:right;}
		a{
			margin-right:5px;
		}
	#bind_info_btn{
		float:left ;
		margin-left:10px;
		margin-bottom: 5px;
		margin-top: 5px;
	    border: 1px solid #87b87f;
	    color:#87b87f;
	}
	#bind_info_btn span{
		padding-top: 5px;
	    color:#FFF
	}
</style>
<div class="main-content">
    <div class="page-content">
        <div class="page-content-area">
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
						 <if condition="!$base_third_config && !$base_third_config['third_id']">
						 <button class="chk_express bind_info" id="bind_info_btn" style="" href="{pigcms{:U('User/bind_other',array('pigcms_id'=>$_GET['pigcms_id'],'bind_type'=>1))}">绑定<if condition="$config['open_house_user_name']">亲友/租客&社员<else/>家属/租客</if></button>
						 </if>
						
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <if condition="$config['householdIdShow'] eq 1">
                                        <th>ID</th>
                                    </if>
                                    <th>姓名</th>
                                    <th>手机号</th>
                                    <th>个人证件信息</th>
                                    <th>门禁卡编号</th>
                                    <th>人脸上传</th>
                                    <th>关系</th>
                                    <th>状态</th>
                                    <th class="button-column" width="30%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$user_list">
                                    <volist name="user_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <if condition="$config['householdIdShow'] eq 1">
                                                <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>
                                            </if>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.phone}</div></td>
                                            <td>
                                                <div class="tagDiv">
                                                    身份证号：{pigcms{$vo['id_card']|default='暂无'}<br/>
                                                    IC卡号：{pigcms{$vo['ic_card']|default='暂无'}
                                                </div>
                                            </td>
                                            <td><div class="tagDiv">{pigcms{$vo.card_no}</div></td>
                                            <td><div class="tagDiv">
                                                    <if condition="$vo['faceTxt']">
                                                        <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">{pigcms{$vo['faceTxt']}</a>
                                                    <elseif condition="$vo['is_bind_face']"/>
                                                        <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">已上传</a>
                                                    <else/>
                                                        <a title="{pigcms{$vo.face_img_reason}" style="color: #3a87ad!important;" href="javascript:void(0);" onclick="bounced({pigcms{$vo['pigcms_id']})">未上传</a>
                                                    </if>
                                                </div>
                                            </td>
											<td><div class="tagDiv">
												<if condition='$vo["type"] eq 1'>
													<if condition="$config['open_house_user_name']">亲友<else/>家属</if>
												<elseif condition='$vo["type"] eq 2' />
													<if condition="$config['open_house_user_name']">租客&社员<else/>租客</if>
												<elseif condition='$vo["type"] eq 3' />
													更新房主
												<else />
													未知
												</if>
											</div></td>
                                            <td>
                                            	<if condition='$vo["status"] eq 0'>
													<div class="red">审核失败</div>
												<elseif condition='$vo["status"] eq 1' />
													<div class="green">已绑定</div>
												<elseif condition='$vo["status"] eq 2' />
													<div class="red">审核中</div>
												</if>
                                            </td>
                                            <td>
												<div class="tagDiv">
														<if condition='$vo["status"] eq 1'>
															<if condition='$vo["type"] eq 3'>
																<span class="green">绑定成功<span>
															<else />
                                                                <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                                            <!-- <a href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id'],'no_bind'=>1))}'}">解除绑定</a> -->
                                                                        <a href="javascript:void(0)" onclick="unbind('{pigcms{$vo.pigcms_id}')">解除绑定</a>
                                                                </if>
															</if>
															
														<elseif condition='$vo["status"] eq 2' />
                                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
															<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id']))}'}">确认绑定</a>
                                                            </if>
                                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
                                                                &nbsp;<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="unbind('{pigcms{$vo.pigcms_id}')">拒绝绑定</a>
                                                            </if>
                                                        <else/>
                                                            <if condition="!$base_third_config && !$base_third_config['third_id']">
															<a style="width: 60px;" class="label label-sm label-info" href="javascript:void(0)" onclick="if(confirm('确认进行绑定,请谨慎操作？')){location.href='{pigcms{:U('bind_edit',array('pigcms_id'=>$vo['pigcms_id']))}'}">确认绑定</a>
                                                            </if>
                                                        </if>

													<if condition="!$base_third_config && !$base_third_config['third_id']">
													<a class="bind_info" href="{pigcms{:U('bind_other',array('pigcms_id'=>$vo['pigcms_id'],'edit'=>1))}">编辑</a>
													</if>
														
													<if condition="!$base_third_config && !$base_third_config['third_id']">
													<a href="javascript:void(0)" onclick="if(confirm('确认进行删除,请谨慎操作？')){location.href='{pigcms{:U('bind_delete',array('pigcms_id'=>$vo['pigcms_id']))}'}">删除</a>
													</if>
												
														<if condition="$now_house['write_iccard']">
															<a onclick="WriteSector('{pigcms{$door_sector}','{pigcms{$door_pwd}','{pigcms{$parent_info.door_str}','{pigcms{$vo.pigcms_id}')" href="javascript:void(0);">写卡</a>
														</if>
																											
												</div>
											</td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="12" >暂无信息。</td></tr>
                                </if>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="unbind" style="display: none;">
	<div >
		<label class="col-sm-5"><label for="reason">拒绝原因：</label></label>
		<textarea class="col-sm-7" size="20" style="height: 50px" name="reason" id="reason" /></textarea>
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>



<script>
	var static_path = "{pigcms{$static_path}";
</script>

<if condition="$now_house['write_iccard']">
	<script language="javascript" src="http://127.0.0.1:8008/YOWOCloudRFIDReader.js"></script>
	<script language="javascript" src="{pigcms{$static_path}/js/YOWOReaderBase.js"></script>
	<script language="javascript" type="text/javascript">
	    rfidreader.onResult(function(resultdata) {
	        switch(resultdata.FunctionID){
	            case 8:
	                document.getElementById("CardNo").value = resultdata.CardNo;
	                if(resultdata.Result>0) {
	                    alert("同步成功！");
	                    var cardNoUrl = "{pigcms{:U('User/card_no_add')}";
	                    var card_no = resultdata.CardNo;
	                    var pigcms_id = $("#pigcms_id").val();
		                $.post(cardNoUrl,{card_no:card_no,pigcms_id:pigcms_id},function(data){

		                },'json')
	                } else {
	                    alert("同步失败，错误：" + GetErrStr(resultdata.Result));   
	                }
	                break;
	        }
	    });
	    function WriteSector(SectorID,Key,Data,pigcms_id){
	        rfidreader.KeyMode=0;
	        rfidreader.KeyStringMode=0;
	        rfidreader.KeyString=Key;
	        rfidreader.Repeat=0
	        rfidreader.M1WriteSector(SectorID, Data,0); 
	        $("#pigcms_id").val(pigcms_id);
	    }
	</script>
</if>


<input name="CardNo" type="hidden" id="CardNo" size="10" maxlength="8" readonly>
<input name="pigcms_id" type="hidden" id="pigcms_id">



<script>
    function bounced(id) {
        console.log(id);
        var index = layer.open({
            type: 2,
            area: ['750px', '550px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '人脸上传照片',
            content: "/shequ.php?g=House&c=User&a=faces&id="+id
        });
        layer.style(index, {
            top: '20px'
        });
    }
	$('.bind_info').click(function(){
		var open_house_user_name = {pigcms{$config['open_house_user_name']|intval};
		if (open_house_user_name) {
			var tipTitle = '添加亲友/租客&社员';
		}else{
			var tipTitle = '添加家属/租客';
		}
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle_',iframe);
			},
			id: 'handle_',
			title:tipTitle,
			padding: 0,
			width: 800,
			height: 603,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '50px',
			opacity:'0.4'
		});
		return false;
	});

	// 拒绝绑定
	function unbind(pigcms_id){
		art.dialog({
	        content: document.getElementById('unbind'),
	        id: 'handle',
	        title:'解除绑定',
	        padding: 0,
	        width: 350,
	        height: 150,
	        lock: true,
	        resize: false,
	        background:'black',
	        fixed: false,
	        left: '50%',
	        top: '50px',
	        opacity:'0.4',
	        ok:function(){
				var reason = $('#reason').val();
	        	$.get("{pigcms{:U('bind_edit')}",{pigcms_id:pigcms_id,no_bind:1,reason:reason,data_type:'json'},function(result){
			       if (result.status>0) {
			            alert(result.msg);return false;
			       } else if (result.status==0){
			       		alert(result.msg);
			            window.location.reload();
			       }
			    },'json');
	        },
	    });
		return false;
	};
</script>
</body>
</html>