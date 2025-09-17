
<include file="Public:header"/>
<div class="mainbox">
    <div id="nav" class="mainnav_title">
        <ul>
            <a href="{pigcms{:U('User/levellist')}" class="on">等级管理</a>
            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/addlevel')}','添加一个等级',1100,600,true,false,false,addbtn,'add',true);" style="margin-left:20px;">添加等级</a>
            <if condition="$config.open_recharge_level eq 1">
                <a href="{pigcms{:U('User/recharge_level')}">充值获得等级</a>
            </if>
            <a href="{pigcms{:U('User/activation_code_list')}">激活码管理</a>
            <a href="{pigcms{:U('User/open_user_level_log')}">平台会员开通记录</a>
        </ul>
    </div>
    <!--<table class="search_table" width="100%">
        <tr>
            <td>
                <form action="{pigcms{:U('User/index')}" method="get">
                    <input type="hidden" name="c" value="User"/>
                    <input type="hidden" name="a" value="index"/>
                    筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
                    <select name="searchtype">
                        <option value="uid" <if condition="$_GET['searchtype'] eq 'uid'">selected="selected"</if>>用户ID</option>
                        <option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>昵称</option>
                        <option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>手机号</option>
                    </select>
                    <input type="submit" value="查询" class="button"/>
                </form>
            </td>
        </tr>
    </table>-->
    <form name="myform" id="myform" action="" method="post">
        <div class="table-list">
            <table width="100%" cellspacing="0">
                <colgroup>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <col/>
                    <if condition="$config['level_balance']">
                        <col/>
                    </if>
                    <col width="180" align="center"/>
                </colgroup>
                <thead>
                <tr>
                    <th width="50px">ID</th>
                    <th >等级名称</th>
                    <th >等级级别</th>
                    <th >等级图标</th>
                    <th >等级福利</th>
                    <if condition="$config['level_balance']"><th >有效期</th></if>
                    <th>操作</th>
                </tr>
                </thead>
                <tbody>
                <if condition="is_array($userlevel)">
                    <volist name="userlevel" id="vo">
                        <tr>
                            <td>{pigcms{$vo.id}</td>
                            <td>{pigcms{$vo.lname}</td>
                            <td>{pigcms{$vo.level}</td>
                            <td>
                                <if condition="$vo['icon']">
                                    <img src="{pigcms{$vo.icon|replace_file_domain=###}" style="width:90px; height: 80px;">
                                    <else/>
                                    无
                                </if>
                            </td>
                            <td><if condition="$vo['type'] eq 1">商品按{pigcms{$vo['boon']/10}折优惠<elseif condition="$vo['type'] eq 2" />商品价格立减{pigcms{$vo.boon}{pigcms{$config['Currency_txt']}<else />无</if></td>
                            <if condition="$config['level_balance']"><td><if condition="$vo.validity gt 0">{pigcms{$vo.validity}天<else />永久有效</if></td></if>
                            <td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('User/addlevel',array('lid'=>$vo['id']))}','编辑等级信息',1100,600,true,false,false,editbtn,'edit',true);">编 辑</a> | <a href="javascript:void(0);" class="delete_row" parameter="lid={pigcms{$vo['id']}" url="/admin.php?g=System&c=User&a=dellevel">删除</a></td>
                        </tr>
                    </volist>
                    <tr><td class="textcenter pagebar" colspan="7">{pigcms{$pagebar}</td></tr>
                    <else/>
                    <tr><td class="textcenter red" colspan="7">列表为空！</td></tr>
                </if>
                </tbody>
            </table>
        </div>
    </form>
</div>
<include file="Public:footer"/>
