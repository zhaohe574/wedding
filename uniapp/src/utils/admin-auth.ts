import cache from './cache'
import { ADMIN_TOKEN_KEY } from '@/enums/constantEnums'

export function getAdminToken() {
    return cache.get(ADMIN_TOKEN_KEY)
}

export function setAdminToken(token: string) {
    cache.set(ADMIN_TOKEN_KEY, token)
}

export function clearAdminToken() {
    cache.remove(ADMIN_TOKEN_KEY)
}
