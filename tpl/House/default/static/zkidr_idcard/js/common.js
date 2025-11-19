var issOnlineUrl = "http://127.0.0.1:24010/ZKIDROnline";
var browserFlag = getBrowserType();
var zkidrDownUrl = "{pigcms{$static_path}zkidr_idcard/middleware/ZKIDROnline.exe"
var zkidr_setting = {
	Cert : {
		callBack : function(result){
			setCertificateData(result);
		},
		select : "#button_readID"
	},
	Methods : {
		showMessage : function(type,message){
			// alert(type);
			// alert(message);
			// $("#cert_message").text(message);
			// $("#cert_message_type").text(msgType[type]);
		},
		downloadDrive : function(){
			$.jBox.closeTip();
			messageBox({messageType: "confirm", text: "请安装相关硬件驱动！点击确定下载驱动。", 
				callback: function(result){
					if(result)
					{
						window.location.href = zkidrDownUrl;
					}
					closeMessage();
			}});
		},
		detectSuccess: function(){
			$('#get_idcard').show();
		}
	}
}
function setCertificateData(result)
{
	console.log('result.Certificate', result.Certificate);
	$('#user_name').val(result.Certificate.Name);
	$('#id_card').val(result.Certificate.IDNumber);
	$("select[name='sex[value]']").val(result.Certificate.Sex);
	
	
	var birthday = result.Certificate.Birthday.replace(/\./g,"-");
	$("input[name='birthday[value]']").val(birthday.substr(0,4) + '-' + birthday.substr(4,2) + '-' + birthday.substr(6,2));
	$("input[name='household_register[value]']").val(result.Certificate.Address);
	$("select[name='nation[value]']").val(result.Certificate.Nation + "族");
	
	if(result.Certificate.CardType == '1'){
		$("select[name='nationality[value]']").val('中国');
	}
}

function getRandomNum() 
{
	var random = parseInt(Math.random() * 10000);
	return random;
}

//消息控件的使用类型的类
var msgType = 
{
	info : "info",
	success : "success",
	warning : "warning",
	error : "error",
	loading : "loading"
};

function getBrowserType()
{
	var browserFlag = "";
	 //是否支持html5的cors跨域
	if (typeof(Worker) !== "undefined")
	{
		browserFlag = "html5";
	}
	//此处判断ie8、ie9
	else if(navigator.userAgent.indexOf("MSIE 7.0")>0||navigator.userAgent.indexOf("MSIE 8.0")>0 || navigator.userAgent.indexOf("MSIE 9.0")>0)
	{
		browserFlag = "simple";
	}
	else
	{
		browserFlag = "upgradeBrowser";//当前浏览器不支持该功能,请升级浏览器
	}
	return browserFlag;
}

createISSonlineDevice(zkidr_setting);

function get_idcard_func(){
	new Device().startFun()
}