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
				<div class="mainbox">
					<div id="nav" class="mainnav_title">
						<ul>

							<button class="chk_express bind_info" id="bind_info_btn" style="" href="{pigcms{:U('cate_field_cate_config_add',array('field_cate_id'=>$_GET['field_cate_id']))}">添加字段</button>
						</ul>
					</div>
					<form name="myform" id="myform" action="" method="post">
						<div class="table-list">
							<table width="100%" cellspacing="0">
								<colgroup>
									<col/>
									<col/>
									<col/>
									<col/>
								</colgroup>
								<thead>
								<tr>
									<th>名称</th>
									<th>类型</th>
									<th class="textcenter">操作</th>
								</tr>
								</thead>
								<tbody>
								<if condition="is_array($dataList)">
									<volist name="dataList" id="vo">
										<tr>
											<td>{pigcms{$vo.title}</td>
											<td>
												<if condition="$vo['type'] eq 1">
													文本
												<elseif condition="$vo['type'] eq 2" />
													图片
													<elseif condition="$vo['type'] eq 3" />
													日期
												</if>
											</td>
											<td class="textcenter"><a class="bind_info" href="{pigcms{:U('cate_field_cate_config_edit',array('id'=>$vo['acid']))}">编辑</a>&nbsp;|&nbsp;
												<a href="javascript:void(0)" onclick="if(confirm('确认进行删除,请谨慎操作？')){location.href='{pigcms{:U('cate_field_cate_config_del',array('id'=>$vo['acid']))}'}">删除</a>
											</td>
										</tr>
									</volist>
									<else/>
									<tr><td class="textcenter red" colspan="4"> 列表为空！</td></tr>
								</if>
								</tbody>
							</table>
						</div>
					</form>
				</div>
            </div>
        </div>
    </div>
</div>


<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>


<script>
	var static_path = "{pigcms{$static_path}";
</script>


<script>
	$('.bind_info').click(function(){
		var tipTitle = '添加/编辑字段内容';
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
			top: '38.2%',
			opacity:'0.4',
			cancel: function () {
                window.location.reload()
            }
		});
		return false;
	});


</script>
</body>
</html>