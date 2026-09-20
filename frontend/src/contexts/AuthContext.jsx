import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import api, { ApiError, tokenStorage } from '../lib/apiClient';

// -------------------------------------------------------------------
// Shape normalizer
// -------------------------------------------------------------------
// login/register → { user: {..., customer: {...} }, token, token_type }
// /me            → { id, email, type, customer: {...} }   (bare user)
// We always store the flat user shape: { id, email, type, status, customer }

function normalizeUser(raw) {
  if (!raw) return null;
  return {
    id: raw.id,
    email: raw.email,
    type: raw.type ?? 'user',
    status: raw.status ?? 'active',
    customer: raw.customer ?? null,
  };
}

// -------------------------------------------------------------------
// Context
// -------------------------------------------------------------------

const AuthContext = createContext(null);

// -------------------------------------------------------------------
// Provider
// -------------------------------------------------------------------

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  /** @type {'idle'|'loading'|'ready'} */
  const [initState, setInitState] = useState('idle');
  const [authError, setAuthError] = useState(null);
  const restoreRef = useRef(false);

  // Restore session on mount via /api/auth/me
  useEffect(() => {
    if (restoreRef.current) return;
    restoreRef.current = true;

    const token = tokenStorage.get();
    if (!token) {
      setInitState('ready');
      return;
    }

    setInitState('loading');
    api
      .get('/auth/me')
      .then((data) => {
        setUser(normalizeUser(data));
        setInitState('ready');
      })
      .catch((err) => {
        // 401 means token is bad — tokenStorage.remove() already called in apiClient
        tokenStorage.remove();
        setUser(null);
        setInitState('ready');
        if (err instanceof ApiError && err.status !== 401) {
          setAuthError(err.message);
        }
      });
  }, []);

  // ------------------------------------------------------------------
  // login
  // ------------------------------------------------------------------
  const login = useCallback(async (email, password) => {
    setAuthError(null);
    const data = await api.post('/auth/login', { email, password });
    tokenStorage.set(data.token);
    setUser(normalizeUser(data.user));
    return data;
  }, []);

  // ------------------------------------------------------------------
  // register
  // ------------------------------------------------------------------
  const register = useCallback(async (fields) => {
    setAuthError(null);
    const data = await api.post('/auth/register', fields);
    tokenStorage.set(data.token);
    setUser(normalizeUser(data.user));
    return data;
  }, []);

  // ------------------------------------------------------------------
  // logout
  // ------------------------------------------------------------------
  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout', {});
    } finally {
      tokenStorage.remove();
      setUser(null);
    }
  }, []);

  const value = {
    user,
    isAuthenticated: user !== null,
    isInitializing: initState !== 'ready',
    authError,
    login,
    register,
    logout,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

// -------------------------------------------------------------------
// Hook
// -------------------------------------------------------------------

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used inside <AuthProvider>');
  return ctx;
}

export default AuthContext;
