<include file="Public:header"/>
    <form id="myform" method="post" enctype="multipart/form-data">
        <table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
        <tr>
            <th width="160"><label for="keyword"><span style="color:red;">*</span>关键词</label></th>
            <td>
                <input type="hidden" value="1" name="type" />
                <input type="text"  class="input fl" id="keyword" name="keyword" value="<if condition="$lottery['keyword'] eq ''">幸运大转盘<else/>{pigcms{$lottery.keyword}</if>" />
                <em class="notice_tips" tips="只能写一个关键词，用户输入此关键词将会触发此活动。"></em>
            </td>
        </tr>
        <tr>
            <th width="160"><span style="color:red;">*</span>活动名称</th>
            <td><input type="text"  class="input fl" name="title" value="<if condition="$lottery['title'] eq ''">幸运大转盘活动开始了<else/>{pigcms{$lottery.title}</if>" /></td>
        </tr>
        <tr>
            <th width="160"><span style="color:red;">*</span>兑奖信息</th>
            <td><input type="text" style="width: 300px;" class="input fl" name="txt" value="<if condition="$lottery['txt'] eq ''">兑奖请联系我们，电话138********<else/>{pigcms{$lottery.txt}</if>" /></td>
        </tr>
        <tr>
            <th width="160"><span style="color:red;">*</span>中奖提示</th>
            <td><input type="text"  class="input fl" name="sttxt" value="{pigcms{$lottery.sttxt}"/>
            <em class="notice_tips" tips="中奖后,提示信息"></em>
            </td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>活动时间</th>
            <td>
            <input type="text" class="hasDatepicker input fl" id="statdate" value="<if condition="$lottery['statdate'] neq ''">{pigcms{$lottery.statdate|date="Y-m-d",###}</if>" onClick="WdatePicker()" name="statdate" />
            到
            <input type="text" class="hasDatepicker input" id="enddate" value="<if condition="$lottery['enddate'] neq ''">{pigcms{$lottery.enddate|date="Y-m-d",###}</if>" name="enddate"  onClick="WdatePicker()"  />
            </td>
        </tr>

        <tr>
            <th>活动开始图片</th>
            <td>
                <input type="file" size="50" onchange="preview1(this)" placeholder="请上传图片" name="starpicurl" />
            </td>
        </tr>
        <tr>
            <th></th>
            <td><img style="width:417px;height:200px" id="img" src="<if condition="$lottery['starpicurl'] neq ''">{pigcms{$lottery['starpicurl']}<else />/static/images/activity-{pigcms{$activity}-start.jpg</if>"></td>
        </tr>

        <tr>
            <th width="160">活动说明</th>
            <td><textarea id="info" name="info"  style="height:125px;width: 480px;" ><if condition="$lottery['info'] eq ''">亲，请点击进入幸运大转盘抽奖活动页面，祝您好运哦！<else/>{pigcms{$lottery.info}</if></textarea></td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>重复抽奖回复</th>
            <td>
            <input class="input fl" type="text" id="aginfo" value="<if condition="$lottery['aginfo'] eq ''">亲，继续努力哦！<else/>{pigcms{$lottery.aginfo}</if>" name="aginfo"/>
            <em class="notice_tips" tips="如果设置只允许抽一次奖的，请写：你已经玩过了，下次再来。 如果设置可多次抽奖，请写：亲，继续努力哦！"></em>
            </td>
        </tr>
        <tr>
            <th width="160"><span style="color:red;">*</span>活动结束公告主题</th>
            <td>
                <input  class="input fl" style="width: 218px;" id="endtite" value="<if condition="$lottery['endtite'] eq ''">幸运大转盘活动已经结束了<else/>{pigcms{$lottery.endtite}</if>" name="endtite" type="text" />
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>
        <tr>
            <th>活动结束图片</th>
            <td>
                <input type="file" size="50" onchange="preview2(this)" placeholder="请上传图片" name="endpicurl" />
            </td>
        </tr>
        <tr>
            <th></th>
            <td><img style="width:417px;height:200px" id="endimg" src="<if condition="$lottery['endpicurl'] neq ''">{pigcms{$lottery['endpicurl']}<else />/static/images/activity-{pigcms{$activity}-end.jpg</if>"></td>
        </tr>

        <tr>
            <th>活动背景图片</th>
            <td>
                <input type="file" size="50" onchange="preview3(this)" placeholder="请上传图片" name="bg" />
            </td>
        </tr>
        <tr>
            <th></th>
            <td><img style="width:217px;height:300px" id="backimg" src="<if condition="$lottery['bg'] neq ''">{pigcms{$lottery['bg']}<else />/tpl/Wap/default/static/images/activity-lottery-bg2.jpg</if>"></td>
        </tr>

        <tr>
            <th width="90">背景类型</th>
            <td>
                <select name="bgtype" class="valid" tips="选择平铺建议选择在平铺时上下左右可以相连的小图片。 选择填充建议选择符合手机尺寸的图片。">
                    <option value="0" <if condition="$lottery['bgtype'] eq 0">selected</if>>平铺</option>
                    <option value="1" <if condition="$lottery['bgtype'] eq 1">selected</if>>填充</option>
                </select>
            </td>
        </tr>

        <tr>
            <th width="160"><label for="endinfo">活动结束说明</label></th>
            <td>
                <textarea id="endinfo" name="endinfo"  style="height:125px;width: 480px;" ><if condition="$lottery['endinfo'] eq ''">亲，活动已经结束，请继续关注我们的后续活动哦。<else/>{pigcms{$lottery.endinfo}</if></textarea>
                <em class="notice_tips" tips="换行请输入&lt;br/&gt;"></em>
            </td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>一等奖奖品设置</th>
            <td>
                <input class="input fl" name="fist" id="fist" type="text" value="{pigcms{$lottery.fist}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>一等奖奖品数量</th>
            <td>
                <input  class="input fl"  name="fistnums" id="fistnums" type="text" value="{pigcms{$lottery.fistnums}"/>
            </td>
        </tr>


        <tr>
            <th width="160">二等奖奖品设置</th>
            <td>
                <input class="input fl" name="second" id="second" type="text" value="{pigcms{$lottery.second}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160">二等奖奖品数量</th>
            <td>
                <input class="input fl"  name="secondnums" id="secondnums" type="text" value="{pigcms{$lottery.secondnums}"/>
            </td>
        </tr>


        <tr>
            <th width="160">三等奖奖品设置</th>
            <td>
                <input class="input fl" name="third" id="third" type="text" value="{pigcms{$lottery.third}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160">三等奖奖品数量</th>
            <td>
                <input class="input fl"  name="thirdnums" id="thirdnums" type="text" value="{pigcms{$lottery.thirdnums}"/>
            </td>
        </tr>


        <tr>
            <th width="160">四等奖奖品设置</th>
            <td>
                <input class="input fl" name="four" id="four" type="text" value="{pigcms{$lottery.four}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160">四等奖奖品数量</th>
            <td>
                <input class="input fl"  name="fournums" id="fournums" type="text" value="{pigcms{$lottery.fournums}"/>
            </td>
        </tr>


        <tr>
            <th width="160">五等奖奖品设置</th>
            <td>
                <input class="input fl" name="five" id="five" type="text" value="{pigcms{$lottery.five}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160">五等奖奖品数量</th>
            <td>
                <input class="input fl"  name="fivenums" id="fivenums" type="text" value="{pigcms{$lottery.fivenums}"/>
            </td>
        </tr>


        <tr>
            <th width="160">六等奖奖品设置</th>
            <td>
                <input class="input fl" name="six" id="six" type="text" value="{pigcms{$lottery.six}"/>
                <em class="notice_tips" tips="请不要多于50字!"></em>
            </td>
        </tr>

        <tr>
            <th width="160">六等奖奖品数量</th>
            <td>
                <input class="input fl"  name="sixnums" id="sixnums" type="text" value="{pigcms{$lottery.sixnums}"/>
            </td>
        </tr>

        <tr>
            <th width="160">每次抽奖所需的积分</th>
            <td>
                <input class="input fl" size="10"  name="costscore" type="text" value="{pigcms{$lottery.costscore}"/>
                <em class="notice_tips" tips="0为不需要积分"></em>
            </td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>预计活动的人数</th>
            <td>
                <input class="input fl" size="10" name="allpeople" id="allpeople" type="text" value="{pigcms{$lottery.allpeople}"/>
                <em class="notice_tips" tips="预估活动人数直接影响抽奖概率：中奖概率 = 奖品总数/(预估活动人数*每人抽奖次数) <br/><span class='red'>如果要确保任何时候都100%中奖建议设置为1人参加!并且奖项只设置一等奖"></em>
            </td>
        </tr>

        <tr>
            <th width="160"><span style="color:red;">*</span>每人最多允许抽奖次数</th>
            <td>
                <input class="input fl" size="10" name="canrqnums" id="canrqnums" type="text" value="{pigcms{$lottery.canrqnums}"/>
                <em class="notice_tips" tips="必须是数字!"></em>
            </td>
        </tr>

        <tr>
            <th width="90"><span style="color:red;">*</span>抽奖次数限制</th>
            <td>
                <select name="numtype" class="valid" tips="选择无限制则每人最多允许抽奖次数无效">
                    <option value="0" <if condition="$lottery['numtype'] eq 0">selected</if>>无限制</option>
                    <option value="1" <if condition="$lottery['numtype'] eq 1">selected</if>>每日限制</option>
                    <option value="2" <if condition="$lottery['numtype'] eq 2">selected</if>>分享限制</option>
                </select>
            </td>
        </tr>

        <tr class="daynums" style="display: none;">
            <th width="160">每天最多抽奖次数</th>
            <td>
                <input class="input fl" size="10" name="daynums" id="daynums" type="text" value="{pigcms{$lottery.daynums}"/>
                <em class="notice_tips" tips="必须小于总抽奖次数！ 0 为不限制 抽完总数就不能抽了! 可以抽奖天数 = 总数/每天抽奖次数!"></em>
            </td>
        </tr>
        <tr class="invite_nums" style="display: none;">
            <th width="160">分享邀请前有多少次机会</th>
            <td>
                <input class="input fl" size="10" name="sharebeforenum" id="sharebeforenum" type="text" value="<if condition="$lottery['sharebeforenum'] eq ''">1<else/>{pigcms{$lottery.sharebeforenum}</if>"/>
                <em class="notice_tips" tips="在网友分享邀请获得抽奖机会之前有多少次机会。必须小于每人抽奖总次数。至少要给1次机会"></em>
            </td>
        </tr>
        <tr class="invite_nums" style="display: none;">
            <th width="160">分享邀请多少次获得一次抽奖机会</th>
            <td>
                <input class="input fl" size="10" name="shareupnum" id="shareupnum" type="text" value="{pigcms{$lottery.shareupnum}"/>
                <em class="notice_tips" tips="分享邀请前的机会用完后每邀请成功多少次后会增加一次抽奖机会，增加机会的上限不会超过每人抽奖总次数。"></em>
            </td>
        </tr>

        <tr>
            <th width="160">每人中奖次数</th>
            <td>
                <input class="input fl" size="10" name="maxgetprizenum" id="maxgetprizenum" type="text" value="<if condition="$lottery['maxgetprizenum'] eq ''">1<else/>{pigcms{$lottery.maxgetprizenum}</if>"/>
                <em class="notice_tips" tips="请在活动说明中写清楚每人中奖次数"></em>
            </td>
        </tr>

        <tr>
        <th width="80">是否限制每人每天只中一次奖</th>
            <td class="radio_box">
                <span class="cb-enable"><label class="cb-enable <if condition="$lottery['isdaylottery'] eq 1">selected</if>"><span>是</span><input type="radio" name="isdaylottery" value="1"  <if condition="$lottery['isdaylottery'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable"><label class="cb-disable <if condition="$lottery['isdaylottery'] eq 0">selected</if>"><span>否</span><input type="radio" name="isdaylottery" value="0"  <if condition="$lottery['isdaylottery'] eq 0">checked="checked"</if>/></label></span>
                <em class="notice_tips" tips="若选择“是”，将在网友当天中了一次奖后24小时内不能再玩，抽奖次数也不会再累加，请在活动说明中写清楚限制了每人每天只中一次奖！"></em>
            </td>
        </tr>

        <tr>
            <th width="160">每人每次中奖时间间隔</th>
            <td>
                <input class="input fl" size="10" name="timespan" id="timespan" type="text" value="<if condition="$lottery['timespan'] eq ''">0<else/>{pigcms{$lottery.timespan}</if>"/>
                <em class="notice_tips" tips="默认0为没有时间间隔。单位（分钟）。设置此项大于零则网友中奖之后在XX分钟内不会再中奖，抽奖次数依然累加。"></em>
            </td>
        </tr>

        <tr>
        <th width="160"><span style="color:red;">*</span>兑奖密码</th>
            <td>
                <input class="input fl" size="10" name="parssword" id="parssword" type="text" value="{pigcms{$lottery.parssword}"/>
                <span class="form_tips"></span>
                <em class="notice_tips" tips="消费确认密码长度小于15位"></em>
            </td>
        </tr>

        <tr>
            <th width="160">SN码重命名为</th>
            <td>
                <input class="input fl" size="10" name="renamesn" id="renamesn" type="text" value="{pigcms{$lottery.renamesn}"/>
                <em class="notice_tips" tips="例如：CND码,充值密码,SN码 这个主意用于修改SN码的名称，不懂请别修改"></em>
            </td>
        </tr>

        <tr>
            <th width="160">手机号重命名</th>
            <td>
                <input class="input fl" size="10" name="renametel" id="renametel" type="text" value="{pigcms{$lottery.renametel}"/>
                <span class="form_tips"></span>
                <em class="notice_tips" tips="例如：QQ号,微信号,手机号等其他联系方式，不懂请别修改"></em>
            </td>
        </tr>

        <tr>
            <th width="160">抽奖页面是否显示奖品数量</th>
            <td>
                <span class="cb-enable"><label class="cb-enable <if condition="$lottery['displayjpnums'] eq 1">selected</if>"><span>是</span>
					<input type="radio" name="displayjpnums" value="1" <if condition="$lottery['displayjpnums'] eq 1">checked="checked"</if>/></label></span>
                <span class="cb-disable"><label class="cb-disable  <if condition="$lottery['displayjpnums'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="displayjpnums" value="0" <if condition="$lottery['displayjpnums'] eq 0">checked="checked"</if>/></label></span>
            </td>
        </tr>
    </table>
    <div class="btn hidden">
        <input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
        <input type="reset" value="取消" class="button" />
    </div>
    </form>
<script type="text/javascript">
$(document).ready(function() {
    function preview1(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#img').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function preview2(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#endimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function preview3(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#backimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    if($('select[name=numtype]').val()==1){
        $('.daynums').show();
        $('.invite_nums').hide();
    }else if($('select[name=numtype]').val() == 2) {
        $('.invite_nums').show();
        $('.daynums').hide();
    }

    $('select[name=numtype]').change(function () {
        if ($(this).val() == 0) {
            $('.daynums').hide();
            $('.invite_nums').hide();
        } else if ($(this).val() == 1) {
            $('.daynums').show();
            $('.invite_nums').hide();
        } else if ($(this).val() == 2) {
            $('.invite_nums').show();
            $('.daynums').hide();
        }
    });
});
</script>
<script type="text/javascript" src="/static/js/date/WdatePicker.js"></script>
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<include file="Public:footer"/>