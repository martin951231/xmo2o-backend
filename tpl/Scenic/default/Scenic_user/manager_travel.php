<include file="Public/header"/>
<include file="Public/header_user"/>
	<section class="personal">
		<div class="w1200 clr">
			<div class="fl nav">
				<ul>
					<li class="gr">
						<a href="{pigcms{:U('index')}">个人信息</a>
					</li>
					<li class="mp">
						<a href="{pigcms{:U('ticket_order_list')}">门票订单</a>
					</li>
					<li class="dy">
						<a href="{pigcms{:U('aguide_order_list')}">导游订单</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('add_guide')}">申请向导</a>
					</li>
					<li class="xd">
						<a href="{pigcms{:U('guide_order')}">我是向导订单</a>
					</li>
					<li class="yb">
						<a href="{pigcms{:U('mate')}">我的游伴</a>
					</li>
					<li class="cx">
						<a href="{pigcms{:U('travel_person')}">出行人信息</a>
					</li>
					<li class="xc on">
						<a href="{pigcms{:U('manager_travel')}">我的行程</a>
					</li>
					<li class="sc">
						<a href="{pigcms{:U('collection')}">我的收藏</a>
					</li>
				</ul>
			</div>
            <div class="fr navright">
                <div class="askfor">
                    <div class="ask_top">
                        <h2>我的行程</h2>
                    </div>
                    <div class="askfor_dd">
                        <dl>
                        	<volist name="plan_detial" id="vo">
	                            <dd>
	                                <div class="askfor_ddn">
	                                    <a href="{pigcms{:U('Scenic_travel_plan/travel_plan_detial',array('id'=>$vo['id']))}" class="clr">
	                                        <div class="img fl">
	                                            <img src="{pigcms{:file_domain()}/upload/scenic/config/{pigcms{$vo.scenic_pic}" width="290" height="103">
	                                        </div>
	                                        <div class="p305">
	                                            <h2>{pigcms{$vo['city_name']}{pigcms{$vo['day']}日行程计划</h2>
	                                            <div class="termini">
	                                                <span>目的地：</span>
	                                                {pigcms{$vo['scenic_list']}
	                                            </div>
	                                            <div class="fate"><i>{pigcms{$vo['day']}</i>天</div>
	                                        </div>
	                                    </a>
	                                    <div class="del" onclick="del({pigcms{$vo['id']})"></div>
	                                </div>
	                            </dd>
                            </volist>
                        </dl>
						<style type="text/css">
							.pagebar{background:#fe6903;color:#fff;border:1px solid #fe6903;display: inline-block;height: 30px;line-height: 30px;text-align: center;margin: 0 2px;border: 1px solid #e3e3e3;padding:0 15px;}
						</style>
						<div class="pages">
							<span class="num">{pigcms{$pagebar}</span>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<script type="text/javascript">
		/*删除*/
		function del(del_id){
			$.ajax({
				type : "post",
				url : "{pigcms{:U('Scenic_travel_plan/del_travel')}",
				dataType : "json",
				data:{
					id	:	del_id,
				},
				success : function(result){
					if(result.error_code == 0){
						alert('删除成功');
						location.reload();
					}else{
						alert('删除失败');
					}
				},
				error:function(){
					alert('网络错误，请重试');
				}
			})
		}
	</script>
<include file="Public/footer"/>







