<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Appoint/index')}">{pigcms{:L_("预约管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("预约列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				#shopList .table-striped{table-layout:fixed;word-break:keep-all;             /* {pigcms{:L_("不换行")} */
white-space:nowrap;            /* {pigcms{:L_("不换行")} */
overflow:hidden;                  /* {pigcms{:L_("内容超出宽度时隐藏超出部分的内容")} */
text-overflow:ellipsis;  }
				#shopList .table-striped th,#shopList .table-striped td{width:100%;
         /* {pigcms{:L_("不换行")} */
white-space:nowrap;            /* {pigcms{:L_("不换行")} */
overflow:hidden;                  /* {pigcms{:L_("内容超出宽度时隐藏超出部分的内容")} */
text-overflow:ellipsis;            /* {pigcms{:L_("当对象内文本溢出时显示省略标记(...) ；需与overflow:hidden;一起使用。")}*/}

#shopList .table-striped td:last-child{text-overflow:clip; text-align:center; width:100%}
#shopList .table-striped td .title{
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  cursor: pointer;
  word-wrap: break-word;
}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form action="{pigcms{:U('Appoint/order_list')}" method="get" style="float: left;">
						<input type="hidden" name="c" value="Appoint"/>
						<input type="hidden" name="a" value="index"/>
						
						
						{pigcms{:L_("服务名称")} <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>&nbsp;
						{pigcms{:L_("定金状态")}
						<select name="payment_status">
							<option value="-1" <if condition="$_GET['payment_status'] eq '-1'">selected="selected"</if>>{pigcms{:L_("全部")}</option>
							<option value="0" <if condition="$_GET['payment_status'] eq '0'">selected="selected"</if>>{pigcms{:L_("不收定金")}</option>
							<option value="1" <if condition="$_GET['payment_status'] eq '1'">selected="selected"</if>>{pigcms{:L_("收定金")}</option>
						</select>&nbsp;
						{pigcms{:L_("创建时间")}
						<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
						<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
						&nbsp;
						<input type="submit" value="{pigcms{:L_("查询")}" class="btn button" style="margin-bottom: 3px;"/>　
					</form>
					<button style="float: left;" class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("添加预约")}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="80">{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("服务名称")}</th>
									<!--th>{pigcms{:L_("开始时间")}</th>
									<th>{pigcms{:L_("结束时间")}</th-->
									<if condition="!C('butt_open')">
										<th>{pigcms{:L_("创建时间")}</th>
										<th>{pigcms{:L_("分类名称")}</th>
										<th>{pigcms{:L_("查看二维码")}</th>
									</if>
									<th>{pigcms{:L_("定金状态")}</th>
									<th>{pigcms{:L_("价格")}</th>
									<th>{pigcms{:L_("预约状态")}</th>
									<if condition="!C('butt_open')"><th>{pigcms{:L_("审核状态")}</th></if>
									<th style="text-align:center;width:150px;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$appoint_list">
									<volist name="appoint_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.appoint_id}</td>
											<!--<td><a href="{pigcms{$config.site_url}/index.php?g=Appoint&c=Detail&appoint_id={pigcms{$vo.appoint_id}" target="_blank" data-title="{pigcms{$vo.appoint_name}" data-pic="{pigcms{$vo.list_pic}" class="appoint_name">{pigcms{$vo.appoint_name}</a></td>-->
											<td style="white-space: initial;text-overflow: unset;">
											    <div class="title" title="{pigcms{$vo.appoint_name}">{pigcms{$vo.appoint_name}</div>
											</td>
											<!--td>{pigcms{$vo.start_time|date='Y-m-d',###}</td>
											<td>{pigcms{$vo.end_time|date='Y-m-d',###}</td-->
											<if condition="!C('butt_open')">
											<td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>		
											<td>{pigcms{$vo.category_name}&nbsp;&nbsp;<if condition='$vo["is_autotrophic"]==1'>(<span class="green">{pigcms{:L_("平台自营")}</span>)</if></td>
											<td><if condition='$vo["is_autotrophic"]==0'><a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=appoint&id={pigcms{$vo.appoint_id}&img=1" class="see_qrcode">{pigcms{:L_("查看二维码")}</a><else/>无</if></td>
											</if>
											<td>
												<if condition="$vo['payment_status'] eq 0"><span style="color:green">{pigcms{:L_("不收定金")}</span>
												<elseif condition="$vo['payment_status'] eq 1" /><span style="color:red">{pigcms{:L_("收定金")}</span>
												</if>
											</td>
											<td>
												{pigcms{:L_("定金")}：<if condition="$vo.payment_status eq 0">--<else/>{pigcms{$config['Currency_symbol']}{pigcms{$vo.payment_money}</if>
												<br/>
												{pigcms{:L_("全价")}：<if condition="$vo.is_appoint_price eq 0">面议<else/>{pigcms{$config['Currency_symbol']}{pigcms{$vo.appoint_price}</if>
											</td>
											<td>
												<if condition="$vo['appoint_status'] eq 0"><span style="color:green">{pigcms{:L_("开启")}</span>
												<elseif condition="$vo['appoint_status'] eq 1" /><span style="color:red">{pigcms{:L_("关闭")}</span>
												</if>
											</td>
											<if condition="!C('butt_open')">
											<td>
												<if condition="$vo['check_status'] eq 0"><span style="color:red">{pigcms{:L_("待审核")}</span>
												<elseif condition="$vo['check_status'] eq 1" /><span style="color:green">{pigcms{:L_("通过")}</span>
												</if>
											</td>
											</if>
											<td style="text-align:center;">
												<a class="label label-sm label-info" title="{pigcms{:L_("订单列表")}" href="{pigcms{:U('Appoint/order_list',array('appoint_id'=>$vo['appoint_id']))}">{pigcms{:L_("订单列表")}</a>
												<a class="label label-sm label-info" title="{pigcms{:L_("编辑")}" href="{pigcms{:U('Appoint/frame_edit',array('appoint_id'=>$vo['appoint_id'],'is_show'=>1))}">{pigcms{:L_("编辑")}</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="10" >{pigcms{:L_("您没有添加过预约！")}</td></tr>
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
    var appoint_edit_load = sessionStorage.getItem("appoint_edit_load");
    if (appoint_edit_load && appoint_edit_load==1) {
        sessionStorage.removeItem("appoint_edit_load");
        location.reload();
    }
	function CreateShop(){
		window.location.href = "{pigcms{:U('Appoint/add')}";
	}
	
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
</script>
<include file="Public:footer"/>
