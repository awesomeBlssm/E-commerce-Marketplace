import { useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useDispatch, useSelector } from 'react-redux';
import { fetchOrders, selectOrders, selectOrdersError, selectOrdersLoading } from '../store/slices/ordersSlice';
import styles from './OrdersPage.module.css';

function formatPrice(cents, currency = 'PHP') { return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format((cents ?? 0) / 100); }
function formatDate(value) { return value ? new Date(value).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'; }

export default function OrdersPage() {
  const dispatch = useDispatch();
  const orders = useSelector(selectOrders);
  const loading = useSelector(selectOrdersLoading);
  const error = useSelector(selectOrdersError);
  useEffect(() => { dispatch(fetchOrders()); }, [dispatch]);

  return <div className="container"><div className={styles.page}><div className={styles.heading}><div><p className={styles.eyebrow}>Your account</p><h1>Orders</h1></div><Link className="btn btn-ghost" to="/account">Account</Link></div>{loading && <div className={styles.state}><div className="spinner spinner-lg" /><p>Loading orders…</p></div>}{error && <div className={styles.error} role="alert">{error}<button className="btn btn-primary" onClick={() => dispatch(fetchOrders())}>Try again</button></div>}{!loading && !error && orders.length === 0 && <div className={styles.state}><h2>No orders yet</h2><p>Your placed orders will appear here.</p><Link className="btn btn-primary" to="/">Browse Products</Link></div>}{!loading && !error && orders.length > 0 && <div className={styles.list}>{orders.map((order) => <Link className={styles.order} to={`/orders/${order.id}`} key={order.id}><div><strong>{order.number}</strong><span>{formatDate(order.placed_at)}</span></div><div><span className={styles.status}>{order.status}</span><strong>{formatPrice(order.total_cents, order.currency)}</strong></div></Link>)}</div>}</div></div>;
}