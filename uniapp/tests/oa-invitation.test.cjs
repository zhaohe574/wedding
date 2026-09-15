const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const ts = require('typescript')
test('真实缓存保留冷启动邀请，普通进入不覆盖，非法入口清理，十分钟后失效', () => {
    let now = 1000000
    const storage = new Map()
    const compile = text => ts.transpileModule(text, { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText
    const cache = fs.readFileSync(path.join(__dirname, '../src/utils/cache.ts'), 'utf8').replace('export default cache', '')
    const invitation = fs.readFileSync(path.join(__dirname, '../src/utils/oa-invitation.ts'), 'utf8').replace(/^import .*$/gm, '').replace(/export /g, '')
    const context = {
        Date: class extends Date { constructor() { super(now) } },
        uni: { setStorageSync: (k, v) => storage.set(k, v), getStorageSync: k => storage.get(k), removeStorageSync: k => storage.delete(k) }
    }
    vm.runInNewContext(compile(cache + '\n' + invitation + '\nglobalThis.api = {captureOaInvitation, getOaInvitation};'), context)
    const api = context.api, route = '/pages/oa_subscribe/oa_subscribe', token = 'b'.repeat(64)
    api.captureOaInvitation(route, { invitation: token }); assert.equal(api.getOaInvitation(), token)
    api.captureOaInvitation(route, {}); assert.equal(api.getOaInvitation(), token)
    api.captureOaInvitation('/pages/index/index', { invitation: 'invalid' }); assert.equal(api.getOaInvitation(), token)
    api.captureOaInvitation(route, { invitation: 'invalid' }); assert.equal(api.getOaInvitation(), '')
    api.captureOaInvitation(route, { invitation: token }); now += 601000; assert.equal(api.getOaInvitation(), '')
})
