<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
<!--		<ul class="breadcrumb">-->
<!--			<li>-->
<!--				<i class="ace-icon fa fa-shopping-cart gear-icon"></i>-->
<!--				功能库-->
<!--			</li>-->
<!--			<li><a href="{pigcms{:U('Openphone/phone')}">常用电话</a></li>-->
<!--			 <li class="active"><a href="{pigcms{:U('Openphone/phone',array('cat_id'=>$now_cat['cat_id']))}">电话列表</a></li>-->
<!--		</ul>-->
	</div>
	<!-- 内容头部 -->
	<div class="page-content">

        <div class="page-content-area">
            <button class="btn " onclick="park_config()">停车费设置</button>&nbsp;&nbsp;
            <button class="btn " onclick="preferential_list()">月租车管理</button>&nbsp;&nbsp;
            <button class="btn " onclick="park_temporary_list()">临时停车管理</button>&nbsp;&nbsp;
            <button class="btn " onclick="vehicle_management()">车辆管理</button>&nbsp;&nbsp;
            <br/><br/>

                <button class="btn btn-success" style="margin-bottom: 10px" onclick="park_temporary_add()">添加临时车类型</button>
            &nbsp;&nbsp;&nbsp;&nbsp;

            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="20%">临时车类型名称</th>
                                    <th width="15%">临时车类型</th>

                                    <th width="15%">管理</th>


                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.month_name}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.type}</div></td>
                                <td><div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('Park/temporary_charge_list',array('park_month_id'=>$vo['id']))}">进入</a></div></td>
                                            <td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('park_month_edit',array('id'=>$vo['id']))}">修改</a>&nbsp;

                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('park_month_del',array('id'=>$vo['id']))}'}">删除</a>

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
function park_temporary_add(){
	var url = "{pigcms{:U('park_temporary_add')}";
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