const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const vm = require('node:vm')
const ts = require('typescript')
const source = fs.readFileSync(require('node:path').join(__dirname, '../src/pages/oa_subscribe/oa_subscribe.vue'), 'utf8')
const script = source.match(/<script setup lang="ts">([\s\S]*?)<\/script>/)[1].replace(/^import .*$/gm, '')
const compiled = ts.transpileModule(script + '\nglobalThis.page = {status, inviteState, invitation, refresh, confirmBinding, goBack};', { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText
const flush = () => new Promise(resolve => setImmediate(resolve))
function fixture(token = '') {
    const hooks = {}, timers = new Map(), calls = { query: 0, confirm: 0, modal: 0 }
    let saved = token
    const server = { state: 'ready', can_confirm: true, expires_time: Date.now() / 1000 + 600, message: '确认本人账号' }
    const context = {
        reactive: x => x, ref: value => ({ value }), computed: fn => ({ get value() { return fn() } }),
        onLoad: fn => hooks.load = fn, onShow: fn => hooks.show = fn, onHide: fn => hooks.hide = fn, onUnload: fn => hooks.unload = fn,
        setTimeout: fn => { timers.set(1, fn); return 1 }, clearTimeout: id => timers.delete(id),
        useThemeStore: () => ({}), useUserStore: () => ({ isLogin: true, token: 'login', userInfo: {} }),
        getOaInvitation: () => saved, clearOaInvitation: () => { saved = '' }, captureOaInvitation() {}, OA_BINDING_PATH: '/pages/oa_subscribe/oa_subscribe',
        oaSubscribeStatus: async () => ({ bound: false }), oaInvitationStatus: async () => { calls.query++; return { ...server } },
        oaInvitationConfirm: async () => { calls.confirm++; return { state: 'completed', can_confirm: false, binding: { bound: true } } },
        showError() {}, showSuccess() {}, confirmModal: async () => { calls.modal++; return true }, uni: {}
    }
    vm.runInNewContext(compiled, context)
    hooks.load({})
    return { page: context.page, context, hooks, calls, timers, server, saved: () => saved }
}
test('普通入口只读关注状态，不生成邀请或展示确认资格', async () => {
    const f = fixture(); f.hooks.show(); await flush()
    assert.equal(f.calls.query, 0); assert.equal(f.page.inviteState.can_confirm, false)
    assert.doesNotMatch(source, /setInterval|oaSubscribeEntry|oaSubscribeQrCode|previewImage/)
})
test('邀请查询不绑定，主动确认一次完成且不追加弹窗', async () => {
    const f = fixture('a'.repeat(64)); f.hooks.show(); await flush()
    assert.equal(f.calls.confirm, 0); assert.equal(f.page.inviteState.can_confirm, true)
    await Promise.all([f.page.confirmBinding(), f.page.confirmBinding()])
    assert.equal(f.calls.confirm, 1); assert.equal(f.calls.modal, 0)
    assert.equal(f.saved(), ''); assert.equal(f.page.status.bound, true)
})
test('过期立即撤销确认资格，隐藏和离开清理定时器，返回重新查询', async () => {
    const f = fixture('a'.repeat(64)); f.hooks.show(); await flush()
    f.timers.get(1)(); assert.equal(f.page.inviteState.can_confirm, false)
    await f.page.confirmBinding(); assert.equal(f.calls.confirm, 0)
    f.hooks.hide(); assert.equal(f.timers.size, 0)
    f.hooks.show(); await flush(); assert.equal(f.calls.query, 2)
    f.hooks.unload(); assert.equal(f.timers.size, 0)
})
test('无效或冲突邀请不能提交', async () => {
    for (const state of ['invalid', 'conflict', 'unfollowed', 'unavailable']) {
        const f = fixture('a'.repeat(64)); Object.assign(f.server, { state, can_confirm: false })
        f.hooks.show(); await flush(); await f.page.confirmBinding(); assert.equal(f.calls.confirm, 0)
    }
})
test('页面隐藏后的旧请求不能恢复确认资格', async () => {
    const f = fixture('a'.repeat(64)); let resolve
    f.context.oaInvitationStatus = () => new Promise(done => { resolve = done })
    f.hooks.show(); await flush(); f.hooks.hide(); resolve({ ...f.server }); await flush()
    assert.equal(f.page.inviteState.can_confirm, false); assert.equal(f.timers.size, 0)
})
test('查询失败后可主动重试，不保留旧确认资格', async () => {
    const f = fixture('a'.repeat(64))
    f.context.oaInvitationStatus = async () => { throw new Error('网络失败') }
    f.hooks.show(); await flush(); assert.equal(f.page.inviteState.can_confirm, false)
    f.context.oaInvitationStatus = async () => ({ ...f.server })
    await f.page.refresh(); assert.equal(f.page.inviteState.can_confirm, true)
})
test('暂不设置与顶部返回使用明确层数，返回原通知页面', () => {
    const f = fixture('a'.repeat(64)); const visits = []
    f.context.getCurrentPages = () => [{ route: 'packages/pages/notification/index' }, { route: 'pages/oa_subscribe/oa_subscribe' }]
    f.context.uni.navigateBack = options => visits.push(options.delta)
    f.page.goBack(); assert.deepEqual(visits, [1]); assert.equal(f.saved(), '')
    assert.match(source, /BaseNavbar[^>]*@back="goBack"/)
})
test('服务号直接进入、只有登录中转时回个人中心，不调用无目标返回', () => {
    for (const routes of [['pages/oa_subscribe/oa_subscribe'], ['pages/login/login', 'pages/bind_mobile/bind_mobile', 'pages/oa_subscribe/oa_subscribe'], ['packages/pages/404/404', 'pages/oa_subscribe/oa_subscribe']]) {
        const f = fixture(); const visits = []
        f.context.getCurrentPages = () => routes.map(route => ({ route }))
        f.context.uni.switchTab = options => visits.push(options.url)
        f.page.goBack(); assert.deepEqual(visits, ['/pages/user/user'])
    }
})
test('返回跳过登录和重复邀请页，原生返回失败仍能进入个人中心', () => {
    const f = fixture(); const visits = []
    f.context.getCurrentPages = () => ['pages/user/user', 'pages/oa_subscribe/oa_subscribe', 'pages/login/login', 'pages/oa_subscribe/oa_subscribe'].map(route => ({ route }))
    f.context.uni.navigateBack = options => { visits.push(options.delta); options.fail() }
    f.context.uni.switchTab = options => { visits.push(options.url); options.fail() }
    f.context.uni.reLaunch = options => visits.push(options.url)
    f.page.goBack(); assert.deepEqual(visits, [3, '/pages/user/user', '/pages/user/user'])
})
