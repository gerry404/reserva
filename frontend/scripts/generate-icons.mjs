/**
 * Génère les icônes PNG PWA — pur Node.js, zéro dépendance externe.
 * Usage: npm run generate:icons
 */

import { deflateSync } from 'zlib'
import { writeFileSync, mkdirSync } from 'fs'
import { fileURLToPath } from 'url'
import { dirname, join } from 'path'

const __dirname = dirname(fileURLToPath(import.meta.url))
const outDir    = join(__dirname, '../public/icons')
mkdirSync(outDir, { recursive: true })

// ─── PNG builder minimal (RGB 8-bit, sans alpha) ─────────────────────────────
function u32(n) {
  const b = Buffer.allocUnsafe(4)
  b.writeUInt32BE(n, 0)
  return b
}

function crc32(buf) {
  let crc = 0xffffffff
  for (const byte of buf) {
    crc ^= byte
    for (let i = 0; i < 8; i++) crc = crc & 1 ? (crc >>> 1) ^ 0xedb88320 : crc >>> 1
  }
  return (crc ^ 0xffffffff) >>> 0
}

function chunk(type, data) {
  const t   = Buffer.from(type, 'ascii')
  const crcBuf = Buffer.concat([t, data])
  return Buffer.concat([u32(data.length), t, data, u32(crc32(crcBuf))])
}

function buildPNG(size, pixels /* Uint8Array, RGB triplets */) {
  const sig  = Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])
  const ihdr = chunk('IHDR', Buffer.concat([u32(size), u32(size), Buffer.from([8, 2, 0, 0, 0])]))

  // Build raw scanlines with filter byte 0
  const raw = Buffer.allocUnsafe(size * (1 + size * 3))
  for (let y = 0; y < size; y++) {
    raw[y * (1 + size * 3)] = 0 // filter none
    pixels.copy(raw, y * (1 + size * 3) + 1, y * size * 3, (y + 1) * size * 3)
  }
  const idat = chunk('IDAT', deflateSync(raw, { level: 6 }))
  const iend = chunk('IEND', Buffer.alloc(0))

  return Buffer.concat([sig, ihdr, idat, iend])
}

// ─── Draw icon pixels ─────────────────────────────────────────────────────────

/**
 * Dessine la marque Nuvo : un arc ouvert et son point.
 *
 * Même géométrie que public/favicon.svg, rasterisée à la main — ce script n'a
 * aucune dépendance externe et Node ne sait pas rendre du SVG seul.
 */
function drawIcon(size, maskable = false) {
  const pixels = Buffer.allocUnsafe(size * size * 3)

  // Une icône maskable est rognée par le système : le fond couvre tout le
  // carré et le motif reste dans la zone sûre centrale.
  const r = maskable ? 0 : Math.round(size * 0.22)

  // Dégradé de marque : #1F7D57 → #0E3E28, le vert forêt de la palette.
  const fromR = 0x1f, fromG = 0x7d, fromB = 0x57
  const toR   = 0x0e, toG   = 0x3e, toB   = 0x28

  // Fractions du côté — l'échelle se réduit sur une maskable pour rester dans
  // la zone sûre.
  const k        = maskable ? 0.80 : 1
  const RADIUS   = 0.281 * k   // rayon de l'arc, du centre à son axe
  const STROKE   = 0.050 * k   // demi-épaisseur du trait
  const DOT_R    = 0.088 * k   // rayon du point
  const DOT_ANG  = -0.55       // position angulaire du point, en radians

  // Ouverture de l'arc autour du point. Assez large pour que le point s'en
  // détache nettement : trop serrée, les deux se soudent en une seule masse.
  const GAP      = 0.62

  const dotX = Math.cos(DOT_ANG) * RADIUS
  const dotY = Math.sin(DOT_ANG) * RADIUS

  // L'aiguille : du centre vers le point, sans le toucher.
  const HAND_LEN    = RADIUS * 0.44
  const HAND_STROKE = STROKE * 0.72

  /** Le pixel appartient-il à la marque ? */
  function inMark(px, py, s) {
    const x = (px + 0.5) / s - 0.5
    const y = (py + 0.5) / s - 0.5

    // Le point plein.
    const ddx = x - dotX, ddy = y - dotY
    if (ddx * ddx + ddy * ddy <= DOT_R * DOT_R) return true

    // L'aiguille : distance au segment [centre → HAND_LEN dans la direction
    // du point]. C'est elle qui fait lire une horloge plutôt qu'un anneau.
    const hx = Math.cos(DOT_ANG) * HAND_LEN
    const hy = Math.sin(DOT_ANG) * HAND_LEN
    const t = Math.max(0, Math.min(1, (x * hx + y * hy) / (hx * hx + hy * hy)))
    const px2 = x - hx * t, py2 = y - hy * t
    if (px2 * px2 + py2 * py2 <= HAND_STROKE * HAND_STROKE) return true

    // L'arc : un anneau, moins l'ouverture autour du point.
    const dist = Math.sqrt(x * x + y * y)
    if (Math.abs(dist - RADIUS) > STROKE) return false

    // Écart angulaire au point, ramené dans [-π, π].
    let delta = Math.atan2(y, x) - DOT_ANG
    while (delta > Math.PI) delta -= 2 * Math.PI
    while (delta < -Math.PI) delta += 2 * Math.PI

    return Math.abs(delta) > GAP
  }

  function inRoundedRect(px, py, s, rad) {
    if (rad === 0) return true
    const cx = px < rad ? rad : px > s - rad - 1 ? s - rad - 1 : px
    const cy = py < rad ? rad : py > s - rad - 1 ? s - rad - 1 : py
    const dx = px - cx, dy = py - cy
    return dx * dx + dy * dy <= rad * rad
  }

  for (let y = 0; y < size; y++) {
    const t = y / (size - 1)
    for (let x = 0; x < size; x++) {
      const i = (y * size + x) * 3

      if (!inRoundedRect(x, y, size, r)) {
        // Pas de canal alpha dans ce PNG : hors pastille, on peint le gris de
        // fond de l'application.
        pixels[i] = 0xf9; pixels[i + 1] = 0xfa; pixels[i + 2] = 0xfb
        continue
      }

      if (inMark(x, y, size)) {
        pixels[i] = 255; pixels[i + 1] = 255; pixels[i + 2] = 255
      } else {
        pixels[i]     = Math.round(fromR + (toR - fromR) * t)
        pixels[i + 1] = Math.round(fromG + (toG - fromG) * t)
        pixels[i + 2] = Math.round(fromB + (toB - fromB) * t)
      }
    }
  }

  return buildPNG(size, pixels)
}

// ─── Generate all sizes ───────────────────────────────────────────────────────
const icons = [
  { size: 192, file: 'icon-192.png',          maskable: false },
  { size: 512, file: 'icon-512.png',           maskable: false },
  { size: 512, file: 'icon-maskable-512.png',  maskable: true  },
  { size: 180, file: 'apple-touch.png',        maskable: false },
]

for (const { size, file, maskable } of icons) {
  const buf  = drawIcon(size, maskable)
  const path = join(outDir, file)
  writeFileSync(path, buf)
  console.log(`✓  ${file}  (${size}×${size}, ${(buf.length / 1024).toFixed(0)} KB)`)
}

// Copy apple-touch-icon to public root
const atiBuf = drawIcon(180, false)
writeFileSync(join(__dirname, '../public/apple-touch-icon.png'), atiBuf)
console.log('✓  apple-touch-icon.png  (180×180)')

console.log('\n✅ Toutes les icônes PWA ont été générées !')
console.log('   Elles sont dans frontend/public/icons/')
