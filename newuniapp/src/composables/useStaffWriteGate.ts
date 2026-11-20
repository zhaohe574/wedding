/**
 * 服务人员工作台写能力灰度开关（feature_switch.staff_center_write）。
 *
 * 背景：API 线为 P0 写能力（资料/动态/作品/证书增删改）加了灰度，
 * V2Logic / IndexLogic 的 feature_switch 下发新增 staff_center_write（0=关闭 1=开放，默认 0）。
 * 开关关闭时后端对写请求返回 code=0、msg=『功能灰度中，暂未开放』。
 *
 * 前端口径（review/publish 的 canReview 同款，两条铁律）：
 *   1. 读不到 / 空值一律按「关闭」处理 —— 宁可藏入口，不给必失败按钮；
 *   2. 入口随开关隐藏（列表页入口、卡片操作、编辑按钮），但页面本身仍可直接访问：
 *      编辑页表单可浏览，提交/删除按钮替换为提示文案，submit 函数开头再兜底一次。
 *
 * 消费方：center / dynamic-list / work-list / certificate-list / profile +
 * profile-edit / dynamic-publish / work-edit / certificate-edit。
 */
import { computed } from 'vue'
import { useAppStore } from '@/stores/app'

/** 灰度关闭时的统一提示文案（与后端拒绝 msg 保持一致） */
export const STAFF_WRITE_GATE_TIP = '功能灰度中，暂未开放'

export function useStaffWriteGate() {
    const appStore = useAppStore()

    const canWrite = computed(() => {
        const cfg: any = (appStore.config as any) || {}
        // 两处取值路径与 review/publish 的 canReview 一致：顶层 feature_switch 优先，index 下兜底
        const flag =
            cfg?.feature_switch?.staff_center_write ??
            cfg?.index?.feature_switch?.staff_center_write
        if (flag === undefined || flag === null || flag === '') return false
        return Number(flag) === 1
    })

    return { canWrite }
}
