import { Link, useRouteError, isRouteErrorResponse } from 'react-router-dom';
import styles from './ErrorPage.module.css';

export default function ErrorPage() {
  const error = useRouteError();

  const is404 =
    isRouteErrorResponse(error) && error.status === 404;

  const title = is404 ? 'Page not found' : 'Something went wrong';
  const emoji = is404 ? '🔍' : '⚠️';
  const message = is404
    ? "The page you're looking for doesn't exist or has been moved."
    : error?.data?.message ?? error?.message ?? 'An unexpected error occurred.';

  return (
    <div className={styles.page}>
      <div className={styles.content}>
        <span className={styles.emoji}>{emoji}</span>
        {is404 && <p className={styles.code}>404</p>}
        <h1 className={styles.title}>{title}</h1>
        <p className={styles.message}>{message}</p>
        <div className={styles.actions}>
          <Link to="/" className="btn btn-primary btn-lg">
            Back to Shop
          </Link>
          <button
            className="btn btn-outline btn-lg"
            onClick={() => window.history.back()}
          >
            Go Back
          </button>
        </div>
      </div>
    </div>
  );
}
