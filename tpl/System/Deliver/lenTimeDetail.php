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
            <th>上线打卡</th>
            <th>休息打卡</th>
            <th>在线时长(秒)</th>
        </tr>
        <notempty name='list'>
            <volist name="list" id="vo">
                <tr>
                    <td>{pigcms{$vo.start}</td>
                    <td>{pigcms{$vo.end}</td>
                    <td>{pigcms{$vo.working_time}</td>
                </tr>
            </volist>
            <else />
            <tr>
                <td colspan="3">暂无打卡记录</td>
            </tr>
        </notempty>
    </table>
</body>

</html>