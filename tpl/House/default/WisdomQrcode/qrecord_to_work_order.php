<include file="Publics:header"/>
   <style>
   table tr{line-height: 50px;}
   </style>
	<body style="background-color: #fff;">
        <div style="margin-top: 15px;padding: 20px;">
        <input value="{pigcms{$record_log.id}" name="log_id" id="log_id" type="hidden" >
		<table style="width: 100%;">
			<tr>
				<th width="15%"><span style="color:red;">*</span>工单类目</th>
				<td ><select style="width: 80%;"  name="f_cate_id" id="f_cate_id">
                  <option value="0">请选择工单类目</option>
                    <volist name="workcates" id="voo">
                        <option value="{pigcms{$voo.id}">{pigcms{$voo.cate_name}</option>
                    </volist>
                </select></td>
			</tr>
			<tr>
				<th width="15%"><span style="color:red;">*</span>工单分类</th>
				 <td ><select style="width: 80%;"  name="cate_id" id="cate_id">
                  <option value="0">请选择工单分类</option>

                </select></td>
			</tr>

		</table>
                <div class="space"></div>
                <div class="clearfix form-actions" style="margin-top: 60px;
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
            $('#f_cate_id').change(function(){
                var  f_cate_id=$(this).val();
                console.log('f_cate_id',f_cate_id);
                $.post("{pigcms{:U('get_work_order_cate')}"+"&f_cate_id="+f_cate_id,function(ret){
                    var htmlTmp='<option value="0">请选择工单分类</option>';
                    if(ret.workcates && ret.workcates.length>0){
                        for(ii=0;ii<ret.workcates.length;ii++){
                            htmlTmp+='<option value="'+ret.workcates[ii].id+'">'+ret.workcates[ii].cate_name+'</option>';
                        }
                    }

                    $('#cate_id').html(htmlTmp);
                },'JSON');
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