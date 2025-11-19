<include file="Public:header"/>
<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
                    <a href="{pigcms{:U('Settlein_refund/index')}" class="on">待审核记录</a>|
                    <a href="{pigcms{:U('Settlein_refund/audit')}">已审核记录</a>
                </ul>
			</div>
			<form name="myform" id="myform" action="" method="post">
				<div class="table-list">
					<table width="100%" cellspacing="0">
						<thead>
							<tr>
								<th>编号</th>
								<th>商家名称</th>
								<th>申请时间</th>
								<th>退款金额</th>
								<th>备注</th>  
								<th class="textcenter">操作</th>
							</tr>
						</thead>
						<tbody>
							<if condition="is_array($list)">
								<volist name="list" id="vo">
									<tr>
                                        <td>{pigcms{$vo.order_id}</td>
                                        <td>{pigcms{$vo.mer_name}</td>
                                        <td>{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</td>
                                        <td>{pigcms{$vo.money}</td>
                                        <td>{pigcms{$vo.remark}</td>
                                        <td class="textcenter">
                                            <a href="javascript:;" class="btn agree_btn" data-id="{pigcms{$vo.order_id}">同意</a>
                                            <a href="javascript:;" class="btn reject_btn" data-id="{pigcms{$vo.order_id}" style="margin-left: 10px;">驳回</a>
                                        </td>
									</tr>
								</volist>
								<tr><td class="textcenter pagebar" colspan="10">{pigcms{$pagebar}</td></tr>
							<else/>
								<tr><td class="textcenter red" colspan="10">列表为空！</td></tr>
							</if>
						</tbody>
					</table>
				</div>
			</form>
</div>

<include file="Public:footer"/>

<script>
    $(document).ready(function(){
        $('.agree_btn').click(function(){
            let id = $(this).attr('data-id'); 

            let index = layer.confirm('确定同意退款申请吗？',{
                title: '提示',
                btn: ['确定', '取消']
            },
            function(){
                $.post("{pigcms{:U('Settlein_refund/handleAuditAjax')}", {
                    type : 1,
                    id : id
                },function(res){
                    if(res.error == 0){
                        layer.msg('操作成功',{icon:1,time:1000},function(){
                            location.href = location.href
                        })
                    }else{
                        layer.msg('操作成功',{icon:2,time: 1500});
                    }
                }, 'json')
                layer.close(index);
            },function(){
                layer.close(index);
            }
            )
        });
        $('.reject_btn').click(function(){
            let id = $(this).attr('data-id');

            layer.prompt({
                title:'请输入驳回理由',
                formType: 2,
                arrea:['300px', '200px']
            },function(val,index){ 

                $.post("{pigcms{:U('Settlein_refund/handleAuditAjax')}", {
                    type : 2,
                    id : id,
                    msg: val
                },function(res){
                    if(res.error == 0){
                        layer.msg('操作成功',{icon:1,time:1000},function(){
                            location.href = location.href
                        })
                    }else{
                        layer.msg('操作成功',{icon:2,time: 1500});
                    }
                }, 'json')


                layer.close(index)
            })
        });
    });
</script>