const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs'), path = require('node:path'), vm = require('node:vm'), ts = require('typescript')
const source = fs.readFileSync(path.join(__dirname, '../src/utils/oa-reminder.ts'), 'utf8').replace(/^import .*$/gm, '').replace(/export /g, '')
const compiled = ts.transpileModule(source + '\nglobalThis.api = {remindBeforeOaAction, chooseOaReminder, registerOaReminderHost, unregisterOaReminderHost, oaReminderDialog, shouldRemindAfterLogin};', { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText
const flush = () => new Promise(resolve => setImmediate(resolve))
function fixture() {
    let now = Date.parse('2026-09-15T15:59:00Z'), invitation = '', queries = 0, saves = 0
    let pages = [{ route: 'packages/pages/order_confirm/order_confirm' }]
    const user = { token: 'first', userInfo: { id: 1 } }, storage = new Map(), timers = new Map(), visits = []
    const status = { bound: false, follow_status: 'unknown' }
    const ctx = {
        shallowReactive: x => x, useUserStore: () => user, getCurrentPages: () => pages,
        getOaInvitation: () => invitation, OA_BINDING_PATH: '/pages/oa_subscribe/oa_subscribe', BACK_URL: 'back',
        cache: { get: key => storage.get(key), set: (key, value) => storage.set(key, value) },
        Date: class extends Date { static now() { return now } },
        setTimeout: fn => { const key = {}; timers.set(key, fn); return key }, clearTimeout: key => timers.delete(key),
        oaReminderStatus: async () => { queries++; return { ...status } },
        oaReminderSkipToday: async () => { saves++; return {} },
        uni: { navigateTo: options => visits.push(options.url), showToast() {} }
    }
    vm.runInNewContext(compiled, ctx); ctx.api.registerOaReminderHost('page')
    return { api: ctx.api, ctx, status, user, storage, visits, timers, queries: () => queries, saves: () => saves,
        advance: ms => now += ms, invite: value => invitation = value, pages: value => pages = value }
}
test('本次跳过继续一次操作，下次重新提醒；今天跳过跨动作生效，北京时间零点恢复', async () => {
    const f = fixture()
    const first = f.api.remindBeforeOaAction(); await flush(); assert.equal(f.api.oaReminderDialog.title, '开启服务号提醒')
    f.api.chooseOaReminder('once'); assert.equal(await first, true)
    const second = f.api.remindBeforeOaAction(); await flush(); assert.equal(f.api.oaReminderDialog.host, 'page')
    f.api.chooseOaReminder('today'); assert.equal(await second, true); assert.equal(f.saves(), 1)
    assert.equal(await f.api.remindBeforeOaAction(), true); assert.equal(f.queries(), 2)
    f.advance(61000)
    const tomorrow = f.api.remindBeforeOaAction(); await flush(); assert.equal(f.queries(), 3)
    f.api.chooseOaReminder('once'); assert.equal(await tomorrow, true)
})
test('已关注且绑定不提醒，取消关注和未知状态分别提示', async () => {
    const f = fixture(); Object.assign(f.status, { bound: true, follow_status: 'followed' })
    assert.equal(await f.api.remindBeforeOaAction(), true); assert.equal(f.api.oaReminderDialog.host, '')
    for (const [status, title] of [['unfollowed', '重新关注，恢复微信提醒'], ['unknown', '确认关注状态']]) {
        f.status.follow_status = status; const request = f.api.remindBeforeOaAction(); await flush()
        assert.equal(f.api.oaReminderDialog.title, title); f.api.chooseOaReminder('once'); await request
    }
})
test('主动查看绑定方法暂停当前业务，只打开设置页', async () => {
    const f = fixture(); const request = f.api.remindBeforeOaAction(); await flush()
    f.api.chooseOaReminder('settings'); assert.equal(await request, false)
    assert.deepEqual(f.visits, ['/pages/oa_subscribe/oa_subscribe'])
})
test('查询超过两秒或失败直接继续，迟到响应不再弹窗', async () => {
    const f = fixture(); let resolve
    f.ctx.oaReminderStatus = () => new Promise(done => resolve = done)
    const request = f.api.remindBeforeOaAction(); await flush(); [...f.timers.values()][0]()
    assert.equal(await request, true); resolve(f.status); await flush(); assert.equal(f.api.oaReminderDialog.host, '')
    f.ctx.oaReminderStatus = async () => { throw new Error('网络失败') }
    assert.equal(await f.api.remindBeforeOaAction(), true)
})
test('离页中止等待，换号不提交旧操作，重复点击不叠加弹窗', async () => {
    const f = fixture(); const request = f.api.remindBeforeOaAction(); await flush()
    assert.equal(await f.api.remindBeforeOaAction(), false)
    f.api.unregisterOaReminderHost('page'); assert.equal(await request, false)
    f.api.registerOaReminderHost('page'); const again = f.api.remindBeforeOaAction(); await flush()
    f.user.token = 'second'; f.api.chooseOaReminder('once'); assert.equal(await again, false)
})
test('保存失败本机当天仍跳过，其他账号不继承，服务端跨设备偏好生效', async () => {
    const f = fixture(); f.ctx.oaReminderSkipToday = async () => { throw new Error('保存失败') }
    const first = f.api.remindBeforeOaAction(); await flush(); f.api.chooseOaReminder('today'); assert.equal(await first, true)
    assert.equal(await f.api.remindBeforeOaAction(), true)
    f.user.userInfo.id = 2; f.user.token = 'second'
    const other = f.api.remindBeforeOaAction(); await flush(); assert.equal(f.api.oaReminderDialog.host, 'page')
    f.api.chooseOaReminder('once'); await other
    f.status.reminder_snoozed_today = true; f.status.reminder_skip_until = 9999999999
    assert.equal(await f.api.remindBeforeOaAction(), true); assert.equal(f.api.oaReminderDialog.host, '')
})
test('待邀请优先，登录继续业务不额外提醒，独立登录需要检查', async () => {
    const f = fixture(); f.invite('invitation'); assert.equal(await f.api.remindBeforeOaAction(), true); assert.equal(f.queries(), 0)
    assert.equal(f.api.shouldRemindAfterLogin(), false); f.invite('')
    f.pages([{ route: 'pages/user/user' }, { route: 'pages/login/login' }]); assert.equal(f.api.shouldRemindAfterLogin(), true)
    f.storage.set('back', '/packages/pages/order_confirm/order_confirm?id=1'); assert.equal(f.api.shouldRemindAfterLogin(), false)
    f.storage.delete('back'); f.pages([{ route: 'packages/pages/dynamic_detail/dynamic_detail' }, { route: 'pages/login/login' }])
    assert.equal(f.api.shouldRemindAfterLogin(), false)
})
