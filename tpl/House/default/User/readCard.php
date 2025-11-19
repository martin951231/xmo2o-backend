<include file="Public:header"/>
<style>
    .binding_status{
        display: none;
    }
    .bin_room_user{
        margin-left: 80px !important;
    }
    .bin_room_user span:first-child{
        display: inline-block;min-width: 138px;margin-right:5px;
    }
</style>
<div class="main-content">
	<!-- 内容头部 -->
	<!-- 内容头部 -->
	<div class="page-content" style="height: 400px">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<form  class="form-horizontal" method="post">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
                                <input  name="type" id="type" type="hidden"  value="{pigcms{$_GET['type']}" />
                                <input  name="type" id="vacancy_id" type="hidden"  value="{pigcms{$_GET['vacancy_id']}" />
								<div class="form-group">
									<label class="col-sm-1"><label for="brand_list">设备品牌</label></label>
									<select name="brand_list" id="brand_list" class="col-sm-2">
										<option value="">请选择设备品牌</option>
                                            <volist name="brand_list" id="vo">
                                                <option value="{pigcms{$vo.id}" data-key="{pigcms{$vo.key}" data-title="{pigcms{$vo.title}">{pigcms{$vo.title}</option>
                                            </volist>
									</select>
								</div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="brand_type">设备类型</label></label>
                                    <select name="brand_type" id="brand_type" class="col-sm-2">
                                        <option value="">请选择设备类型</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="car_stop_num">IC卡号</label></label>
                                    <input class="col-sm-2" size="20" name="ic_card" id="ic_card" type="text"  placeholder="获取IC卡号" value="" readonly/>
                                </div>
							</div>
						</div>
						<div class="space"></div>
							<div class="clearfix form-actions">
                                <if condition="$_GET['type'] neq 1">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info submit_info" type="button">
										<i class="ace-icon fa fa-check bigger-110"></i>
										确定
									</button>
								</div>
                                </if>
                                <if condition="$_GET['type'] eq 1">
                                <div class="col-md-offset-3 col-md-9">
                                    <button class="btn btn-info submit_info1" type="button">
                                        <i class="ace-icon fa fa-check bigger-110"></i>
                                        增加
                                    </button>
                                </div>
                                </if>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
<link href="{pigcms{$static_public}layui/css/layui.css" rel="stylesheet">
<script src="{pigcms{$static_public}layui/layui.all.js"></script>
<script>
    layui.config({
        base: '{pigcms{$static_public}layui/lay/ajaxCascader/'
        ,version: '1.6'
    });
    layui.use(['jquery','ajaxCascader'],function(){
    var $ = layui.jquery;
    var cascader = layui.ajaxCascader;
    var wsUri ="ws://127.0.0.1:10090/";
    var websocket = window._websocket = new WebSocket(wsUri);
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
                $('#ic_card').val(s);
               /* $.post("http://www.group.com/index.php?g=Index&c=Index&a=test111",{'data':data},function(result){
                    console.info('4234sssssss',result);
                },'json')*/
            });
        });



    })

</script>
<script>
    var url = "{pigcms{:U('getType')}";
    $('#brand_list').change(function(){
        console.log(111111);
        var brand_id = $(this).val();
        $.post(url,{'brand_id':brand_id},function(data){
            if(data['status'] == 0){
                alert(data.msg);
            }else{
                var list = data.list;
                var shtml = '<option>请选择设备类型</option>';

                if(list){
                    for(var i=0;i<list.length;i++){
                        shtml += '<option  data-key="'+list[i].hardware_config.isSupportCard+'" data-title="'+list[i].sub_title+'" value="'+list[i].id+'">'+list[i].sub_title+'</option>'
                    }
                }
                console.log(shtml);
                $('#brand_type').html(shtml)
            }
        },'json');
    })
    $('#brand_type').change(function(){
        var brand_key = $(this).find("option:selected").attr('data-key');
        console.log(brand_key);
        if (brand_key!=1){
            alert('当前设备不支持读卡功能');
            //parent.window.location.reload();
        }

    })
    $('.submit_info').click(function(){
        var ic_card = $('#ic_card').val();//车位id
        if (!ic_card){
            layer.alert('请先读取卡号');
        }
        var index = layer.load(1, {shade: [0.1,'#000'],time:3000});
        // layer.close(index);
        parent.window.callbackFunc(ic_card);
        var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
        parent.layer.close(index); //再执行关闭
    });
    $('.submit_info1').click(function(){
        var ic_card = $('#ic_card').val();
        if (!ic_card){
            layer.alert('请先读取卡号');
        }
        var brand_list = $('#brand_list').find("option:selected").attr('data-title');
       //  var brand_list = $('#brand_list').val();
        if (!brand_list){
            layer.alert('请先选择品牌');
        }
        var brand_type =$('#brand_type').find("option:selected").attr('data-title');
        //var brand_type = $('#brand_type').val();//车位id
        if (!brand_type){
            layer.alert('请先选择类型');
        }
        var vacancy_id =$('#vacancy_id').val();
        $.post("{pigcms{:U('add_ic_card')}",{'brand_list':brand_list,'brand_type':brand_type,'ic_card':ic_card,'vacancy_id':vacancy_id},function(data){
            if(data['status'] == 0){
                alert(data.msg);
            }else{
                alert(data.msg);
                //parent.window.location.reload();
            }
        },'json');

    });
</script>
<include file="Public:footer"/>