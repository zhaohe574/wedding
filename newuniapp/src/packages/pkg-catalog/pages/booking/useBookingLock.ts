import { computed, ref, type Ref } from 'vue'
import type { Tone } from '@shared/contracts'
import { lockSchedule, releaseLock } from '@/api/catalog'
import { toast } from '@/composables/useToast'
import { report } from '@/platform/report'
import type { BookingLockState } from './booking-helpers'

/**
 * 锁档状态机（08 屏进入即锁，15 分钟 TTL）。
 * - locked：已锁住，显示真实剩余时间
 * - failed：业务失败（别人先锁）→ 必须拦，留在本页引导改期
 * - expired：保留超时 → 可原地重锁，和「被抢」不是同一件事
 * - error：接口异常 → 不拦，说真话「服务人员确认前不预留」
 */
export function useBookingLock(options: {
    staffId: Ref<number>
    date: Ref<string>
    orderId: Ref<number>
}) {
    const { staffId, date, orderId } = options

    const lockState = ref<BookingLockState>('idle')
    const lockLeft = ref(0)
    let lockTimer: ReturnType<typeof setInterval> | null = null

    const orderBadge = computed<{ label: string; tone: Tone }>(() => {
        if (orderId.value > 0) return { label: '待确认', tone: 'warning' }
        if (lockState.value === 'failed') return { label: '档期被占用', tone: 'danger' }
        if (lockState.value === 'expired') return { label: '档期已释放', tone: 'warning' }
        if (lockState.value === 'locked') return { label: '档期已保留', tone: 'success' }
        if (lockState.value === 'locking') return { label: '锁档中', tone: 'neutral' }
        return { label: '待提交', tone: 'warning' }
    })

    const lockSummaryText = computed(() => {
        if (orderId.value > 0) return '申请已提交，等待服务人员确认档期'
        if (lockState.value === 'locked') {
            const mm = String(Math.floor(lockLeft.value / 60)).padStart(2, '0')
            const ss = String(lockLeft.value % 60).padStart(2, '0')
            return `保留 ${mm}:${ss}，超时重选`
        }
        if (lockState.value === 'locking') return '正在为你锁定档期…'
        if (lockState.value === 'failed') return '该档期刚被订走，请改期'
        if (lockState.value === 'expired') return '保留时间已到，可重新锁档'
        return '服务人员确认前档期暂不预留'
    })

    function stopLockTimer() {
        if (lockTimer) clearInterval(lockTimer)
        lockTimer = null
    }

    async function doLock() {
        if (!staffId.value || !date.value) return
        if (lockState.value === 'locking') return
        if (lockState.value === 'locked' && lockLeft.value > 0) return
        lockState.value = 'locking'
        try {
            await lockSchedule({ staff_id: staffId.value, date: date.value, lock_duration: 900 })
            lockState.value = 'locked'
            lockLeft.value = 900
            stopLockTimer()
            lockTimer = setInterval(() => {
                lockLeft.value -= 1
                if (lockLeft.value <= 0) {
                    stopLockTimer()
                    // 超时 ≠ 被别人订走：前者可以原地重锁，后者只能改期
                    lockState.value = 'expired'
                }
            }, 1000)
        } catch (e: any) {
            // 区分「业务失败（别人已锁）」与「接口异常」：前者必须拦，后者不能拦。
            const msg = String(e?.message || '')
            const isBusiness = /锁定|已被|不可约|占用|已订/.test(msg)
            if (isBusiness) {
                lockState.value = 'failed'
                toast.info('该档期刚被订走，请换一个日期')
            } else {
                lockState.value = 'error'
                report('api_error', {
                    endpoint: 'schedule.lockSchedule',
                    message: '档期锁定失败（降级放行）'
                })
            }
        }
    }

    /** 保留超时后原地重锁；和「被别人订走」不同，后者只能改期 */
    function relockSchedule() {
        stopLockTimer()
        lockLeft.value = 0
        lockState.value = 'idle'
        doLock()
    }

    function releaseActiveLock(targetDate?: string) {
        const d = targetDate || date.value
        if (lockState.value === 'locked' && !orderId.value && staffId.value && d) {
            releaseLock({ staff_id: staffId.value, date: d }).catch(() => undefined)
        }
        stopLockTimer()
        lockState.value = 'idle'
    }

    function markOrderCreated() {
        lockState.value = 'idle'
        stopLockTimer()
    }

    return {
        lockState,
        lockLeft,
        orderBadge,
        lockSummaryText,
        stopLockTimer,
        doLock,
        relockSchedule,
        releaseActiveLock,
        markOrderCreated
    }
}
