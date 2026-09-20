/**
 * CHECKOUT BOUNDARY CONTRACT
 * ===========================
 *
 * This file documents which values are SERVER-AUTHORITATIVE for checkout.
 * The frontend MUST NOT calculate, trust, or display these as final values.
 *
 * SERVER-AUTHORITATIVE (never calculate in frontend):
 * ---------------------------------------------------
 * - prices (variant.price_cents) — can change between add-to-cart and checkout
 * - discounts / promotions — applied by the server only
 * - shipping cost — calculated server-side based on address and weight
 * - tax amount — calculated server-side based on jurisdiction
 * - order total — always from the server response, never summed client-side
 * - inventory availability — checked server-side at order placement
 * - currency conversion — server sets the canonical currency per order
 *
 * WHAT THE FRONTEND CAN DISPLAY:
 * --------------------------------
 * - "From $X.XX" on listing and detail pages (for reference only)
 * - Cart items with per-unit variant price (informational, not final)
 * - Clearly labelled "estimated" subtotal (optional, never binding)
 *
 * MISSING BACKEND CONTRACTS BEFORE CHECKOUT UI:
 * -----------------------------------------------
 * 1. Order creation endpoint (POST /api/orders) — shape TBD
 * 2. Shipping rate endpoint — not yet exposed publicly
 * 3. Payment intent endpoint — not implemented
 * 4. Promo code application — backend exists (/api/promotions) but not wired
 *
 * Do NOT implement payment collection or checkout totals until the above
 * backend contracts are documented and tested (Step 13 of the roadmap).
 */
