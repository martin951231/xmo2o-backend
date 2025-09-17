/**
 * Created by win7 on 2018-11-05.
 */
    mui.init();
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var ashop=common.getCache('ashop');
var ctuan=common.getCache('ctuan');
var byin=common.getCache('byin');
var discount_controler = 0;
var config = null
var tiandituGeocode = null
common.http('Merchantapp&a=config',{'client':client,noTip:true}, function(data){
    discount_controler= data.discount_controler;
    config = data
	if(!data.cash_alias_name){
		$('.pay_in_store').hide();
	}
    //天地图
    if(config.tianditu_web_key){
        $.getScript('https://api.tianditu.gov.cn/api?v=4.0&tk='+config.tianditu_web_key);

    }else if(!config.map_config || config.map_config == 'baidu'){
        $.getScript('https://api.map.baidu.com/api?v=2.0&ak='+config.baidu_map_ak+'&callback=initialize_map');
    }

    detail()
});

if(ashop){
	$('.ashop').text(ashop);
}else{
	$('.ashop').closest('div').hide();
}
if(ctuan){
	$('.ctuan').text(ctuan);
}else{
	$('.ctuan').closest('div').hide();
}
if(byin){
	$('.byin').text(byin);
}else{
	$('.byin').closest('div').hide();
}


var store_id=$.getUrlParam('store_id');
var status=$.getUrlParam('status');
var type=$.getUrlParam('type');
console.log(type)
//console.log(store_id);
var storeClass=[];//店铺分类
var citySerach="合肥";//根据城市搜索
var trade=[];//所有商圈
var tradeId="";//商圈ID
var long="",lat="";
var fid='',ziid='';
var sheng,shi,qu,quan;
var area_ids;
var moren_area_name="";
var j=1;
var pic_lens=1;
var cat_id='';
var back=false;
var app=true;
var searchBack=false;
var cityList=[];
var setting={
    sing_up:'',
    chackchange:true,
    hashchange:0,
    long:'',
    lat:"",
    adress:'',
    add_location:{},
    star:false,
    map_init:true
}
var bind_name = 'input';
var wh=$(window).height();
var h=wh-250-45+'px';
$("#searchMore").css('height',h);
$("#searchAdd").css("height",(wh-45)+'px')
var map=null,mk=null;
if (navigator.userAgent.indexOf("MSIE") != -1){ bind_name = 'propertychange' }
function trim(str){ //删除左右两端的空格
    return str.replace(/(^\s*)|(\s*$)/g, "");
}

// if(store_id!=0){

// }
function detail(){
    let queryParams = {
        'client':client,
        'store_id':store_id,
    }
    
    if(config.tianditu_web_key){
        queryParams.coordinate_type = 'wgs84'
    }
    
    common.http('Merchantapp&a=claim_store',queryParams,function(data){
        console.log(data);
        storeClass=data.catList;
        long=data.long||'';
        lat=data.lat||'';
        setting.lat=(data.lat && data.lat!=null && data.lat!='')?data.lat:'';
        setting.long=(data.long && data.long!=null && data.long!='')?data.long:'';
        setting.adress=(data.adress && data.adress!=null && data.adress!='')?data.adress:'';
        fid=data.cat_fid;
        console.log(fid);
        ziid=data.ziid;
        sheng=data.province_id;
        shi=data.city_id;
        qu=data.area_id;
        area_ids=data.area_id;
        quan=data.circle_id;
        $('#province_id').val(data.province_id);
        $('#city_id').val(data.city_id);
        if(data.circle_id!=0){
            $('#trade').show();
        }
        moren_area_name=data.area_name;
        if(store_id!=0){
            if(data.area_id!=0){
                common.http('WapMerchant&a=ajaxCircle',{'client':client,'id':data.area_id},function(rel){
                    //console.log(rel);
                    if(rel==null){
                        document.getElementById('trade').style.display="none";
                        mui.toast('该地区下还没有商圈哦!');
                    }else{
                        trade=[];
                        for(var i=0;i<rel.length;i++){
                            var type_card={'value':'','text':''};
                            type_card.value=rel[i].area_id;
                            type_card.text=rel[i].area_name;
                            trade.push(type_card);
                        }
                        document.getElementById('trade').style.display="block";
    
                    }
    
    
    
    
                });
            }else{
    
            }
        }else{
    
        }
    
        if(data.cat_fname!=undefined){
            $('#change_text').text(data.cat_fname+" "+data.cat_name).css('color','#333');
        }else{
            $('#change_text').text('请选择店铺分类');
        }
    
        if(data.province_name!=undefined){
            $('#change_address').text(data.province_name+" "+data.city_name+" "+data.area_name).css('color','#333');
            if(data.city_name!=undefined){
                citySerach=data.city_name
            }
        }else{
            $('#change_address').text("请选择店铺所在地");
        }
        if(data.circle_id!="0"){
            console.log(store_id);
            if(store_id!=0){
                $('#tradeAddress').text(data.circle_name).css('color','#333');
                $('#trade').show();
            }else{
                $('#tradeAddress').text("请选择店铺所在商圈");
                $('#trade').hide();
            }
        }else{
            $('#tradeAddress').text("请选择店铺所在商圈");
            $('#trade').hide();
        }
    
        $('.shop_name input').val(data.name);
        $('.txt_info').val(data.txt_info);
        $('.uesrPhone').val(data.phone);
        $('.kePhone').val(data.kefu_phone);
        data.ismain==1?$('.ismain').addClass('mui-active'):$('.ismain').removeClass('mui-active');
        data.have_group==1?$('.have_group').addClass('mui-active'):$('.have_group').removeClass('mui-active');
        data.have_meal==1?$('.have_meal').addClass('mui-active'):$('.have_meal').removeClass('mui-active');
        data.have_shop==1?$('.have_shop').addClass('mui-active'):$('.have_shop').removeClass('mui-active');
        $('.consumption').val(data.permoney);//人均消费
        if(setting.lat!=''){
            $('#addressText').text(setting.lat+','+setting.long);
        }
        $(".adress_detail").text(setting.adress);
    
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
        // 渲染图片
        var sum_pic='';
        var pic_len=data.pic.length;
        pic_lens=pic_len;
        $.each(data.pic,function(i,val){
            sum_pic+='<div class="mui-pull-left delate_photo" ><img src='+val.url+' data-url='+val.title+' data-preview-src="" data-preview-group="1"/><i></i></div>';
        });
        $('.showImg').append(sum_pic);
        $('.showImg').width(105*(pic_len+1)+150);
        // 加载优惠方式
        if(data.discount_txt.discount_type==0){
            $('.discountType>div:eq(0) input').attr('checked','checked');
        }else if(data.discount_txt.discount_type==1){
            $('.discountType>div:eq(2) input').attr('checked','checked');
            $('.discount_percent').val(data.discount_txt.discount_percent);
        }else if(data.discount_txt.discount_type==2){
            $('.discountType>div:eq(4) input').attr('checked','checked');
            $('.condition_price').val(data.discount_txt.condition_price);
            $('.minus_price').val(data.discount_txt.minus_price);
        }
        //加载vip优惠
        if(data.vip_discount_type==2){
            $('.VipDiscountType>div:eq(0) input:eq(0)').attr('checked','checked');
        }else if(data.vip_discount_type==1){
            $('.VipDiscountType>div:eq(0) input:eq(1)').attr('checked','checked');
        }
        if(discount_controler == 1){
            $("#shap_manger .discountType").find("input").attr("disabled","disabled");
            $("#shap_manger .VipDiscountType").find("input").attr("disabled","disabled");
        }
        //map()
        if(common.checkIosApp()){
            common.iosFunction('getlocation/locationOk', {name: 'getlocation', callback: 'locationOk'});
        }else if(common.checkAndroidApp()){
            window.pigcmspackapp.getlocation('locationOk');
        }else{
            app=false
        }
    
    
    
    });
}

function locationOk(name,lng,lat){
    setting.add_location={
        lng:lng,
        lat:lat
    }
}
common.http('Merchantapp&a=cityList',{}, function(data){
    cityList=data;
});

// 保存按钮点击
mui('.mui-content').on('tap','.shop_keep',function(e){
    var name=$('.shop_name input').val();//店铺名称
    var txt_info=$('.txt_info').val();//店铺详细描述
    var phone=$('.uesrPhone').val();//联系电话
    var kefu_phone=$('.kePhone').val();//客服电话
    var pic=[];//上传图片
    $.each($('.showImg .delate_photo'),function(i,val){
        pic.push($(this).find('img').data('url'));
    });
    var  ismain=$('.ismain').is('.mui-active')?1:0;
    var have_group=$('.have_group').is('.mui-active')?1:0;
    var have_meal=$('.have_meal').is('.mui-active')?1:0;
    var have_shop=$('.have_shop').is('.mui-active')?1:0;
    // 营业时间
    var length=$('.timeManger>.mui-clearfix').length;
    console.log(length);
    var close_1="",close_2="",close_3="",open_1="",open_2="",open_3="";
    open_1=$('.timeManger>.mui-clearfix:eq(0) ul:eq(0) li h4').text();
    close_1=$('.timeManger>.mui-clearfix:eq(0) ul:eq(2) li h4').text();
    if(length==2){
        open_2=$('.timeManger>.mui-clearfix:eq(1) ul:eq(0) li h4').text();
        close_2=$('.timeManger>.mui-clearfix:eq(1) ul:eq(2) li h4').text();

    }else if(length==3){
        open_2=$('.timeManger>.mui-clearfix:eq(1) ul:eq(0) li h4').text();
        close_2=$('.timeManger>.mui-clearfix:eq(1) ul:eq(2) li h4').text();
        open_3=$('.timeManger>.mui-clearfix:eq(2) ul:eq(0) li h4').text();
        close_3=$('.timeManger>.mui-clearfix:eq(2) ul:eq(2) li h4').text();
    }
    var province_id=$('#province_id').val();
    var city_id=$('#city_id').val();
    var area_id=$('#area_id').val();
    var cat_fid=$('#change_text').data('fid');

    var cat_id=$('#change_text').data('id');
    var permoney=$('.consumption').val();//人均消费
    var adress=$('.adress_detail').val();
    if(adress ==''){
        adress='';
    }
    //优惠方式
    var discount_percent="",discount_type=0,condition_price="",minus_price="";
    // var discount_percent="",discount_type,condition_price="",minus_price="";
    if($('.discountType .a1 input').is(':checked')){
        discount_type=0;
    }else if($('.discountType .a2').is(':checked')){
        discount_type=1;
        discount_percent=$('.discount_percent').val();
    }else {
        discount_type=2;
        condition_price=$('.condition_price').val();
        minus_price=$('.minus_price').val();
    }
    //vip优惠
    var vip_discount_type='';
    if($('.VipDiscountType input:eq(0)').is(':checked')){
        vip_discount_type = 2;
    }else if($('.VipDiscountType input:eq(1)').is(':checked')){
        vip_discount_type = 1;
    }

    var as_text=$('#change_text').text();
    if(name==''){
        mui.toast('店铺名称必填');
    }else{


        if(txt_info==''){
            mui.toast('店铺描述必填');
        }else{
            if(as_text=='请选择店铺分类'){
                mui.toast('请选择店铺分类')
            }else{
                if(permoney==''){
                    mui.toast('人均消费必填');
                }else{
                    if(phone==''){
                        mui.toast('联系电话必填');
                    }else{
                        if(kefu_phone==0){
                            mui.toast('客服电话必填');
                        }else{
                            /*if(discount_percent!='' ){
                             if(isNaN(discount_percent)){
                             mui.toast('折扣价格应输入');
                             return false

                             }

                             }*/
                            if($("#addressText").html()=='请选择店铺地址'){
                                mui.toast('请选择店铺地址');
                                return false
                            }
                            var l=$("#addressText").html().split(",");
                            var long_lat=l[1]+','+l[0];
                            let queryParams = {'client':client,'store_id':store_id,"name":name,'txt_info':txt_info,'phone':phone,'kefu_phone':kefu_phone,'pic':pic,'ismain':ismain,'have_group':have_group,'have_meal':have_meal,'have_shop':have_shop,'close_1':close_1,'close_2':close_2,'close_3':close_3,'open_1':open_1,'open_2':open_2,'open_3':open_3,'province_id':province_id,'city_id':city_id,'area_id':area_ids,'circle_id':tradeId,'cat_fid':cat_fid,'cat_id':cat_id,'permoney':permoney,'adress':adress,'discount_type':discount_type,'discount_percent':discount_percent,'condition_price':condition_price,'minus_price':minus_price,'long_lat':long_lat,'status':status,'vip_discount_type':vip_discount_type}
                            if(config.tianditu_web_key){
                                queryParams.coordinate_type = 'wgs84'
                            }
                            common.http('Merchantapp&a=storeModify',queryParams,function(data){
                                console.log(data);
                                if(data.length==0){
                                    mui.toast("保存成功");
                                    if(common.checkApp()){
                                        setTimeout(function(){
                                            if(common.checkAndroidApp()){
                                                window.pigcmspackapp.closewebview(2);
                                            }else{
                                                common.iosFunction('closewebview/2', {name:'closewebview', type: '2'});
                                            }
                                        },2000);
                                    }else{
                                        setTimeout(function(){
                                            history.go(-1);
                                            document.execCommand('Refresh');
                                        },2000);
                                    }

                                }
                            });
                        }

                    }
                }
            }

        }
    }

});
var result='';
var validate=function(res,is_global){
    result=res.replace(/(^\s+)|(\s+$)/g,"");
    if(is_global.toLowerCase()=="g") {
        if(result==''){
            return false
        }else{
            return true
        }
    }
}
// 搜索框动态搜索
if(!common.isMobile()){
    $('#editmap_id').bind(bind_name ,function(){
        var val=$(this).val();
        $(".pt_45").css("padding-top",'45px')
        if(validate(val,'g')){
            $(".close_map").hide();
            $("#searchAdd").html("");
            $(".remove_input").text("取消");
            back=true;
            setTimeout(function(){
                search(result,false);
            },500)


        }
    })
}else{
    $('#myform').bind('search', function (e) {
        $(".pt_45").css("padding-top",'45px')
        var val=$(this).val();
        if(validate(val,'g')){
            $(".close_map").hide();
            $("#searchAdd").html("");
            $(".remove_input").text("取消");
            back=true;
            setTimeout(function(){
                search(result,false);
            },500)


        }
    });

    function loadData(){
        $("#shap_manger").hide();
        $("#map_baidu").show();
        $(".close_map").hide();
        $("#searchAdd").html("");
        $(".remove_input").text("取消");
        back=true;
        search(result,false);
        $('#editmap_id').blur()
    }

}





$('#txtAddress').keyup(function(){
    var address=$(this).val();
    search(address);
});
mui('body') .on('tap','.remove_input',function(e){
    if(back){
        $('#searchAdd').hide().html('');
        $('.remove_input').html('完成');
        $("#editmap_id").val("");
        $(".close_map").show();
    }else{
        lat=setting.lat;
        long=setting.long;
        if(setting.long!=''){
            $('#addressText').text(setting.lat+','+setting.long);
        }
        $(".adress_detail").text(setting.adress);
        back_star()
    }
});
function back_star(){
    $("#map_baidu").hide();
    $("#shap_manger").show();
    $(".close_map").show();
    $("#searchAdd").html("").hide();
    $("#editmap_id").val('')
}
function search(address,type,city){
    var post_url = requestImgUrl+'/index.php?g=Index&c=Map&a=suggestion&query='+address+'&city_limit=1';
    if(config.tianditu_web_key){
        const {lat,lng} = map.getCenter()
        post_url = post_url + `'&coordinate_type=wgs84&lat=${lat}&lng=${lng}`};
    }
    $.get(post_url,{}, function(data) {
            var res=data;
            var datas=[];
            if(typeof(res)!='object'){
                res=JSON.parse(res)
            }
            if(res && res.length && res.length>0){
                datas=res;

            }else{
                if(res.status == 1){
                    if(res.result && res.result.length>0){
                        datas=res.result
                    }
                }
            }
            var str='';
            if(type){

                laytpl(document.getElementById('add').innerHTML).render(datas, function(html){
                    $('#searchMore').html(html).show();
                    back=false;

                });
            }else{
                laytpl(document.getElementById('show_add').innerHTML).render(datas, function(html){
                    $('#searchAdd').html(html).show();
                    back=true;

                });

            }
            if(common.checkIosApp()){
                $("#searchAdd").css("padding-top",'20px')
            }

        }

    )





}



//地址点击
mui('.mui-content').on('tap','.shop_adress',function(e){
    $(".close_map").removeClass("mr_t45");
    $("#shap_manger").hide();
    $('#map_baidu').show();
    if(!setting.star){
        open_map();
    }else{
        $(".close_map").addClass("mr_t45")
    }


});
//显示地图
function open_map(fun) {
    var  lats='';
    var lngs='';
    if(app){
        lats=setting.add_location.lat||'';
        lngs=setting.add_location.lng||''
    }else{
        lats=setting.lat;
        lngs=setting.long
    }
    var window_w=$(window).width();
    $("#searchMore").css({
        'top':window_w-29+'px',
        'left':'0px',
        'right':'0px'
    })
    $("#map_baidu").show();
    $("#shap_manger").hide();

    if(config.tianditu_web_key){
        //创建对象
        tiandituGeocode = new T.Geocoder();
        map = new T.Map('map_show');
        map.centerAndZoom(new T.LngLat(116.331398,39.897445),6);
        map.addEventListener("dragend", function(e){
			var centerMap = map.getCenter();
			getPositionInfo(centerMap.lat,centerMap.lng);
		});
        map.addEventListener('zoomend', function (e) {
			var centerMap = map.getCenter();
			getPositionInfo(centerMap.lat,centerMap.lng);
		});
        if(lngs == '' || lats == '' || Math.abs(lngs) == 0 || Math.abs(lats) == 0){
			var geolocation = new T.Geolocation();
			geolocation.getCurrentPosition(function(r){
				if (this.getStatus() == 0){
                    map.centerAndZoom(r.lnglat, 15)
                    var marker = new T.Marker(r.lnglat);
                    map.addOverLay(marker);

                }else if(this.getStatus() == 1){
                    map.centerAndZoom(r.lnglat, r.level)
                    var marker = new T.Marker(r.lnglat);
                    map.addOverLay(marker);
                }
			},{enableHighAccuracy: true})

		}else{
			var oPoint = new T.LngLat(lngs, lats);
			map.centerAndZoom(oPoint, 16);
			getPositionInfo(lats,lngs);
		}
    }else if(!config.map_config || config.map_config == 'baidu'){
    map = new BMap.Map("map_show",{"enableMapClick":false});
    map.enableScrollWheelZoom();
    map.enableContinuousZoom();
    $(".close_map").addClass("mr_t45")
    map.centerAndZoom(new BMap.Point(116.331398,39.897445),6);
    map.addEventListener("dragend", function(e){
        var centerMap = map.getCenter();
        getPositionInfo(centerMap.lat,centerMap.lng);
    });
    map.addEventListener('zoomend', function (e) {
        console.log(e)
        var centerMap = map.getCenter();
        console.log(centerMap)
        getPositionInfo(centerMap.lat,centerMap.lng);
    });
    console.log(type)
    if(lngs == '' || lats == ''|| type==0){
        var geolocation = new BMap.Geolocation();
        geolocation.getCurrentPosition(function(r){
            if(this.getStatus() == BMAP_STATUS_SUCCESS){
                var oPoint = new BMap.Point(r.point.lng, r.point.lat);
                map.centerAndZoom(oPoint,16);

                getPositionInfo(r.point.lat,r.point.lng);

            } else {
                mui.alert('failed'+this.getStatus());
            }
        },{enableHighAccuracy: true})

    }else{
        var oPoint = new BMap.Point(lngs, lats);
        map.centerAndZoom(oPoint, 16);
        getPositionInfo(lats,lngs);
    }
   }

}
function getPositionInfo(lat,lng){
    if(config.tianditu_web_key){
        $.post(`/v20/public/index.php/common/api.BaiduMap/reverseGeocoding`,{lng,lat,coordinate_type:'wgs84'},function(res){
			if(res.status == 1000){
				getPositionAdress(res.data)
			}
		})
    }else{
    $.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&location='+lat+','+lng+'&output=json&pois=1&callback=getPositionAdress&json=?');
    }
}
function localSearchResult(result){
    //根据返回类型解析搜索结果
    let pois = result.getPois() || []
    pois = pois.map(v=>{
        var lnglatArr = v.lonlat.split(',')
        return {
            point:  {
                x:lnglatArr[0], 
				y:lnglatArr[1], 
            },
            name: v.name,
			addr: v.address
        }
    })
    let obj = {
        status: 0,
        result:{
            location:  {
                lng: '', 
				lat: '', 
            },
            pois,
        },
    }
    getPositionAdress(obj)
}
function getPositionAdress(result){
    console.log(result)
    if(result.status == 0){
        var res = result.result;
        laytpl(document.getElementById('add').innerHTML).render(res.pois, function(html){
            $('#searchMore').html(html).show();
            if(config.tianditu_web_key){
                setting.lat=res.location.lat;
                setting.long=res.location.lon;
                setting.adress=res.addressComponent.address;
                if(setting.map_init){
                    map.centerAndZoom(new T.LngLat(setting.long,setting.lat),16);
                    setting.map_init=false
                }
            }else{
                setting.lat=res.pois[0].point.y;
                setting.long=res.pois[0].point.x;
                setting.adress=res.pois[0].name;
                if(setting.map_init){
                    map.centerAndZoom(new BMap.Point(res.pois[0].point.x,res.pois[0].point.y),16);
                    setting.map_init=false
                }
            }
            back=false;
            setting.star=true;
        });



    }else{
        alert('获取位置失败！');
    }
}
function setPlace(lat,lng) {
    if(back){
        getPositionInfo(lat,lng)
    }else{
        if(config.tianditu_web_key){
            map.centerAndZoom(new T.LngLat(lng,lat),16);
        }else{
            map.centerAndZoom(new BMap.Point(lng,lat),16);
        }
    }


}
//选择地址
mui('body') .on('tap','.allAddress li',function(e){
    $("#editmap_id").blur();
    $(".allAddress li .mui-left").removeClass("adres_check").addClass("adres_no_check");
    $(this).find(".mui-left").removeClass("adres_no_check").addClass("adres_check");
    var $_lat=setting.lat=$(this).data('lat');
    var $_long=setting.long=$(this).data('long');
    citySerach=$(this).data('city');
    var search_name=setting.adress=$(this).find('.recommend').text();
    if(!back){
        setting.map_init=false;
        setPlace($_lat,$_long)
        $('#searchAdd').hide().html('');
    }else{
        setting.map_init=true;
        $('#searchAdd').hide().html('');
        $('.remove_input').html('完成');
        $("#editmap_id").val("");
        $(".close_map").show();
        $('#searchMore').show().html('');
        setPlace($_lat,$_long)

    }

});
//关闭按钮点击
$('body #middlePopover p i.mui-pull-right').click(function(e){
    mask.close();
    mui('#middlePopover').popover('hide');
});

mui('body').on('tap','.mui-backdrop',function(e){
    mask.close();
    mui('#middlePopover').popover('hide');
});

// 上传图片
function imgUpload(id){
    var index = layer.open({type:2,shade:false,shadeClose:false});
    var fullPath=window.document.location.href;
    var pathName=window.document.location.pathname;
    var pos=fullPath.indexOf(pathName);
    var localhostPath=fullPath.substring(0,pos);
    var post_url = localhostPath+'/appapi.php?c=WapMerchant&a=uploadStoreImage';
    // console.log(post_url);
    $.ajaxFileUpload({
        url:post_url,
        secureuri:false,
        fileElementId:id,
        dataType: 'json',
        type: 'post',
        // data : {'wxcard_img' : 1},
        success: function(data, status){
            //console.log(data);
            var str='<div class="mui-pull-left delate_photo" ><img src='+data.result.url+' data-url='+data.result.title+' data-preview-src="" data-preview-group="1"/><i></i></div>';
            $('.showImg').append(str);
            layer.close(index);

        },
        error: function(data, status, e){
            //  alert(e);
            //alert(111);
            layer.close(index);
            mui.toast('上传失败');
        }
    });
}
// 上传图片
$('.up_img').off('change','input').on('change','input',function(e){
    var id=$(this).attr('id');
    var length=$('.showImg .delate_photo').length;
    if(pic_lens<10){
        pic_lens++;
        console.log(pic_lens);
        imgUpload(id);
        $('.showImg').width(105*(pic_lens+1)+150);
    }else{
        mui.toast('最多只能上传10张图片');
    }

});

mui('.left_srcoll').scroll({
    scrollY:false,
    scrollX:true,
    startX:0,
    startY:0,
    indicators:false,
    deceleration:0.0005,
    bounce:true
});
mui('.top_scroll').scroll({
    deceleration:0.0005
});
mui.previewImage();
mui.init({
    swipeBack:true //启用右滑关闭功能
});
// 进去页面清空所有input框内数据
$('input').val('');
var mask = mui.createMask();   //遮罩层





// 添加时间点击
mui('.mui-content').on('tap','.addTime',function(e){
    var length=$('.timeManger .mui-clearfix').length;
    if(length<3){
        var str='<div class="mui-clearfix">	<ul class="mui-pull-left date_btn"><li><sapn class="time_show">开始时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">00:00</h4> </li></ul><ul class="mui-pull-left"><li></li><li class="time_zhi">至</li></ul><ul class="mui-pull-left date_btn"><li><sapn class="time_show">停业时间</sapn></li><li> <h4  data-options={"type":"time"} class="btn time_active">00:00</h4> </li></ul><ul class="mui-pull-right jianTime"><li> <i></i></li></ul></div>';
        $('.timeManger').append(str);
    }else{
        mui.toast("最多只能选择3个时间段");
    }

    // (function($, doc){
    // 	var btns = $('.date_btn');
    // 	btns.each(function(i, btn) {
    // 		btn.addEventListener('tap', function() {
    // 			var me=this;
    // 			var optionsJson = '{"type":"time"}';
    // 			var options = JSON.parse(optionsJson);
    // 			// var id = this.getAttribute('id');
    // 			var picker = new $.DtPicker(options);
    // 			picker.show(function(rs) {

    // 				 * rs.value 拼合后的 value
    // 				 * rs.text 拼合后的 text
    // 				 * rs.y 年，可以通过 rs.y.vaue 和 rs.y.text 获取值和文本
    // 				 * rs.m 月，用法同年
    // 				 * rs.d 日，用法同年
    // 				 * rs.h 时，用法同年
    // 				 * rs.i 分（minutes 的第二个字母），用法同年

    // 				$(me)[0].lastElementChild.children[0].innerText=rs.text;


    // 				picker.dispose();
    // 			});
    // 		}, false);
    // 	});
    // })(mui, document);
});
//删除时间点击
mui('.mui-content').on('tap','.jianTime',function(e){
    $(this).parents('.mui-clearfix').remove();
});


//店铺分类
(function($, doc) {
    $.init();
    mui('.mui-content').on('tap','#showUserPicker1',function(e){
        e.stopPropagation();
        e.preventDefault();
        document.activeElement.blur();
        /**
         * 获取对象属性的值
         * 主要用于过滤三级联动中，可能出现的最低级的数据不存在的情况，实际开发中需要注意这一点；
         * @param {Object} obj 对象
         * @param {String} param 属性名
         */
        var _getParam = function(obj, param) {
            return obj[param] || '';
        };
        //普通示例
        var cityPicker= new $.PopPicker({
            layer: 2
        });
        ///console.log(storeClass);
        cityPicker.setData(storeClass);
        cityPicker.pickers[0].setSelectedValue(fid);
        cityPicker.pickers[1].setSelectedValue(ziid);

        cityPicker.show(function(items) {
            document.getElementById('change_text').innerHTML = items[0].text+" "+items[1].text;
            document.getElementById('change_text').style.color="#333";
            document.getElementById('change_text').setAttribute('data-fid',items[0].value);
            document.getElementById('change_text').setAttribute('data-id',items[1].value);
            //返回 false 可以阻止选择框的关闭
            //return false;
        });

    });
})(mui, document);
function count(o){
    var t = typeof o;
    if(t == 'string'){
        return o.length;
    }else if(t == 'object'){
        var n = 0;
        for(var i in o){
            n++;
        }
        return n;
    }
    return false;
}
/**///睡眠方法
function sleep(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime)
            return;
    }
}

//店铺所在地

(function($, doc) {
    $.init();
    mui('.mui-content').on('tap','#storeAddress',function(e) {
        e.stopPropagation();
        e.preventDefault();
        document.activeElement.blur();
        //普通示例
        var cityPicker3 = new $.PopPicker({
            layer: 3
        });
        cityPicker3.setData(cityList);
        cityPicker3.pickers[0].setSelectedValue(sheng);
        cityPicker3.pickers[1].setSelectedValue(shi);
        cityPicker3.pickers[2].setSelectedValue(qu);
        j++;
        if(moren_area_name!=""&&j==2){
            var mui_body= document.getElementsByClassName('mui-poppicker-body')[0].lastElementChild.lastElementChild.children[1];
            var a=document.getElementsByClassName('mui-poppicker-body')[0].children[2].children[0].children[1].children;
            for(var i=0;i<a.length;i++){
                if(a[i].innerText==moren_area_name){
                    a[0].classList.remove('highlight');
                    a[i].classList.add('highlight');
                    mui_body.style.webkitTransform = "perspective(1000px) rotateY(0deg) rotateX(" +(20*i)+ "deg)";
                }
            }

        }
        cityPicker3.show(function(items){
            document.getElementById('province_id').value = items[0].value;
            document.getElementById('city_id').value = items[1].value;
            area_ids= items[2].value;
            citySerach=items[1].text;
            if(trim(items[0].text)==trim(items[1].text)){
                document.getElementById('change_address').innerText =  items[0].text + " " + items[2].text;
            }else{
                document.getElementById('change_address').innerText =  items[0].text + " " + items[1].text + " " + items[2].text;
            }
            document.getElementById('change_address').style.color="#333";
            common.http('WapMerchant&a=ajaxCircle',{'client':client,'id':items[2].value},function(data){
                console.log(data);
                if(data==null){
                    document.getElementById('trade').style.display="none";
                    mui.toast('该地区下还没有商圈哦!');
                }else{
                    trade=[];
                    for(var i=0;i<data.length;i++){
                        var type_card={'value':'','text':''};
                        type_card.value=data[i].area_id;
                        type_card.text=data[i].area_name;
                        trade.push(type_card);
                    }
                    document.getElementById('trade').style.display="block";
                }
            });
        });


    });

})(mui, document);
// 商圈下拉框
(function($, doc) {
    $.init();
    mui('.mui-content').on('tap','#shopTrade',function(e) {
        e.stopPropagation();
        e.preventDefault();
        document.activeElement.blur();
        //普通示例
        var userPicker = new $.PopPicker();
        console.log(trade);
        userPicker.setData(trade);
        userPicker.pickers[0].setSelectedValue(quan);
        userPicker.show(function(items) {
            document.getElementById('tradeAddress').innerText = items[0].text;
            document.getElementById('tradeAddress').style.color="#333333";
            tradeId=items[0].value;
        }, false);

    });

})(mui, document);
mui('.mui-content').on('tap','.open_photo',function(e){
    mui.alert('第一张将作为主图，最多上传10张;<br/>图片建议宽度700px,高度420px。','商家图片','知道了',function(e){

    })
});

mui('.mui-content').on('tap','.zhuContent',function(e){
    mui.alert('如果将此店铺设置成主店，系统将自动取消其他已设置的主店',function(e){

    })
});

mui('.mui-content').on('tap','.vip_style',function(e){
    mui.alert('折上折的意思是如果这个用户是有平台VIP等级，平台VIP等级有折扣优惠。那么这个用户的优惠计算方式是先用店铺的优惠进行打折后，再用VIP折扣进去打折；' +
        '折扣最优是指：购买产品的总价用店铺优惠打折后的价格与总价跟VIP优惠打折后的价格进行比较，取最小值的优惠方式。',function(e){

    })
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


var winHeight = $(window).height(); //获取当前页面高度
tmp_px1 = (winHeight/2-225)
$('.mui-popover').css({'top':tmp_px1+'px','margin-top':'0px'});
//图片删除点击
mui('.mui-content').on('tap','.delate_photo i',function(e){
    $(this).parent('.delate_photo').remove();
    pic_lens--;
    $('.showImg').width(105*(pic_lens+1)+150);
});






