<template>
    <el-drawer
        v-model="visible"
        :title="drawerTitle"
        size="min(1180px, 94vw)"
        destroy-on-close
        class="staff-config-drawer"
    >
        <StaffQuestionnaireEditor
            v-if="visible"
            :staff-id="staffId"
            embedded
            @saved="emit('saved')"
            @published="emit('published')"
            @close="visible = false"
        />
    </el-drawer>
</template>

<script setup lang="ts">
import StaffQuestionnaireEditor from './StaffQuestionnaireEditor.vue'

const props = defineProps<{
    modelValue: boolean
    staffId: number
    staffName?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved'): void
    (e: 'published'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const drawerTitle = computed(() =>
    props.staffName ? `${props.staffName} · 新人问卷配置` : '新人问卷配置'
)
</script>

<style scoped lang="scss">
.staff-config-drawer {
    :deep(.el-drawer__body) {
        padding: 16px 20px 24px;
        background: var(--admin-surface-soft);
    }
}
</style>
