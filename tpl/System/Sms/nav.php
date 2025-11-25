<div id="nav" class="mainnav_title">
	<ul>
		<if condition="!$all_new_version || $all_new_version['plat']">
			<a href="{pigcms{:U('Sms/mobileAppList')}" <if condition="ACTION_NAME eq 'mobileAppList' || ACTION_NAME eq 'mobileApp'">class="on"</if>>平台APP版本配置</a>
		</if>
		<if condition="!$all_new_version || $all_new_version['storestaff']">
			<a href="{pigcms{:U('Sms/storestaffApp')}" <if condition="ACTION_NAME eq 'storestaffApp'">class="on"</if>>店员APP版本配置</a>
		</if>
		<if condition="!$all_new_version || $all_new_version['merchant']">
			<a href="{pigcms{:U('Sms/merchantApp')}" <if condition="ACTION_NAME eq 'merchantApp'">class="on"</if>>商家中心APP版本配置</a>
		</if>
		<if condition="!$all_new_version || $all_new_version['deliver']">
			<a href="{pigcms{:U('Sms/deliverApp')}" <if condition="ACTION_NAME eq 'deliverApp'">class="on"</if>>配送员APP版本配置</a>
		</if>
		<if condition="!$all_new_version || $all_new_version['village']">
			<a href="{pigcms{:U('Sms/VillageApp')}" <if condition="ACTION_NAME eq 'VillageApp'">class="on"</if>>社区APP版本配置</a>
		</if>
		<if condition="!$all_new_version || $all_new_version['village_manager']">
			<a href="{pigcms{:U('Sms/VillageManergeApp')}" <if condition="ACTION_NAME eq 'VillageManergeApp'">class="on"</if>>社区管理APP版本配置</a>
		</if>

		<if condition="!$all_new_version || $all_new_version['app_cash_storestaff']">
			<a href="{pigcms{:U('Sms/pcStorestaffApp')}" <if condition="ACTION_NAME eq 'pcStorestaffApp'">class="on"</if>>台式收银机店员版本</a>
		</if>
	</ul>
</div>