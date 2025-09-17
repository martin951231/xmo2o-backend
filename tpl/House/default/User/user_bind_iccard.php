<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="x-page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                    <div style="height: 52px;">
                    <if condition="$card_count lt 2">
					    <button class="btn btn-info" onclick="addICCard()" style="margin: 10px 20px;padding: 1px 20px;">添加IC卡</button>
                    </if>
                    <span style="margin-left: 16px;font-size: 16px;color: red;">最多可以添加 2 张卡</span>

                     <if condition="$card_count gt 0 AND !empty($img_faces)">
					    <button class="btn btn-info" onclick="synICCard()" style="padding: 1px 20px;position: absolute;right: 50px;top: 12px;">同步IC卡</button>
                    </if>
                    </div>
					  <input type="hidden" id="bind_pigcms_id" name="bind_pigcms_id" value="{pigcms{$bind_pigcms_id}">
						<div class="tab-content" style="border:none;min-height: 500px;">
							<div class="tab-pane active">

                                <table class="table table-striped table-bordered table-hover" style="position:relative;">
                                <thead>
                                    <tr>
                                        <th width="10%">卡类型</th>
                                        <th width="20%">卡号</th>
                                        <th width="30%">卡有效期</th>
                                        <th width="12%">卡有效期类型</th>
                                        <th width="20%">操作</th>
                                    </tr>
                                </thead>
                                <tbody class="class-checkbox">
                                    <if condition="$user_iccards">
                                        <volist name="user_iccards" id="vo">
                                            <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                            <td><div class="shopNameDiv">{pigcms{$vo.type_str}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.ic_card_no}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.effective_time}</div></td>
                                            <td><div class="shopNameDiv">{pigcms{$vo.card_expire_time_type_str}</div></td>
                                            <td><div class="shopNameDiv"><a href="javascript:void(0);" class="btn btn-info" onclick="addICCard('{pigcms{$vo.id}')" style=""> 编 辑 </a><a href="javascript:void(0);" class="btn btn-info" onclick="dellICCard('{pigcms{$vo.id}','{pigcms{$vo.ic_card_no}')" style="margin-left: 20px;"> 删 除 </a></div></td>
                                            </tr>
                                       </volist>
                                       <else/>
                                        <tr class="odd" style="text-align:center;"><td class="button-column" colspan="5" >没有数据</td></tr>
                                    </if>
                                </tbody>
							</div>
						</div>
					
			</div>
		</div>
	 </div>
   </div>
</div>
<style type="text/css">

</style>

<script type="text/javascript">
    var bind_pigcms_id="{pigcms{$bind_pigcms_id}";
   function addICCard(id){
        var idd= id!=undefined ? id:0;
        var url = '{pigcms{:U('add_user_iccard')}&bind_pigcms_id='+bind_pigcms_id+'&idd='+idd;
        var index = layer.open({
            type: 2,
            area: ['80%', '380px'],
            fixed: true, //不固定
            // maxmin: true,
            title: '添加卡号',
            content: url
        });
        layer.style(index, {
            top: '50px',
        });
   }

   function synICCard(){
        	$.ajax({
				url:"{pigcms{:U('User/syn_device_img')}",
				type:"post",
				data:{"pigcms_id":bind_pigcms_id},
				dataType: "Json",
				success:function(d){
					if (d.errorCode==0) {
						layer.alert('同步命令已发送！');
						//window.location.reload();
					} else {
						layer.alert(d.errorMsg);
					}
					console.log(d,d.errorCode);
				}
           });
   }

   function dellICCard(id,ic_card_no){
        var idd= id!=undefined ? id:0;
        var post_url = '{pigcms{:U('del_user_iccard')}&bind_pigcms_id='+bind_pigcms_id+'&idd='+idd;
        var tips= '您确认要删除卡号为【'+ic_card_no+'】这张卡吗？'
        layer.confirm(tips,{
            btn : [ '确定', '取消' ], //按钮
            shade : false
            //不显示遮罩
        },
        function(index) {
            var loading = layer.msg('正在处理中...', {
                icon: 16,
                shade: [0.1, '#000'],
                time: false  // 取消自动关闭
            });
            $.ajax({
                url:post_url,
                type:"post",
                data:{idd:idd,ic_card_no:ic_card_no,bind_pigcms_id:bind_pigcms_id},
                dataType: "Json",
                success:function(ret){
                    if(ret.status < 1){
                        layer.msg(ret.info, {icon: 6,time: 5000});
                    } else {
                        layer.msg('操作成功！', {icon: 1,time: 7000});
                    }
                    layer.close(loading);
                    layer.close(index);
                    setTimeout(function(){
                        window.location.reload();
                    }, 2000 )
                    
                }
            });

        }, function(index) {
            layer.close(index);
            
        });
}
</script>


