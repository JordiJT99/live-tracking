import { describe, it, expect } from 'vitest'
import { encode, decode } from './polyline'

// Official Google example: https://developers.google.com/maps/documentation/utilities/polylinealgorithm
const OFFICIAL_COORDS: [number, number][] = [
  [38.5, -120.2],
  [40.7, -120.95],
  [43.252, -126.453],
]
const OFFICIAL_ENCODED = '_p~iF~ps|U_ulLnnqC_mqNvxq`@'

describe('PolylineCodec', () => {
  it('encodes the official Google example', () => {
    expect(encode(OFFICIAL_COORDS)).toBe(OFFICIAL_ENCODED)
  })

  it('decodes the official Google example', () => {
    const result = decode(OFFICIAL_ENCODED)
    expect(result).toHaveLength(3)
    expect(result[0]![0]).toBeCloseTo(38.5, 4)
    expect(result[0]![1]).toBeCloseTo(-120.2, 4)
    expect(result[1]![0]).toBeCloseTo(40.7, 4)
    expect(result[2]![0]).toBeCloseTo(43.252, 4)
  })

  it('round-trips arbitrary Barcelona coordinates', () => {
    const coords: [number, number][] = [
      [41.3864, 2.1734],
      [41.3851, 2.1636],
      [41.4036, 2.1744],
    ]
    const result = decode(encode(coords))
    for (let i = 0; i < coords.length; i++) {
      expect(result[i]![0]).toBeCloseTo(coords[i]![0], 4)
      expect(result[i]![1]).toBeCloseTo(coords[i]![1], 4)
    }
  })

  it('handles negative coordinates', () => {
    const coords: [number, number][] = [[-33.8688, 151.2093]]
    const result = decode(encode(coords))
    expect(result[0]![0]).toBeCloseTo(-33.8688, 4)
    expect(result[0]![1]).toBeCloseTo(151.2093, 4)
  })
})
