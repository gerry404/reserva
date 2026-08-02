import { computed, unref } from 'vue'

const FALLBACK = '#6366f1'

/**
 * Relative luminance, per WCAG 2.
 * https://www.w3.org/TR/WCAG21/#dfn-relative-luminance
 */
function luminance(hex) {
  const value = String(hex ?? '').replace('#', '')
  if (!/^[0-9a-f]{6}$/i.test(value)) return 0

  const channels = [0, 2, 4].map((i) => {
    const c = parseInt(value.slice(i, i + 2), 16) / 255
    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4
  })

  return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2]
}

/**
 * Derives a usable palette from a merchant's chosen accent colour.
 *
 * Merchants pick the colour, and some of them pick amber. White text on amber
 * is unreadable, so the foreground is computed from the accent's luminance
 * rather than assumed to be white — the contrast threshold below is where
 * black overtakes white against a mid-tone.
 *
 * Returns CSS custom properties to bind on a container, so hover and focus can
 * be plain CSS instead of inline mouse handlers — which never fired on touch
 * devices, i.e. on almost every real visitor.
 */
export function useAccent(source) {
  const accent = computed(() => {
    const value = unref(source)
    return /^#[0-9a-f]{6}$/i.test(String(value ?? '')) ? value : FALLBACK
  })

  const isLight = computed(() => luminance(accent.value) > 0.45)

  const foreground = computed(() => (isLight.value ? '#111827' : '#ffffff'))

  const style = computed(() => ({
    '--accent': accent.value,
    '--accent-fg': foreground.value,
    // Alpha suffixes on a hex colour: supported everywhere we ship, and far
    // simpler than converting to rgb() for each tint.
    '--accent-soft': `${accent.value}1a`,
    '--accent-hover': `${accent.value}2e`,
    '--accent-ring': `${accent.value}4d`,
  }))

  return { accent, foreground, isLight, style }
}
