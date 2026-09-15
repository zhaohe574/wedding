<template>
    <section v-if="canManage" class="p-4 border rounded">
        <h3 class="mb-3">小程序账号绑定</h3>
        <p>后台：{{ info.account || adminId }}（{{ info.disable ? '已停用' : '启用' }}）</p>
        <p class="my-2">当前用户：{{ info.nickname || '未绑定' }} {{ info.mobile }} {{ info.user_id ? '#' + info.user_id : '' }}</p>
        <p v-if="info.oa_status" class="mb-3">服务号：{{ info.oa_status.bound ? '已绑定' : '未绑定' }}，{{ info.oa_status.follow_status === 'followed' ? '已关注' : info.oa_status.follow_status === 'unknown' ? '待确认' : '未关注' }}</p>
        <el-select v-model="selected" filterable remote clearable :remote-method="search" :loading="searching" placeholder="搜索昵称或手机号，清空表示解绑" class="w-full">
            <el-option v-for="user in users" :key="user.id" :value="user.id" :label="user.nickname + ' #' + user.id + ' ' + (user.mobile || '')" />
        </el-select>
        <el-input v-model="reason" class="my-3" type="textarea" maxlength="500" placeholder="填写操作原因。换绑仅限同一人更换账号。" />
        <p class="text-gray-500 mb-3">换绑会撤销旧工作身份和后台登录；解绑服务人员会同时停用工作入口。历史业务保留在原档案。</p>
        <el-button type="primary" :loading="saving" v-perms="['auth.admin/bindingSave']" @click="save">保存账号绑定</el-button>
        <el-table v-if="info.audit?.length" class="mt-4" :data="info.audit" size="small">
            <el-table-column label="原用户" prop="old_user_id" />
            <el-table-column label="新用户" prop="new_user_id" />
            <el-table-column label="操作人编号" prop="operator_id" />
            <el-table-column label="原因" prop="reason" min-width="180" />
            <el-table-column label="时间" min-width="160"><template #default="{ row }">{{ new Date(Number(row.create_time) * 1000).toLocaleString() }}</template></el-table-column>
        </el-table>
    </section>
</template>
<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import useUserStore from '@/stores/modules/user'
import request from '@/utils/request'
import { getUserList } from '@/api/consumer'
import feedback from '@/utils/feedback'
const props = defineProps<{ adminId: number; staffId?: number }>()
const userStore = useUserStore()
const canManage = computed(() => Number(userStore.userInfo.root) === 1 || userStore.perms.includes('*') || userStore.perms.includes('auth.admin/bindingSave'))
const emit = defineEmits<{ (event: 'changed', userId: number): void }>()
const info = reactive<any>({})
const users = ref<any[]>([])
const selected = ref<number | undefined>()
const reason = ref('')
const saving = ref(false)
const searching = ref(false)
let searchVersion = 0
const search = async (keyword: string) => {
    const version = ++searchVersion
    searching.value = true
    try { const data = await getUserList({ keyword, page_no: 1, page_size: 20 }); if (version === searchVersion) users.value = data.lists || [] }
    finally { if (version === searchVersion) searching.value = false }
}
const load = async () => {
    if (!props.adminId || !canManage.value) return
    Object.assign(info, await request.get({ url: '/auth.admin/bindingDetail', params: { admin_id: props.adminId } }))
    selected.value = info.user_id || undefined
    users.value = info.user_id ? [{ id: info.user_id, nickname: info.nickname, mobile: info.mobile }] : []
}
const save = async () => {
    if (!reason.value.trim()) return feedback.msgError('请填写操作原因')
    if (saving.value) return
    saving.value = true
    try {
        await feedback.confirm(selected.value ? '确认由同一人使用所选小程序账号？旧账号将失去工作权限。' : '确认解绑并停用相关工作入口？')
        Object.assign(info, await request.post({ url: '/auth.admin/bindingSave', params: {
            admin_id: props.adminId, staff_id: props.staffId || 0, user_id: selected.value || 0, reason: reason.value.trim()
        } }))
        emit('changed', Number(info.user_id)); reason.value = ''; feedback.msgSuccess('绑定关系已更新')
    } finally { saving.value = false }
}
watch(() => props.adminId, () => { void load() }, { immediate: true })
</script>
