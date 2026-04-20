/**
 * Build public URL for files stored on the public disk (storage/app/public → /storage/...).
 */
export function storageUrl(path) {
  if (path == null || path === '') return '';
  const s = String(path).trim();
  if (s.startsWith('http://') || s.startsWith('https://')) return s;
  if (s.startsWith('/storage/')) return s;
  return `/storage/${s.replace(/^\/+/, '')}`;
}

/** Laravel JSON column may decode as array or object depending on data */
export function normalizeStoragePaths(raw) {
  if (!raw) return [];
  if (Array.isArray(raw)) return raw.map(String).filter(Boolean);
  if (typeof raw === 'object') return Object.values(raw).map(String).filter(Boolean);
  return [];
}
