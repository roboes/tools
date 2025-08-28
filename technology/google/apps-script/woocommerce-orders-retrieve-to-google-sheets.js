// WooCommerce - WooCommerce Orders Retrieve to Google Sheets
// Last update: 2025-08-28


// https://script.google.com > New project >
// - Editor > Services > Add a service > Gmail API
// - Triggers > Add Trigger >
// -- Choose which function to run: WooCommerceOrdersRetrieve
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Minutes timer
// -- Select minute interval: Every 30 minutes


function WooCommerceOrdersRetrieve(options) {
  options = options || {};
  var test_mode = options.test_mode || false;
  var last_fetch_date_reset = options.last_fetch_date_reset || false;

  var apiEndpoint = "/wp-json/wc/v3/orders";
  var perPage = 100;
  var page = 1;
  var orders = [];

  if (last_fetch_date_reset) PropertiesService.getScriptProperties().deleteProperty("LAST_FETCH_DATE");

  // Get last fetch date to fetch only new orders
  var lastFetchDate = PropertiesService.getScriptProperties().getProperty("LAST_FETCH_DATE");

  // Fetch paginated orders
  while (true) {
    var url = options.site_url + apiEndpoint + "?per_page=" + perPage + "&page=" + page;
    if (lastFetchDate) url += "&after=" + encodeURIComponent(lastFetchDate);

    var requestOptions = {
      "method": "get",
      "headers": {
        "Authorization": "Basic " + Utilities.base64Encode(options.consumer_key + ":" + options.consumer_secret),
        "X-App-Name": "woocommerce-orders-retrieve-to-google-sheets"
      },
      "muteHttpExceptions": true
    };

    var response = UrlFetchApp.fetch(url, requestOptions);
    if (response.getResponseCode() !== 200) {
      Logger.log("Error fetching orders: " + response.getContentText());
      break;
    }

    var data = JSON.parse(response.getContentText());
    if (data.length === 0) break; // No more orders

    orders = orders.concat(data);
    page++;

    if (test_mode) break; // Stop after first page if test mode
  }

  if (orders.length === 0) {
    Logger.log("No new orders found.");
    return;
  }

  // Write to Sheet
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName("Orders");
  if (!sheet) sheet = SpreadsheetApp.getActiveSpreadsheet().insertSheet("Orders");

  // If sheet is empty, add headers
  if (sheet.getLastRow() === 0) {
    var headers = [
      "order_id", "status", "currency", "created_date", "modified_date", "paid_date", "completed_date", "customer_id", "customer_note", "discount_total", "discount_tax", "shipping_total", "shipping_tax", "cart_tax", "total", "total_tax", "payment_method",
      // Billing
      "billing_first_name", "billing_last_name", "billing_company", "billing_address_1", "billing_address_2", "billing_city", "billing_state", "billing_postcode", "billing_country", "billing_email", "billing_phone",
      // Shipping
      "shipping_first_name", "shipping_last_name", "shipping_company", "shipping_address_1", "shipping_address_2", "shipping_city", "shipping_state", "shipping_postcode", "shipping_country",
      // Items
      // "items"
    ];
    sheet.appendRow(headers);
  }

  // Append orders
  orders.forEach(function(order) {
    var items = order.line_items.map(function(item){
      return item.name + " x" + item.quantity;
    }).join(", ");

    // Capitalize status
    var status = order.status ? order.status.charAt(0).toUpperCase() + order.status.slice(1) : "";

    // Map payment method
    var paymentMethod = order.payment_method;
    if (paymentMethod === "stripe_cc") paymentMethod = "Stripe";
    else if (paymentMethod === "ppcp-gateway") paymentMethod = "PayPal";
    else if (paymentMethod === "bacs") paymentMethod = "Direct Bank Transfer";

    sheet.appendRow([
      order.id,
      status,
      order.currency,
      order.date_created,
      order.date_modified,
      order.date_paid,
      order.date_completed,
      order.customer_id,
      order.customer_note,
      order.discount_total,
      order.discount_tax,
      order.shipping_total,
      order.shipping_tax,
      order.cart_tax,
      order.total,
      order.total_tax,
      paymentMethod,
      order.billing.first_name,
      order.billing.last_name,
      order.billing.company,
      order.billing.address_1,
      order.billing.address_2,
      order.billing.city,
      order.billing.state,
      order.billing.postcode,
      order.billing.country,
      order.billing.email,
      order.billing.phone,
      order.shipping.first_name,
      order.shipping.last_name,
      order.shipping.company,
      order.shipping.address_1,
      order.shipping.address_2,
      order.shipping.city,
      order.shipping.state,
      order.shipping.postcode,
      order.shipping.country,
      // items
    ]);
  });

  // Save newest date to fetch only new orders next time
  var newestDate = orders[0].date_created; // WooCommerce returns newest first
  PropertiesService.getScriptProperties().setProperty("LAST_FETCH_DATE", newestDate);

  Logger.log("Orders fetched: " + orders.length + (test_mode ? " (test mode)" : ""));
}



WooCommerceOrdersRetrieve({
  site_url: "https://website.com",
  consumer_key: "ck_xxxxxxxx",
  consumer_secret: "cs_xxxxxxxx",
  test_mode: false,
  last_fetch_date_reset: false,
});


