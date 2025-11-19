<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
	<div class="row">
		<div class="col-xs-12">
			<div id="shopList" class="grid-view">
				<table class="table table-striped table-bordered table-hover" style="position:relative;">
					<thead>
					<tr>

						<th width="5%">小区名称</th>

						<th width="10%">待投诉建议数量</th>
						<th width="5%">操作</th>
					</tr>
					</thead>
					<tbody class="class-checkbox">
					<if condition="$dataList">
						<volist name="dataList" id="vo">
					<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
					    <td><div class="tagDiv">{pigcms{$vo.village_name}</div></td>
					    <td><div class="tagDiv">{pigcms{$vo.suggest_count}</div></td>
					    <td><div class="tagDiv" style="color: #2681f3;"><a href="{pigcms{:U('NewProperty/village_login',array('village_id'=>$vo['village_id'],'type'=>suggest))}" target="_blank" style="color:red;">管理入口</a></div></td>

					</tr>
					</volist>

					<else/>
					<tr class="odd"><td class="button-column" colspan="14" >没有任何数据。</td></tr>
					</if>
					</tbody>

				</table>
				<if condition="$dataList">
					<div style="float: right; padding-left: 5px; ">
						<input style="width: 50px;" type="text" name="page" id="page" value="{pigcms{$_GET['page']}">
						<button onclick='checkPage()' style="background-color: #3a87ad!important; color: #fff; width: 50px; height: 30px;">跳转</button>
						<script>
							function checkPage(){
								var page = $("#page").val();
								if(page == ''){
									return false;
								}
								location.href = "{pigcms{:U('suggest_list')}"+"&page="+page;
							}
						</script>
					</div>
					{pigcms{$pagebar}
				</if>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">

$('#add_info').submit(function(){
    $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
        console.log('result', result);
        if (result && result.status==1) {
            layer.open({
                content: '添加成功！添加数据不能实时显示，如需显示请刷新页面查看！',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    window.parent.close_all_page(result);
                }
            })
        } else if (result.info) {
            layer.open({
                content: result.info,
                shadeClose: false,
                closeBtn: 0
                ,btn: ['知道了']
            })
        } else {
            layer.open({content: '添加失败'});
        }
    });
    return false;
});


</script>
<include file="Publics:footer"/>
