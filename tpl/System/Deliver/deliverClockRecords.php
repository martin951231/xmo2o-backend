<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>
    <style>
        table{
            width: 100%;
            border: 1px solid #ddd;
            text-align: left;
        }
        tr,td,th{
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <table cellpadding="10" cellspacing="0">
        <tr>
            <th>类型</th>
            <th>时间</th>
        </tr>
        <notempty name='list'>
            <volist name="list" id="vo">
                <tr>
                    <td>
                        <if condition="$vo['clock_type'] eq 1">
                            上线
                        <elseif condition="$vo['clock_type'] eq 2"/>
                            <font color='red'>休息</font>
                        <else/>
                            --
                        </if>
                    </td>
                    <td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
                </tr>
            </volist>
            <else />
            <tr>
                <td colspan="2">暂无打卡记录</td>
            </tr>
        </notempty>
    </table>
</body>

</html>