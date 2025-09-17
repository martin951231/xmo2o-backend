<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" >
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('WisdomQrcode/cate_index')}">智慧巡检码</a>
			</li>
			
			<li class="active">二维码管理</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
        <div class="page-content-area">
        	
<!--                <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('index')}'" <if condition="!in_array(199,$house_session['menus'])">disabled="disabled"</if>>分类管理</button> &nbsp;&nbsp;-->
                <button class="btn btn-success" style="margin-bottom: 10px" onclick="cate_add()">添加二维码</button>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <button class="btn btn-success" style="margin-bottom: 10px" onclick="location.href='{pigcms{:U('cate_index')}'" >二维码类别管理列表</button> &nbsp;&nbsp;
            <div class="row">

                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">序号</th>
                                    <th width="15%">二维码名称</th>
                                    <th width="15%">位置</th>
                                    <th width="15%">添加时间</th>
                                    <th width="15%">查看</th>
                                    <th class="button-column" width="15%">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <if condition="$dataList">
                                    <volist name="dataList" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="tagDiv">{pigcms{$vo.num_id}</div></td>
                                            <td><div class="tagDiv">{pigcms{$vo.name}</div></td>
                                            <if condition="isset($vo['from_type']) AND $vo['from_type'] eq 1">
                                            <td><div class="tagDiv">{pigcms{$vo.map_point}</div></td>
                                            <else />
                                            <td><div class="tagDiv">{pigcms{$vo.long},{pigcms{$vo.lat}</div></td>
                                            </if>
                                           	<td><div class="tagDiv">{pigcms{$vo.add_time|date="Y-m-d H:i:s",###}</div></td>
                                           <td><div class="tagDiv"><a href="?qrcode_id={pigcms{$vo.id}&spread=1&village_id={pigcms{$village_id}&name={pigcms{$vo.name}&wis_code=1&map_type=0" class="see_qrcode">{pigcms{:L_("查看二维码")}</a></div></td>
                                            <td class="button-column">
                                            <if condition="!isset($vo['from_type']) OR $vo['from_type'] neq 1">
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('qrcode_edit',array('id'=>$vo['id'],'cate_id'=>$_GET['cate_id']))}">修改</a>&nbsp;
                                                <if condition="in_array(206,$house_session['menus'])">
                                                <a style="width: 60px;" class="label label-sm label-info" title="删除" href="javascript:void(0)" onClick="if(confirm('确认删除该内容？')){location.href='{pigcms{:U('qrcode_del',array('id'=>$vo['id']))}'}">删除</a>
                                                </if>

                                                </if>
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
function cate_add(){
	var url = "{pigcms{:U('qrcode_add',array('cate_id'=>$_GET['cate_id']))}";
	window.location.href = url;
}


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
</script>

<include file="Public:footer"/>