import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../../lib/apiClient';

// -------------------------------------------------------------------
// Thunks
// -------------------------------------------------------------------

/**
 * GET /api/orders — paginated order list for the authenticated user.
 */
export const fetchOrders = createAsyncThunk(
  'orders/fetchOrders',
  async (page = 1, { rejectWithValue }) => {
    try {
      return await api.get('/orders', { params: { page } });
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

/**
 * GET /api/orders/:id — single order with full lines.variant and addresses.
 */
export const fetchOrderDetail = createAsyncThunk(
  'orders/fetchOrderDetail',
  async (orderId, { rejectWithValue }) => {
    try {
      return await api.get(`/orders/${orderId}`);
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

/**
 * POST /api/orders — place a new order.
 * Payload: { lines: [{ variant_id, quantity }], addresses: [...] }
 * Prices, discounts, shipping, tax and total are server-authoritative.
 */
export const placeOrder = createAsyncThunk(
  'orders/placeOrder',
  async (payload, { rejectWithValue }) => {
    try {
      return await api.post('/orders', payload);
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0, errors: err.errors });
    }
  }
);

// -------------------------------------------------------------------
// Slice
// -------------------------------------------------------------------

const initialState = {
  items: [],
  pagination: null,
  selectedOrder: null,
  loading: false,
  detailLoading: false,
  error: null,
};

const ordersSlice = createSlice({
  name: 'orders',
  initialState,
  reducers: {
    clearOrdersError(state) {
      state.error = null;
    },
    clearSelectedOrder(state) {
      state.selectedOrder = null;
    },
  },
  extraReducers: (builder) => {
    builder
      // Fetch list
      .addCase(fetchOrders.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchOrders.fulfilled, (state, action) => {
        state.loading = false;
        const { data, meta } = action.payload;
        state.items = data ?? [];
        state.pagination = meta
          ? {
              currentPage: meta.current_page,
              lastPage:    meta.last_page,
              total:       meta.total,
              perPage:     meta.per_page,
            }
          : null;
      })
      .addCase(fetchOrders.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload?.message ?? 'Failed to load orders.';
      })

      // Fetch detail
      .addCase(fetchOrderDetail.pending, (state) => {
        state.detailLoading = true;
        state.error = null;
      })
      .addCase(fetchOrderDetail.fulfilled, (state, action) => {
        state.detailLoading = false;
        state.selectedOrder = action.payload;
      })
      .addCase(fetchOrderDetail.rejected, (state, action) => {
        state.detailLoading = false;
        state.error = action.payload?.message ?? 'Failed to load order.';
      })

      // Place order
      .addCase(placeOrder.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(placeOrder.fulfilled, (state, action) => {
        state.loading = false;
        state.items.unshift(action.payload);
        state.selectedOrder = action.payload;
      })
      .addCase(placeOrder.rejected, (state, action) => {
        state.loading = false;
        state.error = action.payload?.message ?? 'Failed to place order.';
      });
  },
});

export const { clearOrdersError, clearSelectedOrder } = ordersSlice.actions;

// -------------------------------------------------------------------
// Selectors
// -------------------------------------------------------------------
export const selectOrders        = (state) => state.orders.items;
export const selectOrdersPagination = (state) => state.orders.pagination;
export const selectOrdersLoading = (state) => state.orders.loading;
export const selectOrdersError   = (state) => state.orders.error;
export const selectSelectedOrder = (state) => state.orders.selectedOrder;
export const selectDetailLoading = (state) => state.orders.detailLoading;

export default ordersSlice.reducer;
