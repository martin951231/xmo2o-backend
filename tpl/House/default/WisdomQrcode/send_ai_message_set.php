<include file="Publics:header"/>
   <style>
   table tr{line-height: 70px;}
   </style>
	<body style="background-color: #fff;overflow-y: hidden;">
        <div style="padding: 0 20px;">
        <input value="{pigcms{$record_log.id}" name="log_id" id="log_id" type="hidden" >
		<table style="width: 100%;">
			<tr>
				<th width="15%"><span style="color:red;">*</span>选择工作人员</th>
				<td >
                   <div style="color:red;">
                    请按住 Ctrl (windows) 或 Command (Mac) 按钮来选择多个工作人员数据
                    </div>
                <select style="width: 90%;height: 250;"  name="ai_send_notice_worker[]" id="ai_send_notice_worker" multiple="true">
                    <volist name="house_worker" id="voo">
                        <option value="{pigcms{$voo.wid}">姓名：{pigcms{$voo.name}，手机号：{pigcms{$voo.phone}<php>if($voo['openid'] && strlen($voo['openid'])>15){ echo '【已绑定微信】'; }else{ echo '【未绑定微信】'; }</php></option>
                    </volist>
                </select>
                    <div style="color:red;height: 60px;">
                     
                    </div>
                </td>
			</tr>
			<tr>
				<th width="15%"><span style="color:red;">*</span>发送消息Ai类型</th>
				<td ><select style="width: 80%;"  name="ai_send_type" id="ai_send_type">
                  <option value="1">AI设别巡检</option>
                  <option value="2">智能巡检 </option>
                  <option value="3">AI设别巡检 和 智能巡检 </option>
                </select></td>
			</tr>
		</table>
                <div class="space"></div>
                <div class="clearfix form-actions" style="margin-top: 30px;
    text-align: center;">
                    <div class="col-md-offset-3 col-md-9">
                        <button class="btn btn-info" type="submit" style="line-height: 2px;" onclick="submitAddWorkOrder()">
                            <i class="ace-icon fa fa-check bigger-110"></i>
                             保 存 
                        </button>
                    </div>
                </div>
		</div>
		
	</body>
    <script type="text/javascript">
            $('#ai_send_notice_worker').change(function(){
                var  selectedV=$(this).val();
                console.log('selectedV',selectedV);
            });

        var is_posting=false;
       function submitAddWorkOrder(){
           if(is_posting){
                layer.msg('正在为您生成工单，请稍等...');
                return false;
           }
           var postArr={};
            postArr.log_id="{pigcms{$record_log.id}";
            var f_cate_id=$('#f_cate_id').val()||0;
            f_cate_id=parseInt(f_cate_id);
            var cate_id=$('#cate_id').val()||0;
            cate_id=parseInt(cate_id);
            if(f_cate_id<1){
                layer.msg('请选择工单类目！');
                return false;
            }
            if(cate_id<1){
                layer.msg('请选择工单分类！');
                 return false;
            }
            postArr.f_cate_id=f_cate_id;
            postArr.cate_id=cate_id;
            is_posting=true;
            $.post("{pigcms{:U('add_work_order')}",postArr,function(result){
                console.log('result', result);
                if (result && result.err_code==0) {
                    layer.open({
                        content: '操作成功！',
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['确定']
                        ,yes:function(index){
                            window.parent.close_all_page(result);
                        }
                    })
                } else if (result.msg) {
                    window.is_posting=false;
                    layer.open({
                        content: result.msg,
                        shadeClose: false,
                        closeBtn: 0
                        ,btn: ['知道了']
                    })
                } else {
                    window.is_posting=false;
                    layer.open({content: '添加失败'});
                }
            },"JSON");
        }
		</script>
<include file="Publics:footer"/>