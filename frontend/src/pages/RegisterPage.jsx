import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import { ApiError } from '../lib/apiClient';
import styles from './AuthPage.module.css';

export default function RegisterPage() {
  const { register } = useAuth();
  const navigate = useNavigate();
  const toast = useToast();


  const [fields, setFields] = useState({
    email: '',
    password: '',
    password_confirmation: '',
    full_name: '',
    phone: '',
    accepts_marketing: false,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [fieldErrors, setFieldErrors] = useState({});

  function handleChange(e) {
    const { name, value, type, checked } = e.target;
    setFields((f) => ({ ...f, [name]: type === 'checkbox' ? checked : value }));
  }

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setFieldErrors({});

    // Client-side validation
    const errs = {};
    if (!fields.email) errs.email = ['Email is required.'];
    if (!fields.password) errs.password = ['Password is required.'];
    if (fields.password.length > 0 && fields.password.length < 8)
      errs.password = ['Password must be at least 8 characters.'];
    if (fields.password !== fields.password_confirmation)
      errs.password_confirmation = ['Passwords do not match.'];
    if (Object.keys(errs).length > 0) {
      setFieldErrors(errs);
      return;
    }

    setLoading(true);
    try {
      await register(fields);
      toast.success('Account created! Welcome to Blossom Market.');
      navigate('/', { replace: true });

    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          setFieldErrors(err.errors);
        } else {
          setError(err.message);
        }
      } else {
        setError('An unexpected error occurred. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className={styles.page}>
      <div className={styles.card}>
        <div className={styles.header}>
          <span className={styles.logoMark}>✿</span>
          <h1 className={styles.title}>Create your account</h1>
          <p className={styles.subtitle}>Join Blossom Market and start shopping</p>
        </div>

        <form id="register-form" onSubmit={handleSubmit} className={styles.form} noValidate>
          {error && (
            <div className={styles.alert} role="alert" aria-live="polite">
              {error}
            </div>
          )}

          <div className="form-group">
            <label className="form-label" htmlFor="reg-name">Full Name</label>
            <input
              id="reg-name"
              name="full_name"
              type="text"
              className="form-input"
              value={fields.full_name}
              onChange={handleChange}
              autoComplete="name"
              placeholder="Jane Smith"
            />
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="reg-email">Email <span className={styles.required}>*</span></label>
            <input
              id="reg-email"
              name="email"
              type="email"
              className={`form-input ${fieldErrors.email ? 'error' : ''}`}
              value={fields.email}
              onChange={handleChange}
              autoComplete="email"
              placeholder="you@example.com"
              required
            />
            {fieldErrors.email && <p className="form-error">{fieldErrors.email[0]}</p>}
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="reg-phone">Phone</label>
            <input
              id="reg-phone"
              name="phone"
              type="tel"
              className="form-input"
              value={fields.phone}
              onChange={handleChange}
              autoComplete="tel"
              placeholder="+1 555 000 0000"
            />
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="reg-password">Password <span className={styles.required}>*</span></label>
            <input
              id="reg-password"
              name="password"
              type="password"
              className={`form-input ${fieldErrors.password ? 'error' : ''}`}
              value={fields.password}
              onChange={handleChange}
              autoComplete="new-password"
              placeholder="Min. 8 characters"
              required
            />
            {fieldErrors.password && <p className="form-error">{fieldErrors.password[0]}</p>}
          </div>

          <div className="form-group">
            <label className="form-label" htmlFor="reg-password-confirm">Confirm Password <span className={styles.required}>*</span></label>
            <input
              id="reg-password-confirm"
              name="password_confirmation"
              type="password"
              className={`form-input ${fieldErrors.password_confirmation ? 'error' : ''}`}
              value={fields.password_confirmation}
              onChange={handleChange}
              autoComplete="new-password"
              placeholder="Repeat password"
              required
            />
            {fieldErrors.password_confirmation && (
              <p className="form-error">{fieldErrors.password_confirmation[0]}</p>
            )}
          </div>

          <label className={styles.checkboxLabel}>
            <input
              id="reg-marketing"
              name="accepts_marketing"
              type="checkbox"
              checked={fields.accepts_marketing}
              onChange={handleChange}
            />
            <span>Send me news and special offers</span>
          </label>

          <button
            id="register-submit"
            type="submit"
            className="btn btn-primary btn-lg"
            style={{ width: '100%' }}
            disabled={loading}
          >
            {loading ? (
              <>
                <span className="spinner" style={{ borderTopColor: 'white', width: 16, height: 16, borderWidth: 2 }} />
                Creating account…
              </>
            ) : (
              'Create Account'
            )}
          </button>
        </form>

        <p className={styles.switchLink}>
          Already have an account?{' '}
          <Link to="/login" className={styles.link}>Sign in</Link>
        </p>
      </div>
    </div>
  );
}
