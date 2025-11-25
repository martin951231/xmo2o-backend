<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>redirect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <script type="text/javascript">
    	function getJsBridge(){window._dsf=window._dsf||{};return{call:function(method,args,cb){var ret="";if(typeof args=="function"){cb=args;args={}}if(typeof cb=="function"){window.dscb=window.dscb||0;var cbName="dscb"+window.dscb++;window[cbName]=cb;args["_dscbstub"]=cbName}args=JSON.stringify(args||{});if(window._dswk){ret=prompt(window._dswk+method,args)}else{if(typeof _dsbridge=="function"){ret=_dsbridge(method,args)}else{ret=_dsbridge.call(method,args)}}return ret},register:function(name,fun){if(typeof name=="object"){Object.assign(window._dsf,name)}else{window._dsf[name]=fun}}}}window.dsBridge=getJsBridge();
	
		//调用U你家登录原生界面
		// window.O2O_SMALLPIG_JsInterface.login('{"SYSTEM_SIGN":"SMALL_PIG", "CALLBACK_URL":"{pigcms{$callback_url}"}');
		dsBridge.call("login", {"system_sign":"SMALL_PIG", "callback_url":"{pigcms{$callback_url}"});
    </script>
</head>
<body>
<div id="test"></div>
</body>
</html>