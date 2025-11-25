<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Appoint/index')}">预约管理</a>
			</li>
			<li class="active">工作人员列表</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				
				#shopList .table-striped{table-layout:fixed;word-break:keep-all;             /* 不换行 */
white-space:nowrap;            /* 不换行 */
overflow:hidden;                  /* 内容超出宽度时隐藏超出部分的内容 */
text-overflow:ellipsis;  }
				#shopList .table-striped td{width:100%;
word-break:keep-all;             /* 不换行 */
white-space:nowrap;            /* 不换行 */
overflow:hidden;                  /* 内容超出宽度时隐藏超出部分的内容 */
text-overflow:ellipsis;            /* 当对象内文本溢出时显示省略标记(...) ；需与overflow:hidden;一起使用。*/}
                .btn_jish {
                    padding: 7px 6px;
                    border-radius: 12px;
                    outline: none;
                    background-color: #87b87f!important;
                    border-color: #87b87f;
                    display: inline-block;
                    color: #FFF!important;
                    text-shadow: 0 -1px 0 rgba(0,0,0,.25)!important;
                    background-image: none!important;
                    border: 5px solid #FFF;
                    box-shadow: none!important;
                    -webkit-transition: all ease .15s;
                    -o-transition: all ease .15s;
                    transition: all ease .15s;
                    cursor: pointer;
                    vertical-align: middle;
                    margin: 0;
                    position: relative;
                }
			</style>
			<div class="row">
            <div class="col-xs-12">
					<div style="border:1px solid #c5d0dc;padding-left:22px;margin-bottom:10px;margin-top:20px;">
						<div style="margin-top:10px;" class="alert alert-info">
							<button data-dismiss="alert" class="close" type="button"><i class="ace-icon fa fa-times"></i></button>工作人员指预约门店项目中提供相应服务的工作{pigcms{$config['appoint_worker_name'] ? $config['appoint_worker_name'] : '技师'}人员。
						</div>
				</div>
            
				<div class="col-xs-12">
                    <button class="btn btn-success" onclick="CreateWorker()">添加{pigcms{$config['appoint_worker_name'] ? $config['appoint_worker_name'] : '技师'}</button>
                    <a class="btn_jish" target="_blank" href="{pigcms{$config['site_url']}/packapp/worker">
                        登录移动版技师中心
                    </a>
                    <div style="color: #31708f;display:inline;" ></div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="80">编号</th>
									<th>姓名</th>
									<th>性别</th>
									<th>头像</th>
									<th>绑定微信号</th>
									<th>所属店铺</th>
									<th>联系号码</th>
									<th>状态</th>
									<th width="300" style="text-align:center;">操作</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$work_list">
									<volist name="work_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.merchant_worker_id}</td>
											<td>{pigcms{$vo.name}</td>
											<if condition="$vo['sex'] eq 1"><td>男</td><else/><td>女</td></if>
											<td><img width="50" height="50" src="{pigcms{:file_domain()}/upload/appoint/{pigcms{$vo.avatar_path}"/></td>

                                            <td class="nickname_{pigcms{$vo['merchant_worker_id']}">
                                                <if condition="empty($vo['openid'])">
                                                    <a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_tmp_qrcode&qrcode_id={pigcms{$vo['merchant_worker_id'] + 400000000}&img=1" data-id="{pigcms{$vo['merchant_worker_id']}" class="see_qrcode" id="qr_{pigcms{$vo['merchant_worker_id']}" style="color:green">绑定微信号</a>
                                                <elseif condition="!empty($vo['openid'])"/>
                                                    {pigcms{$vo.nickname}&nbsp;&nbsp;<a style="width:60px;" data-id="{pigcms{$vo.merchant_worker_id}" class="label label-sm label-info cancel" title="解除绑定" href="javascript:;">解除绑定</a>
                                                <else/>
                                                    -
                                                </if>
                                            </td>

											<td>{pigcms{$store_info[$vo['merchant_store_id']]}</td>
											<td>{pigcms{$vo.mobile}</td>
											<if condition="$vo['status'] eq 1"><td class="green">开启</td><else/><td><span class="red">关闭</span></td></if>
											
											<td style="text-align:center;">
												<!-- <a style="width: 60px;" class="label label-sm label-info" title="评论列表" href="{pigcms{:U('Message/Appoint_reply',array('Appoint_id'=>$vo['Appoint_id']))}">评论列表</a>&nbsp;&nbsp;&nbsp; -->
												<a style="width: 60px;" class="label label-sm label-info" title="修改" href="{pigcms{:U('Appoint/worker_edit',array('merchant_worker_id'=>$vo['merchant_worker_id']))}">修改</a>&nbsp;&nbsp;&nbsp;<a style="width: 60px;" class="label label-sm label-info" title="删除" onClick="if(confirm('确认删除该{pigcms{$config['appoint_worker_name'] ? $config['appoint_worker_name'] : '技师'}？')){worker_del({pigcms{$vo['merchant_worker_id']});}">删除</a>&nbsp;&nbsp;&nbsp;<a style="width: 60px;" class="label label-sm label-info" title="订单列表" href="{pigcms{:U('order_list',array('merchant_worker_id'=>$vo['merchant_worker_id']))}">订单列表</a>&nbsp;&nbsp;&nbsp;<a style="width: 60px;" class="label label-sm label-info" title="评论列表" href="{pigcms{:U('worker_reply',array('merchant_worker_id'=>$vo['merchant_worker_id']))}">评论列表</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >您没有添加过{pigcms{$config['appoint_worker_name'] ? $config['appoint_worker_name'] : '技师'}！</td></tr>
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
	function CreateWorker(){
		window.location.href = "{pigcms{:U('Appoint/worker_add')}";
	}
	
	function worker_del(merchant_worker_id){
		var url="{pigcms{:U('Appoint/worker_del')}";
		$.post(url,{'merchant_worker_id':merchant_worker_id},function(data){
			alert(data['error_msg'])
			if(data.status==1){
				location.href="{pigcms{:U('Appoint/worker_list')}";
			}
		},'json')
	}
	
	// "取消绑定")}
    $('.cancel').click(function(){
        var id = $(this).attr('data-id'), obj = $(this);
        obj.attr('disabled', true);
        $.get("{pigcms{:U('Appoint/cancel_bind_wx')}", {id:id}, function(result){
            obj.attr('disabled', false);
            if (result.error_code == 1) {
                alert(result.msg);
            } else {
                var qrcode_id = 400000000 + parseInt(id);
                obj.hide();
                $('.nickname_'+id).html('<a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_tmp_qrcode&qrcode_id=' + qrcode_id + '&img=1" data-id="' + id + '" class="see_qrcode" style="color:green">{pigcms{:L_("绑定微信号")}</a>')
            }
        }, 'json');
    });
    // "绑定微信")}
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
