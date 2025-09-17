<include file="Publics:header"/>
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<style>
	.page-content .btn-info { border: none;}
	.page-content .clearfix.form-actions {text-align: -webkit-center;}
</style>
<div class="page-content" style="background-color: white; width: 755px;">
	<div class="row" style="background-color: white;">
		<div class="col-xs-12">
			<form id="add_info" class="form-horizontal" method="post" action="__SELF__">
				<div class="tab-content">
                    <input type="hidden" value="{pigcms{$charge_info['id']}" name="id">
					<div class="form-group">
                        <label class="col-sm-1"><font style="color: red">*</font><label for="">收费标准名称：</label></label>
						<input class="col-sm-2" size="20" name="charge_name" id="charge_name" placeholder="收费标准名称" type="text" value="{pigcms{$charge_info['charge_name']}" style="width: 45%;margin-left: -1.4%;"/>
					</div>
                    <div class="form-group">
                        <label class="col-sm-1"><font style="color: red">*</font><label for="">收费类型：</label></label>
                        <select name="charge_type" style="width: 45%;margin-left: 2.8%;">
                            <option value="1" selected >二十四小时</option>
                       </select>
                    </div>
                    <div class="form-group" style="display: flex">
                        <label class="col-sm-1"><font style="color: red">*</font><label style="white-space: nowrap" for="">费用设置：</label></label>
                        <div style="margin-left: 3.3%;" id="charge_set_html">
                            <div style="margin-bottom: 10px;">
                                <input class="col-sm-2" size="20" name="charge_set[1][0]"  placeholder="请输入时长" type="text" value="{pigcms{$charge_info['charge_set'][0]['time']}" style="width: 20%;"/><span class="icon_rule" style="border-color: #d9d5d5;margin-right: 13px;">小时</span>
                                <input class="col-sm-2" size="20" name="charge_set[2][0]"  placeholder="请输入收费金额" type="text" value="{pigcms{$charge_info['charge_set'][0]['money']}" style="width: 25%;"/><span class="icon_rule" style="border-color: #d9d5d5">元</span>
                                <img width="29px" src="../static/images/add4.png" style="border: 1px solid;border-color: #c3c0c0;" onclick="add_charge_set()">
                            </div>
                            <if condition="$charge_info['charge_set1']">
                                <volist name="charge_info['charge_set1']" id="vo">
                                    <div style="margin-bottom: 10px;" class="time_input{pigcms{$i+1}">
                                        <input class="col-sm-2" size="20" name="charge_set[1][{pigcms{$i+1}]"  placeholder="请输入时长" type="text" value="{pigcms{$vo['time']}" style="width: 20%;"/>
                                        <span class="icon_rule" style="border-color: #d9d5d5;margin-right: 13px;">小时</span>
                                        <input class="col-sm-2" size="20" name="charge_set[2][{pigcms{$i+1}]"  placeholder="请输入收费金额" type="text" value="{pigcms{$vo['money']}" style="width: 25%;"/>
                                        <span class="icon_rule" style="border-color: #d9d5d5">元</span>
                                        <img width="29px" src="../static/images/del1.jpg" style="border: 1px solid;border-color: #c3c0c0;" onclick="del_charge_set({pigcms{$i+1})">
                                    </div>
                                </volist>
                            </if>
                            <div style="color: red;">1、设置时长，并设置时长收费标准<br>
                                2、车辆入场后超过设置时长，不超过时长收费金额，按时长收费标准收费<br>
                                3、未设置免费时间，车辆入场后按设置时长收费
                            </div>
                        </div>

                    </div>
					<div class="form-group">
						<label class="col-sm-1">免费时长:</label>
                        <input class="col-sm-2" size="20" name="free_time" id="free_time" placeholder="免费停车时长" type="text" value="{pigcms{$charge_info['free_time']}" style="margin-left: 6.4%;margin-right: -0.6%;width: 38.3%;"/>
                        <span class="icon_rule" style="border-color: #d9d5d5;margin-right: 20px;">分钟</span>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">封顶收费:</label>
                        <input class="col-sm-2" size="20" name="max_charge_money" id="max_charge_money" placeholder="最大收取费用" type="text" value="{pigcms{$charge_info['max_charge_money']}" style="margin-left: 6.4%;margin-right: -0.6%;width: 40%;"/>
                        <span class="icon_rule" style="border-color: #d9d5d5">元</span>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">首停单位:</label>
                        <input class="col-sm-2" size="20" name="first_free_time" id="first_free_time" placeholder="首停的免费时长" type="text" value="{pigcms{$charge_info['first_free_time']}" style="margin-left: 6.4%;margin-right: -0.6%;width: 38.3%;"/>
                        <span class="icon_rule" style="border-color: #d9d5d5;margin-right: 20px;">分钟</span>
                        <p style="margin: 5px 17% -5px;font-size: 13px;color: red">*首停单位为0时，不计算首停</p>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-1">首停收费:</label>
                        <input class="col-sm-2" size="20" name="first_charge_money" id="first_charge_money" placeholder="首停费用" type="text" value="{pigcms{$charge_info['first_charge_money']}" style="margin-left: 6.4%;margin-right: -0.6%;width: 40%;"/>
                        <span class="icon_rule" style="border-color: #d9d5d5">元</span>
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
		</div>
		</form>
	</div>
</div>
<script type="text/javascript" src="https://api.map.baidu.com/api?ak=4c1bb2055e24296bbaef36574877b4e2&v=2.0&s=1" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/map.js?t=01"></script>
<style>
    .icon_rule {
        color: #757575;
        border-radius: 0;
        padding: 5px 13px 9px 13px;
        background-color: #e7e7e7;
        border: 1px solid;
    }

    .icon_em{
        font-size: 20px;
        border: 1px solid;
        padding: 0px 8px 0px 8px;
    }
	input.ke-input-text {
		background-color: #FFFFFF;
		background-color: #FFFFFF!important;
		font-family: "sans serif",tahoma,verdana,helvetica;
		font-size: 12px;
		line-height: 24px;
		height: 24px;
		padding: 2px 4px;
		border-color: #848484 #E0E0E0 #E0E0E0 #848484;
		border-style: solid;
		border-width: 1px;
		display: -moz-inline-stack;
		display: inline-block;
		vertical-align: middle;
		zoom: 1;
	}
	.form-group>label{font-size:12px;line-height:24px;}
	#upload_ico_pic_box,#upload_hello_pic_box{margin-top:20px;height:100px;}
	#upload_ico_pic_box .upload_pic_li,#upload_hello_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
	#upload_ico_pic_box img,#upload_hello_pic_box img{width:100px;height:70px;}
</style>

<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script type="text/javascript" src="./static/js/date/WdatePicker.js"></script>
<script type="text/javascript">
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
				layer.open({content: '编辑失败'});
			}
		});
		return false;
	});
    var i=0;
    function add_charge_set(){
        i=i+1;
        var  html='<div style="margin-bottom: 10px;" class="time_input'+i+'"> <input class="col-sm-2" size="20" name="charge_set[1]['+i+']"  placeholder="请输入时长" type="text" value="" style="width: 20%;"/><span class="icon_rule" style="border-color: #d9d5d5;margin-right: 13px;">小时</span> <input class="col-sm-2" size="20" name="charge_set[2]['+i+']"  placeholder="请输入收费金额" type="text" value="" style="width: 25%;"/><span class="icon_rule" style="border-color: #d9d5d5">元</span> <img width="29px" src="../static/images/del1.jpg" style="border: 1px solid;border-color: #c3c0c0;" onclick="del_charge_set('+i+')"> </div>'
        $('#charge_set_html').prepend(html);
    }

    function del_charge_set(index) {
        $('.time_input'+index).remove();
    }

</script>
<include file="Publics:footer"/>
