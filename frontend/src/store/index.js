import { configureStore } from '@reduxjs/toolkit';
import cartReducer from './slices/cartSlice';
import ordersReducer from './slices/ordersSlice';
import productsReducer from './slices/productsSlice';

/**
 * Central Redux store.
 *
 * Auth state lives in AuthContext (not Redux) to avoid duplicating token
 * management logic. Theme state lives in ThemeContext.
 */
const store = configureStore({
  reducer: {
    products: productsReducer,
    cart: cartReducer,
    orders: ordersReducer,
  },
});

export default store;
