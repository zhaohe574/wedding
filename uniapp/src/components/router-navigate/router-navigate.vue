<template>
    <!-- #ifdef H5 -->
    <navigator hover-class="none" :url="url" :open-type="navType" :delta="delta">
        <slot />
    </navigator>
    <!-- #endif -->
    <!-- #ifndef H5 -->
    <view @click="navigate" class="class">
        <slot />
    </view>
    <!-- #endif -->
</template>
<script>
import { NavigationTypesEnums } from 'uniapp-router-next'

export default {
    options: {
        virtualHost: true
    },
    externalClasses: ['class'],
    props: {
        to: {
            type: [String, Object]
        },
        navType: {
            type: String,
            default: 'navigate'
        },
        delta: {
            type: Number,
            default: 1
        }
    },
    computed: {
        url() {
            if (!this.to) {
                return
            }
            const type = NavigationTypesEnums[this.navType]
            const resolved = this.$uniRouter?.resolve?.(this.to, type)
            const fullPath =
                resolved?.fullPath ||
                (typeof this.to === 'string'
                    ? this.to
                    : this.to.path || this.to.url)
            if (!fullPath) {
                return
            }
            return this.withBase(fullPath)
        }
    },
    methods: {
        withBase(fullPath) {
            if (/^https?:\/\//i.test(fullPath) || fullPath.startsWith('//')) {
                return fullPath
            }
            const base = this.normalizeBase(this.getBase())
            if (base === '/' || fullPath.startsWith(base)) {
                return fullPath
            }
            const normalizedPath = fullPath.startsWith('/') ? fullPath.slice(1) : fullPath
            return `${base}${normalizedPath}`
        },
        getBase() {
            const baseFromRouter = this.$uniRouter?.options?.h5?.base
            if (baseFromRouter) {
                return baseFromRouter
            }
            try {
                if (typeof __uniConfig !== 'undefined' && __uniConfig?.router?.base) {
                    return __uniConfig.router.base
                }
            } catch (error) {
                return '/'
            }
            return '/'
        },
        normalizeBase(base) {
            if (!base) {
                return '/'
            }
            if (base.startsWith('.')) {
                return base.endsWith('/') ? base : `${base}/`
            }
            let normalized = base
            if (!normalized.startsWith('/')) {
                normalized = `/${normalized}`
            }
            if (!normalized.endsWith('/')) {
                normalized = `${normalized}/`
            }
            return normalized
        },
        navigate() {
            const type = NavigationTypesEnums[this.navType]
            if (type == null) {
                return console.error(` "navType" unknown type \n\n value：${this.navType}`)
            }
            let to = this.to || {}
            if (this.navType == 'navigateBack') {
                to = {
                    delta: this.delta
                }
            }
            this.$uniRouter.navigate(to, type)
        }
    }
}
</script>
