// Vitest setup
import { vi } from 'vitest'

// Mock fetch globally
const fetchMock = vi.fn()
globalThis.fetch = fetchMock

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
globalThis.localStorage = localStorageMock

// Mock crypto.randomUUID
if (typeof globalThis.crypto !== 'undefined') {
  Object.defineProperty(globalThis.crypto, 'randomUUID', {
    value: vi.fn(() => 'test-uuid-1234'),
    configurable: true,
  })
}

export { fetchMock, localStorageMock }