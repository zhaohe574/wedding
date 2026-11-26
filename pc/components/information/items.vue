<template>
    <NuxtLink class="info-item" :to="`/information/detail/${id}`">
        <div v-if="onlyTitle" class="info-item__row">
            <slot name="title" :title="title">
                <span class="info-item__title line-clamp-1">{{ title }}</span>
            </slot>
            <span class="info-item__time" v-if="showTime">
                {{ createTime }}
            </span>
        </div>
        <div
            v-else
            class="info-item__full"
            :class="{
                'info-item__full--border': border,
                'info-item__full--row': !isHorizontal
            }"
        >
            <div
                v-if="image"
                class="info-item__media"
                :class="{ 'info-item__media--row': !isHorizontal }"
            >
                <ElImage :src="image" fit="cover" :style="getImageStyle" />
            </div>

            <div
                class="info-item__body"
                :class="{ 'info-item__body--padded': isHorizontal }"
            >
                <slot name="title" :title="title">
                    <div
                        class="info-item__title"
                        :class="`line-clamp-${titleLine}`"
                    >
                        {{ title }}
                    </div>
                </slot>

                <p v-if="showDesc && desc" class="info-item__desc">
                    {{ desc }}
                </p>
                <div
                    v-if="showAuthor || showTime || showClick"
                    class="info-item__meta"
                >
                    <span v-if="showAuthor && author">{{ author }}</span>
                    <span
                        v-if="showAuthor && author"
                        class="info-item__divider"
                        aria-hidden="true"
                        >|</span
                    >
                    <span class="info-item__time" v-if="showTime">{{
                        createTime
                    }}</span>
                    <span v-if="showClick" class="info-item__click">
                        <ElIcon><View /></ElIcon>
                        <span>{{ click }}</span>
                    </span>
                </div>
            </div>
        </div>
    </NuxtLink>
</template>
<script lang="ts" setup>
import { ElImage, ElIcon } from 'element-plus'
import { View } from '@element-plus/icons-vue'
const props = defineProps({
    index: {
        type: Number
    },
    id: {
        type: Number
    },
    title: {
        type: String
    },
    desc: {
        type: String
    },
    image: {
        type: String
    },
    author: {
        type: String
    },
    click: {
        type: Number
    },
    createTime: {
        type: String
    },
    onlyTitle: {
        type: Boolean,
        default: true
    },
    isHorizontal: {
        type: Boolean,
        default: false
    },
    titleLine: {
        type: Number,
        default: 1
    },
    border: {
        type: Boolean,
        default: true
    },
    source: {
        type: String,
        default: 'default'
    },
    imageSize: {
        type: String,
        default: 'default'
    },
    showAuthor: {
        type: Boolean,
        default: true
    },
    showDesc: {
        type: Boolean,
        default: true
    },
    showClick: {
        type: Boolean,
        default: true
    },
    showTime: {
        type: Boolean,
        default: true
    },
    showSort: {
        type: Boolean,
        default: true
    }
})

const getImageStyle = computed(() => {
    switch (props.imageSize) {
        case 'default':
            return {
                width: '180px',
                height: '135px'
            }
        case 'mini':
            return {
                width: '120px',
                height: '90px'
            }
        case 'large':
            return {
                width: '260px',
                height: '195px'
            }
    }
})
</script>
<style lang="scss" scoped>
.info-item {
    display: block;
    color: inherit;
    text-decoration: none;

    &:hover {
        .info-item__title,
        .info-item__row {
            color: var(--pc-gold);
        }
    }

    &__row {
        display: flex;
        align-items: center;
        margin-top: 16px;
        color: var(--pc-ink);
        transition: color var(--dur-fast) var(--ease-out);

        &::before {
            content: '';
            flex: none;
            width: 6px;
            height: 6px;
            margin-right: 10px;
            border-radius: 50%;
            background: var(--pc-gold);
        }
    }

    &__full {
        padding-top: 16px;

        &--border {
            padding-bottom: 16px;
            border-bottom: 1px solid var(--pc-line);
        }

        &--row {
            display: flex;
            align-items: center;
        }
    }

    &__media {
        flex: none;
        overflow: hidden;
        border-radius: 8px;
        background: rgba(201, 169, 110, 0.08);

        &--row {
            margin-right: 16px;
        }

        :deep(.el-image) {
            display: block;
            border-radius: 8px;
        }
    }

    &__body {
        flex: 1;
        min-width: 0;

        &--padded {
            padding: 8px;
        }
    }

    &__title {
        color: var(--pc-ink);
        font-size: 16px;
        font-weight: 500;
        line-height: 1.5;
        transition: color var(--dur-fast) var(--ease-out);
    }

    &__row &__title {
        flex: 1;
        min-width: 0;
        font-size: 14px;
        font-weight: 400;
    }

    &__time {
        color: var(--pc-ink-3);
        font-size: 13px;
        white-space: nowrap;
    }

    &__row &__time {
        margin-left: 16px;
        font-size: 12px;
    }

    &__desc {
        margin: 12px 0 0;
        color: var(--pc-ink-2);
        font-size: 14px;
        line-height: 1.7;
    }

    &__meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
        color: var(--pc-ink-3);
        font-size: 12px;
    }

    &__divider {
        color: var(--pc-line-strong);
    }

    &__click {
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
}
</style>
