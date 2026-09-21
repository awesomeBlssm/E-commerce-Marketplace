import { useEffect } from "react";
import { Link, useParams } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import {
  fetchOrderDetail,
  selectDetailLoading,
  selectSelectedOrder,
  selectOrdersError,
} from "../store/slices/ordersSlice";
import styles from "./OrdersPage.module.css";

function formatPrice(cents, currency = "PHP") {
  return new Intl.NumberFormat("en-PH", { style: "currency", currency }).format(
    (cents ?? 0) / 100,
  );
}
function addressLines(address) {
  return [
    [address.line1, address.line2].filter(Boolean).join(", "),
    [address.barangay, address.city].filter(Boolean).join(", "),
    [address.province, address.region].filter(Boolean).join(", "),
    address.postal_code,
  ]
    .filter(Boolean);
}

export default function OrderDetailPage() {
  const { id } = useParams();
  const dispatch = useDispatch();
  const order = useSelector(selectSelectedOrder);
  const loading = useSelector(selectDetailLoading);
  const error = useSelector(selectOrdersError);
  useEffect(() => {
    dispatch(fetchOrderDetail(id));
  }, [dispatch, id]);
  if (loading || !order)
    return (
      <div className={styles.state}>
        <div className="spinner spinner-lg" />
        <p>{error ?? "Loading order…"}</p>
        {error && (
          <Link className="btn btn-primary" to="/orders">
            Back to orders
          </Link>
        )}
      </div>
    );
  const shipping = order.addresses?.find(
    (address) => address.type === "shipping",
  );
  return (
    <div className="container">
      <div className={styles.page}>
        <div className={styles.heading}>
          <div>
            <p className={styles.eyebrow}>Order confirmation</p>
            <h1>{order.number}</h1>
            <p>
              {order.status} · {new Date(order.placed_at).toLocaleString()}
            </p>
          </div>
          <Link className="btn btn-ghost" to="/orders">
            All orders
          </Link>
        </div>
        <div className={styles.detailGrid}>
          <section className={styles.panel}>
            <h2>Items</h2>
            {order.lines?.map((line) => (
              <div className={styles.line} key={line.id}>
                <div>
                  <strong>{line.title}</strong>
                  <span>
                    {line.variant_title || line.sku} · Qty {line.quantity}
                  </span>
                </div>
                <strong>{formatPrice(line.total_cents, order.currency)}</strong>
              </div>
            ))}
          </section>
          <aside className={styles.panel}>
            <h2 style={{ marginBottom: 10 }}>Delivery</h2>
            {shipping ? (
              <div className={styles.addressBlock}>
                <div className={styles.addressContact}>
                  <strong>{shipping.full_name}</strong>
                  {shipping.phone && <span>{shipping.phone}</span>}
                </div>
                <address>
                  {addressLines(shipping).map((line) => (
                    <span key={line}>{line}</span>
                  ))}
                </address>
              </div>
            ) : (
              <p>No shipping address recorded.</p>
            )}
            <h2 className={styles.totalHeading}>Total</h2>
            <strong className={styles.detailTotal}>
              {formatPrice(order.total_cents, order.currency)}
            </strong>
            <p className={styles.note}>
              Shipping, tax, and discounts are shown exactly as returned by the
              server.
            </p>
          </aside>
        </div>
      </div>
    </div>
  );
}
