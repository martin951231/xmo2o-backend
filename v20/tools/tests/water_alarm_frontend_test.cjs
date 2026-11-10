const fs = require('fs')
const vm = require('vm')
const assert = require('assert')
const path = require('path')
const root = 'D:/projectView/platform_dev'
const compiler = require(path.join(root, 'node_modules/vue-template-compiler'))
const source = fs.readFileSync(path.join(root, 'src/views/community/village/waterAlarm/index.vue'), 'utf8')
const parsed = compiler.parseComponent(source)
assert.deepStrictEqual(compiler.compile(parsed.template.content).errors, [])
const script = parsed.script.content.replace(/^import .*$/gm, '').replace('export default', 'component =')
const sandbox = { component: null, moment: {}, api: {}, Date, Number, Blob, document: { hidden: false }, setInterval, clearInterval }
vm.runInNewContext(script, sandbox)
const component = sandbox.component
function context () {
  const ctx = component.data()
  Object.entries(component.methods).forEach(([name, method]) => { ctx[name] = method.bind(ctx) })
  Object.entries(component.computed || {}).forEach(([name, getter]) => { Object.defineProperty(ctx, name, { get: () => getter.call(ctx) }) })
  ctx.$set = (items, index, value) => { items[index] = value }
  ctx.messages = []
  ctx.$message = Object.fromEntries(['error', 'success', 'warning'].map(key => [key, msg => ctx.messages.push([key, msg])]))
  return ctx
}
async function run () {
  const ctx = context()
  const device = { id: 1, name: '测试报警器', volume: 1, state_known: true, last_seen: Date.now() / 1000, state: { alarm_active: false, flash_active: false, volume: 1 } }
  ctx.rows = [device]
  let resolve
  let calls = 0
  ctx.call = () => { calls++; return new Promise(done => { resolve = done }) }
  const pending = ctx.operate(device, 'alarm_start')
  assert.strictEqual(ctx.busy, true)
  assert.strictEqual(ctx.rows[0].state.alarm_active, false, 'no optimistic alarm state')
  await ctx.operate(device, 'alarm_start')
  assert.strictEqual(calls, 1, 'duplicate clicks suppressed')
  resolve({ ok: true, message: '成功', device: { ...device, state: { alarm_active: true, flash_active: true } } })
  assert.strictEqual(await pending, true)
  assert.strictEqual(ctx.rows[0].state.alarm_active, true)
  assert.strictEqual(ctx.busy, false)
  ctx.call = async () => { throw new Error('timeout') }
  assert.strictEqual(await ctx.operate(ctx.rows[0], 'stop'), false)
  assert.strictEqual(ctx.rows[0].state_known, false, 'failed stop becomes unknown')
  assert.strictEqual(ctx.busy, false, 'controls recover after failure')
  let stopAction
  ctx.call = async (method, params) => { stopAction = params.action; return { ok: true, message: '已停止', device } }
  await ctx.operate(ctx.rows[0], 'stop')
  assert.strictEqual(stopAction, 'stop', 'stop allowed from unknown state')
  assert.strictEqual(ctx.known({ ...device, last_seen: 1 }), false, 'stale snapshot not presented as current')
  ctx.volume = 0; ctx.volumeVisible = true
  await ctx.saveVolume()
  assert.strictEqual(ctx.volumeVisible, true, 'invalid mute volume not submitted')
  ctx.volume = 3; ctx.selected = device
  ctx.call = async () => ({ ok: false, message: '失败', device })
  await ctx.saveVolume()
  assert.strictEqual(ctx.volumeVisible, true, 'failed volume keeps dialog open')
  ctx.name = ' '; ctx.editorVisible = true
  await ctx.save()
  assert.strictEqual(ctx.saving, false, 'blank name rejected')
  ctx.name = '地下车库报警器'
  ctx.call = async () => { throw new Error('offline') }
  await ctx.save()
  assert.strictEqual(ctx.editorVisible, true, 'failed save preserves input')
  assert.strictEqual(ctx.name, '地下车库报警器')
  assert(source.includes('requestNew(api[action], data'), 'bounded request timeout uses shared auth client')
  assert(source.includes('timeout: 30000'))
  assert(source.includes('停止声光'), 'unknown-state stop entry exists')
  assert(fs.readFileSync(path.join(root, 'src/router/routerComponents/community/village/index.js'), 'utf8').includes("waterAlarm: () => import('@/views/community/village/waterAlarm/index')"))
  ctx.ttsSelected = device
  ctx.ttsContent = '水'.repeat(67)
  assert.strictEqual(ctx.ttsByteLength, 201, 'Chinese validated as UTF8 bytes')
  let textCalls = 0
  ctx.call = async () => { textCalls++; return { list: [] } }
  await ctx.addTtsText()
  assert.strictEqual(textCalls, 0, 'oversized text never submitted')
  ctx.ttsContent = '[v10]不要允许控制码'
  await ctx.addTtsText()
  assert.strictEqual(textCalls, 0, 'inline voice control codes rejected')
  ctx.ttsContent = '发生水浸，请处理'
  ctx.call = async () => { throw new Error('offline') }
  await ctx.addTtsText()
  assert.strictEqual(ctx.ttsContent, '发生水浸，请处理', 'failed save preserves TTS input')
  assert.strictEqual(ctx.ttsSaving, false)
  ctx.call = async () => ({ list: [{ id: 7, content: ctx.ttsContent }] })
  await ctx.addTtsText()
  assert.strictEqual(ctx.ttsRows[0].id, 7)
  assert.strictEqual(ctx.ttsContent, '', 'successful add refreshes list and clears input')
  let payload
  ctx.call = async (action, params) => { payload = params; return { ok: true, message: '播报', device } }
  await ctx.speakText({ id: 7 })
  assert.strictEqual(payload.id, 1)
  assert.strictEqual(payload.text_id, 7)
  assert.strictEqual(payload.action, 'tts_start')
  assert.strictEqual(ctx.playingTextId, 0, 'TTS button recovers after response')
  assert(source.includes('停止播报'))
  ctx.ttsRows = [{ id: 7, content: '水浸' }]
  ctx.call = async () => { throw new Error('offline') }
  await ctx.deleteTtsText({ id: 7 })
  assert.strictEqual(ctx.ttsRows.length, 1, 'failed delete keeps row visible')
  assert.strictEqual(ctx.ttsSaving, false)
  ctx.call = async (action, params) => { assert.strictEqual(action, 'deleteText'); assert.strictEqual(params.id, 1); assert.strictEqual(params.text_id, 7); return { list: [] } }
  await ctx.deleteTtsText({ id: 7 })
  assert.strictEqual(ctx.ttsRows.length, 0, 'confirmed delete refreshes rows')
  assert(source.includes('@confirm="deleteTtsText(item)"'), 'delete requires confirmation')
  console.log('PASS Vue template, confirmed-state buttons, duplicate-click protection, failure recovery, stop from unknown state, stale status, volume/name validation, input preservation, bounded requests and route registration')
}
run().catch(error => { console.error(error); process.exitCode = 1 })
