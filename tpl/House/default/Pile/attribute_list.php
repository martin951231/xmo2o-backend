<include file="Public:header"/>
<div class="main-content">
    <!-- 内容头部 -->
    <div class="breadcrumbs" id="breadcrumbs">
        <ul class="breadcrumb">
            <li>
                <i class="ace-icon fa fa-user"></i>
                <a href="{pigcms{:U('attribute_list')}">智慧充电桩管理</a>
            </li>
            <li class="active">智慧充电桩属性列表</li>
        </ul>
    </div>
    <!-- 内容头部 -->
    <div class="page-content">
        <div class="page-content-area">
            <style>
                .ace-file-input a {display:none;}
            </style>
            <div class="row">
                <div class="col-xs-12">
                    <div id="shopList" class="grid-view">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                            <tr>
                                <th width="20%">编号</th>
                                <th width="40%">功能属性名称</th>
                                <!--<th class="button-column" width="20%">操作</th>-->
                            </tr>
                            </thead>
                            <tbody>
                            <if condition="$list">
                                <volist name="list" id="vo">
                                    <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
                                        <td><div class="tagDiv">{pigcms{$vo.id}</div></td>
                                        <td><div class="tagDiv"><a style="color: #0a8ddf" href="{pigcms{:U('attribute_standard_list',array('attribute_id'=>$vo['id']))}">{pigcms{$vo.attribute_name}</a></div></td>
                                    <!--<td class="am-text-middle">
                                            <div class="tpl-table-black-operation">
                                                <a title="编辑" href="javascript:;" onclick="get_default_value('{pigcms{$vo.id}')">查看默认值</a>
                                            </div>
                                        </td>-->
                                    </tr>
                                </volist>
                            <else/>
                                <tr class="odd">
                                    <td class="button-column" colspan="12" >
                                        相关设备功能属性（例如：设备消费标准）请前往<a style="color: #2681f3;" href="https://www.lvcchong.com/" target="_blank">《智慧充电综合管理平台》</a>登录后进行设置。
                                    </td>
                                </tr>
                            </if>
                            </tbody>
                        </table>
                        {pigcms{$list.pagebar}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<style>
    td,th{
        text-align: center;
    }
</style>
<script>
    function get_default_value(id) {
        let title = '';
        let table = '';
        switch (id) {
            case '1':
                title = '消费标准默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>单价</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>1</td>\n' +
                    '      <td>元/CNY</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>时间</td>\n' +
                    '      <td>0~10</td>\n' +
                    '      <td>4</td>\n' +
                    '      <td>小时/h</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>电量</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>1</td>\n' +
                    '      <td>度/kw.h</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '2':
                title = '标准功率默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>标准功率</td>\n' +
                    '      <td>0~8000</td>\n' +
                    '      <td>220</td>\n' +
                    '      <td>瓦/W</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '3':
                title = '上报功率参数默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>功率变化阀值</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>15</td>\n' +
                    '      <td>瓦/W</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>最小上报间隔</td>\n' +
                    '      <td>0~255</td>\n' +
                    '      <td>255</td>\n' +
                    '      <td>秒/sec</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '4':
                title = '极限功率默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>极限功率</td>\n' +
                    '      <td>10~3000</td>\n' +
                    '      <td>990</td>\n' +
                    '      <td>瓦/W</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '5':
                title = '最大充电时长默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>最大充电时长</td>\n' +
                    '      <td>60~86400</td>\n' +
                    '      <td>43200</td>\n' +
                    '      <td>秒/sec</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '6':
                title = '充满断电参数默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>时长限制</td>\n' +
                    '      <td>0~36000</td>\n' +
                    '      <td>1800</td>\n' +
                    '      <td>秒/sec</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>功率限制</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>15</td>\n' +
                    '      <td>瓦/W</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '7':
                title = '空载断电参数默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>时长</td>\n' +
                    '      <td>0~3600</td>\n' +
                    '      <td>10</td>\n' +
                    '      <td>秒/sec</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>功率</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>1</td>\n' +
                    '      <td>瓦/W</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            case '8':
                title = '上报温度阀值默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>温度</td>\n' +
                    '      <td>-20~120</td>\n' +
                    '      <td>70</td>\n' +
                    '      <td>摄氏度/℃</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
                break;
            default:
                title = '消费标准默认值';
                table = '<table class="layui-table">\n' +
                    '  <colgroup>\n' +
                    '    <col width="150">\n' +
                    '    <col width="200">\n' +
                    '    <col>\n' +
                    '  </colgroup>\n' +
                    '  <thead>\n' +
                    '    <tr>\n' +
                    '      <th>字段名称</th>\n' +
                    '      <th>设定范围</th>\n' +
                    '      <th>设定值</th>\n' +
                    '      <th>单位</th>\n' +
                    '    </tr> \n' +
                    '  </thead>\n' +
                    '  <tbody>\n' +
                    '    <tr>\n' +
                    '      <td>单价</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>1</td>\n' +
                    '      <td>元/CNY</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>时间</td>\n' +
                    '      <td>0~10</td>\n' +
                    '      <td>4</td>\n' +
                    '      <td>小时/h</td>\n' +
                    '    </tr>\n' +
                    '    <tr>\n' +
                    '      <td>电量</td>\n' +
                    '      <td>0~100</td>\n' +
                    '      <td>1</td>\n' +
                    '      <td>度/kw.h</td>\n' +
                    '    </tr>\n' +
                    '  </tbody>\n' +
                    '</table>';
        }
        $.post("{pigcms{:U('get_default_value')}",{id:id},function(data){
            if(data.status){
                layer.open({
                    type: 1,
                    title:title,
                    area: ['550px', '240px'],
                    content: table
                });
            }
        },'json');
    }
</script>
<include file="Public:footer"/>
