import { ref } from 'vue'

/**
 * 轻触觉反馈（01 总方案 §3.11）。
 *
 * 分级：light = Tab / 选中 / 点赞 / 勾选；medium = 提交 / 抽屉吸附全屏；
 *       heavy = 失败与不可逆操作。振动永远只是补充，视觉反馈必须独立存在。
 *
 * 降级：`uni.vibrateShort` 在 H5 和部分安卓机型上不存在或无效，
 *       统一 try/catch 静默失败，不向调用方抛错。
 */

export type HapticLevel = 'light' | 'medium' | 'heavy'

/** Storage 键：设置页「触感反馈」开关的持久化位置 */
export const HAPTICS_STORAGE_KEY = 'miniapp_haptics_enabled'

/** light 级同控件节流窗口：连点不连续震 */
const LIGHT_THROTTLE_MS = 300

/**
 * 默认开启：只有显式落过 `'0'` / `false` / `'false'` 才算关闭。
 * 老版本没有这个键，读出来是空串，必须落到「开」而不是「关」。
 */
function readEnabled(): boolean {
    try {
        const raw = uni.getStorageSync(HAPTICS_STORAGE_KEY)
        return !(raw === '0' || raw === false || raw === 'false')
    } catch {
        // 存储不可读（隐私模式等）：按默认开处理
        return true
    }
}

/** 响应式开关状态：页面模板可直接绑定（如设置页的 elastic-switch） */
export const hapticsEnabled = ref<boolean>(readEnabled())

export function isHapticsEnabled(): boolean {
    return hapticsEnabled.value
}

export function setHapticsEnabled(next: boolean): void {
    hapticsEnabled.value = next
    try {
        uni.setStorageSync(HAPTICS_STORAGE_KEY, next ? '1' : '0')
    } catch {
        // 存储不可写：本次会话仍生效，冷启动回到默认值
    }
}

/** 按控件维度的上次触发时间；键不同互不干扰 */
const lastLightAt: Record<string, number> = {}

export function haptic(level: HapticLevel = 'light', throttleKey?: string): void {
    if (!hapticsEnabled.value) return

    if (level === 'light') {
        const key = throttleKey || '_default'
        const now = Date.now()
        if (now - (lastLightAt[key] || 0) < LIGHT_THROTTLE_MS) return
        lastLightAt[key] = now
    }

    try {
        // H5 / 低版本基础库没有该方法时会抛 TypeError，与「机型不支持」一样静默降级
        uni.vibrateShort({ type: level } as any)
    } catch {
        /* 视觉反馈始终存在，触觉失败不影响交互 */
    }
}
