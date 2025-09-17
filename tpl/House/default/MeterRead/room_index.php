<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
<!--			<li>-->
<!--				<i class="ace-icon fa fa-shopping-cart gear-icon"></i>-->
<!--				功能库-->
<!--			</li>-->
<!--			<li><a href="{pigcms{:U('Openphone/phone')}">常用电话</a></li>-->
			<!-- <li class="active"><a href="{pigcms{:U('Openphone/phone',array('cat_id'=>$now_cat['cat_id']))}">电话列表</a></li> -->
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
        	

                <button class="btn btn-success" style="margin-bottom: 10px" onclick="room_add()">添加房间</button>
            &nbsp;&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('project_index',array('cate_id'=>$_GET['cate_id']))}'" >项目列表</button> &nbsp;&nbsp;
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>


                                     <th width="10%">房间ID</th>
                                    <th width="10%">物业编号</th>
                                    <th width="10%">楼号</th>
                                    <th width="10%">单元名称</th>
                                    <th width="10%">层号</th>
                                    <th width="10%">房间号</th>
                                    <th width="10%">房屋面积</th>
                                    <th width="10%">抄表状态</th>

                                    <th class="button-column" width="10%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                            <if condition="$result['list']">
                                <volist name="result['list']" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.pigcms_id}</div></td>

                                        <td><div class="tagDiv">{pigcms{$vo.usernum}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.floor_layer}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.floor_name}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.layer}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.room}</div></td>
                                        <td><div class="tagDiv">{pigcms{$vo.housesize}</div></td>
                                        <td><div class="tagDiv" style="background-color: {pigcms{$vo['read_status_color']};border-radius: 5px;text-align: -webkit-center;color: white;width: 5rem;">{pigcms{$vo.read_status_desc}</div></td>

                                        <td class="button-column">


                                                &nbsp;<a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该条信息？')){location.href='{pigcms{:U('room_del',array('room_id'=>$vo['pigcms_id'],'cate_id'=>$_GET['cate_id'],'project_id'=>$_GET['project_id']))}'}">删除</a>

                                        </td>
                                    </tr>
                            </volist>
                            <else/>
                                  <tr class="odd"><td class="button-column" colspan="12" >没有任何信息。</td></tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$result.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style type="text/css">
    .ke-dialog-body .ke-input-text{height: 30px;}
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">

var selecteds = "{pigcms{$selecteds}";
var project_id = "{pigcms{$_GET['project_id']}";
var cate_id = "{pigcms{$_GET['cate_id']}";
function room_add() {
    localStorage.removeItem('choose_floor');
    var url = "{pigcms{:U('room_add')}";
    var href = url + '&selecteds='+selecteds+'&project_id='+project_id +'&cate_id='+cate_id;
    layer.open({
        type: 2,
        title:'选择房间',
        content: href,
        area: ['750px', '485px'],
        maxmin: false
    });
}

function close_all_page(info) {
    console.log('info');
    console.log(info);
	var url = "{pigcms{:U('room_index',array('cate_id'=>$_GET['cate_id'],'project_id'=>$_GET['project_id']))}";
	window.location.href = url;
    layer.closeAll();
}
</script>

<include file="Public:footer"/>