import { computed } from 'vue'
import { useNavBarMetrics } from '@/hooks/useNavBarMetrics'

export const useFixedNavbarPagingStyle = () => {
    const { navBarHeight } = useNavBarMetrics()

    return computed(() => ({
        top: `${navBarHeight}px`
    }))
}
