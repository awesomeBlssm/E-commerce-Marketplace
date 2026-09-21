import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { useDispatch, useSelector } from "react-redux";
import { useAuth } from "../contexts/AuthContext";
import { useToast } from "../contexts/ToastContext";
import api from "../lib/apiClient";
import {
  clearCart,
  fetchCartWithItems,
  fetchUserCart,
  selectCart,
  selectCartItems,
} from "../store/slices/cartSlice";
import { placeOrder } from "../store/slices/ordersSlice";
import styles from "./CheckoutPage.module.css";

const emptyAddress = {
  full_name: "",
  line1: "",
  line2: "",
  city: "",
  region: "",
  postal_code: "",
  country_code: "PH",
  phone: "",
};

const PSGC_API = "https://psgc.gitlab.io/api";

async function fetchPsgc(path) {
  const response = await fetch(`${PSGC_API}${path}`);
  if (!response.ok) throw new Error("Unable to load Philippine address options.");
  return response.json();
}

function sameLocationName(first, second) {
  return first?.trim().replace(/\s+/g, " ").toLowerCase() === second?.trim().replace(/\s+/g, " ").toLowerCase();
}

function isNcrRegion(address) {
  return address.region_code === "130000000" || sameLocationName(address.region, "National Capital Region");
}

function addressFormFromSaved(savedAddress, customer) {
  return {
    ...emptyAddress,
    ...(savedAddress
      ? {
          line1: savedAddress.line1 ?? "",
          line2: savedAddress.line2 ?? "",
          city: savedAddress.city ?? "",
          province: savedAddress.province ?? "",
          barangay: savedAddress.barangay ?? "",
          region: savedAddress.region ?? "",
          postal_code: savedAddress.postal_code ?? "",
          country_code: savedAddress.country_code ?? "PH",
        }
      : {}),
    full_name: customer?.full_name ?? "",
    phone: customer?.phone ?? "",
  };
}

function formatPrice(cents, currency = "PHP") {
  return new Intl.NumberFormat("en-PH", { style: "currency", currency }).format(
    (cents ?? 0) / 100,
  );
}

export default function CheckoutPage() {
  const { user } = useAuth();
  const customer = user?.customer;
  const dispatch = useDispatch();
  const navigate = useNavigate();
  const toast = useToast();
  const cart = useSelector(selectCart);
  const items = useSelector(selectCartItems);
  const [addresses, setAddresses] = useState([]);
  const [selectedAddressId, setSelectedAddressId] = useState("new");
  const [address, setAddress] = useState(emptyAddress);
  const [sameBilling, setSameBilling] = useState(true);
  const [billing, setBilling] = useState(emptyAddress);
  const [regions, setRegions] = useState([]);
  const [provinces, setProvinces] = useState([]);
  const [cities, setCities] = useState([]);
  const [barangays, setBarangays] = useState([]);
  const [locationLoading, setLocationLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    let active = true;
    async function load() {
      try {
        const activeCart = await dispatch(fetchUserCart()).unwrap();
        if (activeCart?.id)
          await dispatch(fetchCartWithItems(activeCart.id)).unwrap();
        if (customer?.id) {
          const data = await api.get(`/customers/${customer.id}`);
          const savedAddresses = data.addresses ?? [];
          if (active) {
            setAddresses(savedAddresses);
            const preferredAddress =
              savedAddresses.find(
                (savedAddress) => savedAddress.type === "shipping" && savedAddress.is_default,
              ) ??
              savedAddresses.find((savedAddress) => savedAddress.is_default) ??
              savedAddresses[0];

            if (preferredAddress) {
              setSelectedAddressId(preferredAddress.id);
              setAddress(addressFormFromSaved(preferredAddress, customer));
            }
          }
        }
      } catch (err) {
        if (active) toast.error(err.message ?? "Unable to load checkout.");
      } finally {
        if (active) setLoading(false);
      }
    }
    load();
    return () => {
      active = false;
    };
  }, [customer?.id, dispatch, toast]);

  useEffect(() => {
    fetchPsgc("/regions/").then(setRegions).catch((err) => toast.error(err.message));
  }, [toast]);

  useEffect(() => {
    if (!address.region || address.region_code || !regions.length) return;
    const region = regions.find((item) => sameLocationName(item.name, address.region));
    if (region) setAddress((current) => ({ ...current, region_code: region.code }));
  }, [regions, address.region, address.region_code]);

  useEffect(() => {
    if (!address.region_code) {
      setProvinces([]);
      setCities([]);
      return;
    }

    setLocationLoading(true);
    fetchPsgc(`/regions/${address.region_code}/provinces/`)
      .then((data) => {
        setProvinces(data);
        if (data.length === 0) {
          if (isNcrRegion(address)) {
            setAddress((current) => ({ ...current, province: "Metro Manila", province_code: "ncr" }));
          }
          return fetchPsgc(`/regions/${address.region_code}/cities-municipalities/`).then((citiesData) => {
            setCities(citiesData);
            const city = citiesData.find((item) => sameLocationName(item.name, address.city));
            if (city && !address.city_code) {
              setAddress((current) => ({ ...current, city_code: city.code }));
            }
          });
        }
        const province = data.find((item) => sameLocationName(item.name, address.province));
        if (province && !address.province_code) {
          setAddress((current) => ({ ...current, province_code: province.code }));
        }
        return null;
      })
      .catch(() => setProvinces([]))
      .finally(() => setLocationLoading(false));
  }, [address.region_code]);

  useEffect(() => {
    if (!address.province_code || isNcrRegion(address)) return;
    setLocationLoading(true);
    fetchPsgc(`/provinces/${address.province_code}/cities-municipalities/`)
      .then((data) => {
        setCities(data);
        const city = data.find((item) => sameLocationName(item.name, address.city));
        if (city && !address.city_code) {
          setAddress((current) => ({ ...current, city_code: city.code }));
        }
      })
      .catch(() => setCities([]))
      .finally(() => setLocationLoading(false));
  }, [address.province_code]);

  useEffect(() => {
    if (!address.city_code) {
      setBarangays([]);
      return;
    }
    setLocationLoading(true);
    fetchPsgc(`/cities-municipalities/${address.city_code}/barangays/`)
      .then((data) => {
        setBarangays(data);
        const barangay = data.find((item) => sameLocationName(item.name, address.barangay));
        if (barangay && !address.barangay_code) {
          setAddress((current) => ({ ...current, barangay_code: barangay.code }));
        }
      })
      .catch(() => setBarangays([]))
      .finally(() => setLocationLoading(false));
  }, [address.city_code]);

  function selectSavedAddress(id) {
    setSelectedAddressId(id);
    const saved = addresses.find((item) => item.id === id);
    setAddress(addressFormFromSaved(saved, customer));
  }

  function updateAddress(setter, event) {
    const { name, value } = event.target;
    if (setter === setAddress) setSelectedAddressId("new");
    setter((current) => ({ ...current, [name]: value }));
  }

  function handleBillingToggle(event) {
    const checked = event.target.checked;
    setSameBilling(checked);
    if (!checked) setBilling({ ...address });
  }

  function handleLocationChange(event) {
    const { name, value } = event.target;
    const selected = event.target.selectedOptions[0];
    const labels = {
      region_code: "region",
      province_code: "province",
      city_code: "city",
      barangay_code: "barangay",
    };
    const next = {
      [name]: value,
      [labels[name]]: selected?.dataset.name ?? "",
    };

    if (name === "region_code") {
      Object.assign(next, { province: "", province_code: "", city: "", city_code: "", barangay: "", barangay_code: "" });
    } else if (name === "province_code") {
      Object.assign(next, { city: "", city_code: "", barangay: "", barangay_code: "" });
    } else if (name === "city_code") {
      Object.assign(next, { barangay: "", barangay_code: "" });
    }

    setSelectedAddressId("new");
    setAddress((current) => ({ ...current, ...next }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    if (!items.length || !cart) return;
    setSubmitting(true);
    const shippingAddress = {
      type: "shipping",
      full_name: customer?.full_name ?? user?.email ?? "",
      ...address,
    };
    const billingAddress = sameBilling
      ? { ...address, type: "billing" }
      : {
          type: "billing",
          full_name: customer?.full_name ?? user?.email ?? "",
          ...billing,
        };
    try {
      const order = await dispatch(
        placeOrder({
          lines: items.map((item) => ({
            variant_id: item.variant_id,
            quantity: item.quantity,
          })),
          addresses: [shippingAddress, billingAddress],
        }),
      ).unwrap();
      await api
        .patch(`/carts/${cart.id}`, { status: "converted" })
        .catch(() => {});
      dispatch(clearCart());
      navigate(`/orders/${order.id}`, { replace: true });
    } catch (err) {
      toast.error(err.message ?? "Unable to place your order.");
    } finally {
      setSubmitting(false);
    }
  }

  if (loading)
    return (
      <div className={styles.state}>
        <div className="spinner spinner-lg" />
        <p>Loading checkout…</p>
      </div>
    );

  const estimatedSubtotal = items.reduce(
    (sum, item) => sum + (item.variant?.price_cents ?? 0) * item.quantity,
    0,
  );
  const currency = cart.currency ?? items[0]?.variant?.currency ?? "PHP";

  return (
    <div className="container">
      <div className={styles.page}>
        <div className={styles.heading}>
          <div>
            <p className={styles.eyebrow}>Checkout</p>
            <h1>Delivery details</h1>
          </div>
          <Link to="/cart" className="btn btn-ghost">
            Back to cart
          </Link>
        </div>
        <form className={styles.layout} onSubmit={handleSubmit}>
          <div className={styles.formColumn}>
            {addresses.length > 0 && (
              <fieldset className={styles.section}>
                <legend>Saved addresses</legend>
                <div className={styles.savedList}>
                  {addresses.map((saved) => (
                    <label key={saved.id} className={styles.saved}>
                      <input
                        type="radio"
                        name="saved-address"
                        checked={selectedAddressId === saved.id}
                        onChange={() => selectSavedAddress(saved.id)}
                      />
                      <span>
                        <strong>{saved.type}</strong>
                        <br />
                        {saved.line1}, {saved.barangay},{saved.city}, {saved.region}
                      </span>
                    </label>
                  ))}
                  <label className={styles.saved}>
                    <input
                      type="radio"
                      name="saved-address"
                      checked={selectedAddressId === "new"}
                      onChange={() => selectSavedAddress("new")}
                    />
                    <span>Use a different address</span>
                  </label>
                </div>
              </fieldset>
            )}
            <fieldset className={styles.section}>
              <legend>Contact information</legend>
              <ContactFields
                value={address}
                onChange={(event) => updateAddress(setAddress, event)}
              />
            </fieldset>
            <fieldset className={styles.section}>
              <legend>Shipping address</legend>
              <AddressFields
                value={address}
                onChange={(event) => updateAddress(setAddress, event)}
                location={{ regions, provinces, cities, barangays, loading: locationLoading }}
                onLocationChange={handleLocationChange}
                includeContact={false}
              />
            </fieldset>
          </div>
          <aside className={styles.summary}>
            <h2 className={styles.heading}>Order summary</h2>
            {items.map((item) => (
              <div className={styles.line} key={item.id}>
                <span>
                  {item.variant?.product?.title ?? item.variant?.sku ?? "Item"}{" "}
                  × {item.quantity}
                </span>
                <span>
                  {formatPrice(
                    (item.variant?.price_cents ?? 0) * item.quantity,
                    item.variant?.currency ?? currency,
                  )}
                </span>
              </div>
            ))}
            <div className={styles.total}>
              <span>Estimated subtotal</span>
              <strong>{formatPrice(estimatedSubtotal, currency)}</strong>
            </div>
            <button
              className="btn btn-primary"
              type="submit"
              disabled={submitting}
            >
              {submitting ? "Placing order…" : "Place order"}
            </button>
          </aside>
        </form>
      </div>
    </div>
  );
}

function ContactFields({ value, onChange }) {
  return (
    <div className={styles.fields}>
      {[
        ["full_name", "Full name"],
        ["phone", "Phone"],
      ].map(([name, label]) => (
        <label key={name} className={styles.field}>
          <span>{label} *</span>
          <input
            className="form-input"
            name={name}
            value={value[name] ?? ""}
            onChange={onChange}
            required
          />
        </label>
      ))}
    </div>
  );
}

function AddressFields({ value, onChange, location, onLocationChange, includeContact = true }) {
  const ncr = location && isNcrRegion(value);
  const locationFields = location
    ? [
        ["region_code", "Region", location.regions],
        ["province_code", "Province", ncr ? [{ code: "ncr", name: "Metro Manila" }] : location.provinces],
        ["city_code", "City / Municipality", location.cities],
        ["barangay_code", "Barangay", location.barangays],
      ]
    : [];

  return (
    <div className={styles.fields}>
      {locationFields.map(([name, label, options]) => (
        <label key={name} className={styles.field}>
          <span>{label} *</span>
          <select
            className="form-input"
            name={name}
            value={value[name] ?? ""}
            onChange={onLocationChange}
            disabled={location.loading || (name === "province_code" && !value.region_code) || (name === "city_code" && !value.province_code && !value.region_code) || (name === "barangay_code" && !value.city_code)}
            required
          >
            <option value="">Select {label.toLowerCase()}</option>
            {options.map((option) => (
              <option key={option.code} value={option.code} data-name={option.name}>
                {option.name}
              </option>
            ))}
          </select>
        </label>
      ))}
      {[
        ...(includeContact ? [["full_name", "Full name"]] : []),
        ["line1", "Address line 1"],
        ["line2", "Address line 2"],
        ["city", "City"],
        ["region", "Region / Province"],
        ["postal_code", "Postal code"],
        ["country_code", "Country code"],
        ...(includeContact ? [["phone", "Phone"]] : []),
      ]
        .filter(([name]) => !location || (name !== "city" && name !== "region"))
        .map(([name, label]) => (
        <label key={name} className={styles.field}>
          <span>
            {label}
            {name !== "line2" && " *"}
          </span>
          <input
            className="form-input"
            name={name}
            value={value[name] ?? ""}
            onChange={onChange}
            required={name !== "line2"}
            maxLength={name === "country_code" ? 2 : undefined}
          />
        </label>
      ))}
    </div>
  );
}
