# 传感器管理与语音联动

先执行 `sqls/package/release/2026-10-23_water_alarm_sensor.sql`，新增两张表，无旧表变更。
上传新增 WaterAlarmSensorService.php，以及修改的 WaterAlarmService.php、WaterAlarmController.php、WaterLeakDeviceController.php。保留之前已发布的 WaterLeakReport.php、WaterAlarmTransport.php 等依赖。
编译并发布 platform_dev 前端，页面新增传感器管理页签，设备管理改称喇叭管理。

在对应小区后台添加传感器：点击生成随机ID，填写报警词并保存。ID由服务端生成和预留，字母大写，不接受手动指定。报警词最多199 UTF-8字节。
将生成的ID配置到现场传感器，上报实际JSON请求体，例如：

```json
{"ID":"替换成生成的24位ID","zhuangtai":1.00}
```

服务端从全局唯一ID查出登记小区，再按小区隔离查询设备和报警词。可附加 village_id；若与登记小区不一致则拒绝。
原固定ID 1111 不再自动绑定喇叭，需改成后台生成并保存的ID。

漏水时按登记报警词循环TTS播报并闪光，重复上报不重启播报。多台漏水按添加顺序优先播报，当前传感器恢复后切换到下一台；全部恢复才停止声光。首次正常上报也会核对并停止声光。
离线仍保存最新传感器状态，下次上报重试；无上报不自动恢复。手动停止后，若下一次上报仍有漏水会再次启动。
保持原回调URL、网关进程和端口配置；无需修改Supervisor。上报日志仍在项目根目录water_leak_device.log。
回调延续现有公开接口：传感器ID用于匹配登记记录，不等同于设备身份认证。
