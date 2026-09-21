import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import Cropper from 'react-easy-crop';
import { useDispatch, useSelector } from 'react-redux';
import { useAuth } from '../contexts/AuthContext';
import { useToast } from '../contexts/ToastContext';
import api from '../lib/apiClient';
import {
  fetchOrders,
  selectOrders,
  selectOrdersLoading,
  selectOrdersError,
  selectOrdersPagination,
} from '../store/slices/ordersSlice';
import styles from './AccountPage.module.css';

function formatPrice(cents, currency = 'PHP') {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(cents / 100);
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

const emptyAddress = {
  type: 'shipping',
  line1: '',
  line2: '',
  city: '',
  province: '',
  barangay: '',
  region: '',
  postal_code: '',
  country_code: 'PH',
  region_code: '',
  province_code: '',
  city_code: '',
  barangay_code: '',
  is_default: false,
};

const PSGC_API = 'https://psgc.gitlab.io/api';

async function fetchPsgc(path) {
  const response = await fetch(`${PSGC_API}${path}`);
  if (!response.ok) throw new Error('Unable to load Philippine address options.');
  return response.json();
}

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
  const { user, logout, refreshUser } = useAuth();
  const customer = user?.customer ?? null;
  const dispatch = useDispatch();
  const toast = useToast();
  const orders = useSelector(selectOrders);
  const ordersLoading = useSelector(selectOrdersLoading);
  const ordersError = useSelector(selectOrdersError);
  const pagination = useSelector(selectOrdersPagination);
  const [orderPage, setOrderPage] = useState(1);
  const [activeTab, setActiveTab] = useState('details');
  const [customerDetails, setCustomerDetails] = useState(customer ?? null);
  const [profileForm, setProfileForm] = useState({
    email: user?.email ?? customer?.email ?? '',
    full_name: customer?.full_name ?? '',
    phone: customer?.phone ?? '',
    accepts_marketing: customer?.accepts_marketing ?? false,
  });
  const [addresses, setAddresses] = useState([]);
  const [addressForm, setAddressForm] = useState(emptyAddress);
  const [editingAddressId, setEditingAddressId] = useState(null);
  const [showAddressForm, setShowAddressForm] = useState(false);
  const [profileSaving, setProfileSaving] = useState(false);
  const [addressSaving, setAddressSaving] = useState(false);
  const [addressesLoading, setAddressesLoading] = useState(false);
  const [regions, setRegions] = useState([]);
  const [provinces, setProvinces] = useState([]);
  const [cities, setCities] = useState([]);
  const [barangays, setBarangays] = useState([]);
  const [locationLoading, setLocationLoading] = useState(false);
  const [avatarUrl, setAvatarUrl] = useState(user?.avatar_url ?? null);
  const [avatarUploading, setAvatarUploading] = useState(false);
  const [avatarCropSource, setAvatarCropSource] = useState(null);
  const [avatarCrop, setAvatarCrop] = useState({ x: 0, y: 0 });
  const [avatarCropZoom, setAvatarCropZoom] = useState(1);
  const [avatarCroppedAreaPixels, setAvatarCroppedAreaPixels] = useState(null);
  const avatarInputRef = useRef(null);

  useEffect(() => {
    dispatch(fetchOrders(orderPage));
  }, [dispatch, orderPage]);

  useEffect(() => {
    fetchPsgc('/regions/')
      .then(setRegions)
      .catch((err) => toast.error(err.message));
  }, [toast]);

  useEffect(() => {
    if (!addressForm.region || addressForm.region_code || !regions.length) return;
    const region = regions.find((item) => item.name === addressForm.region);
    if (region) setAddressForm((current) => ({ ...current, region_code: region.code }));
  }, [regions, addressForm.region, addressForm.region_code]);

  useEffect(() => {
    if (!addressForm.region_code) {
      setProvinces([]);
      setCities([]);
      return;
    }

    setLocationLoading(true);
    fetchPsgc(`/regions/${addressForm.region_code}/provinces/`)
      .then((data) => {
        setProvinces(data);
        if (data.length === 0) {
          return fetchPsgc(`/regions/${addressForm.region_code}/cities-municipalities/`).then(setCities);
        }
        if (addressForm.province && !addressForm.province_code) {
          const province = data.find((item) => item.name === addressForm.province);
          if (province) setAddressForm((current) => ({ ...current, province_code: province.code }));
        }
      })
      .catch(() => {
        setProvinces([]);
        return fetchPsgc(`/regions/${addressForm.region_code}/cities-municipalities/`).then(setCities);
      })
      .finally(() => setLocationLoading(false));
  }, [addressForm.region_code, addressForm.province, addressForm.province_code]);

  useEffect(() => {
    if (!addressForm.province_code) return;

    setLocationLoading(true);
    fetchPsgc(`/provinces/${addressForm.province_code}/cities-municipalities/`)
      .then((data) => {
        setCities(data);
        if (addressForm.city && !addressForm.city_code) {
          const city = data.find((item) => item.name === addressForm.city);
          if (city) setAddressForm((current) => ({ ...current, city_code: city.code }));
        }
      })
      .catch(() => setCities([]))
      .finally(() => setLocationLoading(false));
  }, [addressForm.province_code, addressForm.city, addressForm.city_code]);

  useEffect(() => {
    if (!addressForm.city_code) {
      setBarangays([]);
      return;
    }

    setLocationLoading(true);
    fetchPsgc(`/cities-municipalities/${addressForm.city_code}/barangays/`)
      .then((data) => {
        setBarangays(data);
        if (addressForm.barangay && !addressForm.barangay_code) {
          const barangay = data.find((item) => item.name === addressForm.barangay);
          if (barangay) setAddressForm((current) => ({ ...current, barangay_code: barangay.code }));
        }
      })
      .catch(() => setBarangays([]))
      .finally(() => setLocationLoading(false));
  }, [addressForm.city_code, addressForm.barangay, addressForm.barangay_code]);

  useEffect(() => {
    if (!customer?.id) return;

    setCustomerDetails(customer);
    setProfileForm({
      email: user?.email ?? customer.email ?? '',
      full_name: customer.full_name ?? '',
      phone: customer.phone ?? '',
      accepts_marketing: customer.accepts_marketing ?? false,
    });
    setAddressesLoading(true);
    api
      .get(`/customers/${customer.id}`)
      .then((data) => {
        const savedAddresses = data.addresses ?? [];
        setAddresses(savedAddresses);
        setShowAddressForm(savedAddresses.length === 0);
      })
      .catch((err) => toast.error(err.message ?? 'Failed to load addresses.'))
      .finally(() => setAddressesLoading(false));
  }, [customer, user, toast]);

  async function handleLogout() {
    await logout();
    toast.info('You have been signed out.');
  }

  function handleAvatarChange(event) {
    const file = event.target.files?.[0];
    if (!file) return;

    if (avatarCropSource) URL.revokeObjectURL(avatarCropSource);
    setAvatarCropSource(URL.createObjectURL(file));
    setAvatarCrop({ x: 0, y: 0 });
    setAvatarCropZoom(1);
    setAvatarCroppedAreaPixels(null);
    event.target.value = '';
  }

  function closeAvatarCrop() {
    if (avatarCropSource) URL.revokeObjectURL(avatarCropSource);
    setAvatarCropSource(null);
  }

  async function uploadCroppedAvatar() {
    if (!avatarCropSource) return;

    setAvatarUploading(true);
    try {
      const image = new Image();
      image.src = avatarCropSource;
      await new Promise((resolve, reject) => {
        image.onload = resolve;
        image.onerror = reject;
      });

      if (!avatarCroppedAreaPixels) throw new Error('Please adjust the crop before uploading.');
      const canvas = document.createElement('canvas');
      canvas.width = 512;
      canvas.height = 512;
      const context = canvas.getContext('2d');
      context.drawImage(
        image,
        avatarCroppedAreaPixels.x,
        avatarCroppedAreaPixels.y,
        avatarCroppedAreaPixels.width,
        avatarCroppedAreaPixels.height,
        0,
        0,
        512,
        512,
      );

      const croppedFile = await new Promise((resolve) => {
        canvas.toBlob((blob) => resolve(new File([blob], 'avatar.jpg', { type: 'image/jpeg' })), 'image/jpeg', 0.9);
      });
      const formData = new FormData();
      formData.append('avatar', croppedFile);
      const response = await api.post('/auth/avatar', formData);
      setAvatarUrl(response.avatar_url ?? null);
      await refreshUser();
      closeAvatarCrop();
      toast.success('Profile photo updated.');
    } catch (err) {
      toast.error(err.message ?? 'Failed to upload profile photo.');
    } finally {
      setAvatarUploading(false);
    }
  }

  async function handleProfileSubmit(event) {
    event.preventDefault();
    setProfileSaving(true);
    try {
      const updated = await api.patch(`/customers/${customer.id}`, profileForm);
      setCustomerDetails(updated);
      await refreshUser();
      toast.success('Customer details updated.');
    } catch (err) {
      toast.error(err.message ?? 'Failed to update customer details.');
    } finally {
      setProfileSaving(false);
    }
  }

  function handleAddressChange(event) {
    const { name, value, type, checked } = event.target;
    setAddressForm((current) => ({ ...current, [name]: type === 'checkbox' ? checked : value }));
  }

  function handleLocationChange(event) {
    const { name, value } = event.target;
    const selected = event.target.selectedOptions[0];
    const labels = {
      region_code: 'region',
      province_code: 'province',
      city_code: 'city',
      barangay_code: 'barangay',
    };
    const next = {
      [name]: value,
      [labels[name]]: selected?.dataset.name ?? '',
    };

    if (name === 'region_code') {
      next.province = '';
      next.province_code = '';
      next.city = '';
      next.city_code = '';
      next.barangay = '';
      next.barangay_code = '';
    }
    if (name === 'province_code') {
      next.city = '';
      next.city_code = '';
      next.barangay = '';
      next.barangay_code = '';
    }
    if (name === 'city_code') {
      next.barangay = '';
      next.barangay_code = '';
    }

    setAddressForm((current) => ({ ...current, ...next }));
  }

  function startAddressEdit(address) {
    setEditingAddressId(address.id);
    setAddressForm({ ...emptyAddress, ...address });
    setShowAddressForm(true);
  }

  function resetAddressForm() {
    setEditingAddressId(null);
    setAddressForm(emptyAddress);
    setShowAddressForm(false);
  }

  async function handleAddressSubmit(event) {
    event.preventDefault();
    setAddressSaving(true);
    try {
      const path = editingAddressId
        ? `/customers/${customer.id}/addresses/${editingAddressId}`
        : `/customers/${customer.id}/addresses`;
      const addressPayload = { ...addressForm };
      delete addressPayload.region_code;
      delete addressPayload.province_code;
      delete addressPayload.city_code;
      delete addressPayload.barangay_code;
      const saved = editingAddressId
        ? await api.patch(path, addressPayload)
        : await api.post(path, addressPayload);

      setAddresses((current) => editingAddressId
        ? current.map((address) => address.id === saved.id ? saved : address)
        : [...current, saved]);
      resetAddressForm();
      toast.success(editingAddressId ? 'Address updated.' : 'Address added.');
    } catch (err) {
      toast.error(err.message ?? 'Failed to save address.');
    } finally {
      setAddressSaving(false);
    }
  }

  async function handleAddressDelete(addressId) {
    if (!window.confirm('Remove this address?')) return;
    try {
      await api.delete(`/customers/${customer.id}/addresses/${addressId}`);
      setAddresses((current) => {
        const remaining = current.filter((address) => address.id !== addressId);
        if (remaining.length === 0) setShowAddressForm(true);
        return remaining;
      });
      if (editingAddressId === addressId) resetAddressForm();
      toast.info('Address removed.');
    } catch (err) {
      toast.error(err.message ?? 'Failed to remove address.');
    }
  }

  if (!user) return null;

  const { email, type, status } = user;
  const displayedCustomer = customerDetails ?? customer;

  return (
    <div className="container">
      <div className={styles.page}>
        {/* ── Profile header ── */}
        <div className={styles.header}>
          <button
            type="button"
            className={styles.avatarButton}
            onClick={() => avatarInputRef.current?.click()}
            disabled={avatarUploading}
            aria-label="Upload profile photo"
          >
            <div className={styles.avatar}>
              {avatarUrl ? (
                <img src={avatarUrl} alt="Profile" />
              ) : (
                (displayedCustomer?.full_name?.[0] ?? email[0]).toUpperCase()
              )}
              {avatarUploading && <span className={styles.avatarOverlay}>Uploading…</span>}
            </div>
          </button>
          <input
            ref={avatarInputRef}
            className={styles.avatarInput}
            type="file"
            accept="image/jpeg,image/png,image/webp"
            onChange={handleAvatarChange}
            aria-label="Choose profile photo"
          />
          {avatarCropSource && (
            <div className={styles.cropBackdrop} role="dialog" aria-modal="true" aria-labelledby="crop-avatar-title">
              <div className={styles.cropDialog}>
                <h2 id="crop-avatar-title">Crop profile photo</h2>
                <div className={styles.cropPreview}>
                  <Cropper
                    image={avatarCropSource}
                    crop={avatarCrop}
                    zoom={avatarCropZoom}
                    aspect={1}
                    cropShape="rect"
                    showGrid
                    onCropChange={setAvatarCrop}
                    onZoomChange={setAvatarCropZoom}
                    onCropComplete={(_, croppedAreaPixels) => setAvatarCroppedAreaPixels(croppedAreaPixels)}
                  />
                </div>
                <label className={styles.cropControl}>
                  <span>Zoom</span>
                  <input type="range" min="1" max="3" step="0.05" value={avatarCropZoom} onChange={(event) => setAvatarCropZoom(Number(event.target.value))} />
                </label>
                <div className={styles.cropActions}>
                  <button type="button" className="btn btn-ghost" onClick={closeAvatarCrop} disabled={avatarUploading}>Cancel</button>
                  <button type="button" className="btn btn-primary" onClick={uploadCroppedAvatar} disabled={avatarUploading}>
                    {avatarUploading ? 'Uploading…' : 'Use photo'}
                  </button>
                </div>
              </div>
            </div>
          )}
          <div>
            <h1 className={styles.name}>
              {displayedCustomer?.full_name ?? email.split('@')[0]}
            </h1>
            <p className={styles.email}>{email}</p>
          </div>
        </div>

        <div className={styles.tabs} role="tablist" aria-label="Account sections">
          <button
            type="button"
            role="tab"
            aria-selected={activeTab === 'details'}
            className={`${styles.tab} ${activeTab === 'details' ? styles.tabActive : ''}`}
            onClick={() => setActiveTab('details')}
          >
            Customer Details
          </button>
          <button
            type="button"
            role="tab"
            aria-selected={activeTab === 'orders'}
            className={`${styles.tab} ${activeTab === 'orders' ? styles.tabActive : ''}`}
            onClick={() => setActiveTab('orders')}
          >
            Order History
            {pagination && <span className={styles.tabCount}>{pagination.total}</span>}
          </button>
        </div>

        <div className={styles.tabPanel}>
          {/* ── Left column ── */}
          {activeTab === 'details' && (
          <div className={styles.detailsPanel} role="tabpanel" aria-label="Customer details">
            <div className={styles.detailsSidebar}>
            {/* Account details */}
            <section className={styles.section} aria-labelledby="account-details">
              <h2 id="account-details" className={styles.sectionTitle}>Account Details</h2>
              <dl className={styles.dl}>
                <dt>Email</dt>       <dd className={styles.emailValue} title={email}>{email}</dd>
                <dt>Account type</dt><dd style={{ textTransform: 'capitalize' }}>{type}</dd>
                <dt>Status</dt>     <dd style={{ textTransform: 'capitalize' }}>{status}</dd>
              </dl>
            </section>

            {/* Customer profile */}
            {customer && (
              <section className={styles.section} aria-labelledby="customer-profile">
                <h2 id="customer-profile" className={styles.sectionTitle}>Customer Profile</h2>
                <form className={styles.form} onSubmit={handleProfileSubmit}>
                  <label className={styles.field}>
                    <span>Email address</span>
                    <input
                      className="form-input"
                      type="email"
                      required
                      value={profileForm.email}
                      onChange={(event) => setProfileForm({ ...profileForm, email: event.target.value })}
                      maxLength={255}
                    />
                  </label>
                  <label className={styles.field}>
                    <span>Full name</span>
                    <input
                      className="form-input"
                      value={profileForm.full_name}
                      onChange={(event) => setProfileForm({ ...profileForm, full_name: event.target.value })}
                      maxLength={120}
                    />
                  </label>
                  <label className={styles.field}>
                    <span>Phone</span>
                    <input
                      className="form-input"
                      value={profileForm.phone}
                      onChange={(event) => setProfileForm({ ...profileForm, phone: event.target.value })}
                      maxLength={32}
                    />
                  </label>
                  <label className={styles.checkboxField}>
                    <input
                      type="checkbox"
                      checked={profileForm.accepts_marketing}
                      onChange={(event) => setProfileForm({ ...profileForm, accepts_marketing: event.target.checked })}
                    />
                    <span>Receive marketing updates</span>
                  </label>
                  <button className="btn btn-primary btn-sm" type="submit" disabled={profileSaving}>
                    {profileSaving ? 'Saving…' : 'Save Details'}
                  </button>
                </form>
              </section>
            )}

            </div>

            <div className={styles.detailsMain}>
            {customer && (
              <section className={styles.section} aria-labelledby="customer-addresses">
                <h2 id="customer-addresses" className={styles.sectionTitle}>Addresses</h2>
                {addressesLoading ? (
                  <div className={styles.ordersLoading}><div className="spinner" /><span>Loading addresses…</span></div>
                ) : addresses.length === 0 ? (
                  <p className={styles.mutedText}>No addresses saved yet.</p>
                ) : (
                  <div className={styles.addressList}>
                    {addresses.map((address) => (
                      <div key={address.id} className={styles.addressCard}>
                        <div>
                          <div className={styles.addressHeading}>
                            <strong>{address.type}</strong>
                            {address.is_default && <span className="badge badge-brand">Default</span>}
                          </div>
                          <p>{address.line1}{address.line2 ? `, ${address.line2}` : ''}</p>
                          <p>{[address.barangay, address.city, address.province, address.region, address.postal_code].filter(Boolean).join(', ')}</p>
                          <p>{address.country_code}</p>
                        </div>
                        <div className={styles.addressActions}>
                          <button className="btn btn-outline btn-sm" type="button" onClick={() => startAddressEdit(address)}>Edit</button>
                          <button className="btn btn-outline btn-sm" type="button" onClick={() => handleAddressDelete(address.id)}>Remove</button>
                        </div>
                      </div>
                    ))}
                  </div>
                )}

                {!showAddressForm && (
                  <button
                    type="button"
                    className="btn btn-primary btn-sm"
                    onClick={() => setShowAddressForm(true)}
                  >
                    Add another address
                  </button>
                )}

                {showAddressForm && <form className={styles.addressForm} onSubmit={handleAddressSubmit}>
                  <h3>{editingAddressId ? 'Update Address' : 'Add Address'}</h3>
                  <div className={styles.formGrid}>
                    <label className={styles.field}>
                      <span>Type</span>
                      <select className="form-input" name="type" value={addressForm.type} onChange={handleAddressChange}>
                        <option value="shipping">Shipping</option>
                        <option value="billing">Billing</option>
                      </select>
                    </label>
                    <label className={styles.field}>
                      <span>Region</span>
                      <select className="form-input" name="region_code" value={addressForm.region_code} onChange={handleLocationChange} required>
                        <option value="">Select region</option>
                        {regions.map((region) => <option key={region.code} value={region.code} data-name={region.name}>{region.name}</option>)}
                      </select>
                    </label>
                    <label className={styles.field}>
                      <span>Province</span>
                      <select className="form-input" name="province_code" value={addressForm.province_code} onChange={handleLocationChange} disabled={!provinces.length}>
                        <option value="">{provinces.length ? 'Select province' : 'Not applicable'}</option>
                        {provinces.map((province) => <option key={province.code} value={province.code} data-name={province.name}>{province.name}</option>)}
                      </select>
                    </label>
                    <label className={styles.field}>
                      <span>City / Municipality</span>
                      <select className="form-input" name="city_code" value={addressForm.city_code} onChange={handleLocationChange} required disabled={!cities.length}>
                        <option value="">{locationLoading ? 'Loading...' : 'Select city / municipality'}</option>
                        {cities.map((city) => <option key={city.code} value={city.code} data-name={city.name}>{city.name}</option>)}
                      </select>
                    </label>
                    <label className={styles.field}>
                      <span>Barangay</span>
                      <select className="form-input" name="barangay_code" value={addressForm.barangay_code} onChange={handleLocationChange} required disabled={!barangays.length}>
                        <option value="">{barangays.length ? 'Select barangay' : 'Not available'}</option>
                        {barangays.map((barangay) => <option key={barangay.code} value={barangay.code} data-name={barangay.name}>{barangay.name}</option>)}
                      </select>
                    </label>
                    <label className={styles.field}>
                      <span>Country code</span>
                      <input className="form-input" name="country_code" value={addressForm.country_code} onChange={handleAddressChange} maxLength={2} required />
                    </label>
                    <label className={styles.fieldWide}>
                      <span>Address line 1</span>
                      <input className="form-input" name="line1" value={addressForm.line1} onChange={handleAddressChange} maxLength={255} required />
                    </label>
                    <label className={styles.fieldWide}>
                      <span>Address line 2</span>
                      <input className="form-input" name="line2" value={addressForm.line2} onChange={handleAddressChange} maxLength={255} />
                    </label>
                    <label className={styles.field}>
                      <span>Postal code</span>
                      <input className="form-input" name="postal_code" value={addressForm.postal_code} onChange={handleAddressChange} maxLength={20} required />
                    </label>
                  </div>
                  <label className={styles.checkboxField}>
                    <input type="checkbox" name="is_default" checked={addressForm.is_default} onChange={handleAddressChange} />
                    <span>Set as default address</span>
                  </label>
                  <div className={styles.formActions}>
                    <button className="btn btn-primary btn-sm" type="submit" disabled={addressSaving}>
                      {addressSaving ? 'Saving…' : editingAddressId ? 'Update Address' : 'Add Address'}
                    </button>
                    {editingAddressId && <button className="btn btn-ghost btn-sm" type="button" onClick={resetAddressForm}>Cancel</button>}
                  </div>
                </form>}
              </section>
            )}

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
          </div>
          )}

          {/* ── Right column — Order History ── */}
          {activeTab === 'orders' && (
          <div className={styles.ordersPanel} role="tabpanel" aria-label="Order history">
            <section className={styles.section} aria-labelledby="order-history">
              <div className={styles.ordersHeader}>
                <h2 id="order-history" className={[styles.sectionTitle, styles.removeMargin]}>
                  Order History
                  {pagination && (
                    <span className={styles.orderCount}>{pagination.total} order{pagination.total !== 1 ? 's' : ''}</span>
                  )}
                </h2>
                
                <Link className="btn btn-ghost" to="/orders">
                  All orders
                </Link>
              </div>

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
          )}
        </div>
      </div>
    </div>
  );
}
