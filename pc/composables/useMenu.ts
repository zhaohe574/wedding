import { NAVBAR } from '@/constants/menu'
export default function useMenu() {
    return {
        menu: useState(() => NAVBAR),
        sidebar: computed(() => []),
        hasSidebar: computed(() => false)
    }
}
