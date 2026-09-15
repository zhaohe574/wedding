<template>
    <div class="bg-white render-html p-[30px] w-[1200px] mx-auto min-h-screen">
        <div v-if="error || !data" class="text-center" role="status">
            <p>内容暂时无法加载。</p>
            <button @click="refresh()">重新加载</button>
        </div>
        <template v-else>
            <h1 class="text-center">{{ data.title }}</h1>
            <div class="mx-auto" v-html="data.content"></div>
        </template>
    </div>
</template>
<script lang="ts" setup>
import { getPolicy } from '~~/api/app'

const route = useRoute()
const { data, error, refresh } = await useAsyncData(
    () =>
        getPolicy({
            type: route.params.type
        }),
    {
        watch: [() => route.params.type]
    }
)

definePageMeta({
    layout: 'blank'
})
</script>
<style lang="scss" scoped></style>
