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
        	
<!--                <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('index')}'" <if condition="!in_array(199,$house_session['menus'])">disabled="disabled"</if>>分类管理</button> &nbsp;&nbsp;-->
<!--                <button class="btn btn-success" style="margin-bottom: 10px" onclick="cate_add()">添加类别</button>&nbsp;&nbsp;-->
                            <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('record_index')}'" >抄表记录</button>
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">类别名称</th>
                                    <th width="10%">项目管理</th>
                                    <th width="10%">负责人管理</th>
                                    <th width="10%">缴费周期管理</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.cate_name}</div></td>
                                            <td><div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('MeterRead/project_index',array('cate_id'=>$vo['id']))}">管理</a></div></td>

                                <td><div class="tagDiv"><a style="color:lightseagreen;" href="{pigcms{:U('MeterRead/person_index',array('cate_id'=>$vo['id']))}">管理</a></div></td>
                                <td><div class="tagDiv"> <a href="javascript:;" style="width: 60px;color:lightseagreen;" class="" title="" onclick="payment_cycle({pigcms{$vo.id})">缴费周期</a></div></td>

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
<script type="text/javascript">
function cate_add(){
	var url = "{pigcms{:U('cate_add')}";
	window.location.href = url;
}

function close_all_page() {
    layer.closeAll();
    window.location.href = "{pigcms{:U('MeterRead/cate_index')}";
}


function payment_cycle(id){

    var url = "{pigcms{:U('MeterRead/payment_cycle')}";
    var href  = url + '&id='+id
    layer.open({
        type: 2,
        title:'编辑缴费周期',
        content: href,
        area: ['550px', '300px'],
        maxmin: false
    });

}
</script>

<include file="Public:footer"/>