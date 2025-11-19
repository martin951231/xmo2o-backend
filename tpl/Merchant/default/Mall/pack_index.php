<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("一口价打包列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<style>
				.ace-file-input a {display:none;}
				.cur_place{
					padding: 10px 0 20px 0;
					color:#999
				}
				.cur_place span,.cur_place a{
					padding: 0 5px;
					 
				}
			.application-list {
					margin-bottom: 24px;
					-webkit-animation: enterIn .2s ease-out;
					animation: enterIn .2s ease-out;
					-webkit-animation-fill-mode: forwards;
					animation-fill-mode: forwards;
				}
				.application-title {
					margin-bottom: 20px;
					color: #323233;
				}
				.application-title .title-main {
					font-size: 16px;
					font-weight: 700;
				}
				.application-title .title-desc {
					color: #999;
					margin-left: 10px;
					font-size:13px;
				}
			</style>
			<div class="cur_place">
				<span style="padding-left: 0;">平台业务</span>/
				<span>商城管理</span>/ 
			    <a href="{pigcms{:U('Mall/marketingList', array('store_id' => $_GET['store_id']))}">营销活动</a>
			</div>
			<div class="application-title">
				<span class="title-main">打包一口价</span>
				<span class="title-desc">一口价打包售卖，提升销量，促进购物欲望</span>
			</div>
			
			<div class="row">
				<div class="col-xs-12">
					<div style="margin-bottom:10px;">
						<a href="{pigcms{:U('Mall/pack_index',array('store_id'=>$_GET['store_id']))}" class="btn  btn-primary" style="background-color:#6FB3E0;">{pigcms{:L_("活动列表页")}</a>
						<a href="{pigcms{:U('Mall/add_pack',array('store_id'=>$_GET['store_id']))}" class="btn btn-success" style="margin-left:10px;background-color:#0099cc;">{pigcms{:L_("添加活动")}</a>
					</div>
					
					<div class="form-group">
						<label class="col-sm-1" style="display:none">{pigcms{:L_("订单状态筛选：")}</label>
						<select id="status" name="status" style="display:none">
							<volist name="status_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						<form action="{pigcms{:U('Mall/pack_index')}" method="get">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="pack_index"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							
							{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							
							<font color="#000">{pigcms{:L_("活动开始时间：")}</font>
							<input type="text" class="input-text" name="sdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.sdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="sdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.sdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							<font color="#000">{pigcms{:L_("活动结束时间：")}</font>
							<input type="text" class="input-text" name="fdate1" style="width:120px;" id="d4311"  value="{pigcms{$_GET.fdate1}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="fdate2" style="width:120px;" id="d4311" value="{pigcms{$_GET.fdate2}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("活动名称")}</th>
									
									<th>{pigcms{:L_("活动开始时间")}</th>
									<th>{pigcms{:L_("活动结束时间")}</th>
                                    <th>{pigcms{:L_("活动最多参与次数")}</th>
									<th>{pigcms{:L_("活动商品")}</th>
									<th>{pigcms{:L_("链接/二维码")}</th>

									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<volist name='list' id='vo'>
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td>{pigcms{$vo.pack_name}</td>
										<td>{pigcms{$vo.begin_time|date="Y-m-d H:i:s",###}</td>
										<td>{pigcms{$vo.end_time|date="Y-m-d H:i:s",###}</td>
										<td>
											<eq name="vo.pack_limit" value="0">
												无限
											<else/>
											{pigcms{$vo.pack_limit}次
											</eq>

										</td>
										<td>
											<a href="/wap.php?g=Wap&c=Mall&a=pack_act&id={pigcms{$vo['id']}" target="_blank">{pigcms{:L_("详情")}</a>
										</td>
                                        <td>
                                            <a href="{pigcms{:U('see_goods_qrcode',array('pack_id'=>$vo['id']))}" class="see_qrcode">{pigcms{:L_("二维码")}</a>
                                        </td>
										<td>
											<a style="width: 60px;" class="label label-sm label-success " href="{pigcms{:U('Mall/add_pack',array('store_id'=>$now_store['store_id'],'id'=>$vo['id']))}">{pigcms{:L_("编辑")}</a>
											<a style="width: 60px;background-color:red !important;" class="label label-sm label-success del" href="{pigcms{:U('Mall/del_pack',array('store_id'=>$now_store['store_id'],'id'=>$vo['id']))}">{pigcms{:L_("删除")}</a>
								
										</td>
									</tr>
								</volist>
								<empty name='list'>
									<tr class="odd">
										<td class="button-column" colspan="30" >{pigcms{:L_("暂无一口价打包活动")}</td>
									</tr>
								</empty>
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
	$(function(){
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
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
        $('.del').live('click',function(){
            if(!confirm('{pigcms{:L_("确认删除该活动?")}')){
            	return false;
            }
        });

        $('.synczbw').click(function(){
            var index = layer.load(1, {shade: [0.7,'#000']}), store_id = $(this).data('store_id');
            $.post("{pigcms{:U('Mall/syncZbw')}", {'store_id':$(this).data('store_id')}, function(res){
                if (res.sCode == 0) {
                    syncGoods(store_id, 1, index);
                } else {
                    layer.msg(res.sError);
                    layer.close(index)
                }
            },'json');
        });
	});

    function syncGoods(store_id, page, index)
    {
        $.post("{pigcms{:U('Mall/syncZbwGoods')}", {'store_id':store_id, 'page':page}, function(res){
            if (res.sCode == 0) {
                if (res.page == 0) {
                    layer.msg('{pigcms{:L_("同步完成")}');
                    layer.close(index);
                } else {
                    syncGoods(store_id, res.page, index);
                }
            } else {
                layer.msg(res.sError);
                layer.close(index);
            }
        },'json');
    }
    
	$(function(){
		/*{pigcms{:L_("店铺状态")}*/
		updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "store_theme");
	});
	function updateStatus(dom1, dom2, status1, status2, attribute){
		$(dom1).each(function(){
			if($(this).attr("data-status")==status1){
				$(this).attr("checked",true);
			}else{
				$(this).attr("checked",false);
			}
			$(dom2).show();
		}).click(function(){
			var _this = $(this), type = 'open', id = $(this).attr("data-id");
			_this.attr("disabled",true);
			if(_this.attr("checked")){	//"开启")}
				type = 'open';
			}else{		//"关闭")}
				type = 'close';
			}
			$.ajax({
				url:"{pigcms{:U('Mall/change_mall')}",
				type:"post",
				data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
				dataType:"text",
				success:function(d){
					if(d != '1'){		//"失败")}
						if(type=='open'){
							_this.attr("checked",false);
						}else{
							_this.attr("checked",true);
						}
						bootbox.alert("{pigcms{:L_("操作失败")}");
					}
					_this.attr("disabled",false);
				}
			});
		});
	}
</script>
<include file="Public:footer"/>