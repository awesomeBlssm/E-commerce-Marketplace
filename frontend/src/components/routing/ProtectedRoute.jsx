import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';

/**
 * Wraps any route that requires authentication.
 * - If still initializing (checking /me), renders a loading spinner.
 * - If unauthenticated, redirects to /login preserving the intended path.
 * - Accepts an optional `requiredType` prop ('admin', 'seller') to restrict access.
 */
export default function ProtectedRoute({ children, requiredType }) {
  const { isAuthenticated, isInitializing, user } = useAuth();
  const location = useLocation();

  if (isInitializing) {
    return (
      <div
        style={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          minHeight: '400px',
          flexDirection: 'column',
          gap: '1rem',
        }}
      >
        <div className="spinner spinner-lg" />
        <p style={{ color: 'var(--text-secondary)', fontSize: 'var(--text-sm)' }}>
          Restoring session…
        </p>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" state={{ from: location }} replace />;
  }

  // Role guard — seller/admin only routes
  if (requiredType && user?.type !== requiredType && user?.type !== 'admin') {
    return (
      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '5rem',
          gap: '1rem',
          textAlign: 'center',
          minHeight: '400px',
        }}
      >
        <span style={{ fontSize: '3rem' }}>🔒</span>
        <h1 style={{ fontSize: 'var(--text-2xl)', fontWeight: 700 }}>Access Denied</h1>
        <p style={{ color: 'var(--text-secondary)' }}>
          You do not have permission to view this page.
        </p>
      </div>
    );
  }

  return children;
}
