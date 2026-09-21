import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link, useSearchParams } from 'react-router-dom';

import api from '../lib/apiClient';
import ProductGrid from '../components/catalog/ProductGrid';
import {
  clearProductsError,
  fetchProducts,
  selectPagination,
  selectProducts,
  selectProductsError,
  selectProductsLoading,
} from '../store/slices/productsSlice';
import styles from './LandingPage.module.css';

const DEFAULT_CATEGORIES = [
  'Everyday Carry',
  'Home Office',
  'Travel Essentials',
  'Outdoor Living',
  'Audio and Tech',
  'Gifts',
];

export default function LandingPage() {
  const dispatch = useDispatch();
  const products = useSelector(selectProducts);
  const loading = useSelector(selectProductsLoading);
  const error = useSelector(selectProductsError);
  const pagination = useSelector(selectPagination);
  const [searchParams, setSearchParams] = useSearchParams();
  const selectedCategory = searchParams.get('category') || null;
  const [page, setPage] = useState(1);
  const [categories, setCategories] = useState(DEFAULT_CATEGORIES);

  const [prevCategory, setPrevCategory] = useState(selectedCategory);
  if (prevCategory !== selectedCategory) {
    setPrevCategory(selectedCategory);
    setPage(1);
  }

  // Fetch real top categories for the strip
  useEffect(() => {
    const ctrl = new AbortController();
    api
      .get('/categories', { params: { sort: 'products_count', per_page: 8 }, signal: ctrl.signal })
      .then((res) => {
        const list = res?.data ?? (Array.isArray(res) ? res : []);
        if (list.length > 0) {
          setCategories(list.map((c) => c.name));
        }
      })
      .catch(() => {});
    return () => ctrl.abort();
  }, []);

  useEffect(() => {
    const promise = dispatch(fetchProducts({ page, category: selectedCategory }));
    return () => promise.abort();
  }, [dispatch, page, selectedCategory]);

  function handleRetry() {
    dispatch(clearProductsError());
    dispatch(fetchProducts({ page, category: selectedCategory }));
  }

  function handleCategorySelect(cat) {
    const next = selectedCategory === cat ? null : cat;
    setPage(1);
    if (next) {
      setSearchParams({ category: next }, { replace: true });
    } else {
      setSearchParams({}, { replace: true });
    }
  }

  function handleClearFilter() {
    setPage(1);
    setSearchParams({}, { replace: true });
  }

  return (
    <div className={styles.page}>
      {/* ── Hero ── */}
      <section className={styles.hero} aria-labelledby="hero-heading">
        <div className={styles.heroBg} aria-hidden="true">
          <div className={styles.orb1} />
          <div className={styles.orb2} />
          <div className={styles.orb3} />
        </div>
        <div className={`container ${styles.heroContent}`}>
          <span className={styles.heroEyebrow}>New Season, New Arrivals</span>
          <h1 id="hero-heading" className={`display-heading ${styles.heroTitle}`}>
            Discover&nbsp;
            <span className={styles.heroGradient}>Amazing</span>
            <br />
            Products
          </h1>
          <p className={styles.heroSubtitle}>
            Curated marketplace featuring top-quality products from the world&apos;s
            finest sellers. Shop with confidence.
          </p>
          <div className={styles.heroActions}>
            <a href="#products" className="btn btn-primary btn-lg">
              Shop Now
            </a>
            <Link to="/register" className="btn btn-outline btn-lg">
              Join for Free
            </Link>
          </div>

          {/* Stats */}
          <div className={styles.heroStats}>
            {[
              { value: '10K+', label: 'Products' },
              { value: '500+', label: 'Sellers' },
              { value: '50K+', label: 'Happy Customers' },
            ].map((stat) => (
              <div key={stat.label} className={styles.stat}>
                <span className={styles.statValue}>{stat.value}</span>
                <span className={styles.statLabel}>{stat.label}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Featured categories strip ── */}
      <section className={styles.categoriesStrip} aria-label="Browse categories">
        <div className="container">
          <div className={styles.categoriesList}>
            {categories.map((cat) => (
              <button
                key={cat}
                className={`${styles.categoryChip} ${selectedCategory === cat ? styles.categoryChipActive : ''}`}
                onClick={() => handleCategorySelect(cat)}
                aria-pressed={selectedCategory === cat}
              >
                {cat}
              </button>
            ))}
            <Link
              to="/categories"
              className={styles.categoryChip}
              style={{ textDecoration: 'none', display: 'inline-flex', alignItems: 'center', gap: '4px' }}
            >
              All Categories →
            </Link>
          </div>
        </div>
      </section>

      {/* ── Product grid ── */}
      <section
        id="products"
        className={`container ${styles.productsSection}`}
        aria-labelledby="products-heading"
      >
        <div className={styles.sectionHeader}>
          <div>
            <h2 id="products-heading" className={styles.sectionTitle}>
              {selectedCategory ? `${selectedCategory} Products` : 'Featured Products'}
            </h2>
            <p className={styles.sectionSubtitle}>
              {pagination
                ? `${pagination.total.toLocaleString()} products${selectedCategory ? ` in ${selectedCategory}` : ''}`
                : 'Discover our latest collection'}
            </p>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', alignItems: 'center' }}>
            {selectedCategory && (
              <button
                className="btn btn-ghost btn-sm"
                onClick={handleClearFilter}
              >
                ✕ Clear filter
              </button>
            )}
            {pagination && pagination.total > 0 && (
              <div className={styles.sortRow}>
                <span className={styles.sortLabel}>Sort by</span>
                <select className={`form-input ${styles.sortSelect}`} disabled>
                  <option>Latest</option>
                </select>
              </div>
            )}
          </div>
        </div>

        <ProductGrid
          products={products}
          loading={loading}
          error={error}
          onRetry={handleRetry}
        />

        {/* Pagination */}
        {pagination && pagination.lastPage > 1 && (
          <nav
            className={styles.pagination}
            aria-label="Product pages"
          >
            <button
              id="prev-page"
              className="btn btn-outline"
              disabled={page === 1}
              onClick={() => setPage((p) => p - 1)}
            >
              ← Previous
            </button>
            <span className={styles.pageInfo}>
              Page {pagination.currentPage} of {pagination.lastPage}
            </span>
            <button
              id="next-page"
              className="btn btn-outline"
              disabled={page === pagination.lastPage}
              onClick={() => setPage((p) => p + 1)}
            >
              Next →
            </button>
          </nav>
        )}
      </section>

      {/* ── Value props ── */}

    </div>
  );
}
