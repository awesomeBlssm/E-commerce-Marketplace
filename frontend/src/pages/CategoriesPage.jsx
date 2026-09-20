import { useEffect, useMemo, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import api from '../lib/apiClient';
import styles from './CategoriesPage.module.css';

const CATEGORY_EMOJIS = {
  'everyday carry': '🎒',
  'home office': '🖥️',
  'travel essentials': '✈️',
  'outdoor living': '🏕️',
  'audio and tech': '🎧',
  gifts: '🎁',
  electronics: '💻',
  fashion: '👗',
  'home & garden': '🏡',
  beauty: '💄',
  sports: '⚽',
  books: '📚',
  toys: '🧸',
  food: '🍎',
  automotive: '🚗',
  health: '💊',
};

function getCategoryEmoji(name = '') {
  const lower = name.toLowerCase().trim();
  if (CATEGORY_EMOJIS[lower]) return CATEGORY_EMOJIS[lower];
  if (lower.includes('carry') || lower.includes('bag') || lower.includes('pack')) return '🎒';
  if (lower.includes('office') || lower.includes('desk') || lower.includes('work')) return '🖥️';
  if (lower.includes('travel') || lower.includes('trip')) return '✈️';
  if (lower.includes('outdoor') || lower.includes('camp') || lower.includes('hike') || lower.includes('living')) return '🏕️';
  if (lower.includes('audio') || lower.includes('tech') || lower.includes('sound') || lower.includes('headphone')) return '🎧';
  if (lower.includes('gift') || lower.includes('present')) return '🎁';
  if (lower.includes('phone') || lower.includes('electr') || lower.includes('gadget')) return '💻';
  if (lower.includes('cloth') || lower.includes('wear') || lower.includes('fashion') || lower.includes('apparel')) return '👗';
  if (lower.includes('home') || lower.includes('living') || lower.includes('kitchen')) return '🏡';
  if (lower.includes('sport') || lower.includes('fit')) return '⚽';
  if (lower.includes('book') || lower.includes('read')) return '📚';
  return '🛍️';
}

function SkeletonCard() {
  return (
    <div className={`${styles.card} ${styles.skeleton}`}>
      <div className={styles.cardTop}>
        <div className={styles.skeletonIcon} />
      </div>
      <div className={styles.skeletonTitle} />
      <div className={styles.skeletonSub} />
    </div>
  );
}

export default function CategoriesPage() {
  const navigate = useNavigate();
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');
  const [sortBy, setSortBy] = useState('products_count');

  useEffect(() => {
    const ctrl = new AbortController();

    api
      .get('/categories', {
        params: { sort: 'products_count', per_page: 50 },
        signal: ctrl.signal,
      })
      .then((data) => {
        const raw = data?.data ?? (Array.isArray(data) ? data : []);
        setCategories(raw);
        setLoading(false);
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        setError(err.message ?? 'Failed to load categories.');
        setLoading(false);
      });

    return () => ctrl.abort();
  }, []);

  // Filter and sort categories
  const filteredCategories = useMemo(() => {
    let result = categories.filter((c) => !c.parent_id && !c.parent);
    if (result.length === 0 && categories.length > 0) {
      result = categories;
    }

    if (search.trim()) {
      const q = search.toLowerCase().trim();
      result = result.filter(
        (cat) =>
          cat.name.toLowerCase().includes(q) ||
          cat.slug?.toLowerCase().includes(q) ||
          cat.children?.some((sub) => sub.name.toLowerCase().includes(q))
      );
    }

    return [...result].sort((a, b) => {
      if (sortBy === 'products_count') {
        const countDiff = (b.products_count ?? 0) - (a.products_count ?? 0);
        if (countDiff !== 0) return countDiff;
        return a.name.localeCompare(b.name);
      }
      if (sortBy === 'name_asc') {
        return a.name.localeCompare(b.name);
      }
      if (sortBy === 'name_desc') {
        return b.name.localeCompare(a.name);
      }
      return 0;
    });
  }, [categories, search, sortBy]);

  // Max product count to identify top category
  const maxProductCount = useMemo(() => {
    return Math.max(0, ...categories.map((c) => c.products_count ?? 0));
  }, [categories]);

  const totalProducts = useMemo(() => {
    return categories.reduce((sum, c) => sum + (c.products_count ?? 0), 0);
  }, [categories]);

  return (
    <div className={styles.page}>
      {/* ── Hero banner ── */}
      <div className={styles.hero}>
        <div className={`container ${styles.heroContent}`}>
          <nav className={styles.breadcrumb} aria-label="Breadcrumb">
            <Link to="/" className={styles.breadcrumbLink}>Shop</Link>
            <span aria-hidden="true">›</span>
            <span>Categories</span>
          </nav>
          <h1 className={styles.heroTitle}>Browse Categories</h1>
          <p className={styles.heroSub}>
            {categories.length > 0
              ? `Explore our ${categories.length} curated categories with ${totalProducts} products ready to ship.`
              : 'Explore our curated collection across all product categories.'}
          </p>
        </div>
      </div>

      <div className="container">
        <div className={styles.content}>
          {/* ── Toolbar: Search & Sort ── */}
          {!loading && !error && categories.length > 0 && (
            <div className={styles.toolbar}>
              <div className={styles.searchWrap}>
                <span className={styles.searchIcon} aria-hidden="true">🔍</span>
                <input
                  type="text"
                  className={styles.searchInput}
                  placeholder="Search categories..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  aria-label="Search categories"
                />
                {search && (
                  <button
                    className={styles.searchClear}
                    onClick={() => setSearch('')}
                    aria-label="Clear search"
                  >
                    ✕
                  </button>
                )}
              </div>

              <div className={styles.toolbarRight}>
                <span className={styles.resultsCount}>
                  {filteredCategories.length} {filteredCategories.length === 1 ? 'category' : 'categories'}
                </span>
                <div className={styles.sortRow}>
                  <label htmlFor="sort-select" className={styles.sortLabel}>
                    Sort by:
                  </label>
                  <select
                    id="sort-select"
                    className={styles.sortSelect}
                    value={sortBy}
                    onChange={(e) => setSortBy(e.target.value)}
                  >
                    <option value="products_count">Most Products</option>
                    <option value="name_asc">Name (A–Z)</option>
                    <option value="name_desc">Name (Z–A)</option>
                  </select>
                </div>
              </div>
            </div>
          )}

          {/* ── Loading skeletons ── */}
          {loading && (
            <div className={styles.grid}>
              {Array.from({ length: 6 }).map((_, i) => (
                <SkeletonCard key={i} />
              ))}
            </div>
          )}

          {/* ── Error ── */}
          {error && !loading && (
            <div className={styles.errorWrap} role="alert">
              <span className={styles.errorIcon}>⚠️</span>
              <p>{error}</p>
              <button
                className="btn btn-primary"
                onClick={() => window.location.reload()}
              >
                Try again
              </button>
            </div>
          )}

          {/* ── Empty ── */}
          {!loading && !error && categories.length === 0 && (
            <div className={styles.emptyWrap}>
              <span>📂</span>
              <p>No categories found yet.</p>
              <Link to="/" className="btn btn-primary">Browse All Products</Link>
            </div>
          )}

          {/* ── Search Empty ── */}
          {!loading && !error && categories.length > 0 && filteredCategories.length === 0 && (
            <div className={styles.emptyWrap}>
              <span>🔍</span>
              <p>No categories matching &quot;{search}&quot;</p>
              <button className="btn btn-outline btn-sm" onClick={() => setSearch('')}>
                Clear Search
              </button>
            </div>
          )}

          {/* ── Category grid ── */}
          {!loading && filteredCategories.length > 0 && (
            <>
              <div className={styles.grid}>
                {filteredCategories.map((cat) => {
                  return (
                    <button
                      key={cat.id}
                      id={`category-${cat.slug}`}
                      className={styles.card}
                      onClick={() => navigate(`/?category=${encodeURIComponent(cat.name)}`)}
                      title={`Browse ${cat.name}`}
                    >
                      <div className={styles.cardTop}>
                        <span className={styles.cardIconWrap} aria-hidden="true">
                          {getCategoryEmoji(cat.name)}
                        </span>
                      </div>

                      <h2 className={styles.cardTitle}>{cat.name}</h2>


                      {/* Sub-categories */}
                      {cat.children?.length > 0 && (
                        <div className={styles.subCats}>
                          {cat.children.slice(0, 3).map((sub) => (
                            <span key={sub.id} className={styles.subCat}>{sub.name}</span>
                          ))}
                          {cat.children.length > 3 && (
                            <span className={styles.subCat}>+{cat.children.length - 3} more</span>
                          )}
                        </div>
                      )}

                      <span className={styles.cardArrow} aria-hidden="true">→</span>
                    </button>
                  );
                })}
              </div>

              {/* All products fallback */}
              <div className={styles.allProductsRow}>
                <Link to="/" className="btn btn-outline btn-lg">
                  View All Products
                </Link>
              </div>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
