import ProductCard from './ProductCard';
import SkeletonCard from './SkeletonCard';
import styles from './ProductGrid.module.css';

const SKELETON_COUNT = 8;

export default function ProductGrid({ products, loading, error, onRetry }) {
  if (loading) {
    return (
      <div className={styles.grid} aria-busy="true" aria-label="Loading products">
        {Array.from({ length: SKELETON_COUNT }).map((_, i) => (
          <SkeletonCard key={i} />
        ))}
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.state} role="alert">
        <span className={styles.stateIcon}>⚠️</span>
        <h3 className={styles.stateTitle}>Something went wrong</h3>
        <p className={styles.stateText}>{error}</p>
        {onRetry && (
          <button
            id="retry-products"
            className="btn btn-primary"
            onClick={onRetry}
          >
            Try again
          </button>
        )}
      </div>
    );
  }

  if (products.length === 0) {
    return (
      <div className={styles.state}>
        <span className={styles.stateIcon}>🛍️</span>
        <h3 className={styles.stateTitle}>No products found</h3>
        <p className={styles.stateText}>
          Check back soon — new products are added regularly.
        </p>
      </div>
    );
  }

  return (
    <div
      className={`${styles.grid} fade-in`}
      role="list"
      aria-label={`${products.length} products`}
    >
      {products.map((product) => (
        <div key={product.id} role="listitem">
          <ProductCard product={product} />
        </div>
      ))}
    </div>
  );
}
