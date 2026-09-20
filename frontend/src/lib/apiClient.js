/**
 * Lightweight fetch-based API client.
 *
 * Base URL is read from the Vite env variable VITE_API_BASE_URL (default: "/api").
 * The Vite dev-server proxy forwards /api to Laravel at http://127.0.0.1:8000
 * so no machine-specific URLs are hard-coded here.
 */

const BASE_URL = (import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '');

// -------------------------------------------------------------------
// Typed error class
// -------------------------------------------------------------------

export class ApiError extends Error {
  /** @type {number} */
  status;
  /** @type {Record<string, string[]> | null} */
  errors;

  /**
   * @param {string} message
   * @param {number} status
   * @param {Record<string, string[]> | null} errors
   */
  constructor(message, status, errors = null) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

// -------------------------------------------------------------------
// Token helpers (opaque bearer token stored in localStorage)
// -------------------------------------------------------------------

const TOKEN_KEY = 'auth_token';

export const tokenStorage = {
  get: () => localStorage.getItem(TOKEN_KEY),
  set: (token) => localStorage.setItem(TOKEN_KEY, token),
  remove: () => localStorage.removeItem(TOKEN_KEY),
};

// -------------------------------------------------------------------
// Core request function
// -------------------------------------------------------------------

/**
 * @param {string} path  - e.g. "/products" or "/auth/me"
 * @param {RequestInit & { params?: Record<string, string|number> }} options
 * @returns {Promise<unknown>}
 */
async function request(path, options = {}) {
  const { params, headers: extraHeaders = {}, ...fetchOptions } = options;

  // Build URL with optional query params
  let url = `${BASE_URL}${path}`;
  if (params && Object.keys(params).length > 0) {
    const qs = new URLSearchParams(
      Object.fromEntries(
        Object.entries(params).map(([k, v]) => [k, String(v)])
      )
    );
    url = `${url}?${qs.toString()}`;
  }

  // Build headers
  const headers = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...extraHeaders,
  };

  const token = tokenStorage.get();
  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  let response;
  try {
    response = await fetch(url, { ...fetchOptions, headers });
  } catch (err) {
    if (err.name === 'AbortError' || fetchOptions.signal?.aborted) {
      throw err;
    }
    throw new ApiError('Network error — please check your connection.', 0);
  }

  // 204 No Content — nothing to parse
  if (response.status === 204) {
    return null;
  }

  let body;
  try {
    body = await response.json();
  } catch {
    body = null;
  }

  if (!response.ok) {
    // 401 — token is invalid/expired; clear it
    if (response.status === 401) {
      tokenStorage.remove();
    }

    const message =
      body?.message ??
      (response.status === 403
        ? 'You do not have permission to perform this action.'
        : response.status === 422
          ? 'Validation failed.'
          : `Request failed with status ${response.status}.`);

    throw new ApiError(message, response.status, body?.errors ?? null);
  }

  return body;
}

// -------------------------------------------------------------------
// HTTP convenience methods
// -------------------------------------------------------------------

const api = {
  /**
   * @param {string} path
   * @param {{ params?: Record<string, string|number>, signal?: AbortSignal }} [options]
   */
  get: (path, options = {}) =>
    request(path, { method: 'GET', ...options }),

  /**
   * @param {string} path
   * @param {unknown} body
   * @param {{ signal?: AbortSignal }} [options]
   */
  post: (path, body, options = {}) =>
    request(path, {
      method: 'POST',
      body: JSON.stringify(body),
      ...options,
    }),

  /**
   * @param {string} path
   * @param {unknown} body
   * @param {{ signal?: AbortSignal }} [options]
   */
  put: (path, body, options = {}) =>
    request(path, {
      method: 'PUT',
      body: JSON.stringify(body),
      ...options,
    }),

  /**
   * @param {string} path
   * @param {unknown} body
   * @param {{ signal?: AbortSignal }} [options]
   */
  patch: (path, body, options = {}) =>
    request(path, {
      method: 'PATCH',
      body: JSON.stringify(body),
      ...options,
    }),

  /**
   * @param {string} path
   * @param {{ signal?: AbortSignal }} [options]
   */
  delete: (path, options = {}) =>
    request(path, { method: 'DELETE', ...options }),
};

export default api;
