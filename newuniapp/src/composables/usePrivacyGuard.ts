/**
 * 隐私授权守卫（合规 B1，阻断级）。
 *
 * ── 三条机制，都是官方文档写明的，决定了这里的写法 ──────────────────
 *
 * ① **同意状态靠真实 button 记录，不靠 resolve**
 *    `resolve({ event: 'agree' })` 只是让挂起的 API 继续，**微信记录"用户已同意"靠的是
 *    `<button open-type="agreePrivacyAuthorization">` 被点击这个动作本身**，且 resolve 里
 *    要回传该 button 的 `buttonId`。所以弹窗里的"同意"必须是原生 button，不能是普通 view/自绘按钮
 *    —— 否则可能既没记录同意，又把微信的官方兜底弹窗挡掉了（反向优化）。
 *
 * ② **接口是「挂起」不是「失败」**
 *    `onNeedPrivacyAuthorization` 触发后，原 API 调用会**停在那里等 resolve**：
 *    同意 → 继续执行；拒绝 → 报错。所以**不需要**写"失败后重放"的包装器 ——
 *    多一层重放会在"同意后接口本来就成功"的情况下再调一次（表现为选两次图）。
 *
 * ③ **必须用队列，不能早退**
 *    弹窗开着时可能又触发一次隐私接口（典型：连点两下"上传图片"）。
 *    若第二次直接 `return` 不 resolve，那个挂起的请求永远不会被应答 → Promise 悬挂 →
 *    `await` 永不返回，用户看到"点了没反应"。所以每次回调都入队，决策时逐一应答。
 *
 * 弹窗本体 `components/privacy-popup` 挂在 `page-shell` 上 —— **不能挂 App.vue**：
 * 小程序端 App 根没有 wxss 输出路径，组件样式会变成 0 字节（app-toast 当初就这么丢的样式）。
 */
import { ref } from 'vue'
import { toast } from '@/composables/useToast'

/** 弹窗可见性（privacy-popup 消费） */
export const privacyVisible = ref(false)
/** 触发来源（微信 eventInfo.referrer），仅用于排查 */
export const privacyReferrer = ref('')

/**
 * 「同意」按钮的 id。**必须与 privacy-popup 里 `<button :id>` 用同一个常量** ——
 * resolve 时回传的 buttonId 要和真实按钮对上，同意才会被微信记录。
 */
export const AGREE_BTN_ID = 'privacy-agree-btn'

/** 自家协议页：仅在后台未配置隐私指引 / 基础库过低时回落 */
const FALLBACK_POLICY_PAGE = '/pages/agreement/agreement?type=privacy'

type PrivacyPayload = { event: 'agree' | 'disagree'; buttonId?: string }
type PrivacyResolve = (res: PrivacyPayload) => void

/** 等待应答的 resolve 队列（并发调用各自都能被应答，不悬挂） */
let pendingResolves: PrivacyResolve[] = []

/** 在 onLaunch 里调用一次。H5 / App 端无此 API，静默跳过。 */
export function setupPrivacyGuard() {
    // #ifdef MP-WEIXIN
    // 项目未声明 wx 全局（@dcloudio 的类型里没有），从 globalThis 取
    const w = (globalThis as any)?.wx
    if (!w || typeof w.onNeedPrivacyAuthorization !== 'function') return

    w.onNeedPrivacyAuthorization((resolve: PrivacyResolve, eventInfo: any) => {
        pendingResolves.push(resolve)
        privacyReferrer.value = String(eventInfo?.referrer || '')
        // 弹窗只开一次：已经在显示时不重复置 true，也不丢弃 resolve
        privacyVisible.value = true
    })
    // #endif
}

/** 统一应答：清空队列并逐一回填，保证不会有 resolve 被丢掉 */
function drain(payload: PrivacyPayload) {
    const list = pendingResolves
    pendingResolves = []
    list.forEach((r) => {
        try {
            r(payload)
        } catch {
            // 单个 resolve 失败不影响其它
        }
    })
}

/** 用户点「同意并继续」（由原生 button 的 agreeprivacyauthorization 事件触发） */
export function agreePrivacy() {
    privacyVisible.value = false
    drain({ event: 'agree', buttonId: AGREE_BTN_ID })
}

/** 用户点「暂不同意」 */
export function disagreePrivacy() {
    privacyVisible.value = false
    // 拒绝时也把队列全部答掉（答 disagree），否则未应答的请求会挂死
    drain({ event: 'disagree' })
    toast.info('未同意隐私政策，相关功能暂不可用')
}

/**
 * 打开隐私政策。
 *
 * **优先 `wx.openPrivacyContract()`** —— 它打开的是微信后台那份《小程序用户隐私保护指引》，
 * 而审核比对的就是那一份。自家协议页（后端 index.policy）内容一旦与后台不一致
 * （漏收集项、缺生效日期），审核会挑「隐私政策与实际收集不符」，比"没有入口"更难解释。
 * 所以自家页只作为回落：后台未配置指引或基础库过低时才用。
 */
export function openPrivacyPolicy() {
    const w = (globalThis as any)?.wx
    if (w && typeof w.openPrivacyContract === 'function') {
        w.openPrivacyContract({
            fail: () => openFallbackPolicy()
        })
        return
    }
    openFallbackPolicy()
}

function openFallbackPolicy() {
    uni.navigateTo({
        url: FALLBACK_POLICY_PAGE,
        fail: () => toast.info('暂时无法打开隐私政策')
    })
}

/** 失败信息是否由隐私相关原因引起（用于给出更准确的提示，不用于重放） */
export function isPrivacyError(err: unknown): boolean {
    const msg = String(
        (err as any)?.errMsg || (err as any)?.message || (err as any)?.errno || err || ''
    )
    return /privacy|隐私|112/i.test(msg)
}
