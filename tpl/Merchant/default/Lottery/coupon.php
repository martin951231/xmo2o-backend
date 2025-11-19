<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-empire"></i>
				<a href="{pigcms{$url}">{pigcms{:L_("微活动")}</a>
			</li>
			<li class="active">{pigcms{:L_("添加")}{pigcms{$tips}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tab-content">
						<div class="grid-view">
							<form enctype="multipart/form-data" class="form-horizontal" method="post">
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name"><span class="required" style="color:red;">*</span>{pigcms{:L_("关键词")}</label></label>
									<input type="hidden" value="1" name="type" />
									<input type="text" class="col-sm-3" id="keyword" name="keyword" value="<if condition="$lottery['keyword'] eq ''">{pigcms{$tips}<else/>{pigcms{$lottery.keyword}</if>" />
	  								<span class="form_tips">{pigcms{:L_("只能写一个关键词，用户输入此关键词将会触发此活动。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="contact_name"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动名称")}</label></label>
									<input type="text" class="col-sm-3" name="title" value="<if condition="$lottery['title'] eq ''">{pigcms{$tips}{pigcms{:L_("活动开始了")}<else/>{pigcms{$lottery.title}</if>" />
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="long_lat"><span class="required" style="color:red;">*</span>{pigcms{:L_("优惠券介绍")}</label></label>
									<textarea  class="col-sm-3" id="sttxt" name="sttxt"  style="height:125px" >{pigcms{$lottery.sttxt}</textarea>
									<span class="form_tips">{pigcms{:L_("中奖后,提示信息")}</span>
								</div>

								<div class="form-group">
									<label class="col-sm-1"><label for="adress"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动时间")}</label></label>
									<input type="text" class="hasDatepicker" id="statdate" value="<if condition="$lottery['statdate'] neq ''">{pigcms{$lottery.statdate|date="Y-m-d",###}</if>" onClick="WdatePicker()" name="statdate" />
									到
									<input type="text" class="hasDatepicker" id="enddate" value="<if condition="$lottery['enddate'] neq ''">{pigcms{$lottery.enddate|date="Y-m-d",###}</if>" name="enddate"  onClick="WdatePicker()"  />
								</div>
								
								<div class="form-group" style="margin-bottom:-35px;">
									<label class="col-sm-3"><label for="AutoreplySystem_img">{pigcms{:L_("活动开始图片")}</label></label>
								</div>
								<div class="form-group" style="width:417px;padding-left:140px;">
									<label class="ace-file-input">
										<input class="col-sm-4" id="ace-file-input" size="50" onchange="preview1(this)" name="starpicurl" type="file">
										<span class="ace-file-container" data-title="{pigcms{:L_("选择")}">
											<span class="ace-file-name" data-title="{pigcms{:L_("上传图片")}..."><i class=" ace-icon fa fa-upload"></i></span>
										</span>
									</label>
									<div><img style="width:417px;height:200px" id="img" src="<if condition="$lottery['starpicurl'] neq ''">{pigcms{$lottery['starpicurl']}<else />/static/images/activity-{pigcms{$activity}-start.jpg</if>"></div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="info">{pigcms{:L_("优惠券活动说明")}</label></label>
									<textarea  class="col-sm-3" id="info" name="info"  style="height:125px" ><if condition="$lottery['info'] eq ''">{pigcms{:L_("亲，请点击进入")}{pigcms{$tips}{pigcms{:L_("抽奖活动页面，祝您好运哦！")}<else/>{pigcms{$lottery.info}</if></textarea>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("兑换券使用说明")}</label></label>
									<textarea id="aginfo" class="col-sm-3" name="aginfo" style="height: 125px;">{pigcms{$lottery.aginfo}</textarea>
									<span class="form_tips">{pigcms{:L_("用户获取优惠券后显示的提示信息")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort"><span class="required" style="color:red;">*</span>{pigcms{:L_("活动结束公告主题")}</label></label>
									<input class="col-sm-3" id="endtite" value="<if condition="$lottery['endtite'] eq ''">{pigcms{$tips}{pigcms{:L_("活动已经结束了")}<else/>{pigcms{$lottery.endtite}</if>" name="endtite" type="text" />
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group" style="margin-bottom:-35px;">
									<label class="col-sm-3"><label for="AutoreplySystem_img">{pigcms{:L_("活动结束图片")}</label></label>
								</div>
								<div class="form-group" style="width:417px;padding-left:140px;">
									<label class="ace-file-input">
										<input class="col-sm-4" id="ace-file-inputend" size="50" onchange="preview2(this)" name="endpicurl" type="file">
										<span class="ace-file-container" data-title="{pigcms{:L_("选择")}">
											<span class="ace-file-name" data-title="{pigcms{:L_("上传图片")}..."><i class=" ace-icon fa fa-upload"></i></span>
										</span>
									</label>
									<div><img style="width:417px;height:200px" id="endimg" src="<if condition="$lottery['endpicurl'] neq ''">{pigcms{$lottery['endpicurl']}<else />/static/images/activity-{pigcms{$activity}-end.jpg</if>"></div>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="endinfo">{pigcms{:L_("活动结束说明")}</label></label>
									<textarea  class="col-sm-3" id="endinfo" name="endinfo"  style="height:125px" ><if condition="$lottery['endinfo'] eq ''">{pigcms{:L_("亲，活动已经结束，请继续关注我们的后续活动哦。")}<else/>{pigcms{$lottery.endinfo}</if></textarea>
									<span class="form_tips">{pigcms{:L_("换行请输入")}&lt;br&gt;</span>
								</div>
		
								<div class="form-group">
									<label class="col-sm-1"><label for="sort"><span class="required" style="color:red;">*</span>{pigcms{:L_("优惠券名称")}1</label></label>
									<input class="col-sm-3" name="fist" id="fist" type="text" value="{pigcms{$lottery.fist}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort"><span class="required" style="color:red;">*</span>{pigcms{:L_("优惠券数量")}</label></label>
									<input class="col-sm-1"  name="fistnums" id="fistnums" type="text" value="{pigcms{$lottery.fistnums}"/>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("优惠券名称")}2</label></label>
									<input class="col-sm-3" name="second" id="second" type="text" value="{pigcms{$lottery.second}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("优惠券数量")}</label></label>
									<input class="col-sm-1"  name="secondnums" id="secondnums" type="text" value="{pigcms{$lottery.secondnums}"/>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("优惠券名称")}3</label></label>
									<input class="col-sm-3" name="third" id="third" type="text" value="{pigcms{$lottery.third}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("优惠券数量")}</label></label>
									<input class="col-sm-1"  name="thirdnums" id="thirdnums" type="text" value="{pigcms{$lottery.thirdnums}"/>
								</div>
								
								
								<!-- div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("四等奖奖品设置")}</label></label>
									<input class="col-sm-3" name="four" id="four" type="text" value="{pigcms{$lottery.four}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("四等奖奖品数量")}</label></label>
									<input class="col-sm-1"  name="fournums" id="fournums" type="text" value="{pigcms{$lottery.fournums}"/>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("五等奖奖品设置")}</label></label>
									<input class="col-sm-3" name="five" id="five" type="text" value="{pigcms{$lottery.five}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("五等奖奖品数量")}</label></label>
									<input class="col-sm-1"  name="fivenums" id="fivenums" type="text" value="{pigcms{$lottery.fivenums}"/>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("六等奖奖品设置")}</label></label>
									<input class="col-sm-3" name="six" id="six" type="text" value="{pigcms{$lottery.six}"/>
									<span class="form_tips">{pigcms{:L_("请不要多于50字")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort">{pigcms{:L_("六等奖奖品数量")}</label></label>
									<input class="col-sm-1"  name="sixnums" id="sixnums" type="text" value="{pigcms{$lottery.sixnums}"/>
								</div-->
								
								<div class="form-group">
									<label class="col-sm-1"><label for="sort"><span class="required" style="color:red;">*</span>{pigcms{:L_("预计活动的人数")}</label></label>
									<input class="col-sm-1" size="10" name="allpeople" id="allpeople" type="text" value="{pigcms{$lottery.allpeople}"/>
									<span class="form_tips">{pigcms{:L_("预估活动人数直接影响抽奖概率：中奖概率 = 奖品总数")}/({pigcms{:L_("预估活动人数*每人抽奖次数")}) <br/>{pigcms{:L_("如果要确保任何时候都100%中奖建议设置为1人参加")}!<span class='red'>{pigcms{:L_("如果要确保任何时候都100%中奖建议设置为1人参加!并且奖项只设置一等奖")}.</span></span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="canrqnums"><span class="required" style="color:red;">*</span>{pigcms{:L_("每人最多允许抽奖次数")}</label></label>
									<input class="col-sm-1" size="10" name="canrqnums" id="canrqnums" type="text" value="{pigcms{$lottery.canrqnums}"/>
									<span class="form_tips">{pigcms{:L_("必须是数字")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="daynums">{pigcms{:L_("每天最多抽奖次数")}</label></label>
									<input class="col-sm-1" size="10" name="daynums" id="daynums" type="text" value="{pigcms{$lottery.daynums}"/>
									<span class="form_tips">{pigcms{:L_("必须小于总抽奖次数！ 0 为不限制 抽完总数就不能抽了! 可以抽奖天数 = 总数")}/{pigcms{:L_("每天抽奖次数")}!</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="parssword"><span class="required" style="color:red;">*</span>{pigcms{:L_("兑奖密码")}</label></label>
									<input class="col-sm-1" size="10" name="parssword" id="parssword" type="text" value="{pigcms{$lottery.parssword}"/>
									<span class="form_tips">{pigcms{:L_("消费确认密码长度小于15位")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="renamesn">SN{pigcms{:L_("码重命名为")}</label></label>
									<input class="col-sm-1" size="10" name="renamesn" id="renamesn" type="text" value="{pigcms{$lottery.renamesn}"/>
									<span class="form_tips">{pigcms{:L_("例如：CND码,充值密码,SN码 这个主意用于修改SN码的名称，不懂请别修改")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="renametel">{pigcms{:L_("手机号重命名")}</label></label>
									<input class="col-sm-1" size="10" name="renametel" id="renametel" type="text" value="{pigcms{$lottery.renametel}"/>
									<span class="form_tips">{pigcms{:L_("例如：QQ号,微信号,手机号等其他联系方式，不懂请别修改")}</span>
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("抽奖页面是否显示奖品数量")}</label></label>
									<div class="radio">
										<label>
											<input name="displayjpnums" value="0" type="radio" class="ace" <if condition="$lottery['displayjpnums'] eq 0" >checked</if>>
											<span class="lbl" style="z-index: 1">{pigcms{:L_("不显示")}</span>
										</label>
										<label>
											<input name="displayjpnums" value="1" type="radio" class="ace" <if condition="$lottery['displayjpnums'] eq 1" >checked</if>>
											<span class="lbl" style="z-index: 1">{pigcms{:L_("显示")}</span>
										</label>
									</div>										
								</div>
								
								<div class="form-group">
									<label class="col-sm-1"><label for="canrqnums">{pigcms{:L_("注册后才能参与")}</label></label>
									<div class="radio">
										<label>
											<input name="needreg" value="0" type="radio" class="ace" <if condition="$lottery['needreg'] eq 0" >checked</if>>
											<span class="lbl" style="z-index: 1">{pigcms{:L_("不需要先注册")}</span>
										</label>
										<label>
											<input name="needreg" value="1" type="radio" class="ace" <if condition="$lottery['needreg'] eq 1" >checked</if>>
											<span class="lbl" style="z-index: 1">{pigcms{:L_("需要先注册")}</span>
										</label>
									</div>										
								</div>				
								
								
								<if condition="$ok_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:blue;">{pigcms{$ok_tips}</span>				
									</div>
								</if>
								<if condition="$error_tips">
									<div class="form-group" style="margin-left:0px;">
										<span style="color:red;">{pigcms{$error_tips}</span>				
									</div>
								</if>
								<div class="clearfix form-actions">
									<div class="col-md-offset-3 col-md-9">
										<button class="btn btn-info" type="submit">
											<i class="ace-icon fa fa-check bigger-110"></i>
											{pigcms{:L_("保存")}
										</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
function preview1(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) { $('#img').attr('src', e.target.result);}
		reader.readAsDataURL(input.files[0]);
	}
}
function preview2(input){
	if (input.files && input.files[0]){
		var reader = new FileReader();
		reader.onload = function (e) { $('#endimg').attr('src', e.target.result); }
		reader.readAsDataURL(input.files[0]);
	}
}
</script>
<script type="text/javascript" src="/static/js/date/WdatePicker.js"></script>
<include file="Public:footer"/>