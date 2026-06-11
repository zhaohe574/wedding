<template>
    <image
        v-if="isImage"
        class="base-icon base-icon--image"
        :class="mergedClass"
        :src="safeName"
        :mode="imgMode"
        :style="mergedStyle"
        @click="handleActivate"
    />
    <text
        v-else-if="safeName"
        class="base-icon"
        :class="[iconNameClass, mergedClass, { 'base-icon--bold': bold }]"
        :style="mergedStyle"
        @click="handleActivate"
    />
</template>

<script setup lang="ts">
import { computed, useAttrs } from 'vue'

interface Props {
    name?: string
    size?: string | number
    color?: string
    bold?: boolean
    customClass?: string
    customStyle?: Record<string, string | number>
    offsetTop?: string | number
    imgMode?: 'scaleToFill' | 'aspectFit' | 'aspectFill' | 'widthFix' | 'heightFix'
}

const props = withDefaults(defineProps<Props>(), {
    name: '',
    size: '',
    color: '',
    bold: false,
    customClass: '',
    customStyle: () => ({}),
    offsetTop: '',
    imgMode: 'aspectFit'
})

const emit = defineEmits<{
    (event: 'click'): void
    (event: 'tap'): void
}>()

const attrs = useAttrs()

const handleActivate = () => {
    emit('click')
    emit('tap')
}

const normalizeString = (value?: string | number) =>
    typeof value === 'number' ? String(value) : typeof value === 'string' ? value.trim() : ''

const iconAliasMap: Record<string, string> = {
    user: 'my',
    users: 'team',
    wallet: 'funds',
    file: 'folder',
    'file-text': 'order',
    grid: 'menu-grille',
    more: 'more-horizontal',
    plus: 'add',
    minus: 'reduce',
    info: 'tip',
    'info-circle': 'tip',
    mail: 'email',
    empty: 'empty-data',
    inbox: 'empty-data',
    heart: 'like',
    'heart-fill': 'like-fill',
    'play-circle': 'play',
    'play-circle-fill': 'play-fill',
    'play-right': 'play',
    'map-pin': 'location',
    'arrow-right': 'right',
    'arrow-down': 'down',
    'up-arrow': 'up',
    'chevron-right': 'right',
    'chevron-down': 'down',
    'shield-check': 'trusty',
    'check-circle': 'success-circle',
    'close-circle-fill': 'close-circle',
    'warning-circle': 'warning'
}

const formatSize = (value?: string | number) => {
    const size = normalizeString(value)
    if (!size) return ''
    return /^\d+(\.\d+)?$/.test(size) ? `${size}rpx` : size
}

type IconStyleValue = string | number | undefined
type IconStyleObject = Record<string, IconStyleValue>

const toKebabCase = (value: string) => value.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)

const stringifyStyle = (style: IconStyleObject) =>
    Object.entries(style)
        .filter(([, value]) => value !== undefined && value !== '')
        .map(([key, value]) => `${toKebabCase(key)}:${value}`)
        .join(';')

const rawName = computed(() => normalizeString(props.name))
const safeName = computed(() => {
    if (!rawName.value || rawName.value.includes('/')) return rawName.value
    const normalizedName = rawName.value.replace(/^tn-icon-/, '')
    return iconAliasMap[normalizedName] || normalizedName
})
const isImage = computed(() => rawName.value.includes('/'))
const iconNameClass = computed(() => (safeName.value ? `tn-icon-${safeName.value}` : ''))
const iconStyle = computed(() => {
    const size = formatSize(props.size)
    const offsetTop = formatSize(props.offsetTop)
    return {
        ...(size
            ? isImage.value
                ? { width: size, height: size }
                : { fontSize: size }
            : {}),
        ...(props.color && !isImage.value ? { color: props.color } : {}),
        ...(offsetTop ? { transform: `translateY(${offsetTop})` } : {}),
        ...props.customStyle
    }
})

const mergedClass = computed(() => [props.customClass, attrs.class].filter(Boolean))
const mergedStyle = computed<string | IconStyleObject>(() => {
    const inheritedStyle = attrs.style
    if (!inheritedStyle) return iconStyle.value
    if (typeof inheritedStyle === 'string') {
        const baseStyle = stringifyStyle(iconStyle.value)
        return baseStyle ? `${baseStyle};${inheritedStyle}` : inheritedStyle
    }
    if (typeof inheritedStyle === 'object' && !Array.isArray(inheritedStyle)) {
        return {
            ...iconStyle.value,
            ...(inheritedStyle as IconStyleObject)
        }
    }
    return iconStyle.value
})
</script>

<script lang="ts">
export default {
    name: 'BaseIcon',
    inheritAttrs: false,
    options: {
        virtualHost: true
    }
}
</script>

<style lang="scss" scoped>
.base-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1em;
    height: 1em;
    line-height: 1;
    font-size: inherit;
    color: currentColor;
    text-align: center;
    text-decoration: none;
    flex-shrink: 0;
}

.base-icon--bold {
    font-weight: 900;
}

.base-icon--image {
    width: 36rpx;
    height: 36rpx;
}
</style>
