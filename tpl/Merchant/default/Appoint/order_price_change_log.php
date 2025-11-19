<include file="Public:header"/>
<style>
    th {
        padding: 12px 10px 12px 9px;
        background-color: #f8f8f8;
        border-right: 1px solid #e5e3e3;
        font-weight: 100;
        text-align: left;
        background: url(tpl/System/Static/css/img/hr.png) 0 bottom repeat-x;
    }
    p {
        font: normal 12PX Helvetica, STHeiti, "Microsoft YaHei", Verdana, Arial, Tahoma, sans-serif;
    }
</style>
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin: 0px 0px;">
            <volist name="log_list" id="vo">
				<tr>
					<th>
                        <p class="time">{pigcms{$vo.add_time}</p>
                        <p>{pigcms{$vo.content}</p>
                        <if condition="$vo.note">
                        <p>{pigcms{$vo.note}</p>
                        </if>
                    </th>
				</tr>
            </volist>
        </table>
<include file="Public:footer"/>