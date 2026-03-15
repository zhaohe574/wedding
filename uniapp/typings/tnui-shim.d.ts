declare module '@tuniao/tnui-vue3-uniapp' {
    export type TnModalInstance = any
}

declare module '@tuniao/tnui-vue3-uniapp/components/*/src/*.vue' {
    import type { DefineComponent } from 'vue'

    const component: DefineComponent<Record<string, any>, Record<string, any>, any>
    export default component
}

declare module 'tnuiv3p-tn-comment-list/index.vue' {
    import type { DefineComponent } from 'vue'

    const component: DefineComponent<Record<string, any>, Record<string, any>, any>
    export default component
}
