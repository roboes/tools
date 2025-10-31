// WooCommerce - WooCommerce Orders Retrieve to Google Sheets
// Last update: 2025-09-30

// https://script.google.com → New project →
// - Editor → Services → Add a service → Gmail API
// - Triggers → Add Trigger →
// -- Choose which function to run: WooCommerceOrdersRetrieve
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Hour timer
// -- Select minute interval: Every 4 hours

// Google Sheets Query to aggregate orders from line/item level to order level
// =QUERY('sales_orders_articles'!A:BD, "SELECT MAX(A), MAX(B), MAX(C), MAX(D), MAX(E), MAX(F), MAX(G), MAX(H), MAX(I), MAX(J), MAX(K), MAX(L), MAX(M), MAX(N), MAX(O), MAX(P), MAX(Q), MAX(R), MAX(S), MAX(T), MAX(U), MAX(V), MAX(W), MAX(X), MAX(Y), MAX(Z), MAX(AA), MAX(AB), MAX(AC), MAX(AD), MAX(AE), MAX(AF), MAX(AG), MAX(AH), MAX(AI), MAX(AJ), MAX(AK), MAX(AL), MAX(AM), MAX(AN), MAX(AO), MAX(AP), MAX(AQ), SUM(AR), SUM(AS), SUM(AT), SUM(AU), SUM(AV), SUM(AW), SUM(AX), SUM(AY), SUM(AZ), SUM(BA), SUM(BB), SUM(BC), SUM(BD) WHERE A IS NOT NULL GROUP BY A, B LABEL MAX(A) 'source_system', MAX(B) 'order_id', MAX(C) 'created_via', MAX(D) 'status', MAX(E) 'date_created', MAX(F) 'date_created_gmt', MAX(G) 'date_modified', MAX(H) 'date_modified_gmt', MAX(I) 'date_paid', MAX(J) 'date_paid_gmt', MAX(K) 'date_completed', MAX(L) 'date_completed_gmt', MAX(M) 'currency', MAX(N) 'prices_include_tax', MAX(O) 'order_attribution_origin', MAX(P) 'order_attribution_device_type', MAX(Q) 'customer_id', MAX(R) 'customer_user_agent', MAX(S) 'customer_note', MAX(T) 'payment_method', MAX(U) 'payment_method_title', MAX(V) 'language_code', MAX(W) 'billing_first_name', MAX(X) 'billing_last_name', MAX(Y) 'billing_company', MAX(Z) 'billing_address_1', MAX(AA) 'billing_address_2', MAX(AB) 'billing_city', MAX(AC) 'billing_state', MAX(AD) 'billing_postcode', MAX(AE) 'billing_country', MAX(AF) 'billing_email', MAX(AG) 'billing_phone', MAX(AH) 'shipping_first_name', MAX(AI) 'shipping_last_name', MAX(AJ) 'shipping_company', MAX(AK) 'shipping_address_1', MAX(AL) 'shipping_address_2', MAX(AM) 'shipping_city', MAX(AN) 'shipping_state', MAX(AO) 'shipping_postcode', MAX(AP) 'shipping_country', MAX(AQ) 'shipping_methods', SUM(AR) 'discount_amount_net', SUM(AS) 'discount_amount_tax', SUM(AT) 'discount_amount_gross', SUM(AU) 'shipping_amount_net', SUM(AV) 'shipping_amount_tax', SUM(AW) 'shipping_amount_gross', SUM(AX) 'items_amount_net', SUM(AY) 'items_amount_tax', SUM(AZ) 'items_amount_gross', SUM(BA) 'transaction_fee', SUM(BB) 'refund_amount_net', SUM(BC) 'refund_amount_tax', SUM(BD) 'refund_amount_gross'", 1)

// Aggregate order data by customer, classifying each as "New", "Returning", or "Guest" based on their first purchase date, and presents the key metrics in a reportable format
// =QUERY({sales_orders_articles!A:BD, ARRAYFORMULA(IF(ISBLANK(sales_orders_articles!Q:Q), "Guest", IF(sales_orders_articles!E:E = VLOOKUP(sales_orders_articles!Q:Q, QUERY(sales_orders_articles!E:Q, "SELECT Q, MIN(E) WHERE Q IS NOT NULL GROUP BY Q LABEL MIN(E) ''"), 2, FALSE), "New", "Returning")))}, "SELECT MAX(Col1), MAX(Col2), MAX(Col5), MAX(Col6), MAX(Col7), MAX(Col8), MAX(Col9), MAX(Col10), MAX(Col11), MAX(Col12), MAX(Col13), MAX(Col14), MAX(Col17), SUM(Col44), SUM(Col45), SUM(Col46), SUM(Col47), SUM(Col48), SUM(Col49), SUM(Col50), SUM(Col51), SUM(Col52), SUM(Col53), SUM(Col54), SUM(Col55), SUM(Col56), MAX(Col57) WHERE Col1 IS NOT NULL GROUP BY Col1, Col2, Col17 LABEL MAX(Col1) 'source_system', MAX(Col2) 'order_id', MAX(Col5) 'date_created', MAX(Col6) 'date_created_gmt', MAX(Col7) 'date_modified', MAX(Col8) 'date_modified_gmt', MAX(Col9) 'date_paid', MAX(Col10) 'date_paid_gmt', MAX(Col11) 'date_completed', MAX(Col12) 'date_completed_gmt', MAX(Col13) 'currency', MAX(Col14) 'prices_include_tax', MAX(Col17) 'customer_id', SUM(Col44) 'discount_amount_net', SUM(Col45) 'discount_amount_tax', SUM(Col46) 'discount_amount_gross', SUM(Col47) 'shipping_amount_net', SUM(Col48) 'shipping_amount_tax', SUM(Col49) 'shipping_amount_gross', SUM(Col50) 'items_amount_net', SUM(Col51) 'items_amount_tax', SUM(Col52) 'items_amount_gross', SUM(Col53) 'transaction_fee', SUM(Col54) 'refund_amount_net', SUM(Col55) 'refund_amount_tax', SUM(Col56) 'refund_amount_gross', MAX(Col57) 'customer_role'")

function WooCommerceOrdersRetrieve() {
  const options = {
    woocommerce_site_url: "https://website.com",
    woocommerce_consumer_key: "ck_xxxxxxxx",
    woocommerce_consumer_secret: "cs_xxxxxxxx",
    google_sheets_id: "1ABC",
    google_sheets_tab_name: "sales_orders_articles",
    test_mode: false,
    last_fetch_date_reset: false,
  };

  // Payment method mapping
  const paymentMethodMap = {
    "direct-debit": "Direct Bank Transfer",
    bacs: "Direct Bank Transfer",
    paypal: "PayPal",
    "ppcp-gateway": "PayPal",
    stripe_cc: "Stripe",
  };

  if (options.last_fetch_date_reset) PropertiesService.getScriptProperties().deleteProperty("SETTINGS_LAST_SYNC");

  // Get last fetch date to fetch only new orders
  let lastFetchDate = PropertiesService.getScriptProperties().getProperty("SETTINGS_LAST_SYNC");
  if (lastFetchDate) lastFetchDate = lastFetchDate.slice(0, lastFetchDate.lastIndexOf(".")) + "Z";
  Logger.log("The last imported modified date is: " + lastFetchDate);

  // Reusable API fetch function
  const fetchWooCommerceAPI = (endpoint, params = {}) => {
    let url = options.woocommerce_site_url + "/wp-json/wc/v3/" + endpoint;
    const queryString = Object.keys(params)
      .map((key) => encodeURIComponent(key) + "=" + encodeURIComponent(params[key]))
      .join("&");

    if (queryString) {
      url += "?" + queryString;
    }

    const response = UrlFetchApp.fetch(url, {
      method: "get",
      headers: {
        Authorization: "Basic " + Utilities.base64Encode(options.woocommerce_consumer_key + ":" + options.woocommerce_consumer_secret),
        "X-App-Name": "woocommerce-orders-retrieve-to-google-sheets",
      },
      muteHttpExceptions: true,
    });

    if (response.getResponseCode() !== 200) {
      Logger.log(`Error fetching ${endpoint}: ${response.getContentText()}`);
      return null;
    }
    return JSON.parse(response.getContentText());
  };

  // WooCommerce product data
  const productCache = {};

  // Fetch product data
  const getProductCategories = (productId) => {
    if (productCache[productId]) {
      return productCache[productId];
    }
    const productData = fetchWooCommerceAPI(`products/${productId}`);
    if (productData && productData.categories) {
      const categories = productData.categories.map((cat) => ({
        id: cat.id,
        name: cat.name,
      }));
      productCache[productId] = categories;
      return categories;
    }
    productCache[productId] = []; // Cache as empty array to avoid re-fetching
    return [];
  };

  // WooCommerce tax rates
  const taxRatesData = fetchWooCommerceAPI("taxes", { per_page: 100 });
  const woocommerceTaxRates = {};
  if (taxRatesData) {
    taxRatesData.forEach((rate) => {
      woocommerceTaxRates[rate.class] = parseFloat(rate.rate);
    });
  }

  // WooCommerce shipping methods
  const shippingMethodsData = fetchWooCommerceAPI("shipping_methods", {
    per_page: 100,
  });
  const woocommerceShippingMethods = {};
  if (shippingMethodsData) {
    shippingMethodsData.forEach((sm) => {
      woocommerceShippingMethods[sm.id] = sm.title;
    });
  }

  let orders = [];
  let page = 1;
  const perPage = 100;

  // Fetch paginated orders
  while (true) {
    const params = {
      per_page: perPage,
      page: page,
      orderby: "date",
      order: "asc",
    };
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
  let sheet;
  if (options.google_sheets_id) {
    const googleSpreadsheet = SpreadsheetApp.openById(options.google_sheets_id);
    sheet = googleSpreadsheet.getSheetByName(options.google_sheets_tab_name);
    if (!sheet) sheet = googleSpreadsheet.insertSheet(options.google_sheets_tab_name);
  } else {
    throw new Error("Google Sheets ID not provided. Script cannot continue.");
  }

  // If sheet is empty, add headers
  if (sheet.getLastRow() === 0) {
    const headers = [
      "source_system",
      "order_id",
      // "parent_id",
      // "number",
      // "order_key",
      "created_via",
      // "version",
      "status",
      "date_created",
      "date_created_gmt",
      "date_modified",
      "date_modified_gmt",
      "date_paid",
      "date_paid_gmt",
      "date_completed",
      "date_completed_gmt",
      "currency",
      "prices_include_tax",
      "order_attribution_origin",
      "order_attribution_device_type",
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
      "discount_amount_net",
      "discount_amount_tax",
      "discount_amount_gross",
      "shipping_amount_net",
      "shipping_amount_tax",
      "shipping_amount_gross",
      "items_amount_net",
      "items_amount_tax",
      "items_amount_gross",
      "transaction_fee",
      "refund_amount_net",
      "refund_amount_tax",
      "refund_amount_gross",
      "line_item_id",
      "product_category_id",
      "product_category_name",
      "product_id",
      "product_name",
      "sku",
      "variation_id",
      "variation_name",
      "tax_class",
      "quantity",
      "price_gross",
      "line_item_amount_net",
      "line_item_amount_tax",
      "line_item_amount_gross",
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
        existing[row[0]].push(i + 2); // Store row numbers
      }
    });
  }

  const fieldMap = [
    (order) => "WooCommerce",
    (order) => order.id,
    // order => order.parent_id,
    // order => order.number,
    // order => order.order_key,
    (order) => (order.created_via ? order.created_via.charAt(0).toUpperCase() + order.created_via.slice(1) : ""),
    // order => order.version,
    (order) => {
      let status = order.status ? order.status.charAt(0).toUpperCase() + order.status.slice(1) : "";
      if (status === "Completed" && order.refunds && order.refunds.some((r) => parseFloat(r.total || 0) !== 0)) {
        status = "Refunded Partially";
      }
      return status;
    },
    (order) => order.date_created,
    (order) => order.date_created_gmt,
    (order) => order.date_modified,
    (order) => order.date_modified_gmt,
    (order) => order.date_paid,
    (order) => order.date_paid_gmt,
    (order) => order.date_completed,
    (order) => order.date_completed_gmt,
    (order) => order.currency,
    (order) => order.prices_include_tax,
    (order) => {
      let source = (order.meta_data?.find((m) => m.key === "_wc_order_attribution_source_type") || {}).value || "";
      if (source === "typein") {
        return "Direct";
      }
      return source.charAt(0).toUpperCase() + source.slice(1);
    },
    (order) => (order.meta_data?.find((m) => m.key === "_wc_order_attribution_device_type") || {}).value || "",
    (order) => (order.customer_id === 0 ? "" : order.customer_id),
    // order => order.customer_ip_address,
    (order) => order.customer_user_agent,
    (order) => order.customer_note,
    (order) => paymentMethodMap[order.payment_method] || order.payment_method,
    (order) => order.payment_method_title,
    // order => order.transaction_id,
    // order => order.cart_hash,
    // order => JSON.stringify(order.meta_data || []),
    // order => JSON.stringify(order.tax_lines || []),
    // order => JSON.stringify(order.shipping_lines || []),
    // order => JSON.stringify(order.fee_lines || []),
    // order => JSON.stringify(order.coupon_lines || []),
    // order => JSON.stringify(order.refunds || []),
    (order) => order.lang || "",
    (order) => order.billing.first_name,
    (order) => order.billing.last_name,
    (order) => order.billing.company,
    (order) => order.billing.address_1,
    (order) => order.billing.address_2,
    (order) => order.billing.city,
    (order) => order.billing.state,
    (order) => order.billing.postcode,
    (order) => order.billing.country,
    (order) => (order.billing.email ? order.billing.email.toLowerCase() : ""),
    (order) => order.billing.phone,
    (order) => order.shipping.first_name,
    (order) => order.shipping.last_name,
    (order) => order.shipping.company,
    (order) => order.shipping.address_1,
    (order) => order.shipping.address_2,
    (order) => order.shipping.city,
    (order) => order.shipping.state,
    (order) => order.shipping.postcode,
    (order) => order.shipping.country,
    (order) => (order.shipping_lines && order.shipping_lines.length > 0 ? order.shipping_lines.map((sl) => woocommerceShippingMethods[sl.method_id] || sl.method_title).join(", ") : ""),
    (order) => order.discount_total,
    (order) => order.discount_tax,
    (order) => parseFloat(order.discount_total || 0) + parseFloat(order.discount_tax || 0),
    (order) => order.shipping_total,
    (order) => order.shipping_tax,
    (order) => parseFloat(order.shipping_total || 0) + parseFloat(order.shipping_tax || 0),
    (order) => order.line_items.reduce((sum, item) => sum + parseFloat(item.total || 0), 0),
    (order) => order.line_items.reduce((sum, item) => sum + parseFloat(item.total_tax || 0), 0),
    (order) => order.line_items.reduce((sum, item) => sum + parseFloat(item.total || 0) + parseFloat(item.total_tax || 0), 0),
    (order) => {
      const paypalFee = order.meta_data ? order.meta_data.find((m) => m.key === "PayPal Transaction Fee") : null;
      const stripeFee = order.meta_data ? order.meta_data.find((m) => m.key === "_stripe_fee") : null;
      return parseFloat(paypalFee?.value || stripeFee?.value || 0);
    },
    (order) => (order.refunds && order.refunds.length > 0 ? order.refunds.reduce((sum, r) => sum + Math.abs(parseFloat(r.total || 0)), 0) : 0),
    (order) => (order.refunds && order.refunds.length > 0 ? order.refunds.reduce((sum, r) => sum + Math.abs(parseFloat(r.total_tax || 0)), 0) : 0),
    (order) => (order.refunds && order.refunds.length > 0 ? order.refunds.reduce((sum, r) => sum + Math.abs(parseFloat(r.total || 0) + parseFloat(r.total_tax || 0)), 0) : 0),
    (order, item) => item.id,
    (order, item) =>
      getProductCategories(item.product_id)
        .map((c) => c.id)
        .join(", "),
    (order, item) =>
      getProductCategories(item.product_id)
        .map((c) => c.name)
        .join(", "),
    (order, item) => item.product_id,
    (order, item) => item.name,
    (order, item) => item.sku,
    (order, item) => item.variation_id,
    (order, item) =>
      item.meta_data && item.meta_data.length > 0
        ? item.meta_data
            .filter((m) => m.key && m.key.indexOf("pa_") === 0)
            .map((m) => m.display_key + ": " + m.display_value)
            .join(", ")
        : "",
    (order, item) => woocommerceTaxRates[item.tax_class] || (item.tax_class === "" ? woocommerceTaxRates["standard"] : ""),
    (order, item) => item.quantity,
    (order, item) => parseFloat(item.price || 0).toFixed(2),
    (order, item) => item.total,
    (order, item) => item.total_tax,
    (order, item) => parseFloat(item.total || 0) + parseFloat(item.total_tax || 0),
    // (order, item) => JSON.stringify(item.taxes || []),
    // (order, item) => JSON.stringify(item.meta_data || [])
  ];

  // Collect all rows to delete (for orders that already exist on the sheet), and also prepare the new rows to append
  const rows = [];
  const rowsToDeleteAll = [];

  orders.forEach((order) => {
    if (!order.line_items || order.line_items.length === 0) return;

    // If the order already exists in the sheet, mark its rows for deletion
    if (existing[order.id]) {
      rowsToDeleteAll.push(...existing[order.id]);
    }

    // Build new rows for this order's line items
    order.line_items.forEach((item) => {
      const row = fieldMap.map((fieldFn) => fieldFn(order, item));
      rows.push(row);
    });
  });

  // Remove duplicates, sort descending and delete
  if (rowsToDeleteAll.length > 0) {
    const uniqueRows = Array.from(new Set(rowsToDeleteAll));
    uniqueRows.sort((a, b) => b - a); // Descending

    // Delete each row if it's still within the current sheet bounds
    for (let i = 0; i < uniqueRows.length; i++) {
      const r = uniqueRows[i];
      // Safety guard to avoid out-of-bounds
      if (r >= 1 && r <= sheet.getLastRow()) {
        sheet.deleteRow(r);
      } else {
        Logger.log("Skipped deleting out-of-bounds row: " + r);
      }
    }
  }

  // Batch append rows for efficiency
  if (rows.length > 0) {
    sheet.getRange(sheet.getLastRow() + 1, 1, rows.length, rows[0].length).setValues(rows);
  }

  // Save newest date to fetch only new orders next time
  const newestDate = new Date(orders[orders.length - 1].date_modified);
  PropertiesService.getScriptProperties().setProperty("SETTINGS_LAST_SYNC", newestDate.toISOString());

  Logger.log("Orders fetched: " + orders.length + (options.test_mode ? " (test mode)" : ""));
}
