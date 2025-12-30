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
            <button class="btn btn-success" style="margin-bottom: 10px" onclick="record_cate_add()">添加字段类别</button>
            &nbsp;&nbsp;
            <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('category_index')}'" >分类管理列表</button> &nbsp;&nbsp;
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">字段类别</th>
                                    <th width="15%">字段内容管理</th>
                                    <th width="15%">添加时间</th>
                                    <th width="10%">状态</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.record_name}</div></td>
                                            <td><div class="tagDiv"><a class="bind_info green" href="{pigcms{:U('record_cate_field_config_index',array('record_cate_id'=>$vo['id'],'category_id'=>$_GET['category_id']))}">管理</a></div></td>
                                           	<td><div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div></td>
                                <td><div class="tagDiv"><if condition="$vo.status eq '1' ">正常<else />关闭</if></div></td>
                                            <td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('record_cate_edit',array('id'=>$vo['id'],'category_id'=>$_GET['category_id']))}">修改</a>&nbsp;
                                                <if condition="in_array(206,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('record_cate_del',array('id'=>$vo['id']))}'}">删除</a>
                                                </if>
                                           </td>
                                        </tr>
                                    </volist>
                                <else/>
                                    <tr class="odd"><td class="button-column" colspan="6" >没有任何数据。</td></tr>
                                </if>
                            </tbody>
                        </table>
                        {pigcms{$phone_list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
function record_cate_add(){
	var url = "{pigcms{:U('record_cate_add',array('category_id'=>$_GET['category_id']))}";
	window.location.href = url;
}

//$('.bind_info').click(function(){
//
//    var url = "{pigcms{:U('record_cate_add',array('cate_id'=>$_GET['cate_id']))}";
//    window.location.href = url;
//});
//$('.bind_info').click(function(){
//    art.dialog.open($(this).attr('href'),{
//        init: function(){
//            var iframe = this.iframe.contentWindow;
//            window.top.art.dialog.data('iframe_handle',iframe);
//        },
//        id: 'handle',
//        title:'添加',
//        padding: 0,
//        width: 800,
//        height: 603,
//        lock: true,
//        resize: false,
//        background:'black',
//        button: null,
//        fixed: false,
//        close: null,
//        left: '50%',
//        top: '38.2%',
//        opacity:'0.4'
//    });
//    return false;
//});
</script>

<include file="Public:footer"/>