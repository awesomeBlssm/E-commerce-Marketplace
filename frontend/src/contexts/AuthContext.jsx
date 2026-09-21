import { createContext, useCallback, useContext, useEffect, useRef, useState } from 'react';
import api, { ApiError } from '../lib/apiClient';

// -------------------------------------------------------------------
// Shape normalizer
// -------------------------------------------------------------------
// login/register → { user: {..., customer: {...} } }
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

    setInitState('loading');
    api
      .get('/auth/me')
      .then((data) => {
        setUser(normalizeUser(data));
        setInitState('ready');
      })
      .catch((err) => {
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
    setUser(normalizeUser(data.user));
    return data;
  }, []);

  // ------------------------------------------------------------------
  // register
  // ------------------------------------------------------------------
  const register = useCallback(async (fields) => {
    setAuthError(null);
    const data = await api.post('/auth/register', fields);
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
