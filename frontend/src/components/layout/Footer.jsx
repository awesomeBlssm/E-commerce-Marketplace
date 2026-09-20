import styles from './Footer.module.css';

export default function Footer() {
  const year = new Date().getFullYear();

  return (
    <footer className={styles.footer} role="contentinfo">
      <div className={`container ${styles.inner}`}>
        <div className={styles.brand}>
          <span className={styles.logoMark}>✿</span>
          <span className={styles.logoText}>Blossom Market</span>
          <p className={styles.tagline}>Discover amazing products from top sellers</p>
        </div>

        <nav className={styles.links} aria-label="Footer navigation">
          <div className={styles.linkGroup}>
            <h3 className={styles.groupTitle}>Shop</h3>
            <a href="/" className={styles.link}>New Arrivals</a>
            <a href="/categories" className={styles.link}>Categories</a>
            <a href="/brands" className={styles.link}>Brands</a>
          </div>
          <div className={styles.linkGroup}>
            <h3 className={styles.groupTitle}>Account</h3>
            <a href="/login" className={styles.link}>Sign In</a>
            <a href="/register" className={styles.link}>Create Account</a>
            <a href="/account" className={styles.link}>My Orders</a>
          </div>
          <div className={styles.linkGroup}>
            <h3 className={styles.groupTitle}>Support</h3>
            <a href="#" className={styles.link}>Help Center</a>
            <a href="#" className={styles.link}>Returns</a>
            <a href="#" className={styles.link}>Contact Us</a>
          </div>
        </nav>
      </div>

      <div className={styles.bottom}>
        <div className="container">
          <p className={styles.copy}>
            &copy; {year} Blossom Market. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
}
