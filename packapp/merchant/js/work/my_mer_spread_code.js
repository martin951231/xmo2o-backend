mui.init();
var store_id=''; //全局
var store_index='';
var admin_realname=''
var admin_invit_code=''
var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
var config = common.getCache('config',true);
if(!config){
	common.http('Merchantapp&a=config', {}, function(data){
		common.setCache('config',data,true);
		config=common.getCache('config');
		config_loaded();
	});
}else{
	config_loaded();
}

function config_loaded(){
	// 留做处理配置相关
}


function set_index_data(data){
    $('.mer_code').val(data.mer_code);
}

//首页主体数据
/*先初始化判断有没有缓存，有的话先展示缓存内容*/
var mer_code = common.getCache('mer_code',true);
if(mer_code){
	set_index_data(mer_code);
}
common.http('Merchantapp&a=my_mer_spread_code',{'client':client,noTip:1},function(data){
    console.log('data', data);
	common.setCache('mer_code',data,true);
	
	set_index_data(data);
});  



if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}

// 点击复制
var clipboard = new ClipboardJS('.click_copy');
clipboard.on('success', function(e) {
    alert('复制成功');
});

clipboard.on('error', function(e) {
    alert('复制失败，请尝试手动复制！');
});


function copyToClipBoard(str){
    var save = function (e){
        e.clipboardData.setData('text/plain',str);//下面会说到clipboardData对象
        e.preventDefault();//阻止默认行为
    };
    document.addEventListener('copy',save);
    document.execCommand("copy");//使文档处于可编辑状态，否则无效
    // window.clipboardData.setData("Text",str);
	alert('复制成功！')
}