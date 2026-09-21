import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../../lib/apiClient';

// -------------------------------------------------------------------
// Helper — get or create the user's active cart
// Reads variant currency from the variant being added if creating.
// -------------------------------------------------------------------
async function getOrCreateCart(currency = 'PHP') {
  // Try existing carts first
  const listData = await api.get('/carts');
  const existing = listData?.data?.find((c) => c.status === 'active');
  if (existing) return existing;

  // Create a new cart
  return api.post('/carts', { currency });
}

// -------------------------------------------------------------------
// Thunks
// -------------------------------------------------------------------

export const fetchUserCart = createAsyncThunk(
  'cart/fetchUserCart',
  async (_, { rejectWithValue }) => {
    try {
      const data = await api.get('/carts');
      const active = data?.data?.find((c) => c.status === 'active') ?? null;
      return active;
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

/**
 * Load cart items WITH variant info by fetching GET /carts/:id
 * which returns { items: [..., variant: {...} ] }
 */
export const fetchCartWithItems = createAsyncThunk(
  'cart/fetchCartWithItems',
  async (cartId, { rejectWithValue }) => {
    try {
      const data = await api.get(`/carts/${cartId}`);
      return data;
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

/**
 * Adds an item to the cart, creating the cart first if needed.
 * @param {{ variantId: string, quantity?: number, currency?: string }} payload
 */
export const addItemToCart = createAsyncThunk(
  'cart/addItemToCart',
  async ({ variantId, quantity = 1, currency = 'PHP' }, { getState, rejectWithValue }) => {
    try {
      // Use existing cart from state or get/create one
      let cart = getState().cart.cart;
      if (!cart) {
        cart = await getOrCreateCart(currency);
      }

      const item = await api.post(`/carts/${cart.id}/items`, {
        variant_id: variantId,
        quantity,
      });

      return { cart, item };
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

export const updateCartItem = createAsyncThunk(
  'cart/updateCartItem',
  async ({ cartId, itemId, quantity }, { rejectWithValue }) => {
    try {
      return await api.patch(`/carts/${cartId}/items/${itemId}`, { quantity });
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

export const removeCartItem = createAsyncThunk(
  'cart/removeCartItem',
  async ({ cartId, itemId }, { rejectWithValue }) => {
    try {
      await api.delete(`/carts/${cartId}/items/${itemId}`);
      return itemId;
    } catch (err) {
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

// -------------------------------------------------------------------
// Slice
// -------------------------------------------------------------------

const initialState = {
  /** @type {{ id: string, status: string, currency: string } | null} */
  cart: null,
  /** @type {Array<{ id: string, variant_id: string, quantity: number, variant: object }>} */
  items: [],
  loading: false,
  error: null,
};

const cartSlice = createSlice({
  name: 'cart',
  initialState,
  reducers: {
    clearCart(state) {
      state.cart = null;
      state.items = [];
    },
    clearCartError(state) {
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    const pending  = (state)          => { state.loading = true;  state.error = null; };
    const rejected = (state, action)  => { state.loading = false; state.error = action.payload?.message ?? 'Cart operation failed.'; };

    builder
      // Fetch cart (lightweight)
      .addCase(fetchUserCart.pending, pending)
      .addCase(fetchUserCart.fulfilled, (state, action) => {
        state.loading = false;
        state.cart = action.payload;
      })
      .addCase(fetchUserCart.rejected, rejected)

      // Fetch cart WITH items (full detail)
      .addCase(fetchCartWithItems.pending, pending)
      .addCase(fetchCartWithItems.fulfilled, (state, action) => {
        state.loading = false;
        const { items, ...cart } = action.payload;
        state.cart = cart;
        state.items = items ?? [];
      })
      .addCase(fetchCartWithItems.rejected, rejected)

      // Add item (get-or-create cart)
      .addCase(addItemToCart.pending, pending)
      .addCase(addItemToCart.fulfilled, (state, action) => {
        state.loading = false;
        const { cart, item } = action.payload;
        if (!state.cart) state.cart = cart;
        // If item already exists, update quantity; otherwise push
        const existing = state.items.findIndex((i) => i.id === item.id);
        if (existing !== -1) {
          state.items[existing] = item;
        } else {
          state.items.push(item);
        }
      })
      .addCase(addItemToCart.rejected, rejected)

      // Update item quantity
      .addCase(updateCartItem.pending, pending)
      .addCase(updateCartItem.fulfilled, (state, action) => {
        state.loading = false;
        const idx = state.items.findIndex((i) => i.id === action.payload.id);
        if (idx !== -1) state.items[idx] = action.payload;
      })
      .addCase(updateCartItem.rejected, rejected)

      // Remove item
      .addCase(removeCartItem.pending, pending)
      .addCase(removeCartItem.fulfilled, (state, action) => {
        state.loading = false;
        state.items = state.items.filter((i) => i.id !== action.payload);
      })
      .addCase(removeCartItem.rejected, rejected);
  },
});

export const { clearCart, clearCartError } = cartSlice.actions;

// -------------------------------------------------------------------
// Selectors
// -------------------------------------------------------------------
export const selectCart          = (state) => state.cart.cart;
export const selectCartItems     = (state) => state.cart.items;
export const selectCartLoading   = (state) => state.cart.loading;
export const selectCartError     = (state) => state.cart.error;
export const selectCartItemCount = (state) =>
  state.cart.items.reduce((sum, item) => sum + (item.quantity ?? 1), 0);

export default cartSlice.reducer;
