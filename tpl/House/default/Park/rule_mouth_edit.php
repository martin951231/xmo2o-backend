<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<div class="main-content">
	<!-- 内容头部 -->
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post"  id="add_info" action="__SELF__">
						<div class="tab-content">
                            <input type="hidden" value="{pigcms{$detail['id']}" name="id">
                            <div class="form-group">
                                <label class="col-sm-1"><font style="color: red">*</font><label for="">收费标准名称：</label></label>
                                <input class="col-sm-2" size="20" name="park_month_name" id="park_month_name" placeholder="收费标准名称" type="text" value="{pigcms{$detail['park_month_name']}" style="width: 45%;margin-left: -1.4%;"/>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><font style="color: red">*</font><label for="park_month_id">收费类型：</label></label>
                                <select name="park_month_id" style="margin-left: -8px;width: 15%" >
                                    <option value='0'>请选择</option>
                                    <volist name="park_month_type" id="vo">
                                        <option value="{pigcms{$vo.id}"  <if condition='$detail["park_month_id"] eq $vo["id"]'>selected="selected"</if>>{pigcms{$vo['month_name']}</option>
                                    </volist>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><font style="color: red">*</font><label for="park_month_num">停车缴费周期：</label></label>
                                <select name="park_month_num" style="margin-left: -8px;width: 15%" >
                                    <option value='0'>请选择</option>
                                    <option value='1' <if condition='$detail["park_month_num"] eq 1'>selected="selected"</if>>1个月</option>
                                    <option value='3' <if condition='$detail["park_month_num"] eq 3'>selected="selected"</if>>3个月</option>
                                    <option value='6' <if condition='$detail["park_month_num"] eq 6'>selected="selected"</if>>6个月</option>
                                    <option value='12' <if condition='$detail["park_month_num"] eq 12'>selected="selected"</if>>12个月</option>
                                    <option value='24' <if condition='$detail["park_month_num"] eq 24'>selected="selected"</if>>24个月</option>
                                    <option value='0' <if condition='!in_array($detail["park_month_num"],array(1,3,6,12,24))'>selected="selected"</if>>自定义</option>
                                </select>

                                <label style="display:none" <if condition='in_array($detail["park_month_num"],array(1,3,6,12,24))'>style="display:none"</if>>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="10" name="diy_park_month_num" <if condition='!in_array($detail["park_month_num"],array(1,3,6,12,24))'>value="{pigcms{$detail["park_month_num"]}"</if>/>&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">单位：月</span></label>
                            </div>
                            <div class="form-group" style="">
                                <label class="col-sm-1"><font style="color: red">*</font><label>停车缴费费用：</label></label>
                                <input type="text" size="20" name="park_month_price" value="{pigcms{$detail['park_month_price']}" placeholder="0.00" style="margin-left: -8px;width: 45%"/>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-1"><label for="presented_park_month_num">赠送停车时间：</label></label>
                                <select name="presented_park_month_num" style="margin-left: -2px;width: 15%">
                                    <option value='0'>请选择</option>
                                    <option value='1' <if condition='$detail["presented_park_month_num"] eq 1'>selected="selected"</if>>1个月</option>
                                    <option value='3' <if condition='$detail["presented_park_month_num"] eq 3'>selected="selected"</if>>3个月</option>
                                    <option value='6' <if condition='$detail["presented_park_month_num"] eq 6'>selected="selected"</if>>6个月</option>
                                    <option value='0' <if condition='!in_array($detail["presented_park_month_num"],array(1,3,6))'>selected="selected"</if>>自定义</option>
                                </select>
                                <label style="display:none" <if condition='in_array($detail["presented_park_month_num"],array(1,3,6))'>style="display:none"</if>>&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" size="6" name="diy_presented_park_month_num"  <if condition='!in_array($detail["presented_park_month_num"],array(1,3,6))'>value="{pigcms{$detail["presented_park_month_num"]}"</if>/>&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">单位：月</span></label>
                                &nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*可不填写</span>
                            </div>

                            <div class="form-group">
									<label class="col-sm-1" ><font style="color: red">*</font>状态：</label>
										<label style="padding-left:50px;padding-right:20px;"><input type="radio"  <if condition='$detail["status"] eq 1'>checked="checked"</if> class="ace" value="1" name="status"><span style="z-index: 1" class="lbl">开启</span></label>
										<label style="padding-left:0px;"><input type="radio" class="ace" value="0" <if condition='$detail["status"] eq 0'>checked="checked"</if> name="status"><span style="z-index: 1" class="lbl">关闭</span></label>
								</div>
						</div>
						<div class="space"></div>
                        <div class="clearfix form-actions">
                            <div class="col-md-offset-3 col-md-9">
                                <button class="btn btn-info" type="submit">
                                    <i class="ace-icon fa fa-check bigger-110"></i>
                                    编辑
                                </button>
                            </div>
                        </div>
					</form>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
function check_submit(){
	if(!$('select[name="park_month_num"]').val()){
		if(!$('input[name="diy_park_month_num"]').val()){
			alert('停车缴费周期不能为空！');
			return false;
		}
	}
}

$('#add_info').submit(function(){
    $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
        console.log('result', result);

        if (result && result.status==1) {
            layer.open({
                content: '编辑成功',
                shadeClose: false,
                closeBtn: 0
                ,btn: ['确定']
                ,yes:function(index){
                    window.parent.close_all_page(result,'mouth');
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
            layer.open({content: '编辑失败'});
        }
    });
    return false;
});


$('select').change(function(){
	if($(this).val() == 0){
		$(this).next('label').show();
	}else{
		$(this).next('label').hide();
	}
});

$('input[name="diy_type"]').each(function(i){
	$(this).click(function(){
		$('.diy_park_content').each(function(){
			$(this).hide();
		});
		$('.diy_park_content').eq(i).show();
	});
	
});
</script>

<include file="Public:footer"/>