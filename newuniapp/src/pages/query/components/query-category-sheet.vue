<template>
    <!-- 服务类型：磁吸半屏抽屉（v10 §4.3）。标题/关闭由 snap-sheet 统一承担 -->
    <snap-sheet :model-value="open" title="服务类型" @update:model-value="onVisible">
        <view
            hover-class="is-pressed"
            class="row"
            :class="{ 'is-on': modelValue === 0 }"
            @tap="select(0)"
        >
            <text class="row__name">全部类型</text>
            <tn-icon v-if="modelValue === 0" name="check" :size="26" />
        </view>
        <view
            v-for="c in categories"
            :key="c.id"
            hover-class="is-pressed"
            class="row"
            :class="{ 'is-on': modelValue === c.id }"
            @tap="select(c.id)"
        >
            <text class="row__name">{{ c.name }}</text>
            <tn-icon v-if="modelValue === c.id" name="check" :size="26" />
        </view>

        <view v-if="!categories.length && !loading" class="tip">
            <text class="tip__text">服务类型没加载出来</text>
            <text class="tip__link" @tap="emit('retry')">重试</text>
        </view>
    </snap-sheet>
</template>

<script setup lang="ts">
/**
 * 服务类型弹窗：扁平单选列表。
 *
 * 只取一级分类、忽略 children —— 后台分类选择器是 el-cascader + checkStrictly: true
 * （admin/src/views/staff/lists/edit.vue:98），人员可以被绑到父级分类上；
 * 若改成「只允许选叶子」，绑父级的人会静默消失（不报错、不提示）。
 * 反向问题（人员绑二级、选一级查不到）是既有问题，记录为技术债，不在本次处理。
 *
 * 外壳已从 tn-popup 换成 snap-sheet；props / emits 与单选逻辑保持不变。
 */
withDefaults(
    defineProps<{
        open: boolean
        /** 0 = 全部类型 */
        modelValue?: number
        categories: { id: number; name: string }[]
        loading?: boolean
    }>(),
    {
        modelValue: 0,
        categories: () => [],
        loading: false
    }
)

const emit = defineEmits<{
    (e: 'update:open', v: boolean): void
    (e: 'pick', id: number): void
    (e: 'retry'): void
}>()

function select(id: number) {
    emit('pick', id)
    close()
}

function close() {
    emit('update:open', false)
}

/** snap-sheet 的遮罩/下滑关闭统一回调到这里，保证父级 open 同步 */
function onVisible(v: boolean) {
    if (!v) close()
}
</script>

<style lang="scss" scoped>
.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 96rpx;
    border-bottom: 1rpx solid var(--c-line);
    color: var(--c-brand-text);

    &__name {
        font-size: var(--fs-26);
        color: var(--c-ink-2);
    }

    &.is-on &__name {
        color: var(--c-ink);
        font-weight: var(--fw-semibold);
    }
}

.tip {
    padding: 40rpx 0 8rpx;
    text-align: center;

    &__text {
        display: block;
        font-size: var(--fs-xs);
        color: var(--c-ink-3);
    }

    &__link {
        display: inline-block;
        margin-top: 14rpx;
        font-size: var(--fs-22);
        color: var(--c-brand-text);
        text-decoration: underline;
    }
}
</style>
