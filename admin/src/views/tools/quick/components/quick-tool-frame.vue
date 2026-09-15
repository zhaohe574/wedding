<template>
    <section class="image-tool">
        <header class="tool-head">
            <h2>{{ title }}</h2>
            <div class="commands">
                <input ref="fileInput" class="file-input" type="file" accept="image/png,image/jpeg,image/webp" aria-label="选择图片" @change="loadFile" />
                <el-button :icon="Upload" @click="fileInput?.click()">选择图片</el-button>
                <el-button :icon="RefreshLeft" :disabled="!bitmap" title="重置编辑" @click="reset" />
                <el-button type="primary" :icon="Download" :disabled="!bitmap || !!error" :loading="exporting" @click="download">导出</el-button>
            </div>
        </header>
        <el-alert v-if="error" :title="error" type="error" :closable="false" />
        <div class="tool-body">
            <div class="preview">
                <img v-if="preview" :src="preview" :alt="fileName" />
                <el-empty v-else description="尚未选择图片" :image-size="90" />
            </div>
            <el-form class="controls" label-position="top" size="small">
                <p v-if="bitmap" class="file-summary">{{ fileName }} · {{ originalWidth }} × {{ originalHeight }} · {{ fileSize }}</p>
                <el-form-item label="输出格式">
                    <el-radio-group v-model="format"><el-radio-button value="image/jpeg">JPEG</el-radio-button><el-radio-button value="image/png">PNG</el-radio-button><el-radio-button value="image/webp">WebP</el-radio-button></el-radio-group>
                </el-form-item>
                <el-form-item v-if="format !== 'image/png'" :label="'图像质量 ' + quality + '%'">
                    <el-slider v-model="quality" :min="10" :max="100" />
                </el-form-item>
                <div class="fields">
                    <el-form-item label="输出宽度"><el-input-number v-model="width" :min="1" :max="8192" :precision="0" controls-position="right" @change="syncHeight" /></el-form-item>
                    <el-form-item label="输出高度"><el-input-number v-model="height" :min="1" :max="8192" :precision="0" controls-position="right" @change="syncWidth" /></el-form-item>
                </div>
                <el-checkbox v-model="keepRatio">锁定比例</el-checkbox>
                <template v-if="tool === 'image_edit'">
                    <el-divider content-position="left">裁剪区域</el-divider>
                    <div class="fields">
                        <el-form-item label="左侧位置"><el-input-number v-model="crop.x" :min="0" :max="Math.max(0, originalWidth - 1)" :precision="0" controls-position="right" /></el-form-item>
                        <el-form-item label="顶部位置"><el-input-number v-model="crop.y" :min="0" :max="Math.max(0, originalHeight - 1)" :precision="0" controls-position="right" /></el-form-item>
                        <el-form-item label="裁剪宽度"><el-input-number v-model="crop.width" :min="1" :max="Math.max(1, originalWidth - crop.x)" :precision="0" controls-position="right" /></el-form-item>
                        <el-form-item label="裁剪高度"><el-input-number v-model="crop.height" :min="1" :max="Math.max(1, originalHeight - crop.y)" :precision="0" controls-position="right" /></el-form-item>
                    </div>
                    <div class="commands">
                        <el-button :icon="RefreshLeft" title="向左旋转 90 度" aria-label="向左旋转 90 度" @click="rotate(-90)" />
                        <el-button :icon="RefreshRight" title="向右旋转 90 度" aria-label="向右旋转 90 度" @click="rotate(90)" />
                        <el-checkbox v-model="flip">水平翻转</el-checkbox>
                    </div>
                    <el-form-item :label="'亮度 ' + brightness + '%'"><el-slider v-model="brightness" :min="0" :max="200" /></el-form-item>
                    <el-form-item :label="'对比度 ' + contrast + '%'"><el-slider v-model="contrast" :min="0" :max="200" /></el-form-item>
                    <el-checkbox v-model="grayscale">黑白</el-checkbox>
                </template>
            </el-form>
        </div>
    </section>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, shallowRef, watch } from 'vue'
import { Upload, Download, RefreshLeft, RefreshRight } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'

const props = defineProps<{ tool: 'compress' | 'convert' | 'image_edit'; title: string }>()
const fileInput = ref<HTMLInputElement>()
const bitmap = shallowRef<ImageBitmap>()
const originalWidth = ref(0), originalHeight = ref(0)
const fileName = ref(''), fileSize = ref(''), preview = ref(''), error = ref('')
const width = ref(1), height = ref(1), quality = ref(80)
const format = ref('image/jpeg'), keepRatio = ref(true), exporting = ref(false)
const angle = ref(0), flip = ref(false), brightness = ref(100), contrast = ref(100), grayscale = ref(false)
const crop = reactive({ x: 0, y: 0, width: 1, height: 1 })
let loadSequence = 0
const croppedWidth = computed(() => Math.max(1, Math.min(crop.width || 1, originalWidth.value - (crop.x || 0))))
const croppedHeight = computed(() => Math.max(1, Math.min(crop.height || 1, originalHeight.value - (crop.y || 0))))
const ratio = computed(() => angle.value % 180 ? croppedHeight.value / croppedWidth.value : croppedWidth.value / croppedHeight.value)
const syncHeight = (value: number | undefined = width.value) => { if (keepRatio.value) height.value = Math.max(1, Math.round((value || 1) / ratio.value)) }
const syncWidth = (value: number | undefined = height.value) => { if (keepRatio.value) width.value = Math.max(1, Math.round((value || 1) * ratio.value)) }
const rotate = (degrees: number) => {
    angle.value = (angle.value + degrees + 360) % 360
    ;[width.value, height.value] = [height.value, width.value]
}
const reset = () => {
    Object.assign(crop, { x: 0, y: 0, width: originalWidth.value, height: originalHeight.value })
    width.value = originalWidth.value
    height.value = originalHeight.value
    angle.value = 0
    flip.value = grayscale.value = false
    brightness.value = contrast.value = 100
    quality.value = 80
    error.value = ''
}
const loadFile = async (event: Event) => {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0]
    input.value = ''
    if (!file) return
    const sequence = ++loadSequence
    error.value = ''
    try {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('仅支持 JPEG、PNG、WebP 图片')
        if (file.size > 30 * 1024 * 1024) throw new Error('图片不能超过 30 MB')
        const image = await createImageBitmap(file)
        if (sequence !== loadSequence) { image.close(); return }
        if (image.width * image.height > 32000000) { image.close(); throw new Error('图片不能超过 3200 万像素') }
        bitmap.value?.close()
        bitmap.value = image
        originalWidth.value = image.width
        originalHeight.value = image.height
        fileName.value = file.name
        fileSize.value = (file.size / 1024 / 1024).toFixed(2) + ' MB'
        format.value = file.type
        reset()
    } catch (e) { error.value = e instanceof DOMException ? '图片内容无法解码，请确认文件未损坏' : e instanceof Error ? e.message : '图片无法读取' }
}
const render = (previewMode = false) => {
    if (!bitmap.value) return null
    const w = Math.round(Number(width.value)), h = Math.round(Number(height.value))
    if (w < 1 || h < 1 || w > 8192 || h > 8192 || w * h > 24000000) throw new Error('输出宽高需为 1 至 8192，总像素不超过 2400 万')
    const scale = previewMode ? Math.min(1, 1400 / Math.max(w, h)) : 1
    const canvas = document.createElement('canvas')
    canvas.width = Math.max(1, Math.round(w * scale))
    canvas.height = Math.max(1, Math.round(h * scale))
    const ctx = canvas.getContext('2d')
    if (!ctx) throw new Error('浏览器无法创建图像画布')
    if (format.value === 'image/jpeg') { ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, canvas.width, canvas.height) }
    ctx.translate(canvas.width / 2, canvas.height / 2)
    ctx.rotate(angle.value * Math.PI / 180)
    ctx.scale(flip.value ? -1 : 1, 1)
    ctx.filter = 'brightness(' + brightness.value + '%) contrast(' + contrast.value + '%) grayscale(' + (grayscale.value ? 1 : 0) + ')'
    const dw = angle.value % 180 ? canvas.height : canvas.width
    const dh = angle.value % 180 ? canvas.width : canvas.height
    ctx.drawImage(bitmap.value, crop.x || 0, crop.y || 0, croppedWidth.value, croppedHeight.value, -dw / 2, -dh / 2, dw, dh)
    return canvas
}
const refresh = () => {
    if (!bitmap.value) return
    try {
        preview.value = render(true)?.toDataURL(format.value, quality.value / 100) || ''
        error.value = ''
    } catch (e) { error.value = e instanceof Error ? e.message : '预览失败' }
}
watch([bitmap, width, height, quality, format, angle, flip, brightness, contrast, grayscale, crop], refresh, { deep: true, flush: 'post' })
watch([croppedWidth, croppedHeight, keepRatio], () => syncHeight())
watch(() => props.tool, reset)
const download = async () => {
    exporting.value = true
    try {
        const canvas = render()
        if (!canvas) return
        const blob = await new Promise<Blob>((resolve, reject) => canvas.toBlob(
            value => value ? resolve(value) : reject(new Error('图片导出失败')), format.value, quality.value / 100))
        if (blob.type !== format.value) throw new Error('当前浏览器不支持所选格式，请选择 PNG 或 JPEG')
        const url = URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = fileName.value.replace(/\.[^.]+$/, '') + '-edited.' + ({ 'image/jpeg': 'jpg', 'image/png': 'png', 'image/webp': 'webp' }[format.value] || 'png')
        link.click()
        setTimeout(() => URL.revokeObjectURL(url), 1000)
        ElMessage.success('已导出 ' + (blob.size / 1024).toFixed(1) + ' KB')
    } catch (e) { error.value = e instanceof Error ? e.message : '导出失败' }
    finally { exporting.value = false }
}
onBeforeUnmount(() => { loadSequence++; bitmap.value?.close() })
</script>

<style scoped>
.image-tool { padding: 20px; background: var(--el-bg-color); }
.tool-head, .commands { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.tool-head { justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid var(--el-border-color); }
h2 { font-size: 18px; margin: 0; }
.file-input { display: none; }
.tool-body { display: grid; grid-template-columns: minmax(0, 1fr) 290px; gap: 24px; margin-top: 20px; }
.preview { display: flex; justify-content: center; align-items: center; min-height: 420px; background: #eef0f2; overflow: hidden; }
.preview img { max-width: 100%; max-height: 640px; object-fit: contain; }
.controls { min-width: 0; }
.file-summary { overflow-wrap: anywhere; font-size: 12px; color: var(--el-text-color-secondary); }
.fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.fields :deep(.el-input-number) { width: 100%; }
@media (max-width: 800px) { .tool-body { grid-template-columns: 1fr; } .preview { min-height: 240px; } .image-tool { padding: 12px; } }
</style>
