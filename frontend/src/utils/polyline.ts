// Google Encoded Polyline Algorithm
// https://developers.google.com/maps/documentation/utilities/polylinealgorithm

function encodeInt(v: number): string {
  let val = v < 0 ? ~(v << 1) : v << 1
  let s = ''
  while (val >= 32) {
    s += String.fromCharCode(((val & 0x1f) | 0x20) + 63)
    val >>= 5
  }
  return s + String.fromCharCode(val + 63)
}

export function encode(coords: [number, number][]): string {
  let result = ''
  let prevLat = 0
  let prevLng = 0
  for (const [lat, lng] of coords) {
    const iLat = Math.round(lat * 1e5)
    const iLng = Math.round(lng * 1e5)
    result += encodeInt(iLat - prevLat) + encodeInt(iLng - prevLng)
    prevLat = iLat
    prevLng = iLng
  }
  return result
}

export function decode(encoded: string): [number, number][] {
  const coords: [number, number][] = []
  let idx = 0
  let lat = 0
  let lng = 0
  while (idx < encoded.length) {
    let result = 0
    let shift = 0
    let b: number
    do {
      b = encoded.charCodeAt(idx++) - 63
      result |= (b & 0x1f) << shift
      shift += 5
    } while (b >= 32)
    lat += result & 1 ? ~(result >> 1) : result >> 1

    result = 0
    shift = 0
    do {
      b = encoded.charCodeAt(idx++) - 63
      result |= (b & 0x1f) << shift
      shift += 5
    } while (b >= 32)
    lng += result & 1 ? ~(result >> 1) : result >> 1

    coords.push([lat / 1e5, lng / 1e5])
  }
  return coords
}
