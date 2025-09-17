<div id="nav" class="mainnav_title">
	<ul>
		<if condition="$config['single_system_type'] != 'shop'">
			<a href="{pigcms{:U('index')}" <if condition="ACTION_NAME eq 'index'">class="on"</if>>平台首页</a>|
		</if>
		<a href="{pigcms{:U('footer')}" <if condition="ACTION_NAME eq 'footer'">class="on"</if>>平台底部导航</a>|
		<a href="{pigcms{:U('shop_index')}" <if condition="ACTION_NAME eq 'shop_index'">class="on"</if>>外卖首页</a>
		<if condition="$config['single_system_type'] != 'shop' && $config['mall_alias_name'] && !$config['delete_mall_v1'] && $mall_show neq 0">
			<a href="{pigcms{:U('mall_index')}" <if condition="ACTION_NAME eq 'mall_index'">class="on"</if>>商城首页</a>
		</if>
		<if condition="$config['meal_alias_name']">
            <a href="{pigcms{:U('foodshop_index')}" <if condition="ACTION_NAME eq 'foodshop_index'">class="on"</if>>餐饮首页</a>
        </if>
        <if condition="$config['open_baodi2020']">
            <a href="{pigcms{:U('baodi')}" <if condition="ACTION_NAME eq 'baodi'">class="on"</if>>宝地温泉小镇首页</a>
        </if>
	</ul>
</div>
<if condition="($system_session['level'] eq 2) AND (ACTION_NAME neq 'shop_index') AND (ACTION_NAME neq 'mall_index') AND (ACTION_NAME neq 'foodshop_index')">
	<div class="page_tips">
		<ol>
			<li>平台可视化页面使用文档&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://www.yuque.com/pigo2o-service/customer/zmg5oz" target="_blank">【点击查看】</a></li>
		</ol>
	</div>
</if>
