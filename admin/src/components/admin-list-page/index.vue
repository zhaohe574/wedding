<template>
    <admin-page-shell :title="title" :description="description" :compact="compact">
        <template v-if="$slots.extra" #extra>
            <slot name="extra" />
        </template>

        <template v-if="$slots.search" #search>
            <slot name="search" />
        </template>

        <template v-if="$slots.stats" #stats>
            <slot name="stats" />
        </template>

        <el-card class="!border-none" shadow="never">
            <admin-table-toolbar v-if="$slots.toolbar || $slots.actions" :hint="toolbarHint">
                <template v-if="$slots.toolbar" #left>
                    <slot name="toolbar" />
                </template>
                <template v-if="$slots.actions" #right>
                    <slot name="actions" />
                </template>
            </admin-table-toolbar>

            <slot />

            <div v-if="props.pager" class="flex justify-end mt-4">
                <pagination
                    :model-value="props.pager"
                    @update:model-value="(val) => emit('update:pager', val)"
                    @change="emit('change')"
                />
            </div>
        </el-card>

        <template v-if="$slots.footer" #footer>
            <slot name="footer" />
        </template>
    </admin-page-shell>
</template>

<script lang="ts" setup name="AdminListPage">
/**
 * 列表页模板：标题 + 搜索区 + 工具条 + 表格 + 分页。
 *
 * 为什么要有它：后台 20 多个列表页各自拼 el-card / 工具条 / 分页，
 * 于是「有的页面分页靠右、有的居中」「有的工具条在搜索区里、有的在表格上方」。
 * 这里把结构固定下来，差异用插槽吸收 —— 页面只写「搜索什么、列什么、按钮做什么」。
 *
 * 用法：
 *   <admin-list-page title="附加项" :pager="pager" @change="getLists">
 *     <template #search>…el-form…</template>
 *     <template #actions><el-button type="primary">新增</el-button></template>
 *     <el-table :data="pager.lists">…</el-table>
 *   </admin-list-page>
 */
const props = defineProps<{
    title?: string
    description?: string
    /** 分页对象（usePaging 返回），传了才渲染分页条 */
    pager?: Record<string, any>
    /** 工具条左侧说明文案 */
    toolbarHint?: string
    compact?: boolean
}>()

const emit = defineEmits<{
    (e: 'change'): void
    (e: 'update:pager', value: Record<string, any> | undefined): void
}>()
</script>
