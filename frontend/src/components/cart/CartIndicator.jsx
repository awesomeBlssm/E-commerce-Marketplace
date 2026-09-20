import { Link } from 'react-router-dom';
import { useSelector } from 'react-redux';
import { useAuth } from '../../contexts/AuthContext';
import { selectCartItemCount } from '../../store/slices/cartSlice';
import styles from './CartIndicator.module.css';

const CartIcon = () => (
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
    <circle cx="9" cy="21" r="1" />
    <circle cx="20" cy="21" r="1" />
    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
  </svg>
);

export default function CartIndicator() {
  const { isAuthenticated } = useAuth();
  const count = useSelector(selectCartItemCount);

  return (
    <Link
      to={isAuthenticated ? '/cart' : '/login'}
      id="cart-indicator"
      className={styles.cartBtn}
      aria-label={`Cart${count > 0 ? `, ${count} items` : ''}`}
    >
      <CartIcon />
      {count > 0 && (
        <span className={styles.badge} aria-live="polite">
          {count > 99 ? '99+' : count}
        </span>
      )}
    </Link>
  );
}
