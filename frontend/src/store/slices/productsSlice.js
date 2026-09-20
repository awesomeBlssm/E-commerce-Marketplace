import { createAsyncThunk, createSlice } from '@reduxjs/toolkit';
import api from '../../lib/apiClient';

// -------------------------------------------------------------------
// Thunk
// -------------------------------------------------------------------

/**
 * Fetches a page of active, published products from the public catalog.
 * @param {{ page?: number, category?: string }} options
 */
export const fetchProducts = createAsyncThunk(
  'products/fetchProducts',
  async ({ page = 1, category = null } = {}, { rejectWithValue, signal }) => {
    try {
      const params = { page };
      if (category) params.category = category;

      const data = await api.get('/products', {
        params,
        signal,
      });
      return data;
    } catch (err) {
      if (err.name === 'AbortError' || signal.aborted) {
        return rejectWithValue({ aborted: true });
      }
      return rejectWithValue({ message: err.message, status: err.status ?? 0 });
    }
  }
);

// -------------------------------------------------------------------
// Slice
// -------------------------------------------------------------------

const initialState = {
  items: [],
  /** @type {{ currentPage: number, lastPage: number, total: number, perPage: number } | null} */
  pagination: null,
  loading: false,
  error: null,
};

const productsSlice = createSlice({
  name: 'products',
  initialState,
  reducers: {
    clearError(state) {
      state.error = null;
    },
  },
  extraReducers: (builder) => {
    builder
      .addCase(fetchProducts.pending, (state) => {
        state.loading = true;
        state.error = null;
      })
      .addCase(fetchProducts.fulfilled, (state, action) => {
        state.loading = false;
        // Normalize Laravel paginator shape (supports both Resource collections and raw Eloquent paginator)
        const { data, meta, ...rest } = action.payload ?? {};
        state.items = data ?? [];
        const pag = meta ?? rest;
        state.pagination = pag?.current_page
          ? {
              currentPage: pag.current_page,
              lastPage: pag.last_page,
              total: pag.total,
              perPage: pag.per_page,
            }
          : null;
      })
      .addCase(fetchProducts.rejected, (state, action) => {
        if (action.payload?.aborted || action.meta?.aborted) return; // stale or cancelled request, ignore
        state.loading = false;
        state.error = action.payload?.message ?? 'Failed to load products.';
      });
  },
});

export const { clearError: clearProductsError } = productsSlice.actions;

// -------------------------------------------------------------------
// Selectors
// -------------------------------------------------------------------

export const selectProducts = (state) => state.products.items;
export const selectProductsLoading = (state) => state.products.loading;
export const selectProductsError = (state) => state.products.error;
export const selectPagination = (state) => state.products.pagination;

export default productsSlice.reducer;
