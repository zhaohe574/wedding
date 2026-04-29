const fs = require('fs')
const path = require('path')

const repoRoot = path.resolve(__dirname, '..')
const pagesJsonPath = path.join(repoRoot, 'src/pages.json')
const outPath = path.join(repoRoot, 'docs/page-layout-audit-matrix.md')

function stripJsonComments(input) {
    let output = ''
    let inString = false
    let quote = ''
    let escaped = false
    for (let i = 0; i < input.length; i += 1) {
        const ch = input[i]
        const next = input[i + 1]

        if (inString) {
            output += ch
            if (escaped) {
                escaped = false
            } else if (ch === '\\') {
                escaped = true
            } else if (ch === quote) {
                inString = false
                quote = ''
            }
            continue
        }

        if (ch === '"' || ch === "'") {
            inString = true
            quote = ch
            output += ch
            continue
        }

        if (ch === '/' && next === '/') {
            while (i < input.length && input[i] !== '\n') i += 1
            output += '\n'
            continue
        }

        if (ch === '/' && next === '*') {
            i += 2
            while (i < input.length && !(input[i] === '*' && input[i + 1] === '/')) i += 1
            i += 1
            continue
        }

        output += ch
    }
    return output
}

function readPagesJson() {
    return JSON.parse(stripJsonComments(fs.readFileSync(pagesJsonPath, 'utf8')))
}

function countMatches(text, regexp) {
    return (text.match(regexp) || []).length
}

function routeToSourcePath(route) {
    return path.join(repoRoot, 'src', `${route.replace(/^\//, '')}.vue`)
}

function getTagAttributes(content, tagName) {
    const match = content.match(new RegExp(`<${tagName}\\b([^>]*)>`, 's'))
    return match ? match[1] : ''
}

function getScene(content, shellType) {
    if (shellType === 'AuthPageShell') return 'consumer'
    const attrs = getTagAttributes(content, 'PageShell')
    const sceneMatch = attrs.match(/\bscene\s*=\s*["']([^"']+)["']/)
    return sceneMatch ? sceneMatch[1] : ''
}

function hasProp(attrs, propName) {
    return new RegExp(`(?:^|\\s|:)${propName}(?:\\s|=|>|$)`).test(attrs)
}

function classifyRoute(route, routeEntry, scan, tabBarRoutes) {
    const meta = routeEntry.meta || {}
    if (tabBarRoutes.has(route)) return 'tabbar-contract'
    if (meta.white) return 'white-list-public'
    if (meta.experience_contract === 'consumer-compatible-touchpoint') {
        return 'consumer-compatible-touchpoint'
    }
    if (meta.experience_contract === 'staff-center-workspace') return 'staff-workspace-entry'
    if (meta.experience_contract === 'admin-dashboard-workspace') return 'admin-workspace-entry'
    if (scan.scene === 'staff') return 'staff-workspace-child'
    if (scan.scene === 'admin') return 'admin-workspace-child'
    if (meta.auth) return 'auth-consumer-page'
    return 'public-consumer-page'
}

function summarizeNotes(route, routeEntry, scan, tabBarRoutes) {
    const notes = []
    const meta = routeEntry.meta || {}
    if (!scan.fileExists) notes.push('missing source file')
    if (scan.shellType === 'none') notes.push('no PageShell/AuthPageShell')
    if (scan.shellType === 'PageShell' && scan.wmPageContentCount === 0)
        notes.push('no wm-page-content')
    if (tabBarRoutes.has(route) && !scan.hasTabbar)
        notes.push('tabBar route without hasTabbar prop')
    if (!tabBarRoutes.has(route) && scan.hasTabbar) notes.push('hasTabbar on non-tabBar route')
    if (meta.scene_lock && scan.scene && meta.scene_lock !== scan.scene) {
        notes.push(`scene_lock(${meta.scene_lock}) differs from shell scene(${scan.scene})`)
    }
    if (scan.legacySafeBottomCount > 0) notes.push('legacy .safe-bottom marker')
    return notes.join('; ')
}

const pagesJson = readPagesJson()
const routes = []
;(pagesJson.pages || []).forEach((page, index) => {
    routes.push({ index: index + 1, root: 'main', route: `/${page.path}`, entry: page })
})
;(pagesJson.subPackages || []).forEach((subPackage) => {
    ;(subPackage.pages || []).forEach((page, index) => {
        routes.push({
            index: routes.length + 1,
            root: subPackage.root,
            route: `/${subPackage.root}/${page.path}`,
            entry: page,
            subIndex: index + 1
        })
    })
})

const tabBarRoutes = new Set(
    ((pagesJson.tabBar && pagesJson.tabBar.list) || []).map((item) => `/${item.pagePath}`)
)

const rows = routes.map((routeInfo) => {
    const sourcePath = routeToSourcePath(routeInfo.route)
    const fileExists = fs.existsSync(sourcePath)
    const content = fileExists ? fs.readFileSync(sourcePath, 'utf8') : ''
    const pageShellCount = countMatches(content, /<PageShell\b/g)
    const authShellCount = countMatches(content, /<AuthPageShell\b/g)
    const pageShellAttrs = getTagAttributes(content, 'PageShell')
    const shellType =
        authShellCount > 0 ? 'AuthPageShell' : pageShellCount > 0 ? 'PageShell' : 'none'
    const scan = {
        fileExists,
        shellType,
        pageShellCount,
        authShellCount,
        scene: getScene(content, shellType),
        hasTabbar: hasProp(pageShellAttrs, 'hasTabbar'),
        hasSafeBottom: hasProp(pageShellAttrs, 'hasSafeBottom'),
        wmPageContentCount: countMatches(content, /\bwm-page-content\b/g),
        actionAreaCount: countMatches(content, /<ActionArea\b/g),
        actionAreaSafeBottomCount: countMatches(content, /<ActionArea\b[^>]*\bsafeBottom\b/gs),
        legacySafeBottomCount: countMatches(content, /\bsafe-bottom\b/g),
        safeBottomTokenCount: countMatches(content, /--wm-safe-bottom-(?:action|tabbar)/g)
    }
    const routeClass = classifyRoute(routeInfo.route, routeInfo.entry, scan, tabBarRoutes)
    const notes = summarizeNotes(routeInfo.route, routeInfo.entry, scan, tabBarRoutes)
    return { ...routeInfo, sourcePath, scan, routeClass, notes }
})

const classCounts = rows.reduce((acc, row) => {
    acc[row.routeClass] = (acc[row.routeClass] || 0) + 1
    return acc
}, {})

const shellCounts = rows.reduce((acc, row) => {
    acc[row.scan.shellType] = (acc[row.scan.shellType] || 0) + 1
    return acc
}, {})

const navCounts = rows.reduce((acc, row) => {
    const nav = (row.entry.style && row.entry.style.navigationStyle) || 'default'
    acc[nav] = (acc[nav] || 0) + 1
    return acc
}, {})

const authCount = rows.filter((row) => row.entry.meta && row.entry.meta.auth).length
const whiteCount = rows.filter((row) => row.entry.meta && row.entry.meta.white).length
const duplicateRoutes = rows
    .map((row) => row.route)
    .filter((route, index, all) => all.indexOf(route) !== index)
const missingFiles = rows.filter((row) => !row.scan.fileExists)
const unregisteredPageFiles = fs
    .readdirSync(path.join(repoRoot, 'src'), { recursive: true, withFileTypes: true })
    .filter((dirent) => dirent.isFile() && dirent.name.endsWith('.vue'))
    .map((dirent) => path.join(dirent.path, dirent.name))
    .filter((file) => /\/src\/(?:pages|packages\/pages)\//.test(file))
    .filter((file) => !/\/(?:component|components)\//.test(file))
    .map(
        (file) =>
            `/${path
                .relative(path.join(repoRoot, 'src'), file)
                .replace(/\\/g, '/')
                .replace(/\.vue$/, '')}`
    )
    .filter((route) => !rows.some((row) => row.route === route))
    .sort()

function mdCell(value) {
    return String(value || '')
        .replace(/\|/g, '\\|')
        .replace(/\n/g, '<br>')
}

const lines = []
lines.push('# Uniapp page layout audit matrix')
lines.push('')
lines.push('Generated by `node scripts/audit-page-layout.js` from the current working tree.')
lines.push('')
lines.push('## Exact route inventory metrics')
lines.push('')
lines.push(`- Registered routes in \`src/pages.json\`: ${rows.length}`)
lines.push(`  - Main-package routes: ${(pagesJson.pages || []).length}`)
lines.push(`  - Sub-package roots: ${(pagesJson.subPackages || []).length}`)
lines.push(`  - Sub-package routes: ${rows.length - (pagesJson.pages || []).length}`)
lines.push(`- Custom tabBar routes: ${tabBarRoutes.size}`)
lines.push(`- Duplicate registered routes: ${duplicateRoutes.length}`)
lines.push(`- Registered routes missing source files: ${missingFiles.length}`)
lines.push(
    `- Unregistered top-level page source files under \`src/pages\` or \`src/packages/pages\`: ${unregisteredPageFiles.length}`
)
lines.push(`- Auth-gated registered routes (\`meta.auth\`): ${authCount}`)
lines.push(`- White-listed registered routes (\`meta.white\`): ${whiteCount}`)
lines.push(
    `- Navigation style counts: ${Object.entries(navCounts)
        .map(([key, value]) => `${key}=${value}`)
        .join(', ')}`
)
lines.push(
    `- Shell counts: ${Object.entries(shellCounts)
        .map(([key, value]) => `${key}=${value}`)
        .join(', ')}`
)
lines.push(
    `- Routes with \`wm-page-content\`: ${
        rows.filter((row) => row.scan.wmPageContentCount > 0).length
    }`
)
lines.push(
    `- Routes with \`PageShell hasTabbar\`: ${rows.filter((row) => row.scan.hasTabbar).length}`
)
lines.push(
    `- Routes with \`PageShell hasSafeBottom\`: ${
        rows.filter((row) => row.scan.hasSafeBottom).length
    }`
)
lines.push(
    `- Routes with \`ActionArea\`: ${rows.filter((row) => row.scan.actionAreaCount > 0).length}`
)
lines.push(
    `- Routes with \`ActionArea safeBottom\`: ${
        rows.filter((row) => row.scan.actionAreaSafeBottomCount > 0).length
    }`
)
lines.push(
    `- Routes with legacy \`.safe-bottom\` markers: ${
        rows.filter((row) => row.scan.legacySafeBottomCount > 0).length
    }`
)
lines.push('')
lines.push('### Route class counts')
lines.push('')
Object.entries(classCounts)
    .sort(([a], [b]) => a.localeCompare(b))
    .forEach(([key, value]) => lines.push(`- ${key}: ${value}`))
lines.push('')
lines.push('## Audit matrix')
lines.push('')
lines.push(
    '| # | Route | Class | Title | Auth/white | Nav | Shell | Scene | Content | Safe/action scan | Notes |'
)
lines.push('| -: | --- | --- | --- | --- | --- | --- | --- | ---: | --- | --- |')
rows.forEach((row) => {
    const meta = row.entry.meta || {}
    const title = (row.entry.style && row.entry.style.navigationBarTitleText) || '—'
    const nav = (row.entry.style && row.entry.style.navigationStyle) || 'default'
    const authWhite =
        [meta.auth ? 'auth' : '', meta.white ? 'white' : ''].filter(Boolean).join('+') || '—'
    const safe = [
        row.scan.hasTabbar ? 'hasTabbar' : '',
        row.scan.hasSafeBottom ? 'hasSafeBottom' : '',
        row.scan.actionAreaCount ? `ActionArea=${row.scan.actionAreaCount}` : '',
        row.scan.actionAreaSafeBottomCount
            ? `ActionArea.safe=${row.scan.actionAreaSafeBottomCount}`
            : '',
        row.scan.legacySafeBottomCount ? `legacySafe=${row.scan.legacySafeBottomCount}` : '',
        row.scan.safeBottomTokenCount ? `safeTokens=${row.scan.safeBottomTokenCount}` : ''
    ]
        .filter(Boolean)
        .join('; ')
    lines.push(
        `| ${row.index} | \`${mdCell(row.route)}\` | ${mdCell(row.routeClass)} | ${mdCell(
            title
        )} | ${mdCell(authWhite)} | ${mdCell(nav)} | ${mdCell(row.scan.shellType)} | ${mdCell(
            row.scan.scene || '—'
        )} | ${row.scan.wmPageContentCount} | ${mdCell(safe || '—')} | ${mdCell(
            row.notes || '—'
        )} |`
    )
})
lines.push('')
lines.push('## Completeness checks')
lines.push('')
lines.push(
    `- Every registered page is represented exactly once in the matrix: ${
        rows.length === new Set(rows.map((row) => row.route)).size ? 'PASS' : 'FAIL'
    }.`
)
lines.push(
    `- Registered source-file existence: ${
        missingFiles.length === 0
            ? 'PASS'
            : `FAIL (${missingFiles.map((row) => row.route).join(', ')})`
    }.`
)
lines.push(
    `- Duplicate registered route check: ${
        duplicateRoutes.length === 0 ? 'PASS' : `FAIL (${duplicateRoutes.join(', ')})`
    }.`
)
lines.push(
    `- Unregistered page-source check: ${
        unregisteredPageFiles.length === 0 ? 'PASS' : `REVIEW (${unregisteredPageFiles.join(', ')})`
    }.`
)
lines.push('')

fs.writeFileSync(outPath, `${lines.join('\n')}\n`)
console.log(`Wrote ${path.relative(process.cwd(), outPath)}`)
console.log(`registered_routes=${rows.length}`)
console.log(`main_routes=${(pagesJson.pages || []).length}`)
console.log(`subpackage_routes=${rows.length - (pagesJson.pages || []).length}`)
console.log(`duplicate_routes=${duplicateRoutes.length}`)
console.log(`missing_files=${missingFiles.length}`)
console.log(`unregistered_page_files=${unregisteredPageFiles.length}`)
console.log(`route_classes=${JSON.stringify(classCounts)}`)
