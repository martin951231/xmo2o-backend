<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Appoint/project_list')}">项目名称列表</a>
			<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Appoint/project_add', array('parentid' => $parentid))}','添加分类',480,260,true,false,false,addbtn,'add',true);">添加项目名称</a>
			<a href="{pigcms{:U('Appoint/project_city_list')}">项目城市列表</a>
			<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Appoint/project_city_add', array('parentid' => $parentid))}','添加分类',480,260,true,false,false,addbtn,'add',true);">添加项目城市</a>
			<a href="{pigcms{:U('Appoint/project_import')}" class="on">项目名称导入</a>
		</ul>
	</div>	
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<colgroup><col> <col> <col> <col><col><col><col><col><col width="240" align="center"> </colgroup>
				<thead>
					<tr>
						<th>模块</th>
						<th>下载Excel数据<span style="color:red;">（下载前请先添加好数据）</span></th>
						<th style="width: 500px;">导入Excel数据<span style="color:red;">（导入的表格必须是左边下载的表格）</span></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>项目名称信息</td>
						<td><a href="{pigcms{:U('Appoint/project_import_download',array('type'=>'project'))}" class="download_excel">下载表格</a></td>
						<td><input type="file" id="project_file" data-name="project" /></td>
					</tr>
<!--					<tr>-->
<!--						<td>城市名称信息</td>-->
<!--						<td><a href="{pigcms{:U('Appoint/project_import_download',array('type'=>'project_city'))}" class="download_excel">下载表格</a></td>-->
<!--						<td><input type="file" id="project_city_file" data-name="project_city" /></td>-->
<!--					</tr>-->
				</tbody>
			</table>
		</div>

</div>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/fileinput.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}bootstrap-fileinput-master/js/locales/zh.js"></script>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}bootstrap-fileinput-master/css/fileinput.css" />
<script type="text/javascript">
	initUpload("project_file","project");
	initUpload("project_city_file","project_city");


	function initUpload(ctrlId, type){
		if(type == ''){
			return ;
		}
		$("#"+ctrlId).fileinput({
			uploadUrl : '{pigcms{:U("project_import_upload")}',
			language: 'zh', //设置语言
			showBrowse : true,
			showPreview : true,
			showRemove : true,
			showUpload : true,
			showCancel : false,
			showClose : true,
			showCaption : true,
			dropZoneEnabled : false,
			allowedFileExtensions : ['xls'],
			uploadExtraData: function (e) {
				var param = {
					"type" : type
				}
	            return param;
	        }
		}).on("fileuploaded", function (event, data, previewId, index) {
	        if(data.response.status == 1)
	        {
	            layer.msg(data.files[index].name + data.response.info,{offset:'300px'});
	        	//关闭
	            $(".close").click();
	        }
	        else{
	            layer.msg(data.files[index].name + "{pigcms{:L_("导入失败")} " + data.response.info, {icon: 5,offset:'300px'});
		        //重置
		        $("#"+ctrlId).fileinput("clear");
		        $("#"+ctrlId).fileinput("reset");
		        $('#'+ctrlId).fileinput('refresh');
		        $('#'+ctrlId).fileinput('enable');
		        $(".close").click();
	        }
	    });
	}

</script>
<include file="Public:footer"/>