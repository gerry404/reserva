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
function drawIcon(size, maskable = false) {
  const pixels = Buffer.allocUnsafe(size * size * 3)
  const r      = maskable ? 0 : Math.round(size * 0.22) // corner radius

  // Gradient: #6366f1 → #8b5cf6
  const fromR = 0x63, fromG = 0x66, fromB = 0xf1
  const toR   = 0x8b, toG   = 0x5c, toB   = 0xf6

  // Simple bitmap letter "R" at ~56% font size (precomputed 8×11 grid, scaled)
  function isLetterR(px, py, s) {
    const fw   = s * 0.32  // letter width
    const fh   = s * 0.56  // letter height
    const ox   = (s - fw) / 2
    const oy   = (s - fh) / 2

    const x = (px - ox) / fw  // 0..1 within letter box
    const y = (py - oy) / fh

    if (x < 0 || x > 1 || y < 0 || y > 1) return false

    const strokeW = 0.18  // relative stroke width

    // Vertical bar (left)
    if (x < strokeW) return true

    // Top horizontal bar
    if (y < strokeW && x < 0.85) return true

    // Middle horizontal bar
    if (y > 0.44 && y < 0.44 + strokeW && x < 0.85) return true

    // Right arc top half (rough circle approximation)
    if (y < 0.5) {
      const cx = 0.85, cy = 0.25
      const dx = x - cx, dy = y - cy
      const dist = Math.sqrt(dx * dx + dy * dy)
      if (dist < 0.38 && dist > 0.38 - strokeW) return true
    }

    // Diagonal leg (bottom right)
    if (y > 0.5) {
      const legX = strokeW + (y - 0.5) * 1.3
      if (Math.abs(x - legX) < strokeW * 0.85) return true
    }

    return false
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
      const i   = (y * size + x) * 3
      const inBg = inRoundedRect(x, y, size, r)

      if (!inBg) {
        // Transparent → white background in PNG (no alpha channel, use white)
        pixels[i] = 0xf9; pixels[i+1] = 0xfa; pixels[i+2] = 0xfb
        continue
      }

      // Gradient color
      const gr  = Math.round(fromR + (toR - fromR) * t)
      const gg  = Math.round(fromG + (toG - fromG) * t)
      const gb  = Math.round(fromB + (toB - fromB) * t)

      if (isLetterR(x, y, size)) {
        // White letter with slight shadow effect
        pixels[i] = 255; pixels[i+1] = 255; pixels[i+2] = 255
      } else {
        pixels[i] = gr; pixels[i+1] = gg; pixels[i+2] = gb
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
