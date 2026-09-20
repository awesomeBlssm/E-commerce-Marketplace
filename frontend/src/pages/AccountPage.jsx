import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import {
  fetchOrders,
  selectOrders,
  selectOrdersLoading,
  selectOrdersError,
  selectOrdersPagination,
} from '../store/slices/ordersSlice';
import styles from './AccountPage.module.css';

function formatPrice(cents, currency = 'USD') {
  return new Intl.NumberFormat('en-US', { style: 'currency', currency }).format(cents / 100);
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
  });
}

const STATUS_COLORS = {
  pending:    { bg: 'rgba(255,165,0,0.1)',  color: 'hsl(38,92%,35%)' },
  processing: { bg: 'rgba(59,130,246,0.1)', color: 'hsl(210,80%,45%)' },
  shipped:    { bg: 'rgba(16,185,129,0.1)', color: 'hsl(152,68%,35%)' },
  delivered:  { bg: 'rgba(16,185,129,0.15)',color: 'hsl(152,68%,30%)' },
  cancelled:  { bg: 'rgba(220,53,69,0.1)',  color: 'hsl(0,70%,45%)' },
};

function StatusBadge({ status }) {
  const style = STATUS_COLORS[status] ?? { bg: 'var(--surface-2)', color: 'var(--text-muted)' };
  return (
    <span
      style={{
        padding: '2px 10px',
        borderRadius: '9999px',
        fontSize: 'var(--text-xs)',
        fontWeight: 600,
        background: style.bg,
        color: style.color,
        textTransform: 'capitalize',
      }}
    >
      {status}
    </span>
  );
}

export default function AccountPage() {
  const { user, logout } = useAuth();
  const dispatch = useDispatch();
  const toast = useToast();
  const orders = useSelector(selectOrders);
  const ordersLoading = useSelector(selectOrdersLoading);
  const ordersError = useSelector(selectOrdersError);
  const pagination = useSelector(selectOrdersPagination);
  const [orderPage, setOrderPage] = useState(1);

  useEffect(() => {
    dispatch(fetchOrders(orderPage));
  }, [dispatch, orderPage]);

  async function handleLogout() {
    await logout();
    toast.info('You have been signed out.');
  }

  if (!user) return null;

  const { email, type, status, customer } = user;

  return (
    <div className="container">
      <div className={styles.page}>
        {/* ── Profile header ── */}
        <div className={styles.header}>
          <div className={styles.avatar} aria-hidden="true">
            {(customer?.full_name?.[0] ?? email[0]).toUpperCase()}
          </div>
          <div>
            <h1 className={styles.name}>
              {customer?.full_name ?? email.split('@')[0]}
            </h1>
            <p className={styles.email}>{email}</p>
            <div className={styles.badges}>
              <span className="badge badge-brand">{type}</span>
              <span
                className="badge"
                style={{
                  background: status === 'active' ? 'rgba(40,167,69,0.1)' : 'rgba(220,53,69,0.1)',
                  color: status === 'active' ? 'var(--success)' : 'var(--danger)',
                }}
              >
                {status}
              </span>
            </div>
          </div>
        </div>

        <div className={styles.grid}>
          {/* ── Left column ── */}
          <div className={styles.leftCol}>
            {/* Account details */}
            <section className={styles.section} aria-labelledby="account-details">
              <h2 id="account-details" className={styles.sectionTitle}>Account Details</h2>
              <dl className={styles.dl}>
                <dt>Email</dt>       <dd>{email}</dd>
                <dt>Account type</dt><dd style={{ textTransform: 'capitalize' }}>{type}</dd>
                <dt>Status</dt>     <dd style={{ textTransform: 'capitalize' }}>{status}</dd>
              </dl>
            </section>

            {/* Customer profile */}
            {customer && (
              <section className={styles.section} aria-labelledby="customer-profile">
                <h2 id="customer-profile" className={styles.sectionTitle}>Customer Profile</h2>
                <dl className={styles.dl}>
                  {customer.full_name && (<><dt>Full name</dt><dd>{customer.full_name}</dd></>)}
                  {customer.phone    && (<><dt>Phone</dt>    <dd>{customer.phone}</dd></>)}
                  <dt>Marketing</dt>
                  <dd>{customer.accepts_marketing ? '✓ Subscribed' : '✗ Not subscribed'}</dd>
                </dl>
              </section>
            )}

            {/* Quick links */}
            <section className={styles.section}>
              <h2 className={styles.sectionTitle}>Quick Links</h2>
              <div className={styles.quickLinks}>
                <Link to="/cart" className="btn btn-outline">View Cart</Link>
                <Link to="/" className="btn btn-outline">Continue Shopping</Link>
              </div>
            </section>

            {/* Sign out */}
            <section className={styles.section}>
              <button
                id="account-logout-btn"
                className="btn btn-outline"
                style={{ borderColor: 'var(--danger)', color: 'var(--danger)' }}
                onClick={handleLogout}
              >
                Sign Out
              </button>
            </section>
          </div>

          {/* ── Right column — Order History ── */}
          <div className={styles.rightCol}>
            <section className={styles.section} aria-labelledby="order-history">
              <h2 id="order-history" className={styles.sectionTitle}>
                Order History
                {pagination && (
                  <span className={styles.orderCount}>{pagination.total} order{pagination.total !== 1 ? 's' : ''}</span>
                )}
              </h2>

              {ordersLoading && (
                <div className={styles.ordersLoading}>
                  <div className="spinner" />
                  <span>Loading orders…</span>
                </div>
              )}

              {ordersError && !ordersLoading && (
                <div className={styles.ordersError} role="alert">
                  <p>{ordersError}</p>
                  <button
                    className="btn btn-outline btn-sm"
                    onClick={() => dispatch(fetchOrders(orderPage))}
                  >
                    Retry
                  </button>
                </div>
              )}

              {!ordersLoading && !ordersError && orders.length === 0 && (
                <div className={styles.ordersEmpty}>
                  <span>🛍️</span>
                  <p>You haven&apos;t placed any orders yet.</p>
                  <Link to="/" className="btn btn-primary btn-sm">Start Shopping</Link>
                </div>
              )}

              {!ordersLoading && orders.length > 0 && (
                <div className={styles.orderList}>
                  {orders.map((order) => (
                    <div key={order.id} className={styles.orderCard}>
                      <div className={styles.orderCardHeader}>
                        <div>
                          <span className={styles.orderNumber}>{order.number}</span>
                          <span className={styles.orderDate}>{formatDate(order.placed_at)}</span>
                        </div>
                        <StatusBadge status={order.status} />
                      </div>
                      <div className={styles.orderCardBody}>
                        <div className={styles.orderMeta}>
                          <span>{order.lines_count ?? '—'} item{(order.lines_count ?? 0) !== 1 ? 's' : ''}</span>
                          <span>·</span>
                          <span className={styles.orderTotal}>
                            {formatPrice(order.total_cents, order.currency)}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}

              {/* Pagination */}
              {pagination && pagination.lastPage > 1 && (
                <nav className={styles.orderPagination} aria-label="Order pages">
                  <button
                    className="btn btn-outline btn-sm"
                    disabled={orderPage === 1}
                    onClick={() => setOrderPage((p) => p - 1)}
                  >← Prev</button>
                  <span className={styles.pageInfo}>
                    {pagination.currentPage} / {pagination.lastPage}
                  </span>
                  <button
                    className="btn btn-outline btn-sm"
                    disabled={orderPage === pagination.lastPage}
                    onClick={() => setOrderPage((p) => p + 1)}
                  >Next →</button>
                </nav>
              )}
            </section>
          </div>
        </div>
      </div>
    </div>
  );
}
