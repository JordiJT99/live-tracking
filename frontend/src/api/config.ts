// Set VITE_USE_MOCK=false in .env.local when the backend is running
export const USE_MOCK = import.meta.env.VITE_USE_MOCK !== 'false'
