var myScroll,now_page = 0,hasMorePage = true,isLoading = true;
var isApp = motify.checkApp();
var now_area = 0;

$(function(){
	if(isApp){
		$('header').hide();
	}
	$('#backBtn').click(function(){
		redirect(backUrl,'openLeftWindow');
	});
	
	
	$('#search-form').submit(function(){
		var keyword = $.trim($('#keyword').val());
		$('#keyword').val(keyword);
		$('#bindVillageBox,#villageBox,#bindFamilyBox').hide();
		now_page = 0;
		hasMorePage = true;
		upIcon.removeClass('noMore loading').show();
		getUserLocation({okFunction:'pageGetList',okFunctionParam:[true],errorFunction:'pageGetList',errorFunctionParam:[false]});
		return false;
	});

	
});


// 页数
var page = 0;
// 每页展示10个
var pageSize = 10;   
var dropLoad = $('#container1').dropload({
    scrollArea : window,
    loadDownFn : function(me){
        page++;
        // 拼接HTML
        var result = '';
        $.ajax({
            type: 'GET',
            url: ajax_course_index_list_url,
            data:{
              'page':page,
              'pageSize':pageSize,keyword:$('#keyword').val()
            },
            dataType: 'json',
            success: function(data){   
                var arrLen = data.result.list.length;
                if(arrLen > 0){
                    for(var i=0;i<arrLen;i++){
                        result+=' <a href="'+course_detail_url+'&course_id='+data.result.list[i].course_id+'" class="flex border-t">';
						result+='	<div class="img_box">';
                        result+='       <img class="" src="'+data.result.list[i].image +'" alt="">';
						result+='	</div>';
						result+='	<div class="flex_one" style="min-width:0">';
                        result+='       <p class="group__title">'+data.result.list[i].title +'</p>';
						result+='		<div class="flex">';
                        result+='           <p class="group__desc">'+data.result.list[i].nickname+'</p>';
                        result+='           <p style="max-width: 44%;min-width:35%">'+data.result.list[i].view_times +'</p>';
						result+='		</div>';
						result+='	</div>';
						result+='</a>';
                    }
                }else{
                    me.lock(); // 锁定
                    me.noData(); // 无数据
                } 
                setTimeout(function(){
                    $('.chackGroup').append(result);
                    me.resetload(); // 每次数据插入，必须重置
                })
                
            },
            error: function(xhr, type){
                // alert('Ajax error!');
                // 即使加载出错，也得重置
                me.resetload();
            }
        });
    }
});




function androidRefresh(ticket,device){
	if(ticket && device){
		$.post("./wap.php?c=Home&a=ajaxLogin", { ticket: ticket, 'Device-Id': device },function(){
			location.reload();
		});
	}
}

/*判断IOS方法调用*/
var iosCommon = {
	getVersion: function () {
		var reg = /versioncode=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '85';
		}else{
			return arr[1];
		}
	},
	iosFunction:function(paramStr, paramObj){
		var pack_version = iosCommon.getVersion();
		var str = parseFloat(pack_version) >= 1000
		if ( str == true) {
			window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
		}
		
	}
}