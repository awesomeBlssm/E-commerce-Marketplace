import { useEffect, useState } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Link } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import {
  fetchCartWithItems,
  fetchUserCart,
  removeCartItem,
  selectCart,
  selectCartError,
  selectCartItems,
  selectCartLoading,
  updateCartItem,
} from '../store/slices/cartSlice';
import styles from './CartPage.module.css';

function formatPrice(cents, currency = 'PHP') {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(cents / 100);
}

export default function CartPage() {
  const [deselectedItemIds, setDeselectedItemIds] = useState(() => new Set());
  const dispatch = useDispatch();
  const toast = useToast();
  const { isAuthenticated } = useAuth();
  const cart = useSelector(selectCart);
  const items = useSelector(selectCartItems);
  const loading = useSelector(selectCartLoading);
  const error = useSelector(selectCartError);

  useEffect(() => {
    if (!isAuthenticated) return;

    // Fetch the active cart, then load its full detail (with items + variant)
    dispatch(fetchUserCart()).then((action) => {
      const activeCart = action.payload;
      if (activeCart?.id) {
        dispatch(fetchCartWithItems(activeCart.id));
      }
    });
  }, [dispatch, isAuthenticated]);

  if (!isAuthenticated) {
    return (
      <div className={styles.stateWrap}>
        <span className={styles.stateIcon}>🔒</span>
        <h1 className={styles.stateTitle}>Sign in to view your cart</h1>
        <p className={styles.stateText}>Your cart items will be saved when you sign in.</p>
        <div className={styles.stateActions}>
          <Link to="/login" className="btn btn-primary">Sign In</Link>
          <Link to="/register" className="btn btn-outline">Create Account</Link>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className={styles.stateWrap}>
        <div className="spinner spinner-lg" />
        <p className={styles.stateText}>Loading your cart…</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className={styles.stateWrap} role="alert">
        <span className={styles.stateIcon}>⚠️</span>
        <h1 className={styles.stateTitle}>Failed to load cart</h1>
        <p className={styles.stateText}>{error}</p>
        <button className="btn btn-primary" onClick={() => dispatch(fetchUserCart())}>
          Try again
        </button>
      </div>
    );
  }

  if (!cart || items.length === 0) {
    return (
      <div className={styles.stateWrap}>
        <span className={styles.stateIcon}>🛒</span>
        <h1 className={styles.stateTitle}>Your cart is empty</h1>
        <p className={styles.stateText}>Start shopping to add items to your cart.</p>
        <Link to="/" className="btn btn-primary">Browse Products</Link>
      </div>
    );
  }

  const selectedItems = items.filter((item) => !deselectedItemIds.has(item.id));
  const totalCents = selectedItems.reduce(
    (total, item) => total + (item.variant?.price_cents ?? 0) * item.quantity,
    0,
  );
  const totalCurrency = selectedItems.find((item) => item.variant?.currency)?.variant?.currency
    ?? cart.currency
    ?? 'PHP';

  async function handleQtyChange(item, qty) {
    if (qty < 1) return;
    try {
      await dispatch(updateCartItem({ cartId: cart.id, itemId: item.id, quantity: qty })).unwrap();
    } catch (err) {
      toast.error(err?.message ?? 'Failed to update quantity.');
    }
  }

  async function handleRemove(item) {
    try {
      await dispatch(removeCartItem({ cartId: cart.id, itemId: item.id })).unwrap();
      toast.info('Item removed from cart.');
    } catch (err) {
      toast.error(err?.message ?? 'Failed to remove item.');
    }
  }

  return (
    <div className="container">
      <div className={styles.page}>
        <h1 className={styles.pageTitle}>Shopping Cart</h1>
        <p className={styles.itemCount}>{items.length} item{items.length !== 1 ? 's' : ''}</p>

        <div className={styles.layout}>
          {/* Items list */}
          <div className={styles.itemsList}>
            {items.map((item) => {
              const variant = item.variant;
              const imageUrl = variant?.images?.[0]?.url ?? variant?.product?.images?.[0]?.url;
              return (
                <div key={item.id} className={styles.cartItem}>
                  <input
                    type="checkbox"
                    className={styles.itemCheckbox}
                    checked={!deselectedItemIds.has(item.id)}
                    onChange={() => {
                      setDeselectedItemIds((currentIds) => {
                        const nextIds = new Set(currentIds);
                        if (nextIds.has(item.id)) {
                          nextIds.delete(item.id);
                        } else {
                          nextIds.add(item.id);
                        }
                        return nextIds;
                      });
                    }}
                    aria-label={`Select ${variant?.sku ? `SKU ${variant.sku}` : 'cart item'}`}
                  />
                  <div className={styles.itemImage}>
                    {imageUrl ? (
                      <img src={imageUrl} alt={`Variant ${variant.sku ?? ''}`} />
                    ) : (
                      <div className={styles.imgPlaceholder}>📦</div>
                    )}
                  </div>
                  <div className={styles.itemInfo}>
                    <Link
                      to={`/products/${variant?.product_id ?? ''}`}
                      className={styles.itemTitle}
                    >
                      {variant?.sku ? `SKU: ${variant.sku}` : `Item (${item.id.slice(0, 8)})`}
                    </Link>
                    {variant?.price_cents != null && (
                      <span className={styles.itemUnitPrice}>
                        {formatPrice(variant.price_cents, variant.currency)} each
                      </span>
                    )}
                    {variant?.option_values?.map((optionValue) => (
                      <span key={optionValue.id} className={styles.itemOption}>
                        {optionValue.option?.name ?? 'Option'}: {optionValue.value}
                      </span>
                    ))}
                  </div>

                  <div className={styles.itemActions}>
                    <div className={styles.qtyControls}>
                      <button
                        id={`qty-dec-${item.id}`}
                        className={styles.qtyBtn}
                        onClick={() => handleQtyChange(item, item.quantity - 1)}
                        aria-label="Decrease quantity"
                      >−</button>
                      <span className={styles.qty}>{item.quantity}</span>
                      <button
                        id={`qty-inc-${item.id}`}
                        className={styles.qtyBtn}
                        onClick={() => handleQtyChange(item, item.quantity + 1)}
                        aria-label="Increase quantity"
                      >+</button>
                    </div>
                    <button
                      id={`remove-${item.id}`}
                      className={styles.removeBtn}
                      onClick={() => handleRemove(item)}
                      aria-label="Remove item"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              );
            })}
          </div>

          {/* Order summary — display only, totals are server-authoritative */}
          <div className={styles.summary}>
            <h2 className={styles.summaryTitle}>Order Summary</h2>

            <div className={styles.summaryRows}>
              <div className={styles.summaryRow}>
                <span>Items ({selectedItems.length})</span>
                <span>{formatPrice(totalCents, totalCurrency)}</span>
              </div>
              <div className={styles.summaryRow}>
                <span>Shipping</span>
                <span className={styles.summaryMuted}>Calculated at checkout</span>
              </div>
              <div className={styles.summaryRow}>
                <span>Tax</span>
                <span className={styles.summaryMuted}>Calculated at checkout</span>
              </div>
            </div>

            <div className={styles.totalRow}>
              <span>Total</span>
              <strong>{formatPrice(totalCents, totalCurrency)}</strong>
            </div>


            <Link
              id="proceed-to-checkout"
              to="/checkout"
              className="btn btn-primary"
              style={{ marginTop: 'var(--space-4)', width: '100%', justifyContent: 'center' }}
            >
              Proceed to Checkout
            </Link>
            <Link to="/" className="btn btn-ghost" style={{ width: '100%', justifyContent: 'center', marginTop: 'var(--space-2)' }}>
              Continue Shopping
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}
