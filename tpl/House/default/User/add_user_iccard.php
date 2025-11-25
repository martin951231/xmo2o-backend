<include file="Public:header"/>
<div class="main-content">
	<!-- 内容头部 -->
	<div class="x-page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
                <form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form" action="">
					  <input type="hidden" id="bind_pigcms_id" name="bind_pigcms_id" value="{pigcms{$bind_pigcms_id}">
						<div class="tab-content" style="border:none;min-height: 310px;">
							<div class="tab-pane active">
								<div class="form-group">
									<label class="col-sm-2" style=""><label for="card_type">卡类型：</label></label>
                                        <select name="card_type" id="card_type" style="width: 200px" required>
                                         <option value="1" <if condition="$user_iccard['type'] lt 2">selected="selected"</if> >普通卡</option>
                                         <option value="2" <if condition="$user_iccard['type'] eq 2">selected="selected"</if> >胁迫卡</option>
                                        </select>
								</div>
                                <div class="form-group">
									<label class="col-sm-2" style=""><label for="ic_card_no">卡号：</label></label>
									<input class="col-sm-5" name="ic_card_no" id="ic_card_no" type="text" maxlength="32" value="{pigcms{$user_iccard['ic_card_no']}" oninput="this.value = this.value.replace(/[^0-9]/g, '');" autocomplete="off" placeholder="请输入卡号仅支持数字最多10位" maxlength="10" required <if condition="$user_iccard AND $user_iccard['ic_card_no']">disabled="true"</if> />
                                    &nbsp;&nbsp;<span>卡号一旦填写保存后不可以编辑修改</span>
								</div>
                                <if condition="$card_expire_time_type eq 1">
                                <div class="form-group" id="effective_time_set">
									<label class="col-sm-2" style=""><label for="start_time_str">卡有效期：</label></label>
									<input class="col-sm-3" name="start_time_str" id="start_time_str" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" type="text" value="{pigcms{$user_iccard['start_time_str']}" autocomplete="off" placeholder="请选择开始时间" maxlength="50" required/>
                                     <span style="float: left;margin: 5px 10px;">至 </span>
                                    <input class="col-sm-3" name="end_time_str" id="end_time_str" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" type="text" value="{pigcms{$user_iccard['end_time_str']}" autocomplete="off" placeholder="请选择结束时间"  maxlength="50" required/>
								</div>
                                </if>
                                <div class="clearfix" style="margin-top: 80px;margin-left: 270px;">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="button"  onclick="addICCard()" >
											<i class="ace-icon fa fa-check bigger-110"></i>
											保存
										</button>
									</div>
								</div>

							</div>
						</div>
					</form>
			</div>
		</div>
	 </div>
   </div>
</div>
<style type="text/css">

</style>

<script type="text/javascript">
    var bind_pigcms_id="{pigcms{$bind_pigcms_id}";
    var idd="{pigcms{$user_iccard['id']}";;
   function addICCard(){
        var posturl = '{pigcms{:U('add_user_iccard_action')}&bind_pigcms_id='+bind_pigcms_id+'&idd='+idd;
        var card_type=$('#card_type').val()||1;
        var ic_card_no=$('#ic_card_no').val()||'';
        ic_card_no=$.trim(ic_card_no);
        if(!ic_card_no){
             layer.alert('请填写卡号！');
             return false;
        }
        if(ic_card_no.length>10){
             layer.alert('卡号长度不能超过10位！');
             return false;
        }
        var postData={idd:idd,bind_pigcms_id:bind_pigcms_id,card_type:card_type,ic_card_no:ic_card_no};
        if($('#effective_time_set').size()>0){
            var start_time_str=$('#start_time_str').val()||'';
            start_time_str=$.trim(start_time_str);
            if(!start_time_str){
                layer.alert('请选择开始时间！');
                return false;
            }
            var end_time_str=$('#end_time_str').val()||'';
            end_time_str=$.trim(end_time_str);
            if(!end_time_str){
                layer.alert('请选择结束时间！');
                return false;
            }
            postData.start_time_str=start_time_str;
            postData.end_time_str=end_time_str;
        }
        
        $.post(posturl,postData,function(ret){
            if(ret.status<1){
                layer.alert(ret.info);
                return false;
            }else{
                layer.msg(ret.info);
                
                //parent.layer.close(index); //再执行关闭
                setTimeout(function(){
                    parent.location.reload();
                    var p_index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                    parent.layer.close(p_index);
                    //parent.location.href='{pigcms{:U('user_bind_iccard')}&bind_pigcms_id='+pigcms_id;
                   
                    // 刷新父弹框
                    parent.layer.iframeReload(null, {
                          title:''
                    });
                }, 2000 )
                
            }
		},'json');
   }
</script>


