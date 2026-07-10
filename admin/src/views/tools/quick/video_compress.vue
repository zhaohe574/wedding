<template>
    <external-tool-frame
        title="视频压缩"
        :url="redpandaFrameUrl"
        description="使用 RedPandaCompress 处理视频压缩，适合 G 级大视频；本系统不保存、不上传、不转存处理文件。"
    />
</template>

<script lang="ts" setup name="quickToolVideoCompress">
import ExternalToolFrame from './components/external-tool-frame.vue'
import configs from '@/config'

const redpandaFrameUrl = `${configs.baseUrl}zh/`
</script>
