const { test } = require('node:test')
const assert = require('node:assert/strict')
const fs = require('node:fs')
const path = require('node:path')
const vm = require('node:vm')
const ts = require('typescript')
const read = page => fs.readFileSync(path.join(__dirname, `../src/pages/${page}/${page}.vue`), 'utf8')
const login = read('login').match(/const loginHandle = async[\s\S]*?\n}\r?\n/)[0]
const mobile = read('bind_mobile').match(/const redirectAfterBindMobile = [\s\S]*?\n}\r?\n/)[0]
function fixture() {
    const visits = [], cleared = []
    const ctx = {
        resolveLoginToken: data => data.token, shouldForceBindMobile: { value: true },
        getOaInvitation: () => 'c'.repeat(64), OA_BINDING_PATH: '/pages/oa_subscribe/oa_subscribe', BACK_URL: 'back',
        shouldRemindAfterLogin: () => false, remindBeforeOaAction: async () => true,
        cache: { remove: key => cleared.push(key) }, showSuccess() {}, showError() {},
        userStore: { login() {}, getUser: async () => {}, clearTemToken() {}, setTemToken() {} },
        navigateToBindMobile: () => visits.push('mobile'), router: { redirectTo: async url => visits.push(url) },
        uni: { hideLoading() {}, redirectTo: ({ url }) => visits.push(url) }
    }
    vm.runInNewContext(ts.transpileModule(login + '\n' + mobile + '\nglobalThis.api = {loginHandle, redirectAfterBindMobile}', { compilerOptions: { target: ts.ScriptTarget.ES2020 } }).outputText, ctx)
    return { api: ctx.api, visits, cleared, ctx }
}
test('已完成登录优先恢复邀请，不回首页或执行普通业务回跳', async () => {
    const f = fixture(); await f.api.loginHandle({ token: 'login', mobile: '13800000000' })
    assert.deepEqual(f.visits, ['/pages/oa_subscribe/oa_subscribe']); assert.deepEqual(f.cleared, ['back'])
})
test('缺手机号先补手机号，完成后直接回邀请页', async () => {
    const f = fixture(); await f.api.loginHandle({ token: 'temporary' }); assert.deepEqual(f.visits, ['mobile'])
    f.api.redirectAfterBindMobile(); assert.deepEqual(f.visits, ['mobile', '/pages/oa_subscribe/oa_subscribe'])
})
test('独立登录后主动查看绑定方法时暂停普通回跳', async () => {
    const f = fixture(); f.ctx.getOaInvitation = () => ''
    f.ctx.shouldRemindAfterLogin = () => true
    f.ctx.remindBeforeOaAction = async () => false
    await f.api.loginHandle({ token: 'login', mobile: '13800000000' })
    assert.deepEqual(f.visits, [])
})
