import { useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import api from '../lib/apiClient';
import { addItemToCart, selectCartLoading } from '../store/slices/cartSlice';
import styles from './ProductDetailPage.module.css';

function formatPrice(cents, currency = 'PHP') {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(cents / 100);
}

function getVariantOptionSelections(variant) {
  return Object.fromEntries(
    (variant?.option_values ?? []).map((optionValue) => [optionValue.option_id, optionValue.id]),
  );
}

const Spinner = () => (
  <div className={styles.spinnerWrap}>
    <div className="spinner spinner-lg" />
  </div>
);

const ImageFallback = () => (
  <div className={styles.imageFallback} aria-hidden="true">
    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1" strokeLinecap="round" strokeLinejoin="round">
      <rect x="3" y="3" width="18" height="18" rx="2" />
      <circle cx="8.5" cy="8.5" r="1.5" />
      <polyline points="21 15 16 10 5 21" />
    </svg>
    <span>No image available</span>
  </div>
);

export default function ProductDetailPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const toast = useToast();
  const { isAuthenticated } = useAuth();
  const cartLoading = useSelector(selectCartLoading);

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeImage, setActiveImage] = useState(0);
  const [selectedVariant, setSelectedVariant] = useState(null);
  const [selectedOptionValues, setSelectedOptionValues] = useState({});
  const [quantity, setQuantity] = useState(1);
  const [addingToCart, setAddingToCart] = useState(false);

  useEffect(() => {
    const ctrl = new AbortController();
    setLoading(true);
    setError(null);

    api
      .get(`/products/${id}`, { signal: ctrl.signal })
      .then((data) => {
        setProduct(data);
        const firstActive = data.variants?.find((v) => v.is_active);
        const initialVariant = firstActive ?? data.variants?.[0] ?? null;
        setSelectedVariant(initialVariant);
        setSelectedOptionValues(getVariantOptionSelections(initialVariant));
        setLoading(false);
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        setError(err.status === 404 ? '404' : (err.message ?? 'Failed to load product.'));
        setLoading(false);
      });

    return () => ctrl.abort();
  }, [id]);

  // ── Add to Cart ──────────────────────────────────────────────────
  async function handleAddToCart() {
    if (!isAuthenticated) {
      navigate('/login', { state: { from: { pathname: `/products/${id}` } } });
      return;
    }
    if (!selectedVariant) return;

    setAddingToCart(true);
    try {
      await dispatch(
        addItemToCart({
          variantId: selectedVariant.id,
          quantity,
          currency: selectedVariant.currency ?? 'PHP',
        })
      ).unwrap();
      toast.success(`"${product?.title}" added to cart!`);
    } catch (err) {
      toast.error(err?.message ?? 'Failed to add item to cart.');
    } finally {
      setAddingToCart(false);
    }
  }

  // ── Loading / Error states ────────────────────────────────────────
  if (loading) return <Spinner />;

  if (error === '404') {
    return (
      <div className={styles.stateWrap}>
        <span className={styles.stateIcon}>🔍</span>
        <h1 className={styles.stateTitle}>Product not found</h1>
        <p className={styles.stateText}>This product may have been removed or the link is incorrect.</p>
        <Link to="/" className="btn btn-primary">Back to Shop</Link>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.stateWrap} role="alert">
        <span className={styles.stateIcon}>⚠️</span>
        <h1 className={styles.stateTitle}>Something went wrong</h1>
        <p className={styles.stateText}>{error}</p>
        <div className={styles.stateActions}>
          <button className="btn btn-primary" onClick={() => window.location.reload()}>Try again</button>
          <button className="btn btn-outline" onClick={() => navigate(-1)}>Go back</button>
        </div>
      </div>
    );
  }

  const { title, description, brand, categories, images, variants, options } = product;
  const displayImages = images?.length > 0 ? images : null;
  const activeVariants = variants?.filter((v) => v.is_active) ?? [];
  const outOfStock = activeVariants.length === 0;

  function selectOptionValue(option, value) {
    const nextSelections = {
      ...selectedOptionValues,
      [option.id]: value.id,
    };
    const matchingVariant = activeVariants.find((variant) => {
      const variantSelections = getVariantOptionSelections(variant);
      return Object.entries(nextSelections).every(
        ([optionId, valueId]) => variantSelections[optionId] === valueId,
      );
    });

    if (matchingVariant) {
      setSelectedVariant(matchingVariant);
      setSelectedOptionValues(getVariantOptionSelections(matchingVariant));
    } else {
      setSelectedOptionValues(nextSelections);
    }
  }

  return (
    <div className="container">
      <div className={styles.page}>
        {/* Breadcrumb */}
        <nav className={styles.breadcrumb} aria-label="Breadcrumb">
          <Link to="/" className={styles.breadcrumbLink}>Shop</Link>
          <span aria-hidden="true">›</span>
          {brand && <span className={styles.breadcrumbLink}>{brand.name}</span>}
          {brand && <span aria-hidden="true">›</span>}
          <span className={styles.breadcrumbCurrent} aria-current="page">{title}</span>
        </nav>

        <div className={styles.layout}>
          {/* Images */}
          <div className={styles.gallery}>
            <div className={styles.mainImage}>
              {displayImages ? (
                <img
                  src={displayImages[activeImage]?.url}
                  alt={displayImages[activeImage]?.alt_text || title}
                  className={styles.mainImg}
                />
              ) : (
                <ImageFallback />
              )}
            </div>

            {displayImages && displayImages.length > 1 && (
              <div className={styles.thumbs} role="list" aria-label="Product images">
                {displayImages.map((img, i) => (
                  <button
                    key={img.id}
                    id={`thumb-${i}`}
                    className={`${styles.thumb} ${i === activeImage ? styles.thumbActive : ''}`}
                    onClick={() => setActiveImage(i)}
                    aria-label={`View image ${i + 1}`}
                    role="listitem"
                  >
                    <img src={img.url} alt={img.alt_text || `Image ${i + 1}`} />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Info panel */}
          <div className={styles.info}>
            {brand && (
              <Link to="/" className={styles.brandLink}>
                <span className="badge badge-brand">{brand.name}</span>
              </Link>
            )}

            <h1 className={styles.title}>{title}</h1>

            {categories?.length > 0 && (
              <div className={styles.categories}>
                {categories.map((cat) => (
                  <span key={cat.id} className={styles.catTag}>{cat.name}</span>
                ))}
              </div>
            )}

            {/* Pricing */}
            <div className={styles.pricingBox}>
              {selectedVariant ? (
                <div className={styles.priceRow}>
                  <span className={styles.price}>
                    {formatPrice(selectedVariant.price_cents, selectedVariant.currency)}
                  </span>
                  {selectedVariant.compare_at_cents &&
                    selectedVariant.compare_at_cents > selectedVariant.price_cents && (
                      <span className={styles.comparePrice}>
                        {formatPrice(selectedVariant.compare_at_cents, selectedVariant.currency)}
                      </span>
                    )}
                  {outOfStock && (
                    <span className={styles.outOfStock}>Out of stock</span>
                  )}
                </div>
              ) : (
                <span className={styles.noPricing}>Pricing not available</span>
              )}
            </div>

            {/* Options */}
            {options?.length > 0 && (
              <div className={styles.options}>
                {options.map((opt) => (
                  <div key={opt.id} className={styles.optionGroup}>
                    <label className={styles.optionLabel}>{opt.name}</label>
                    <div className={styles.optionValues}>
                      {opt.values?.map((val) => (
                        <button
                          key={val.id}
                          type="button"
                          className={`${styles.optionValue} ${selectedOptionValues[opt.id] === val.id ? styles.optionValueActive : ''}`}
                          title={val.value}
                          aria-pressed={selectedOptionValues[opt.id] === val.id}
                          onClick={() => selectOptionValue(opt, val)}
                        >
                          {val.value}
                        </button>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}

            {/* Variant selector */}
            {activeVariants.length > 1 && (
              <div className={styles.variantGroup}>
                <label className={styles.optionLabel} htmlFor="variant-select">Variant</label>
                <select
                  id="variant-select"
                  className="form-input"
                  value={selectedVariant?.id ?? ''}
                  onChange={(e) => {
                    const v = variants.find((x) => x.id === e.target.value);
                    setSelectedVariant(v ?? null);
                    setSelectedOptionValues(getVariantOptionSelections(v));
                  }}
                >
                  {activeVariants.map((v) => (
                    <option key={v.id} value={v.id}>
                      {v.sku ?? `Variant ${v.id.slice(0, 6)}`} — {formatPrice(v.price_cents, v.currency)}
                    </option>
                  ))}
                </select>
              </div>
            )}

            {/* Quantity */}
            {!outOfStock && selectedVariant && (
              <div className={styles.qtyGroup}>
                <label className={styles.optionLabel} htmlFor="qty-input">Quantity</label>
                <div className={styles.qtyRow}>
                  <button
                    id="qty-dec"
                    className={styles.qtyBtn}
                    onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                    aria-label="Decrease quantity"
                    disabled={quantity <= 1}
                  >−</button>
                  <input
                    id="qty-input"
                    type="number"
                    min="1"
                    value={quantity}
                    onChange={(e) => setQuantity(Math.max(1, Number(e.target.value)))}
                    className={styles.qtyInput}
                    aria-label="Quantity"
                  />
                  <button
                    id="qty-inc"
                    className={styles.qtyBtn}
                    onClick={() => setQuantity((q) => q + 1)}
                    aria-label="Increase quantity"
                  >+</button>
                </div>
              </div>
            )}

            {/* Add to Cart */}
            <button
              id="add-to-cart-btn"
              className={`btn btn-primary btn-lg ${styles.addBtn}`}
              disabled={outOfStock || !selectedVariant || addingToCart || cartLoading}
              onClick={handleAddToCart}
            >
              {addingToCart ? (
                <>
                  <span className="spinner" style={{ borderTopColor: 'white', width: 18, height: 18, borderWidth: 2 }} />
                  Adding…
                </>
              ) : outOfStock ? (
                'Out of Stock'
              ) : !isAuthenticated ? (
                'Sign in to Add to Cart'
              ) : (
                'Add to Cart'
              )}
            </button>

            {/* Description */}
            {description && (
              <div className={styles.descSection}>
                <h2 className={styles.descTitle}>Description</h2>
                <p className={styles.desc}>{description}</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
