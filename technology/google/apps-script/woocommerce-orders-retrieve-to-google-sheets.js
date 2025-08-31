// WooCommerce - WooCommerce Orders Retrieve to Google Sheets
// Last update: 2025-08-31


// https://script.google.com > New project >
// - Editor > Services > Add a service > Gmail API
// - Triggers > Add Trigger >
// -- Choose which function to run: WooCommerceOrdersRetrieve
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Minutes timer
// -- Select minute interval: Every 30 minutes


// Google Sheets Query to aggregate orders from line/item level to order level
// =QUERY('Orders Line Level'!A:AY, "SELECT A, MAX(B), MAX(C), MAX(D), MAX(E), MAX(F), MAX(G), MAX(H), MAX(I), MAX(J), MAX(K), MAX(L), MAX(M), MAX(N), MAX(O), MAX(P), MAX(Q), MAX(R), MAX(S), MAX(T), MAX(U), MAX(V), MAX(W), MAX(X), MAX(Y), MAX(Z), MAX(AA), MAX(AB), MAX(AC), MAX(AD), MAX(AE), MAX(AF), MAX(AG), MAX(AH), MAX(AI), MAX(AJ), MAX(AK), MAX(AL), MAX(AM), MAX(AN), MAX(AO), MAX(AP), MAX(AQ), MAX(AR), MAX(AS), MAX(AT), MAX(AU), MAX(AV) WHERE A IS NOT NULL GROUP BY A LABEL A 'order_id', MAX(B) 'created_via', MAX(C) 'status', MAX(D) 'currency', MAX(E) 'prices_include_tax', MAX(F) 'customer_id', MAX(G) 'customer_user_agent', MAX(H) 'customer_note', MAX(I) 'payment_method', MAX(J) 'payment_method_title', MAX(K) 'language_code', MAX(L) 'billing_first_name', MAX(M) 'billing_last_name', MAX(N) 'billing_company', MAX(O) 'billing_address_1', MAX(P) 'billing_address_2', MAX(Q) 'billing_city', MAX(R) 'billing_state', MAX(S) 'billing_postcode', MAX(T) 'billing_country', MAX(U) 'billing_email', MAX(V) 'billing_phone', MAX(W) 'shipping_first_name', MAX(X) 'shipping_last_name', MAX(Y) 'shipping_company', MAX(Z) 'shipping_address_1', MAX(AA) 'shipping_address_2', MAX(AB) 'shipping_city', MAX(AC) 'shipping_state', MAX(AD) 'shipping_postcode', MAX(AE) 'shipping_country', MAX(AF) 'shipping_methods', MAX(AG) 'date_created', MAX(AH) 'date_created_gmt', MAX(AI) 'date_modified', MAX(AJ) 'date_modified_gmt', MAX(AK) 'date_paid', MAX(AL) 'date_paid_gmt', MAX(AM) 'date_completed', MAX(AN) 'date_completed_gmt', MAX(AO) 'discount_tax', MAX(AP) 'discount_total', MAX(AQ) 'shipping_tax', MAX(AR) 'shipping_total', MAX(AS) 'cart_tax', MAX(AT) 'cart', MAX(AU) 'transaction_fee', MAX(AV) 'total_tax' ", 1)


function WooCommerceOrdersRetrieve() {
  const options = {
    site_url: "https://website.com",
    consumer_key: "ck_xxxxxxxx",
    consumer_secret: "cs_xxxxxxxx",
    output_sheet_name: "Orders Line Level",
    test_mode: false,
    last_fetch_date_reset: false,
  };

  // Payment method mapping
  const paymentMethodMap = {
    "direct-debit": "Direct Bank Transfer",
    "bacs": "Direct Bank Transfer",
    "paypal": "PayPal",
    "ppcp-gateway": "PayPal",
    "stripe_cc": "Stripe",
  };

  if (options.last_fetch_date_reset) PropertiesService.getScriptProperties().deleteProperty("SETTINGS_LAST_SYNC");

  // Get last fetch date to fetch only new orders
  let lastFetchDate = PropertiesService.getScriptProperties().getProperty("SETTINGS_LAST_SYNC");
  if (lastFetchDate) lastFetchDate = lastFetchDate.slice(0, lastFetchDate.lastIndexOf('.')) + 'Z';
  Logger.log("The last fetch date is: " + lastFetchDate);

  // Reusable API fetch function
  const fetchWooCommerceAPI = (endpoint, params = {}) => {
    let url = options.site_url + "/wp-json/wc/v3/" + endpoint;
    const queryString = Object.keys(params)
      .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
      .join('&');

    if (queryString) {
      url += '?' + queryString;
    }

    const response = UrlFetchApp.fetch(url, {
      "method": "get",
      "headers": {
        "Authorization": "Basic " + Utilities.base64Encode(options.consumer_key + ":" + options.consumer_secret),
        "X-App-Name": "woocommerce-orders-retrieve-to-google-sheets",
      },
      "muteHttpExceptions": true
    });

    if (response.getResponseCode() !== 200) {
      Logger.log(`Error fetching ${endpoint}: ${response.getContentText()}`);
      return null;
    }
    return JSON.parse(response.getContentText());
  };

  // WooCommerce tax rates
  const taxRatesData = fetchWooCommerceAPI("taxes", { per_page: 100 });
  const woocommerceTaxRates = {};
  if (taxRatesData) {
    taxRatesData.forEach(rate => {
      woocommerceTaxRates[rate.class] = parseFloat(rate.rate);
    });
  }

  // WooCommerce shipping methods
  const shippingMethodsData = fetchWooCommerceAPI("shipping_methods", { per_page: 100 });
  const woocommerceShippingMethods = {};
  if (shippingMethodsData) {
    shippingMethodsData.forEach(sm => {
      woocommerceShippingMethods[sm.id] = sm.title;
    });
  }

  let orders = [];
  let page = 1;
  const perPage = 100;

  // Fetch paginated orders
  while (true) {
    const params = { per_page: perPage, page: page, orderby: "date", order: "asc", orderby: "modified" };
    if (lastFetchDate) {
      params.modified_after = lastFetchDate;
    }

    const data = fetchWooCommerceAPI("orders", params);
    if (!data || data.length === 0) break; // No more orders or API error

    orders = orders.concat(data);
    page++;

    if (options.test_mode) break; // Stop after first page if test mode

  }

  if (orders.length === 0) {
    Logger.log("No new orders found.");
    return;
  }

  // Write to Sheet
  let sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(options.output_sheet_name);
  if (!sheet) sheet = SpreadsheetApp.getActiveSpreadsheet().insertSheet(options.output_sheet_name);

  // If sheet is empty, add headers
  if (sheet.getLastRow() === 0) {
    const headers = [
      // Order core fields
      "order_id",
      // "parent_id",
      // "number",
      // "order_key",
      "created_via",
      // "version",
      "status",
      "currency",
      "prices_include_tax",
      "customer_id",
      // "customer_ip_address",
      "customer_user_agent",
      "customer_note",
      "payment_method",
      "payment_method_title",
      // "transaction_id",
      // "cart_hash",
      // "meta_data",
      // "tax_lines",
      // "shipping_lines",
      // "fee_lines",
      // "coupon_lines",
      // "refunds",
      "language_code",
      "billing_first_name",
      "billing_last_name",
      "billing_company",
      "billing_address_1",
      "billing_address_2",
      "billing_city",
      "billing_state",
      "billing_postcode",
      "billing_country",
      "billing_email",
      "billing_phone",
      "shipping_first_name",
      "shipping_last_name",
      "shipping_company",
      "shipping_address_1",
      "shipping_address_2",
      "shipping_city",
      "shipping_state",
      "shipping_postcode",
      "shipping_country",
      "shipping_methods",
      "date_created",
      "date_created_gmt",
      "date_modified",
      "date_modified_gmt",
      "date_paid",
      "date_paid_gmt",
      "date_completed",
      "date_completed_gmt",
      "discount_tax",
      "discount_total",
      "shipping_tax",
      "shipping_total",
      "cart_tax",
      "cart",
      "transaction_fee",
      "total_tax",
      "total",
      "refund_tax",
      "refund",
      "item_id",
      "item_name",
      // "product_id",
      "sku",
      "variation_id",
      "variation_name",
      "price",
      "quantity",
      "tax_class",
      "subtotal_tax",
      "subtotal",
      "line_total_tax",
      "line_total",
      // "taxes",
      // "line_meta_data",
    ];
    sheet.appendRow(headers);
  }

  // Build index of existing order_ids in sheet
  const existing = {};
  if (sheet.getLastRow() > 1) {
    const values = sheet.getRange(2, 1, sheet.getLastRow() - 1, 1).getValues(); // col A = order_id
    values.forEach((row, i) => {
      if (row[0]) {
        if (!existing[row[0]]) existing[row[0]] = [];
        existing[row[0]].push(i + 2); // store row numbers
      }
    });
  }

  const fieldMap = [
    order => order.id,
    // order => order.parent_id,
    // order => order.number,
    // order => order.order_key,
    order => order.created_via ? order.created_via.charAt(0).toUpperCase() + order.created_via.slice(1) : "",
    // order => order.version,
    order => {
      let status = order.status ? order.status.charAt(0).toUpperCase() + order.status.slice(1) : "";
      if (status === "Completed" && order.refunds && order.refunds.some(r => parseFloat(r.total || 0) !== 0)) {
        status = "Refunded Partially";
      }
      return status;
    },
    order => order.currency,
    order => order.prices_include_tax,
    order => order.customer_id === 0 ? "" : order.customer_id,
    // order => order.customer_ip_address,
    order => order.customer_user_agent,
    order => order.customer_note,
    order => paymentMethodMap[order.payment_method] || order.payment_method,
    order => order.payment_method_title,
    // order => order.transaction_id,
    // order => order.cart_hash,
    // order => JSON.stringify(order.meta_data || []),
    // order => JSON.stringify(order.tax_lines || []),
    // order => JSON.stringify(order.shipping_lines || []),
    // order => JSON.stringify(order.fee_lines || []),
    // order => JSON.stringify(order.coupon_lines || []),
    // order => JSON.stringify(order.refunds || []),
    order => order.lang || "",
    order => order.billing.first_name,
    order => order.billing.last_name,
    order => order.billing.company,
    order => order.billing.address_1,
    order => order.billing.address_2,
    order => order.billing.city,
    order => order.billing.state,
    order => order.billing.postcode,
    order => order.billing.country,
    order => order.billing.email ? order.billing.email.toLowerCase() : "",
    order => order.billing.phone,
    order => order.shipping.first_name,
    order => order.shipping.last_name,
    order => order.shipping.company,
    order => order.shipping.address_1,
    order => order.shipping.address_2,
    order => order.shipping.city,
    order => order.shipping.state,
    order => order.shipping.postcode,
    order => order.shipping.country,
    order => order.shipping_lines && order.shipping_lines.length > 0
      ? order.shipping_lines.map(sl => woocommerceShippingMethods[sl.method_id] || sl.method_title).join(", ")
      : "",
    order => order.date_created,
    order => order.date_created_gmt,
    order => order.date_modified,
    order => order.date_modified_gmt,
    order => order.date_paid,
    order => order.date_paid_gmt,
    order => order.date_completed,
    order => order.date_completed_gmt,
    order => order.discount_tax,
    order => order.discount_total,
    order => order.shipping_tax,
    order => order.shipping_total,
    order => order.cart_tax,
    order => parseFloat(order.total || 0) - parseFloat(order.shipping_total || 0),
    order => {
      const paypalFee = order.meta_data ? order.meta_data.find(m => m.key === 'PayPal Transaction Fee') : null;
      const stripeFee = order.meta_data ? order.meta_data.find(m => m.key === '_stripe_fee') : null;
      return paypalFee ? paypalFee.value : (stripeFee ? stripeFee.value : null);
    },
    order => order.total_tax,
    order => order.total,
    order => order.refunds && order.refunds.length > 0 ? order.refunds.reduce((sum, r) => sum + Math.abs(parseFloat(r.total_tax || 0)), 0) : 0,
    order => order.refunds && order.refunds.length > 0 ? order.refunds.reduce((sum, r) => sum + Math.abs(parseFloat(r.total || 0)), 0) : 0,
    (order, item) => item.id,
    (order, item) => item.name,
    // (order, item) => item.product_id,
    (order, item) => item.sku,
    (order, item) => item.variation_id,
    (order, item) => item.meta_data && item.meta_data.length > 0
      ? item.meta_data.filter(m => m.key && m.key.indexOf("pa_") === 0).map(m => m.display_key + ": " + m.display_value).join(", ")
      : "",
    (order, item) => parseFloat(item.total || 0) / parseFloat(item.quantity || 1),
    (order, item) => item.quantity,
    (order, item) => woocommerceTaxRates[item.tax_class] || (item.tax_class === "" ? woocommerceTaxRates["standard"] : ""),
    (order, item) => item.subtotal_tax,
    (order, item) => item.subtotal,
    (order, item) => item.total_tax,
    (order, item) => item.total,
    // (order, item) => JSON.stringify(item.taxes || []),
    // (order, item) => JSON.stringify(item.meta_data || [])
  ];

  // Map and write orders
  const rows = [];
  orders.forEach(order => {
    if (!order.line_items || order.line_items.length === 0) return;
    // Remove old rows for this order_id
    if (existing[order.id]) {
      const rowsToDelete = existing[order.id];
      // Delete from bottom to top (to not mess indices)
      rowsToDelete.sort((a, b) => b - a).forEach(r => sheet.deleteRow(r));
    }

    order.line_items.forEach(item => {
      const row = fieldMap.map(fieldFn => fieldFn(order, item));
      rows.push(row);
    });
  });

  // Batch append rows for efficiency
  if (rows.length > 0) {
    sheet.getRange(sheet.getLastRow() + 1, 1, rows.length, rows[0].length).setValues(rows);
  }

  // Save newest date to fetch only new orders next time
  const newestDate = new Date(orders[orders.length - 1].date_modified);
  PropertiesService.getScriptProperties().setProperty("SETTINGS_LAST_SYNC", newestDate.toISOString());

  Logger.log("Orders fetched: " + orders.length + (options.test_mode ? " (test mode)" : ""));
}