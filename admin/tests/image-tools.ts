import { createApp, h } from 'vue'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import QuickTool from '../src/views/tools/quick/components/quick-tool-frame.vue'
import HumanSplit from '../src/views/tools/quick/human_split.vue'
import VideoCompress from '../src/views/tools/quick/video_compress.vue'

// 独立挂载真实组件，供无业务数据库的浏览器验收使用。
createApp({ render: () => h('main', [
    h(QuickTool, { tool: 'image_edit', title: '图片编辑' }),
    h(HumanSplit), h(VideoCompress)
]) }).use(ElementPlus).mount('#app')
