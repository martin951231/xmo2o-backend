// Uses the existing admin project's Vue/Babel dependencies; no installation needed.
const fs = require('fs')
const path = require('path')
const vm = require('vm')
const assert = require('assert')
const adminRoot = process.argv[2] || 'D:/projectView/platform_dev'
const miniRoot = process.argv[3] || 'D:/wechatApp/village'
const babel = require(path.join(adminRoot, 'node_modules/@babel/core'))
const compiler = require(path.join(adminRoot, 'node_modules/vue-template-compiler'))
const modulePlugin = require(path.join(adminRoot, 'node_modules/@babel/plugin-transform-modules-commonjs'))
const pending = []
const redirects = []
const uni = {
  login: options => options.success({ code: 'mock-login-code' }),
  request: options => pending.push(options), showLoading () {}, hideLoading () {}, showToast () {}, showModal () {},
  redirectTo: options => redirects.push(options.url)
}
function load (source, imports = {}) {
  const module = { exports: {} }
  const code = babel.transformSync(source, { babelrc: false, configFile: false, plugins: [modulePlugin] }).code
  vm.runInNewContext(code, { module, exports: module.exports, require: name => {
    if (name in imports) return imports[name]
    throw new Error('Unexpected import: ' + name)
  }, uni, Date, Math, Promise, Number, Object, String, Error, setTimeout }, { timeout: 3000 })
  return module.exports
}
let checks = 0
function check (condition, message) { assert(condition, message); checks++; console.log('PASS ' + message) }
async function main () {
  const folder = path.join(miniRoot, 'pages/visitorRegister')
  const registration = load(fs.readFileSync(path.join(folder, 'registration.js'), 'utf8'), { '@/utils/common.js': { requestUrlV20: 'https://example.invalid/v20/public/index.php' } })
  const mixin = registration.default
  const page = compiler.parseComponent(fs.readFileSync(path.join(folder, 'person.vue'), 'utf8'))
  const person = load(page.script.content, { './registration': registration, './DateTimeField.vue': {}, './RegistrationNav.vue': {}, './PhoneAuthorization.vue': {} }).default
  const vehiclePage = compiler.parseComponent(fs.readFileSync(path.join(folder, 'vehicle.vue'), 'utf8'))
  const vehicle = load(vehiclePage.script.content, { './registration': registration, './DateTimeField.vue': {}, './RegistrationNav.vue': {}, './PhoneAuthorization.vue': {} }).default
  function instance (definition) {
    const result = Object.assign({}, mixin.data(), definition.data())
    Object.entries(Object.assign({}, mixin.methods, definition.methods)).forEach(([key, value]) => { result[key] = value.bind(result) })
    return result
  }
  const ctx = instance(person)
  mixin.onLoad.call(ctx, { scene: encodeURIComponent('v=123&t=visitor') })
  check(pending.length === 1 && pending[0].data.village_id === 123, 'scene resolves village without login')
  pending.shift().success({ statusCode: 200, data: { status: 1000, data: { status: 'published', message: '', theme: { background_color: '#123456', background_image: 'https://example.invalid/header.png', title_color: '#FFFFFF' } } } })
  await new Promise(resolve => setImmediate(resolve))
  check(pending.length === 1 && pending[0].url.endsWith('/login') && pending[0].data.code === 'mock-login-code', 'wx.login code sent for server verification')
  pending.shift().success({ statusCode: 200, data: { status: 1000, data: { auth_token: 'mock-token', nickname: '微信用户', phone: '13800000000',
    prefill: { organization_or_id: '当天单位', visit_time: '2000-01-01 00:00:00', id: 999 } } } })
  await new Promise(resolve => setImmediate(resolve))
  check(ctx.available && !ctx.checking, 'published form becomes available')
  check(ctx.form.organization_or_id === '当天单位' && ctx.form.visit_time !== '2000-01-01 00:00:00' && !('id' in ctx.form), 'only allowed non-time fields restored')
  check(ctx.showPhoneAuth && !('nickname' in ctx), 'entry opens phone sheet without collecting nickname')
  ctx.showPhoneAuth = false
  check(ctx.available, 'cancel keeps form available')
  check(ctx.registrationTheme.background_color === '#123456' && ctx.registrationTheme.background_image === 'https://example.invalid/header.png', 'registration applies the scanned village theme returned by server');
  const invalid = instance(person)
  mixin.onLoad.call(invalid, { scene: encodeURIComponent('v=123&t=vehicle') })
  check(!invalid.available && !invalid.checking && pending.length === 0, 'wrong form scene rejected')
  ctx.form = { visit_time: '2026-10-12 10:00:00', visitor_name: '测试', organization_or_id: '测试单位', phone: '13800000000', visit_reason: '拜访', host_name_room: '1栋101', remark: '' }
  await ctx.submitRegistration()
  check(pending.length === 0, 'consent required before network submission')
  ctx.consent = true
  await ctx.authorizePhone({ detail: { errMsg: 'getPhoneNumber:fail user deny' } })
  check(pending.length === 0, 'denied phone authorization sends no request')
  const phoneAuth = ctx.authorizePhone({ detail: { code: 'phone-code' } })
  check(pending[0].data.auth_token === 'mock-token' && pending[0].data.code === 'phone-code', 'phone code exchanged with verified session')
  pending.shift().success({ statusCode: 200, data: { status: 1000, data: { auth_token: 'authorized-token', phone: '13800000000' } } })
  await phoneAuth
  check(!ctx.showPhoneAuth && ctx.authToken === 'authorized-token' && ctx.form.phone === '13800000000', 'authorized phone and session updated')
  const original = JSON.stringify(ctx.form)
  const first = ctx.submitRegistration()
  const second = ctx.submitRegistration()
  check(pending.length === 1 && ctx.submitting, 'double click sends one request')
  const firstRequest = pending.shift()
  firstRequest.fail()
  await first; await second
  check(JSON.stringify(ctx.form) === original && !ctx.submitting && redirects.length === 0, 'network failure preserves input and does not show success')
  const retry = ctx.submitRegistration()
  const retryRequest = pending.shift()
  check(retryRequest.data.request_id === firstRequest.data.request_id, 'retry reuses idempotency key')
  retryRequest.success({ statusCode: 200, data: { status: 1003, msg: '当前登记功能已停止使用' } })
  await retry
  check(JSON.stringify(ctx.form) === original && redirects.length === 0, 'business failure preserves input and does not show success')
  const success = ctx.submitRegistration()
  pending.shift().success({ statusCode: 200, data: { status: 1000, data: { registration_no: 'VR_TEST' } } })
  await success
  check(redirects[0] === '/pages/visitorRegister/success', 'successful save navigates to fixed success page')
  check(registration.consumeSuccess() && !registration.consumeSuccess(), 'success page cannot be entered without a saved submission')
  const car = instance(vehicle)
  car.form = { plate_number: '粤bd12345', entry_time: '2026-10-12 10:00:00', exit_time: '', destination: '1栋', phone: '13800000000' }
  check(car.validate() === '' && car.form.plate_number === '粤BD12345', 'new energy plate and empty exit pass frontend validation')
  car.form.exit_time = '2026-10-12 09:00:00'
  check(car.validate().includes('不能早于'), 'frontend rejects exit before entry')
  ctx.available = false
  await ctx.submitRegistration()
  check(pending.length === 0, 'disabled frontend does not submit')
  for (const name of ['person', 'vehicle', 'success', 'DateTimeField', 'RegistrationNav', 'PhoneAuthorization']) {
    const descriptor = compiler.parseComponent(fs.readFileSync(path.join(folder, name + '.vue'), 'utf8'))
    check(compiler.compile(descriptor.template.content).errors.length === 0, name + ' Vue template valid')
  }
  const appJson = JSON.parse(fs.readFileSync(path.resolve(__dirname, '../../../temp/visitor-register-mini-build/app.json'), 'utf8'))
  check(appJson.pages[0] === 'pages/village_menu/index', 'existing launch page preserved')
  for (const pagePath of ['pages/visitorRegister/person', 'pages/visitorRegister/vehicle', 'pages/visitorRegister/success']) {
    check(appJson.pages.includes(pagePath), pagePath + ' registered in compiled miniapp')
  }
  console.log('ALL ' + checks + ' FRONTEND CHECKS PASSED')
}
main().catch(error => { console.error(error); process.exitCode = 1 })
