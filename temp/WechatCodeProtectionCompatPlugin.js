// WeChat's code-protection parser treats an unquoted object key named `require`
// as a module-loader reference. Quote only that key; keep its runtime behavior.
function compilerDependency(name) {
  return require(require.resolve(name, {
    paths: [process.env.UNI_CLI_CONTEXT || process.cwd(), __dirname]
  }))
}

class WechatCodeProtectionCompatPlugin {
  apply(compiler) {
    compiler.hooks.emit.tap('WechatCodeProtectionCompatPlugin', compilation => {
      const parser = compilerDependency('@babel/parser')
      const traverse = compilerDependency('@babel/traverse').default
      const { ReplaceSource, SourceMapSource, RawSource } = compilerDependency('webpack-sources')
      let count = 0
      Object.keys(compilation.assets).filter(name => name.endsWith('.js')).forEach(name => {
        const asset = compilation.assets[name]
        const code = asset.source().toString()
        if (!code.includes('require')) return
        const ranges = []
        traverse(parser.parse(code, { sourceType: 'unambiguous' }), {
          ObjectProperty(path) {
            const node = path.node
            if (!node.computed && !node.shorthand && node.key.type === 'Identifier' && node.key.name === 'require') {
              ranges.push([node.key.start, node.key.end - 1])
            }
          }
        })
        if (!ranges.length) return
        // Run after minification, otherwise the minifier may remove the quotes again.
        const mapName = name + '.map'
        const mapAsset = compilation.assets[mapName]
        const base = mapAsset
          ? new SourceMapSource(code, name, JSON.parse(mapAsset.source().toString()))
          : asset
        const replacement = new ReplaceSource(base, name)
        ranges.forEach(([start, end]) => replacement.replace(start, end, '"require"'))
        compilation.assets[name] = replacement
        if (mapAsset) {
          const result = replacement.sourceAndMap()
          if (result.map) compilation.assets[mapName] = new RawSource(JSON.stringify(result.map))
        }
        count += ranges.length
      })
      console.log('[WeChat code protection] Quoted ' + count + ' require object key(s).')
    })
  }
}
module.exports = WechatCodeProtectionCompatPlugin
