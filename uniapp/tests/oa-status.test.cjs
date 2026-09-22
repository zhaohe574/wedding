const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const ts = require('typescript')

const source = fs.readFileSync(path.join(__dirname, '../src/utils/oa-status.ts'), 'utf8')
    .replace(/^import .*$/gm, '')
    .replace(/export /g, '')

const compiled = ts.transpileModule(
    source + '\nglobalThis.api = { isOaBound, checkOaBoundStatus, setOaBoundStatus, useOaBound, getOaBoundCacheKey };',
    { compilerOptions: { target: ts.ScriptTarget.ES2020 } }
).outputText

const flush = () => new Promise(resolve => setImmediate(resolve))

function fixture(initialLogin = true, initialBound = false) {
    const storage = new Map()
    let apiCalls = 0
    let registeredListeners = new Map()

    const user = {
        isLogin: initialLogin,
        token: 'test_token',
        userInfo: { id: 88, mobile: '13800000000' }
    }

    const ctx = {
        ref: (val) => ({ value: val }),
        useUserStore: () => user,
        cache: {
            get: (k) => storage.get(k),
            set: (k, v) => storage.set(k, v)
        },
        oaSubscribeStatus: async () => {
            apiCalls++
            return { bound: initialBound }
        },
        uni: {
            $on: (event, fn) => {
                registeredListeners.set(event, fn)
            },
            $emit: (event, payload) => {
                const handler = registeredListeners.get(event)
                if (handler) handler(payload)
            }
        }
    }

    vm.runInNewContext(compiled, ctx)

    return {
        api: ctx.api,
        user,
        storage,
        getApiCalls: () => apiCalls,
        emit: (event, payload) => ctx.uni.$emit(event, payload),
        setServerBound: (bound) => {
            ctx.oaSubscribeStatus = async () => {
                apiCalls++
                return { bound }
            }
        }
    }
}

test('未登录时不查询服务号绑定状态且置为 false', async () => {
    const f = fixture(false)
    const result = await f.api.checkOaBoundStatus()
    assert.equal(result, false)
    assert.equal(f.api.isOaBound.value, false)
    assert.equal(f.getApiCalls(), 0)
})

test('本地有已绑定缓存时立即水合返回 true，避免页面闪烁，不发起重复请求', async () => {
    const f = fixture(true, false)
    const cacheKey = f.api.getOaBoundCacheKey(88)
    f.storage.set(cacheKey, true)

    const result = await f.api.checkOaBoundStatus(false)
    assert.equal(result, true)
    assert.equal(f.api.isOaBound.value, true)
    assert.equal(f.getApiCalls(), 0)
})

test('无缓存时查询服务端并写入缓存', async () => {
    const f = fixture(true, true)
    const result = await f.api.checkOaBoundStatus(false)
    assert.equal(result, true)
    assert.equal(f.api.isOaBound.value, true)
    assert.equal(f.getApiCalls(), 1)

    const cacheKey = f.api.getOaBoundCacheKey(88)
    assert.equal(f.storage.get(cacheKey), true)
})

test('强制刷新时即使有未绑定缓存也重新获取最新绑定状态', async () => {
    const f = fixture(true, true)
    const cacheKey = f.api.getOaBoundCacheKey(88)
    f.storage.set(cacheKey, false)

    const result = await f.api.checkOaBoundStatus(true)
    assert.equal(result, true)
    assert.equal(f.api.isOaBound.value, true)
    assert.equal(f.getApiCalls(), 1)
    assert.equal(f.storage.get(cacheKey), true)
})

test('全局广播 oa_binding_changed 能够即时同步绑定与解绑状态', async () => {
    const f = fixture(true, false)
    assert.equal(f.api.isOaBound.value, false)

    // 模拟在绑定页绑定成功触发广播
    f.emit('oa_binding_changed', { bound: true })
    assert.equal(f.api.isOaBound.value, true)
    const cacheKey = f.api.getOaBoundCacheKey(88)
    assert.equal(f.storage.get(cacheKey), true)

    // 模拟解绑触发广播
    f.emit('oa_binding_changed', { bound: false })
    assert.equal(f.api.isOaBound.value, false)
    assert.equal(f.storage.get(cacheKey), false)
})
