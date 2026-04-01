<template>
    <view
        :class="[
            'wm-tab-panel',
            {
                'wm-tab-panel--active': active,
                'wm-tab-panel--inactive': !active
            }
        ]"
        :style="shouldShow ? '' : 'display: none;'"
    >
        <slot v-if="shouldRender"></slot>
    </view>
</template>

<script lang="ts" setup>
import { ref, provide, inject, watch, computed, onMounted, getCurrentInstance } from 'vue'

const props = withDefaults(
    defineProps<{
        dot?: boolean | string
        name?: boolean | string
        info?: any
    }>(),
    {
        dot: false,
        name: ''
    }
)

const active = ref<boolean>(false)
const shouldShow = ref<boolean>(false)
const shouldRender = ref<boolean>(false)
const inited = ref(false)

const updateTabs: any = inject('updateTabs')
const handleChange: any = inject('handleChange')

const updateRender = (value: boolean) => {
    inited.value = inited.value || value
    active.value = value
    shouldRender.value = inited.value!
    shouldShow.value = value
}
const update = () => {
    if (updateTabs) {
        updateTabs()
    }
}

const instance = getCurrentInstance()
handleChange(instance?.props, updateRender)

onMounted(() => {
    update()
})

const changeData = computed(() => {
    const { dot, info } = props
    return {
        dot,
        info
    }
})

watch(
    () => changeData.value,
    () => {
        update()
    }
)
watch(
    () => props.name,
    (val) => {
        update()
    }
)
</script>

<style scoped>
.wm-tab-panel--active {
    height: auto;
}

.wm-tab-panel--inactive {
    height: 0;
    overflow: visible;
}
</style>
