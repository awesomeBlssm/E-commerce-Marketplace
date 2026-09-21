import { Link } from 'react-router-dom';
import styles from './ProductCard.module.css';

/**
 * Formats a minor-unit integer price (cents) to a locale currency string.
 * @param {number} cents
 * @param {string} currency
 */
function formatPrice(cents, currency = 'PHP') {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(cents / 100);
}

/**
 * Returns the lowest active-variant price from the list.
 * @param {Array} variants
 */
function getMinPrice(variants) {
  if (!variants || variants.length === 0) return null;
  const active = variants.filter((v) => v.is_active);
  if (active.length === 0) return null;
  return active.reduce((min, v) => (v.price_cents < min.price_cents ? v : min));
}

const ImageFallback = () => (
  <div className={styles.imageFallback} aria-hidden="true">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
      <circle cx="8.5" cy="8.5" r="1.5" />
      <polyline points="21 15 16 10 5 21" />
    </svg>
  </div>
);

export default function ProductCard({ product }) {
  const { id, title, brand, first_image, active_variants } = product;
  const minVariant = getMinPrice(active_variants);
  const imageUrl = first_image?.url;
  const imageAlt = first_image?.alt_text || title;

  return (
    <article className={`card ${styles.card}`}>
      <Link
        to={`/products/${id}`}
        className={styles.imageLink}
        tabIndex={-1}
        aria-hidden="true"
      >
        {imageUrl ? (
          <img
            className={styles.image}
            src={imageUrl}
            alt={imageAlt}
            loading="lazy"
            onError={(e) => {
              e.currentTarget.style.display = 'none';
              e.currentTarget.nextElementSibling.style.display = 'flex';
            }}
          />
        ) : null}
        <ImageFallback />
      </Link>

      <div className={styles.body}>
        {brand && (
          <span className={`badge badge-brand ${styles.brand}`}>
            {brand.name}
          </span>
        )}

        <Link to={`/products/${id}`} className={styles.titleLink}>
          <h2 className={styles.title}>{title}</h2>
        </Link>

        <div className={styles.footer}>
          <div className={styles.pricing}>
            {minVariant ? (
              <>
                <span className={styles.priceLabel}>From</span>
                <span className={styles.price}>
                  {formatPrice(minVariant.price_cents, minVariant.currency)}
                </span>
              </>
            ) : (
              <span className={styles.pricePlaceholder}>See pricing</span>
            )}
          </div>

          <Link
            id={`view-product-${id}`}
            to={`/products/${id}`}
            className="btn btn-primary btn-sm"
          >
            View
          </Link>
        </div>
      </div>
    </article>
  );
}
