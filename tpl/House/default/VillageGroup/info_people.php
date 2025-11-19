<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('User/index')}">小区管理</a>
            </li>
            <li class="active">添加小区</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <style>
		.line-span { line-height:34px;}
		.none { display:none}
	</style>
    <div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post" onSubmit="return check_submit()" action="__SELF__">
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">编号：</label></label>
								<input class="col-sm-2" size="20" name="job_number" id="job_number" placeholder="工号" type="text" value="{pigcms{$res.job_number}"/>

							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">姓名：</label></label>
								<input class="col-sm-2" size="20" name="name" id="name" placeholder="" type="text" value="{pigcms{$res.name}"/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1">性别:</label>

								<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$res.gender eq 1">checked="checked"</if>  class="ace" value="1" name="gender"><span style="z-index: 1" class="lbl">男</span></label>
								<label style="padding-left:0px;"><input type="radio" <if condition="$res.gender eq 2">checked="checked"</if> class="ace" value="2" name="gender"><span style="z-index: 1" class="lbl">女</span></label>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">手机号码：</label></label>
								<input class="col-sm-2" size="20" name="phone" id="phone" placeholder="" type="text" value="{pigcms{$res.phone}"/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项&nbsp;此手机号需与注册的手机号一致,才能开门成功</span>
							</div>
<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="housesize">身份证号：</label></label>-->
<!--								<input class="col-sm-2" size="20" name="id_card" id="id_card" placeholder="" type="text" value="{pigcms{$res.id_card}"/>-->
<!--								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">可不填&nbsp;填写后该用户可通过身份证进入门禁</span>-->
<!--							</div>-->
<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="housesize">IC卡：</label></label>-->
<!--								<input class="col-sm-2" size="20" name="ic_card" id="ic_card" placeholder="" type="text" value="{pigcms{$res.ic_card}"/>-->
<!--								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">可不填&nbsp;填写后该用户可通过IC卡进入门禁</span>-->
<!--							</div>-->
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">微信号：</label></label>
								<input class="col-sm-2" size="20" name="weixin_num" id="weixin_num" placeholder="请输入微信号" type="text" value="{pigcms{$res.weixin_num}"/>

							</div>
<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="property_starttime">入职时间:</label></label>-->
<!--								<input type="text" name="job_create_time" class="col-sm-2" value="{pigcms{$res.job_create_time|date='Y-m-d',###}"  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="入职时间"/>-->
<!--							</div>-->
							<div class="form-group">
								<label class="col-sm-1">是否可以开门:</label>

								<label style="padding-left:0px;padding-right:20px;"><input type="radio" <if condition="$res.open_door eq 1">checked="checked"</if> class="ace" value="1" name="open_door"><span style="z-index: 1" class="lbl">是</span></label>
								<label style="padding-left:0px;"><input type="radio" <if condition="$res.open_door eq 0">checked="checked"</if> class="ace" value="0" name="open_door"><span style="z-index: 1" class="lbl">否</span></label>
							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">备注：</label></label>
								<input class="col-sm-2" size="20" name="note" id="note" placeholder="" type="text" value="{pigcms{$res.note}"/>

							</div>
						</div>


						</div>

					</form>
				</div>
    </div>
</div>

<script type="text/javascript">


function check_submit(){

	

}


</script>
<include file="Public:footer"/>
