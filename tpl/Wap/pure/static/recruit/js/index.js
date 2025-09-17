var myScroll,now_page = 0,hasMorePage = true,isLoading = true;
// var isApp = motify.checkApp();
var now_area = 0;
var type = 'index';
	
// 页数
var page = 0;
// 每页展示10个
var pageSize = 10;   

$(function(){  
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
	if(user_long == '0'){
		getUserLocation({okFunction:'getListGeocoderbefore',okFunctionParam:[true],errorFunction:'getListGeocoderbefore',errorFunctionParam:[false]});
	}else{
		pageGetList(user_long,user_lat);
	}
});
function getListGeocoderbefore(type){
	if(type == true){
		if(motify.checkApp()&& motify.checkWeixinApp()==false){
			$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=pageGetList&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');
		}else{
			$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=pageGetList&coordtype=wgs84ll&location='+arguments[3]+','+arguments[2]+'&output=json&pois=1&jsoncallback=?');
		}
	}
}
function pageGetList(){
	getList();
} 
// 切换
var dropLoad = null;
var indexLoad = false;
var cityLoad = false;
var provinceLoad = false;
$(document).on("click",".listGroup>div",function(){
    
    $(".listGroup div").removeClass("active");
    $(this).addClass("active");
    now_page = 0;
    type = $(this).attr('data-type')
    console.log(type)
    $('#container1').children('.'+type).removeClass('hide').siblings().addClass('hide');
    $('.'+type).removeClass('hide');
    page = 0;
    if (dropLoad!=null) {
	    if (type == 'index') {
	        if (!indexLoad) {
	            dropLoad.unlock();
	            dropLoad.noData(false)
	        } else {
	            dropLoad.lock('down');
	            dropLoad.noData();
	        }
	    } else if (type == 'city') {
	        if (!cityLoad) {
	            dropLoad.unlock();
	            dropLoad.noData(false)
	        } else {
	            dropLoad.lock('down');
	            dropLoad.noData();
	        }
	    } else if (type == 'province') {
	        if (!provinceLoad) {
	            dropLoad.unlock();
	            dropLoad.noData(false)
	        } else {
	            dropLoad.lock('down');
	            dropLoad.noData();
	        }
	    }
	    dropLoad.resetload()
	}else{
		getList();
	}
})

function getList(){	
	dropLoad = $('#container1').dropload({
        scrollArea : window,
        loadDownFn : function(me){
            page++;
            // 拼接HTML
            var result = '';
            $.ajax({
                type: 'GET',
                url: ajax_company_list_url,
                data:{
                  'page':page,
                  'pageSize':pageSize,keyword:$('#keyword').val(),user_long:user_long,user_lat:user_lat,type:type,now_area:now_area
                },
                dataType: 'json',
                success: function(data){   
                    var arrLen = data.result.list.length;
                    if(arrLen > 0){
                        for(var i=0;i<arrLen;i++){
                            result+=' <a  data-url="'+company_details_url+'&company_id='+data.result.list[i].company_id+'"   class="flex border-t openUrl newWin">';
                            result+='   <div class="img_box">';
                            result+='       <img class="" src="'+data.result.list[i].image +'" alt="">';
                            result+='   </div>';
                            result+='   <div class="flex_one" style="min-width:0">';
                            result+='       <p class="group__title">'+data.result.list[i].company_name +'</p>';
                            result+='       <div class="flex">';
                            result+='           <div>';
                            result+='           <p class="group__descHd">'+data.result.list[i].address +data.result.list[i].detail +'</p>';
                            result+='           <p>在招职位:'+data.result.list[i].position_count;
                            var position_recommend = data.result.list[i].position_recommend;
                            if(position_recommend.length>0){
                            	for(var j=0;j<position_recommend.length;j++){
                            		result+='		<label class="yewuyuan link" data-url="'+position_detail_url+'&position_id='+position_recommend[j].position_id +'">'+position_recommend[j].name +'</label>';
                            	}
                            }
                            result+='           </p>';
                            result+='           </div>';
                            if (data.result.list[i].range) {
                            result+='           <p style="max-width: 34%;min-width:25%; text-align:right">'+data.result.list[i].range +'</p>';
                            }
                            result+='       </div>';
                            result+='   </div>';
                            result+='</a>';
                        }
                        if (type == 'index') {
					        indexLoad = true;
					    } else if (type == 'city') {
					        cityLoad = true;
					    } else if (type == 'province') {
					        provinceLoad = true;
					    }
                    }else{
                        me.lock(); // 锁定 
                        me.noData(); // 无数据
                    }
                    setTimeout(function(){
                        $('.'+type).append(result);
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

}


