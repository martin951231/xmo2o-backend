(function($) {

    var wsUri ="ws://127.0.0.1:10090/";
    var websocket = window._websocket = new WebSocket(wsUri);

    var app = {
        //设置连接成功或失败是的界面
        ws_rf_query:function (data) {
            $("div.sreen .icons i").removeClass("on").addClass("off");
            if (data.result.status) {
                $("div.sreen .icons i.c1").removeClass('off').addClass("on");
                $("div.sreen .code").html("连接成功");
                $("button.conn_btn").attr("disabled", true);
		$('span.dev_desc').html(data.result.desc);
            } else {
                $("div.sreen .icons i.c2").removeClass('off').addClass("on");
                $("div.sreen .code").html("未发现设备");
                $("button.conn_btn").attr("disabled", false);
		$('span.dev_desc').html("");
            }
        }
    };

    $(document).ready(function () {
        websocket.onopen = function(evt) {
            console.info(evt);

            //查询读卡器状态
	    websocket.send(JSON.stringify({method:"ws_rf_query"}));
	    //设置读卡器模式，auth: 0-不加密 1-加密模式  bytes：3-3字节模式 其他-全数据模式
	    websocket.send(JSON.stringify({method:"ws_rf_setup", auth:1, bytes: 3}));
        };

        websocket.onclose = function(evt) {
            console.info(evt);
        };

        websocket.onmessage = function(evt) {
            console.info(evt);

            var data = JSON.parse(evt.data);
            if("method" in data){
                $(document).trigger("/method/"+data.method, data);
            } else if("event" in data){
                $(document).trigger("/event/"+data.event, data);
            }
        };

        websocket.onerror = function(evt) {
            console.info(evt);
        };

        $(document).bind("/event/ws_card", function (evt, data) {
            console.info('4234',data);
            let s = data.data;
	    if (data.permit == -2) {
                s += " 加密失败";
	    } else if (data.permit == -3) {
                s += " 卡被复制";
	    }
            console.info('dghdkfgdf',s);
            $.post("http://www.group.com/index.php?g=Index&c=Index&a=test111",{'data':data},function(result){
                console.info('4234sssssss',result);
            },'json')
            $("div.sreen>.code").html(s);
        });

        $(document).bind("/method/ws_rf_query", function (evt, data) {
            app.ws_rf_query(data);
        });
    });
})(jQuery)