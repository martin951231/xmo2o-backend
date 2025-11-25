<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
    .page-content .btn-info { border: none;}
    .page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white;">
        <div class="row" style="background-color: white;">
				<div class="col-xs-12">
                    <form id="add_info" class="form-horizontal" method="post" action="__SELF__">
						<input  name="village_id" type="hidden"  value="{pigcms{$_GET['village_id']}"/>
						<input  name="type" type="hidden"  value="{pigcms{$_GET['type']}"/>
						<input  name="department_id" type="hidden"  value="{pigcms{$_GET['department_id']}"/>
						<input  name="property_id" type="hidden"  value="{pigcms{$now_property['id']}"/>
						<div class="tab-content">
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">编号：</label></label>
								<input class="col-sm-2" size="20" name="job_number" id="job_number" placeholder="工号" type="text" value=""/>

							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">姓名：</label></label>
								<input class="col-sm-2" size="20" name="name" id="name" placeholder="" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1">性别:</label>

								<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="gender"><span style="z-index: 1" class="lbl">男</span></label>
								<label style="padding-left:0px;"><input type="radio" class="ace" value="2" name="gender"><span style="z-index: 1" class="lbl">女</span></label>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">手机号码：</label></label>
								<input class="col-sm-2" size="20" name="phone" id="phone" placeholder="" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">*必填项&nbsp;此手机号需与注册的手机号一致,才能进行开门及任务处理</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">身份证号：</label></label>
								<input class="col-sm-2" size="20" name="id_card" id="id_card" placeholder="" type="text" value=""/>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">&nbsp;填写后该用户可通过身份证进入门禁</span>
							</div>
							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">IC卡：</label></label>
								<input class="col-sm-2" size="20" name="ic_card" id="ic_card" placeholder="" type="text" value=""/>
								<button class="btn btn-info" type="button" style="margin-left:10px;display:none;" id="rfid_get_card" onclick="rfid_get_card_func()">
									<i class="ace-icon fa fa-check bigger-110"></i>
									RFID读卡已启动
								</button>
								&nbsp;&nbsp;&nbsp;&nbsp;<span class="red">可不填&nbsp;填写后该用户可通过IC卡进入门禁</span>
							</div>
<!--							<div class="form-group">-->
<!--								<label class="col-sm-1"><label for="housesize">微信号：</label></label>-->
<!--								<input class="col-sm-2" size="20" name="weixin_num" id="weixin_num" placeholder="请输入微信号" type="text" value=""/>-->
<!---->
<!--							</div>-->
							<div class="form-group">
								<label class="col-sm-1"><label for="property_starttime">入职时间:</label></label>
								<input type="text" name="job_create_time" class="col-sm-2" value=""  style="height:42px" onfocus="WdatePicker({isShowClear:true,readOnly:true,dateFmt:'yyyy-MM-dd'})" placeholder="入职时间"/>
							</div>
							<div class="form-group">
								<label class="col-sm-1">是否可以开门:</label>

								<label style="padding-left:0px;padding-right:20px;"><input type="radio" checked="" class="ace" value="1" name="open_door"><span style="z-index: 1" class="lbl">是</span></label>
								<label style="padding-left:0px;"><input type="radio" class="ace" value="0" name="open_door"><span style="z-index: 1" class="lbl">否</span></label>
							</div>

							<div class="form-group">
								<label class="col-sm-1"><label for="housesize">备注：</label></label>
								<input class="col-sm-2" size="20" name="note" id="note" placeholder="" type="text" value=""/>

							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										添加
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
    </div>


<script type="text/javascript">

    $('#add_info').submit(function(){
        $.post($('#add_info').attr('action'),$('#add_info').serialize(),function(result){
            console.log('result', result);
            if (result && result.status==1) {
                layer.open({
                    content: '添加成功！添加数据实时显示但只是填充显示，如需查看完善数据显示请刷新页面查看！',
                    shadeClose: false,
                    closeBtn: 0
                    ,btn: ['确定']
                    ,yes:function(index){
                        window.parent.close_all_page(result);
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
                layer.open({content: '添加失败'});
            }
        });
        return false;
    });


</script>
<if condition="$now_house['write_iccard']">
	<script language="javascript" src="http://127.0.0.1:8008/YOWOCloudRFIDReader.js"></script>
	<script language="javascript" src="{pigcms{$static_path}/js/YOWOReaderBase.js"></script>

	<script language="javascript" type="text/javascript">
		//demo  http://www.youwokeji.com.cn/CloudReader/Sample1.html
		rfidreader.onResult(function(resultdata) {
			console.log(resultdata);
			switch(resultdata.FunctionID) {
				//IC卡读卡器云服务版本号
				case 14:
					$('#rfid_get_card').show();
					console.log(resultdata.strData);
					break;
				case 0:
					if(resultdata.Result>0){
						$('#ic_card').val(resultdata.strData);		
					}
					break;
			}
		});

		function rfid_get_card_func(){
			var FormatID;
			var OrderID;
			
			FormatID = '1'; //1为10进制(10DEC) ，0为16进制(8HEX)
			OrderID = '0'; //0为正常 ，1为倒序
			rfidreader.Repeat=1;
			rfidreader.HaltAfterSuccess=1;
			rfidreader.RequestTypeACardNo(FormatID, OrderID);
		}
		setTimeout(function(){
			rfid_get_card_func();
		},1000);
	</script>
</if>
<include file="Publics:footer"/>
