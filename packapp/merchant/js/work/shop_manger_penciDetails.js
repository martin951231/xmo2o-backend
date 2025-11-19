/**
 * Created by win7 on 2018-11-05.
 */
    mui.init();
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var ashop=common.getCache('ashop');
var ctuan=common.getCache('ctuan');
var byin=common.getCache('byin');
var discount_controler = 0;
common.http('Merchantapp&a=config',{'client':client,noTip:true}, function(data){
    discount_controler= data.discount_controler;
	if(!data.cash_alias_name){
		$('.pay_in_store').hide();
	}
}); 

var store_id= urlParam.store_id; 
 
common.http('WapMerchant&a=store',{'client':client,'store_id':store_id},function(data){ 
        laytpl($('#listWeek').html()).render(data.time_week_arr,function(html){ 
            $('.business_time').append(html);
        });
        data.time_week_arr.forEach(function(value,index){ 
            if(value.week_status == 1){ 
            $('.business_time .mui-card[data-id='+index+'] .mui-switch').addClass('mui-active');
            }
            
        }); 

        $('.timeManger>.mui-clearfix:eq(0) ul:eq(0) li h4').text(data.open_1);
        $('.timeManger>.mui-clearfix:eq(0) ul:eq(2) li h4').text(data.close_1);
        //时间遍历
        if(data.close_2!=data.open_2){
            var str='<div class="mui-clearfix">	<ul class="mui-pull-left date_btn"><li><sapn class="time_show">开始时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">'+data.open_2+'</h4> </li></ul><ul class="mui-pull-left"><li></li><li class="time_zhi">至</li></ul><ul class="mui-pull-left date_btn"><li><sapn class="time_show">停业时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">'+data.close_2+'</h4> </li></ul><ul class="mui-pull-right jianTime"><li> <i></i></li></ul></div>';
            $('.timeManger').append(str);
        }
        if(data.close_3!=data.open_3){
            var str='<div class="mui-clearfix">	<ul class="mui-pull-left date_btn"><li><sapn class="time_show">开始时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">'+data.open_3+'</h4> </li></ul><ul class="mui-pull-left"><li></li><li class="time_zhi">至</li></ul><ul class="mui-pull-left date_btn"><li><sapn class="time_show">停业时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">'+data.close_3+'</h4> </li></ul><ul class="mui-pull-right jianTime"><li> <i></i></li></ul></div>';
            $('.timeManger').append(str);

        } 

        mui('.mui-switch')['switch']()
}); 

//时间设置
(function($, doc){
    mui('.mui-content').on('tap','.date_btn',function(e){
        var timeValue=this.children[1].children[0].innerText;
        //console.log(timeValue);
        var me=this;
        var optionsJson = '{"type":"time","value":"2012-01-01 '+timeValue+'"}';
        var options = JSON.parse(optionsJson);
        // var id = this.getAttribute('id');
        var picker = new $.DtPicker(options);
        // picker.setSelectedValue("2012-01-01"+"08:00");
        picker.show(function(rs) {
            /*
             * rs.value 拼合后的 value
             * rs.text 拼合后的 text
             * rs.y 年，可以通过 rs.y.vaue 和 rs.y.text 获取值和文本
             * rs.m 月，用法同年
             * rs.d 日，用法同年
             * rs.h 时，用法同年
             * rs.i 分（minutes 的第二个字母），用法同年
             */
            $(me)[0].lastElementChild.children[0].innerText=rs.text;


            picker.dispose();
        });

    });
})(mui, document);

mui.init({
    swipeBack:true //启用右滑关闭功能 
});  

// 保存
$('.shop_keep').click(function(){  
    var time_week_arr=[]; 
    var len=$('.business_time .mui-card').length;
    var obj = {}; 
    for(var i=0;i<len;i++){  
        var h=i+1
        if($('.business_time .mui-card:nth-child('+h+') .mui-switch').hasClass('mui-active')){
            obj.week_status=1
        }else{
            obj.week_status=0
        }  
        var time_list=[];  
        var len1=$('.business_time .mui-card:nth-child('+h+') .timeManger .mui-clearfix').length;
        for(var j=0;j<len1;j++){
            var objtime_list={}  
            var k=j+1
            objtime_list.openTime=$('.business_time .mui-card:nth-child('+h+') .mui-clearfix:nth-child('+k+') .openTime').text();
            objtime_list.closeTime=$('.business_time .mui-card:nth-child('+h+') .mui-clearfix:nth-child('+k+') .closeTime').text(); 
            time_list.push(objtime_list);
        }
 
        obj.time_list=time_list; 
        time_week_arr.push(obj); 
    }  
    console.log(time_week_arr);
    common.http('WapMerchant&a=storeModify',{'client':client,'store_id':store_id,'time_week_arr':time_week_arr},function(data){
              
    });
})