// Run the installed WeChat developer tool's own fileFlatter validator offline.
const fs = require('fs')
const path = require('path')
const vm = require('vm')
const assert = require('assert')
const compilerRoot = 'D:/HBuilderX/plugins/uniapp-cli'
const validatorFile = 'D:/微信web开发者工具/code/package.nw/js/common/miniprogram-builder/modules/corecompiler/original/protect/fileFlatter.js'
const output = process.argv[2] || path.resolve(__dirname, '../../../temp/visitor-register-management-build')
const moduleResult = { exports: {} }
function dependency(name) {
  // Desktop wrapper lazily imports dependencies; Node can import them normally.
  if (name === 'licia/lazyImport') return req => req
  return require(require.resolve(name, { paths: [compilerRoot, 'D:/projectView/platform_dev'] }))
}
vm.runInNewContext(fs.readFileSync(validatorFile, 'utf8'), {
  exports: moduleResult.exports, module: moduleResult, require: dependency
}, { filename: validatorFile })
const validate = moduleResult.exports.tryTranslateSingleFile
function check(code, filePath = 'common/vendor.js') {
  return validate({ code, filePath, rootPath: '', nameMapping: {}, miniProgramJSFiles: [],
    sourceFileName: filePath, resolveAlias: () => false })
}
const invalid = check('var module = {exports: {}, require: function() { return 1 }};')
assert(!invalid.translated && invalid.errMsg.includes("ObjectProperty"), 'Must reproduce original WeChat protection error')
assert(check('var module = {exports: {}, "require": function() { return 1 }};').translated, 'Equivalent quoted key must pass')
const uglify = dependency('uglify-js')
const quoted = 'globalThis.result = {path:"demo",exports:{},"require":function(){return this.path}};'
assert(!check(uglify.minify(quoted, { toplevel: true }).code).translated, 'Reproduce quoted-key regression during upload minification')
process.env.UNI_CLI_CONTEXT = compilerRoot
const Plugin = require('D:/wechatApp/uniapp_community/build/WechatCodeProtectionCompatPlugin')
const { RawSource } = dependency('webpack-sources')
let emit
new Plugin().apply({ hooks: { emit: { tap(name, fn) { emit = fn } } } })
const fixture = { assets: { 'fixture.js': new RawSource(quoted) } }
emit(fixture)
const fixed = fixture.assets['fixture.js'].source()
const compact = uglify.minify(fixed, { toplevel: true })
assert(!compact.error && check(compact.code).translated, 'Runtime repair survives upload minification')
const before = {}, after = {}
vm.runInNewContext(quoted, before)
vm.runInNewContext(compact.code, after)
assert.deepStrictEqual(Object.keys(after.result), Object.keys(before.result))
assert.strictEqual(after.result['require'](), before.result['require']())
for (const flag of ['writable', 'enumerable', 'configurable']) {
  assert.strictEqual(Object.getOwnPropertyDescriptor(after.result, 'require')[flag], true)
}
assert(JSON.parse(fs.readFileSync(path.join(output, 'project.config.json'), 'utf8')).setting.uglifyFileName === true, 'Code protection remains enabled in built project')
function files(dir) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const full = path.join(dir, entry.name)
    return entry.isDirectory() ? files(full) : entry.name.endsWith('.js') ? [full] : []
  })
}
let count = 0
const failed = []
for (const file of files(output)) {
  const code = fs.readFileSync(file, 'utf8')
  const result = check(code, path.relative(output, file).replace(/\\/g, '/'))
  if (!result.translated) failed.push({ file: path.relative(output, file), error: result.errMsg.slice(0, 220) })
  const minimized = uglify.minify(code, { toplevel: true, sourceMap: { includeSources: true, filename: file } })
  if (minimized.error) throw minimized.error
  const uploaded = check(minimized.code, path.relative(output, file).replace(/\\/g, '/'))
  if (!uploaded.translated) failed.push({ file: path.relative(output, file), stage: 'upload minification', error: uploaded.errMsg.slice(0, 220) })
  count++
}
if (failed.length) {
  console.error(JSON.stringify(failed, null, 2))
  process.exitCode = 1
} else {
  console.log('PASS original error and quoted-key minification regression reproduced; final repair preserves runtime semantics')
  console.log('PASS code protection enabled; installed WeChat validator accepts all ' + count + ' files before AND after upload-style minification')
}
