import { computed, nextTick, reactive, ref, watch, type ComputedRef, type Ref } from 'vue'
import { getBookingRoleCandidates } from '@/api/catalog'
import { toast } from '@/composables/useToast'
import cache from '@/utils/cache'
import { navigate } from '@/utils/link'
import { regionParams, type RegionContext } from '@/utils/region'
import { shortMoney, type RolePickedPerson } from './booking-helpers'

/**
 * 预约流程里的关联角色选人（婚礼管家 / 婚礼督导）：
 * - 按后台分类「预约关联」开关动态插入步骤
 * - 候选人员按当前日期 + 地区算价并缓存
 * - 支持跳人员详情「选人模式」对比后返回自动回填
 */
export function useBookingRoles(options: {
    staffId: Ref<number>
    date: Ref<string>
    region: Ref<RegionContext>
    roleConfigs: Ref<any[]>
    step: Ref<number>
    isConfirm: ComputedRef<boolean>
    onInvalidateQuote: () => void
}) {
    const { staffId, date, region, roleConfigs, step, isConfirm, onInvalidateQuote } = options

    const rolePicked = reactive<Record<string, RolePickedPerson>>({})
    const roleWanted = reactive<Record<string, boolean>>({})
    const roleCandidateMap = ref<Record<string, any[]>>({})
    const roleLoadingMap = ref<Record<string, boolean>>({})
    const pendingPickRole = ref('')

    const flowRoles = computed(() =>
        roleConfigs.value.filter((r) => r?.role_key === 'butler' || r?.role_key === 'director')
    )

    const activeRole = computed(() => {
        if (isConfirm.value) return null
        const index = step.value - 3
        return index >= 0 ? flowRoles.value[index] || null : null
    })

    const activeRoleNeeded = computed(() =>
        activeRole.value ? !!roleWanted[activeRole.value.role_key] : false
    )

    const activeRolePicked = computed(() =>
        activeRole.value ? rolePicked[activeRole.value.role_key] || null : null
    )

    const activeRoleCandidates = computed(() =>
        activeRole.value ? roleCandidateMap.value[activeRole.value.role_key] || [] : []
    )

    const activeRoleLoading = computed(() =>
        activeRole.value ? !!roleLoadingMap.value[activeRole.value.role_key] : false
    )

    const activeRolePriceText = computed(() => {
        const role = activeRole.value
        if (!role) return ''
        if (activeRolePicked.value) {
            return `${activeRolePicked.value.name} · ¥${shortMoney(activeRolePicked.value.price)}`
        }
        const prices = activeRoleCandidates.value
            .map((c) => Number(c?.price || 0))
            .filter((p) => p > 0)
        const price = prices.length ? Math.min(...prices) : Number(role.default_price || 0)
        return price > 0 ? `¥${shortMoney(price)} 起 · 可选人员` : '按所选人员计价'
    })

    const activeRoleFootText = computed(() => {
        const role = activeRole.value
        if (!role) return ''
        if (!activeRoleNeeded.value) return '未加入该项服务，不产生费用。'
        if (activeRoleLoading.value) return '正在加载可安排人员…'
        if (!activeRoleCandidates.value.length) return '当前日期暂无可安排人员，可换个日期。'
        if (!activeRolePicked.value) return '请选择具体人员，不同人员价格不同。'
        return `已选 ${activeRolePicked.value.name} · ¥${shortMoney(activeRolePicked.value.price)}`
    })

    const roleEstimateSum = computed(() =>
        Object.values(rolePicked).reduce((acc, p) => acc + Number(p?.price || 0), 0)
    )

    async function loadRoleCandidates(roleKey: string, force = false) {
        if (!roleKey) return
        if (!force && roleCandidateMap.value[roleKey]?.length) return
        if (roleLoadingMap.value[roleKey]) return
        roleLoadingMap.value = { ...roleLoadingMap.value, [roleKey]: true }
        try {
            const list: any = await getBookingRoleCandidates({
                staff_id: staffId.value,
                role_key: roleKey,
                date: date.value || undefined,
                ...regionParams(region.value)
            })
            roleCandidateMap.value = {
                ...roleCandidateMap.value,
                [roleKey]: Array.isArray(list) ? list : list?.lists || []
            }
        } catch {
            roleCandidateMap.value = { ...roleCandidateMap.value, [roleKey]: [] }
        } finally {
            roleLoadingMap.value = { ...roleLoadingMap.value, [roleKey]: false }
        }
    }

    function setActiveRoleNeeded(needed: boolean) {
        const role = activeRole.value
        if (!role) return
        const key = role.role_key
        if (!needed) {
            if (roleWanted[key] || rolePicked[key]) {
                delete roleWanted[key]
                delete rolePicked[key]
                onInvalidateQuote()
            }
            return
        }
        roleWanted[key] = true
        loadRoleCandidates(key)
        if (role.schedule_available === false) {
            toast.info(`${role.role_label}在当前日期暂无可安排人员，可换个日期`)
        }
        nextTick(() => uni.pageScrollTo({ scrollTop: 9999, duration: 200 }))
    }

    function pickRoleCandidate(candidate: any) {
        const role = activeRole.value
        if (!role || !candidate) return
        if (candidate.schedule_available === false) {
            toast.info(candidate.schedule_message || '该人员当前日期不可约')
            return
        }
        rolePicked[role.role_key] = {
            staff_id: Number(candidate.staff_id ?? candidate.id ?? 0),
            package_id: Number(candidate.package_id || 0),
            name: String(candidate.name || ''),
            avatar: String(candidate.avatar || ''),
            price: Number(candidate.price || 0)
        }
        roleWanted[role.role_key] = true
        onInvalidateQuote()
    }

    function openRolePersonDetail(candidate: any) {
        const role = activeRole.value
        if (!role || !candidate) return
        const id = Number(candidate.staff_id ?? candidate.id ?? 0)
        if (!id) return
        pendingPickRole.value = role.role_key
        navigate({
            path: '/packages/pkg-catalog/pages/staff-detail/staff-detail',
            query: {
                id,
                pick_role: role.role_key,
                pick_package_id: Number(candidate.package_id || 0),
                ...(date.value ? { date: date.value } : {}),
                ...regionParams(region.value)
            }
        })
    }

    function consumePickedFromDetail() {
        const expectedRole = pendingPickRole.value
        if (!expectedRole) return
        pendingPickRole.value = ''
        const picked: any = cache.get('booking_role_pick')
        if (!picked || String(picked.role_key || '') !== expectedRole) return
        if (Date.now() - Number(picked.ts || 0) > 120000) {
            cache.remove('booking_role_pick')
            return
        }
        rolePicked[expectedRole] = {
            staff_id: Number(picked.staff_id || 0),
            package_id: Number(picked.package_id || 0),
            name: String(picked.name || ''),
            avatar: String(picked.avatar || ''),
            price: Number(picked.price || 0)
        }
        roleWanted[expectedRole] = true
        cache.remove('booking_role_pick')
        onInvalidateQuote()
    }

    function resetAllRoles() {
        roleCandidateMap.value = {}
        for (const key of Object.keys(rolePicked)) delete rolePicked[key]
        for (const key of Object.keys(roleWanted)) delete roleWanted[key]
    }

    function pruneUnavailableRoles() {
        for (const key of Object.keys(rolePicked)) {
            if (!flowRoles.value.some((r) => r.role_key === key)) {
                delete rolePicked[key]
                delete roleWanted[key]
            }
        }
    }

    watch(activeRole, (role) => {
        if (role) loadRoleCandidates(role.role_key)
    })

    return {
        rolePicked,
        roleWanted,
        flowRoles,
        activeRole,
        activeRoleNeeded,
        activeRolePicked,
        activeRoleCandidates,
        activeRoleLoading,
        activeRolePriceText,
        activeRoleFootText,
        roleEstimateSum,
        setActiveRoleNeeded,
        pickRoleCandidate,
        openRolePersonDetail,
        consumePickedFromDetail,
        resetAllRoles,
        pruneUnavailableRoles
    }
}
