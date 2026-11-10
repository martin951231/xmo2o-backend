# 访客登记(新)：开发与部署说明

开发日期：2026-10-12。

## 2026-10-14：登记页导航栏跟随二维码所属小区

- 人员、车辆登记页使用 `RegistrationNav.vue` 自定义导航栏，兼容首页的背景图效果；仍只有顶部导航标题，没有恢复内容区标题。
- `VisitorRegisterPublic/status` 新增公开 `theme` 字段，仅包含背景色、背景图及标题文字颜色。通过本次二维码中的 village_id 查询 `house_village_info` 的首页配置，不使用手机上次选择的小区或全局小区缓存。
- 背景色优先 `index_header_bg_color`，未配置时用系统 `mobile_head_color`，再回退白色；背景图用 `index_header_bg_image`，标题用 `index_title_color`（默认黑色）。图片域名转换和状态栏黑白文字判断沿用首页规则。
- 此次不新增数据库字段。需同步部署后端 `VisitorRegisterService.php` 和小程序页面/组件、`registration.js`、`pages.json`。
- 64 项隔离后端检查和 23 项前端检查通过，包含不同小区主题隔离、默认色、配置更新不读旧缓存、扫码返回主题及组件模板验证。

## 2026-10-13：二维码永久存储修复

- 部署此版本前，执行 `sqls/package/release/2026-10-13_visitor_register_qrcode_storage.sql`，新增 `pigcms_visitor_register_qrcode` 永久图片表，没有到期时间。
- 原版本把“本地图片文件是否存在”作为二维码有效性的条件，文件缺失会让后台预览消失。当前环境无法连接业务库，因此尚未确认线上文件缺失的具体来源。
- 新生成的图片与表单记录在同一事务中保存；后台预览/下载使用数据库中的图片，不依赖本地上传文件、临时 URL 或 token 的有效期。
- 旧二维码的本地文件尚在时，读取表单会自动补存数据库；若旧文件已经丢失且从未备份，需要重新生成一次，之后永久保留。
- 生成时间久远、刷新 token、更新同一 AppID 的 Secret 均不会让二维码图片过期。更换 AppID 仍需为新的小程序生成二维码；人工停用仍会禁止登记。
- PHP 语法及 60 项隔离后端检查通过，其中包含旧图片补存、删除本地图片、将生成时间改为很久以前后仍能预览及发布的回归场景。业务库升级 SQL 尚未执行。

## 实现范围及项目适配

- 后端沿用 ThinkPHP 6 的 `community/village_api` 控制器、`api_output` 响应和现有数据库连接。
- 后台源码为 `D:/projectView/platform_dev`（Vue 2 / Ant Design Vue），复用 `this.request`、数据库菜单及动态组件注册。
- 小程序源码为 `D:/wechatApp/village`（Vue 2 / uni-app），复用项目解析后的 `common.requestUrlV20`；公开登记使用独立轻量请求函数，设置超时、不打印个人信息、不强制登录。
- 两个表单固定为 `visitor` / `vehicle`；名称、编号、页面路径维护在服务端配置文件，不允许后台任意修改。
- 所有配置、二维码、登记记录按 **小区 village_id 隔离**。每个小区的两个表单共用一套微信配置；小程序码 scene 为 `v=<小区ID>&t=<表单类型>`。
- 新增独立表、接口、页面，没有修改原有访客登记业务。现有工作区中其他未提交改动保持原样。

## 文件清单

后端项目 `D:/phpstudy_pro/WWW/xmo2o`：

| 文件 | 作用 |
| --- | --- |
| `sqls/package/release/2026-10-12_visitor_register_new.sql` | 可重复执行的建表、两个表单初始化及菜单插入 |
| `v20/config/visitor_register.php` | 固定表单及页面映射、环境密钥读取 |
| `v20/app/community/model/service/VisitorRegisterService.php` | 配置、二维码、发布停用、登记、查询、离场和导出 |
| `v20/app/community/model/service/VisitorRegisterWechatService.php` | AES-256-GCM、stable_token、缓存、微信二维码与错误处理 |
| `v20/app/community/model/service/VisitorRegisterValidation.php` | 固定字段、日期、手机号、车牌、菜单授权校验 |
| `v20/app/community/controller/village_api/VisitorRegisterController.php` | 后台鉴权接口 |
| `v20/app/community/controller/village_api/VisitorRegisterPublicController.php` | 公开状态和提交接口 |
| `v20/app/community/model/service/VillageMenuService.php` | 新组件在“我的应用”展示，并避免被父级 iframe 路由覆盖（两处增量修改） |
| `v20/tools/tests/visitor_register_test.php` | 隔离 MySQL + Mock 微信验证 |
| `v20/tools/tests/visitor_register_frontend.cjs` | 小程序校验、失败保留、重复点击、成功跳转与编译注册验证 |
| `v20/tools/visitor_register_README.md` | 本说明 |

后台项目 `D:/projectView/platform_dev`：

- 新增 `src/views/community/village/visitorRegister/index.vue`。
- 新增 `src/api/community/visitorRegister.js`。
- 增量修改 `src/router/routerComponents/community/village/index.js`，注册 `visitorRegisterNew`。

小程序项目 `D:/wechatApp/village`：

- 新增 `pages/visitorRegister/person.vue`：来访人员登记固定页面。
- 新增 `pages/visitorRegister/vehicle.vue`：车辆登记固定页面。
- 新增 `pages/visitorRegister/success.vue`：统一成功页。
- 新增 `pages/visitorRegister/DateTimeField.vue`、`registration.js`、`registration.css`。
- 增量修改 `pages.json`，注册上述三个页面，保留原来的启动首页。

微信二维码使用的页面路径不含 `.vue`：

```
pages/visitorRegister/person
pages/visitorRegister/vehicle
```

## 数据库

沿用项目现有 `sqls/package/release` SQL 升级方式，表前缀为项目当前的 `pigcms_`：

1. `pigcms_visitor_register_config`：小区共用 AppID、加密 AppSecret 和版本号。
2. `pigcms_visitor_register_form`：两个固定表单的发布状态与二维码信息；小区+类型唯一。
3. `pigcms_visitor_register_person`：人员记录；登记编号唯一、小区+请求标识唯一。
4. `pigcms_visitor_register_vehicle`：车辆记录、离场状态；同上唯一约束。
5. `pigcms_visitor_register_rate`：按 IP / 分钟的原子限流计数；Redis 不可用也能限流。过期行随请求分批清理。

菜单插入 `pigcms_house_menu_new`，父节点使用现有“我的应用”ID `194`，组件 `visitorRegisterNew`。初始化现有小区的两个表单；新小区第一次打开管理页时也会补齐配置和表单。

**开发时业务数据库未监听，尚未向现有业务数据库执行升级 SQL。** 测试使用独立的本机 13308 端口、`visitor_register_test` 数据库，与现有业务库分离。

## 后台页面与权限

入口：我的应用 → 访客登记(新)。双 Tab 展示各自状态、二维码、发布时间及记录。

- 小程序配置：保存 AppID / AppSecret，测试已保存的配置；密钥字段留空可保留旧值，修改 AppID 时必须填写新 Secret。
- 生成二维码不会自动发布；已有有效二维码时前端重新生成会确认。服务端默认复用有效二维码。
- 发布立即调用后端，后端核对配置、固定页面映射、二维码文件及所属 AppID；停用后保留二维码并拒绝提交。
- 人员记录按姓名、手机号、日期查询；车辆记录按车牌、手机号、进入日期、离场状态查询。
- 列表默认脱敏手机号及“单位或有效证件号码”整个字段；授权详情、Excel 可查看完整内容。
- 导出最多 5000 条；超过时要求缩小查询日期。输出 XLSX，显式字符串单元格避免公式注入、号码精度丢失。文件通过鉴权响应返回，不放入公开下载目录。
- 后端只使用登录身份中的 village_id，不接受客户端任意指定后台小区。
- 沿用现有角色/菜单授权；普通管理员必须明确授权该菜单，包括旧权限函数将空菜单视为全权限的情况。现有超级管理员角色仍可访问。

## 接口

均使用项目现有 POST + JSON 协议。实际 URL 前缀为 `/v20/public/index.php`；成功 `status=1000`，业务错误 `status=1003`，后台未登录 `status=1002`。

后台路径：`/community/village_api.VisitorRegister/<action>`。

| action | 参数 | 返回 |
| --- | --- | --- |
| `configInfo` | 无 | `appid, has_secret`，不返回 Secret |
| `saveConfig` | `appid, appsecret` | 配置摘要 |
| `testConnection` | 无 | 使用已保存凭证验证连接 |
| `forms` | 无 | 两种表单及二维码有效性、预览地址 |
| `generateQr` | `form_type, regenerate=0/1` | 表单列表 |
| `publish` / `disable` | `form_type` | 表单列表 |
| `records` | `form_type, page, page_size, visitor_name/plate_number, phone, date_from, date_to, status` | `list, total` |
| `detail` | `form_type, id` | 本小区完整记录 |
| `registerExit` | `id, exit_time` | 成功消息；拒绝重复离场及早于进入时间 |
| `export` | 同查询条件，不使用分页 | `filename, content`（Base64 XLSX） |

公开路径：`/community/village_api.VisitorRegisterPublic/<action>`。

| action | 参数 | 返回 |
| --- | --- | --- |
| `status` | `village_id, form_type` | 表单名称、编号、状态和提示 |
| `login` | `village_id, form_type, code`（wx.login） | `auth_token, nickname, phone, prefill` |
| `authorizePhone` | `village_id, form_type, auth_token, code`（手机号按钮）, `privacy_consent=1` | `auth_token, phone` |
| `submit` | `village_id, form_type, request_id, privacy_consent=1, auth_token, nickname, data` | `registration_no` |

`data` 字段严格按需求中的两种固定表单，日期时间格式为 `YYYY-MM-DD HH:mm:ss`。首次车辆登记允许 `exit_time` 为 null 或空字符串；填写有效离开时间时直接为 `exited`。

公开接口每 IP 每分钟允许 120 次状态查询；登录、手机号授权及提交分别允许 10 次，使用独立计数避免正常授权占用提交额度。使用服务器 peer IP，不信任客户端伪造的 X-Forwarded-For；部署反向代理时应由可信代理在 Web 服务器层恢复真实 IP，否则多个访客可能共用代理 IP 的限额。

提交请求 ID 长度 16–64，仅允许字母、数字、下划线、短横线。同小区相同请求 ID + 数据返回同一编号，数据变更拒绝复用；额外抑制 60 秒内完全相同的内容。配置、停用和提交采用同样的行锁顺序防止状态竞争。

## 上线配置与顺序

1. 在业务库执行 `sqls/package/release/2026-10-12_visitor_register_new.sql`。SQL 幂等；如目标环境表前缀或“我的应用”ID与本项目不同，部署前核对。
2. 在 PHP 服务进程配置 `VISITOR_REGISTER_ENCRYPTION_KEY`，值为 **32 个密码学随机字节的 Base64 编码**。本项目 ThinkPHP 的 `PHP_VISITOR_REGISTER_ENCRYPTION_KEY` 及受保护的 `.env` `[VISITOR_REGISTER] ENCRYPTION_KEY=...` 也支持。不要提交密钥到代码仓库；更换密钥前须先迁移已加密凭证。
3. 确认 PHP 扩展 OpenSSL、cURL、mbstring、PDO MySQL、Zip 可用。PHP 7.3.4 验证通过。
4. 确认 `upload/visitor_register` 可由 PHP 写入并可通过站点 HTTPS 访问。微信 token 优先复用现有 Redis store，Redis 不可用则使用现有 file store；缓存目录必须禁止外部访问。
5. 部署后端及管理端构建。清理/刷新现有菜单缓存或重新登录；为普通管理员授予“访客登记(新)”菜单。
6. 在微信后台配置 HTTPS request 合法域名，与小程序现有解析域名一致；配置服务端 IP 白名单。小程序 AppID 必须与该项目打包发布使用的 AppID 一致。
7. 编译、审核并发布包含两个新页面的小程序正式版，再到后台保存微信配置、测试连接、分别生成二维码并发布两个表单。
8. 分别扫码验证人员和车辆流程，验证停用、跨账号权限、Excel 内容、车辆离场及 AppID 变更后二维码失效。
9. 依据实际管理制度补充物业联系渠道、个人信息保留期限，确认微信隐私保护指引与页面告知一致。

本功能的“发布”只启用后台业务，不会提交微信审核或发布小程序代码。

## 验证结果

- 7 个新增 PHP 文件语法检查通过（含配置和测试文件）。
- **56 项后端检查通过**：建表及初始化幂等、菜单归属、权限、类型/长度/手机号/日期校验、AES-GCM 与篡改检测、token 缓存/清理、微信错误码脱敏、图片校验、二维码复用/固定路径/小区 scene、过期 token 重试、发布前置条件、状态隔离、幂等与短时重复抑制、列表脱敏、小区隔离、离场校验、XLSX 与限流、AppID 变更失效。
- **21 项小程序交互检查通过**：scene、状态、隐私同意、双击防重、网络/业务失败保留输入、重试请求 ID、成功后跳转、直接进入成功页防护、车牌/离场校验、四个模板及编译页面注册、原首页保留。
- 新增后台页面和 API 文件 ESLint 通过。
- 管理端生产构建通过；存在其他既有页面 CSS 顺序与包体积警告。
- HBuilderX uni-app 微信小程序完整构建通过；存在其他既有页面 key 表达式、组件分包建议、project.config.json 冲突与鸿蒙兼容提示。
- 三个项目本次修改均为 PHP / JavaScript / Vue 2，不涉及 TypeScript 类型检查。
- 构建产物只写入后端项目 `temp/visitor-register-admin-build` 和 `temp/visitor-register-mini-build`，未覆盖线上产物。
- **没有使用真实微信凭证，也没有验证真实微信扫码、正式发布或业务库上的登录联调。**

测试入口：

```
php v20/tools/tests/visitor_register_test.php --isolated
node v20/tools/tests/visitor_register_frontend.cjs D:/projectView/platform_dev D:/wechatApp/village
```

PHP 测试硬性使用本机 13308 的 `visitor_register_test` 专用数据库，不会连接业务库；需要预先初始化隔离测试实例。大部分表是连接级临时表，只有测试菜单使用唯一命名的专用表并在 finally 清理，以兼容 MySQL 5.7 的临时表自查询限制。微信调用全部 Mock，测试生成的图片自动清理。前端测试需要先生成上述小程序构建产物。

## 2026-10-20 微信授权与当天信息回显

- 两个登记页进入后调用 `wx.login`，服务端按二维码所属小区的 AppID/AppSecret 调用 code2Session。前端不能指定 OpenID；OpenID 与 session_key 不返回客户端。登记凭证使用已有 AES-GCM 密钥加密，绑定小区、表单类型、AppID、配置版本及当天日期，有效期最多两小时。
- 微信昵称沿用项目 `pages/my/username.vue` 的 `type="nickname"` 填写能力，由用户选择或输入。人员页自动填入尚未手动修改的访客姓名，仍允许核对实际姓名；车辆页单独填写昵称。
- 勾选告知后点击 `open-type="getPhoneNumber"` 按钮，服务端使用一次性手机号 code 换取号码并核对 AppID watermark。只接受大陆手机号；提交时使用凭证中已验证的号码，不信任前端传入号码。
- 同一微信用户、同一小区、同一类型、上海时区同一天的最近一次成功提交可回显。日期按提交日计算；回填单位、事由、房号、备注或车牌、去向以及当天昵称和授权手机号。每次扫码重新生成来访/进入时间，离开时间不复制。不会自动勾选本次告知同意。
- 新增身份关联表只保存 AppID/OpenID 的 SHA-256 标识、关联登记编号、日期、昵称及授权手机号。旧记录不按手机号猜测身份，因此仅更新后的授权提交参与回显。

发布清单与顺序：

1. 先执行 `sqls/package/release/2026-10-20_visitor_register_identity.sql`（幂等新增表；本次未在业务库执行）。
2. 更新后端 `VisitorRegisterService.php`、`VisitorRegisterWechatService.php`、`village_api/VisitorRegisterPublicController.php`。
3. 小程序更新 `pages/visitorRegister/person.vue`、`vehicle.vue`、`registration.js` 后编译上传审核发布。此次不需要改后台管理端。
4. 新后端提交接口要求微信身份和手机号授权，旧小程序版本不能继续匿名提交，需协调后端切换与小程序发布时间。
5. 微信后台需具备手机号获取能力、配置对应隐私保护指引及 request 合法域名。正式联调请用真实微信扫码并完成授权；模拟测试不能验证帐号权限或真实弹窗。

本次通过 85 项后端隔离测试、30 项前端测试及 3 个业务 PHP 文件语法检查。测试覆盖伪造/过期凭证、跨小区/用户/表单/日期隔离、最近一次覆盖、手机号 watermark、独立限流、拒绝授权、昵称填充和手填姓名保护。微信小程序全量构建通过，仍有其他既有页面 key、分包、project.config.json 及鸿蒙兼容提示；未做真实微信授权联调。

## 2026-10-20 社区管理小程序访客登记入口

项目：`D:\wechatApp\uniapp_community`。

- 首页“审批事项”后新增“访客登记”卡片，显示人员登记记录总数。使用已有白色卡片、`#eaf3ff` 圆底及 `#2681f3` 的 uView 人物图标。
- 记录页仅显示昵称、完整手机号、登记时间，按提交时间倒序；支持分页、下拉刷新、错误重试和空状态。登记时间是成功提交时间，不是预约的来访时间。本入口展示访客人员登记，不混入车辆记录。
- 新接口：`POST manage_api.v1.VisitorRegister/records`，参数 `page,page_size`，返回 `list,total,has_more`。每行只返回列表 key `registration_no` 和所需展示字段。
- 使用管理小程序已有 ticket 登录，服务端从登录账户解析小区，不接受请求中的 village_id 覆盖。仅小区管理人员角色（5）且具有“访客登记(新)”菜单权限可访问；未授权时页面显示明确错误。不会给空菜单权限账号开放全部访客数据。
- 新增按登记编号保存的昵称快照表，避免同一人后来更换昵称影响历史显示。升级脚本回填已有身份关联表能确定昵称的记录；更早无法关联昵称的记录显示“未提供昵称”。

本次发布文件：

1. 先执行 `sqls/package/release/2026-10-20_visitor_register_record_profile.sql`，依赖前一节的 identity 升级已执行。未操作业务库。
2. 后端更新 `v20/app/community/model/service/VisitorRegisterService.php`，新增 `v20/app/community/controller/manage_api/v1/VisitorRegisterController.php`。
3. 管理小程序更新 `pages/Community_menu/index.vue`、`pages.json`，新增 `pages/Community/visitorRegisterNew/list.vue`，重新编译上传发布。本次不需要再次改动访客端 `village` 小程序。
4. 给需要查看记录的管理员授予已有“访客登记(新)”菜单权限，并用真实管理账号验证列表。

验证：95 项后端隔离检查（含管理端鉴权、小区隔离、历史昵称快照和分页）、13 项管理端交互检查通过。管理小程序微信端全量构建通过，编译输出为 `temp/visitor-register-management-build`；既有 key 表达式、project.config.json 冲突及鸿蒙兼容提示未阻止构建。未进行真机登录联调。

管理端交互测试：`node v20/tools/tests/visitor_register_management.cjs`。

## 2026-10-21 管理小程序代码保护兼容修复

微信工具报错 `common/vendor.js: require is not being used properly in 'ObjectProperty'`，来源为 HBuilderX 自带 uniCloud 运行时的 Rollup CommonJS 包装对象 `{path, exports, require: function(...) {...}}`。本机微信工具的 `fileFlatter.js` 把对象属性名 require 也当成非法模块加载器引用。

修复位于 `D:\wechatApp\uniapp_community\vue.config.js` 与 `build/WechatCodeProtectionCompatPlugin.js`，只对微信端构建生效：在 emit 阶段识别该包装对象，等价改为 `Object.defineProperty` 定义 require 属性，同时保持 writable/enumerable/configurable 均为 true，保留函数内容和模块初始化行为。不能只给 require 属性名加引号，因为上传前的二次压缩可能再次去掉引号。

`manifest.json` 的 `mp-weixin.setting.uglifyFileName` 显式保持 true，保证生成的 project.config.json 也开启代码保护。未修改微信工具、HBuilderX 安装文件，未关闭代码保护或脚本压缩。

验证脚本：`node v20/tools/tests/visitor_register_code_protection.cjs`，离线调用已安装微信工具的代码保护校验器，复现原始错误和仅加引号的二次压缩回归，验证修复后对象行为与全部编译文件在压缩前后的保护兼容性。此离线检查不代替真实上传。

更新上述三个管理小程序源文件后，应重新从 HBuilderX 发行微信小程序，再上传新产物；不要直接上传之前生成的旧 vendor.js。本次不需要修改后端或数据库。

## 2026-10-22 手机号弹窗授权与管理端日期筛选（覆盖前述交互与权限规则）

- 访客小程序两个登记页不再采集微信昵称，人员姓名由访客填写；旧客户端传入的可选昵称仍兼容，后端不再要求昵称非空。
- 进入页面自动展示底部滑出弹窗：信息收集告知、同意勾选、蓝色手机号授权按钮、取消。取消仅关闭弹窗，填写内容保留；可点击联系电话重新打开，缺少手机号或协议确认时提交也会重新打开。授权成功后填入手机号并关闭弹窗。当天记录回填保留。
- 管理小程序 records 接口取消角色与菜单授权限制，所有有效登录账号均可访问。小区仍从登录身份获取，不接受客户端指定其他小区；没有当前小区的账号返回空列表，未登录仍拒绝访问。
- 管理记录页最顶部增加日期选择器及“全部”，按北京时间成功提交的自然日筛选。参数 `date=YYYY-MM-DD`，空字符串为全部；日期变更重置分页，支持分页/刷新保留日期。
- 发布后端 `v20/app/community/model/service/VisitorRegisterService.php` 和 `v20/app/community/controller/manage_api/v1/VisitorRegisterController.php`。
- 访客端 `D:\wechatApp\village`：更新 `pages/visitorRegister/person.vue`、`vehicle.vue`、`registration.js`，新增 `PhoneAuthorization.vue`，重新编译发布。
- 管理端 `D:\wechatApp\uniapp_community`：更新 `pages/Community/visitorRegisterNew/list.vue`，重新编译发布。
- 已部署前述表结构的环境本次不需要新增 SQL。101 项后端隔离检查、31 项访客端与 18 项管理端检查通过；未进行真机授权验证。

## 2026-11-02 管理端登记详情

列表点击后进入 `pages/Community/visitorRegisterNew/detail`，展示所有人员登记业务字段和提交时间。新增 `POST manage_api.v1.VisitorRegister/detail`，参数 `registration_no`；按登录小区查询，不要求菜单授权，不返回身份凭证、请求标识或内部哈希。

发布后端 `VisitorRegisterService.php` 与 `manage_api/v1/VisitorRegisterController.php`；管理小程序发布 `pages.json`、`pages/Community/visitorRegisterNew/list.vue`、新增 `detail.vue`。详情路由放在已有 Community 分包。无需数据库升级，无需更新访客端小程序。106 项后端检查通过，包含完整详情和跨小区隔离。
