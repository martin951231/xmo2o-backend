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


                <button class="btn btn-success" style="margin-bottom: 10px" onclick="person_add()">添加负责人</button>
            &nbsp;&nbsp;&nbsp;&nbsp;
                            <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('cate_index')}'" >管理列表</button> &nbsp;&nbsp;
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">负责人姓名</th>
                                    <th width="15%">负责人联系方式</th>
                                    <th width="15%">添加时间</th>
                                    <th width="10%">状态</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.user_phone}</div></td>

                                           	<td>
                                                <if condition="$vo['add_time'] gt 0">
                                                <div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div>
                                                </if>
                                            </td>
                                <td><div class="tagDiv"><if condition="$vo.status eq '1' ">正常<else />关闭</if></div></td>
                                            <td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('person_edit',array('id'=>$vo['id'],'cate_id'=>$_GET['cate_id']))}">修改</a>&nbsp;

                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('person_del',array('id'=>$vo['id']))}'}">删除</a>

                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
function person_add(){
	var url = "{pigcms{:U('person_add',array('cate_id'=>$_GET['cate_id']))}";
	window.location.href = url;
}


</script>

<include file="Public:footer"/>