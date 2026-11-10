const fs = require('fs')
const vm = require('vm')
const assert = require('assert')
const root = 'D:/wechatApp/uniapp_community'
const deps = 'D:/projectView/platform_dev/node_modules/'
const compiler = require(deps + 'vue-template-compiler')
const pending = []
let ticket = 'account-a'
let stopped = 0
const common = { post: (url, data, success, that, error) => pending.push({ url, data, success, error }) }
const uni = { getStorageSync: () => ticket, stopPullDownRefresh: () => stopped++ }
const descriptor = compiler.parseComponent(fs.readFileSync(root + '/pages/Community/visitorRegisterNew/list.vue', 'utf8'))
const sandbox = { module: { exports: {} }, require: () => common, uni }
vm.runInNewContext(descriptor.script.content.replace('export default', 'module.exports ='), sandbox)
const page = sandbox.module.exports
const ctx = page.data()
Object.entries(page.methods).forEach(([key, value]) => { ctx[key] = value.bind(ctx) })
let checks = 0
function check(value, message) { assert(value, message); checks++; console.log('PASS ' + message) }
const record = { registration_no: 'VR1', nickname: '昵称', phone: '13800000000', registration_time: '2026-10-20 12:00:00' }
page.onShow.call(ctx)
check(pending[0].data.page === 1 && !('village_id' in pending[0].data), 'list uses authenticated village without page-supplied village')
pending.shift().success({ list: [record], has_more: true })
check(ctx.records.length === 1 && ctx.page === 1 && !ctx.loading, 'first page loaded')
page.onReachBottom.call(ctx)
page.onReachBottom.call(ctx)
check(pending.length === 1 && pending[0].data.page === 2, 'scroll pagination suppresses duplicate requests')
pending.shift().error({ data: { msg: '没有权限' } })
check(ctx.page === 1 && ctx.records.length === 1 && ctx.error === '没有权限', 'failure keeps existing records and page for retry')
ctx.fetchRecords(false)
check(pending[0].data.page === 2, 'retry requests failed page without skipping')
pending.shift().success({ list: [{ ...record, registration_no: 'VR2' }], has_more: false })
check(ctx.records.length === 2 && !ctx.hasMore, 'next page appended')
page.onReachBottom.call(ctx)
check(pending.length === 0, 'end of list stops pagination')
page.onPullDownRefresh.call(ctx)
const stale = pending.shift()
page.onPullDownRefresh.call(ctx)
stale.success({ list: [record], has_more: false })
check(ctx.records.length === 0 && ctx.loading, 'stale refresh cannot overwrite latest request')
pending.shift().success({ list: [], has_more: false })
check(!ctx.loading && ctx.records.length === 0 && stopped > 0, 'empty state stops refresh')
page.onShow.call(ctx)
ticket = 'account-b'
pending.shift().success({ list: [record], has_more: false })
check(ctx.records.length === 0, 'late response from previous account is not displayed')
page.dateChanged = page.methods.dateChanged
ctx.dateChanged({ detail: { value: '2026-10-22' } })
check(pending[0].data.date === '2026-10-22' && pending[0].data.page === 1, 'date filter resets pagination')
pending.shift().success({ list: [], has_more: false })
ctx.clearDate()
check(pending[0].data.date === '', 'all dates clears filter')
pending.shift().success({ list: [], has_more: false })
check(compiler.compile(descriptor.template.content).errors.length === 0, 'record template compiles')
const home = fs.readFileSync(root + '/pages/Community_menu/index.vue', 'utf8')
check(home.indexOf('@tap="goVisitorRegister"') > home.indexOf('@tap="goApprovalList"'), 'visitor card follows approval')
check(home.includes('name="account-fill" color="#2681f3"') && home.includes('class="approval-icon-bg"'), 'visitor icon reuses homepage blue circular style')
let destination = ''
uni.navigateTo = options => { destination = options.url }
ctx.goDetail(record)
check(destination.endsWith('/detail?registration_no=VR1'), 'record tap navigates using registration reference')
const detailSource = compiler.parseComponent(fs.readFileSync(root + '/pages/Community/visitorRegisterNew/detail.vue', 'utf8'))
check(compiler.compile(detailSource.template.content).errors.length === 0, 'detail template compiles')
const detailSandbox = { module: { exports: {} }, require: () => common, uni }
vm.runInNewContext(detailSource.script.content.replace('export default', 'module.exports ='), detailSandbox)
const detailPage = detailSandbox.module.exports
const detail = detailPage.data()
Object.entries(detailPage.methods).forEach(([key, value]) => { detail[key] = value.bind(detail) })
detailPage.onLoad.call(detail, { registration_no: 'VR1' })
detailPage.onShow.call(detail)
check(pending[0].url.endsWith('/detail') && pending[0].data.registration_no === 'VR1', 'detail loads selected record from server')
pending.shift().success({ visitor_name: '访客', organization_or_id: '测试单位', remark: '完整备注' })
check(detail.record.remark === '完整备注' && detail.fields.length === 8, 'detail displays all registration fields')
detail.loadDetail()
pending.shift().error({ data: { msg: '登记记录不存在' } })
check(detail.record === null && detail.error === '登记记录不存在' && !detail.loading, 'detail errors clear stale personal data and allow retry')
detail.loadDetail()
detailPage.onUnload.call(detail)
pending.shift().success({ visitor_name: '过期响应' })
check(detail.record === null, 'closed detail page ignores late response')
const path = require('path')
const output = path.resolve(__dirname, '../../../temp/visitor-register-management-build')
const appConfig = JSON.parse(fs.readFileSync(path.join(output, 'app.json'), 'utf8'))
const route = 'pages/Community/visitorRegisterNew/list'
check(!appConfig.pages.includes(route), 'visitor page is not incorrectly registered in main package')
const community = appConfig.subPackages.find(item => item.root === 'pages/Community')
check(community && community.pages.filter(item => item === 'visitorRegisterNew/list').length === 1, 'visitor page registered once with relative path in Community subpackage')
check(fs.existsSync(path.join(output, route + '.wxml')), 'subpackage page markup exists in build output')
check(community.pages.includes('visitorRegisterNew/detail') && !appConfig.pages.includes('pages/Community/visitorRegisterNew/detail'), 'detail is registered in Community subpackage only')
console.log('ALL ' + checks + ' MANAGEMENT CHECKS PASSED')
