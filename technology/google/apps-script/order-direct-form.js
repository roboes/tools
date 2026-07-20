// Direct Orders Form
// Last update: 2026-07-20

// https://script.google.com → New project →
//
// - Services
//   Editor → Services → Add a service → Gmail API
//
// - Properties
//   Run setScriptProperties() once to initialise all properties, then fill in real values.
/*
function setScriptProperties() {
  PropertiesService.getScriptProperties().setProperties({
    woocommerce_site_url:              'https://website.com',
    woocommerce_consumer_key:          'ck_xxxxxxxx',
    woocommerce_consumer_secret:       'cs_xxxxxxxx',
    woocommerce_product_category_ids:  '1,2',
    woocommerce_exclude_product_ids:   '1,2',
    woocommerce_exclude_processing_labels: 'Rohkaffee',
    woocommerce_exclude_weight_labels:      '250 g',
    integration_header_value:          'google-apps-script-integration',
    google_sheets_id:                  '1ABC',
    google_sheets_tab_name:            'Orders',
    store_manager_email:               'email@website.com',
    store_shop_name:                   'Shop Name',
    lang:                              'de',
  });
  Logger.log('Script properties set.');
}
*/
// - Deploy as web app:
//   Deploy → New deployment → Web app
//   Execute as: Me | Who has access: Anyone
//   Copy the web app URL → paste into order-direct-form.html → options.scriptUrl

// ======
// Config
// ======
const config = PropertiesService.getScriptProperties().getProperties();

// =======
// Helpers
// =======

/** Formats a YYYY-MM-DD string as a locale-aware date with weekday.
 *  e.g. "de" → "Montag, 07.07.2025"  |  "en" → "Monday, 07/07/2025"
 */
function formatDate(dateString, lang) {
  if (!dateString) return '';
  const [y, m, d] = dateString.split('-');
  const dateObj = new Date(y, m - 1, d);
  const locale = lang === 'en' ? 'en-GB' : 'de-DE';
  const weekday = dateObj.toLocaleDateString(locale, { weekday: 'long' });
  const date = dateObj.toLocaleDateString(locale, { day: '2-digit', month: '2-digit', year: 'numeric' });
  return `${weekday}, ${date}`;
}

/** Fetches the sender's display name and plain-text Gmail signature. */
function getGmailIdentity() {
  try {
    const me = Gmail.Users.Settings.SendAs.list('me').sendAs.find((s) => s.isDefault);
    return {
      name: me?.displayName || config.store_shop_name,
      signature: me?.signature || '',
    };
  } catch (e) {
    return { name: config.store_shop_name, signature: '' };
  }
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

function clearProductsCache() {
  CacheService.getScriptCache().remove('products_list');
  PropertiesService.getScriptProperties().deleteProperty('products_fallback_list');
  PropertiesService.getScriptProperties().deleteProperty('products_last_fetch');
  Logger.log('Cache cleared.');
}

// ============
// Entry points
// ============

function doPost(e) {
  try {
    const data = JSON.parse(e.postData.contents);
    appendOrderToSheet(data);
    sendCustomerEmail(data);
    sendShopManagerEmail(data);
    return jsonResponse({ success: true, message: 'Order received!' });
  } catch (err) {
    return jsonResponse({ success: false, message: err.message });
  }
}

function doGet(e) {
  try {
    const cache = CacheService.getScriptCache();
    const props = PropertiesService.getScriptProperties();

    const cached = cache.get('products_list');
    if (cached) {
      return jsonResponse({ success: true, products: JSON.parse(cached) });
    }

    // Fall back to stored copy if fetched within the last 24 h
    const lastFetch = parseInt(props.getProperty('products_last_fetch') || '0', 10);
    const fallback = props.getProperty('products_fallback_list');
    if (Date.now() - lastFetch < 86400000 && fallback) {
      cache.put('products_list', fallback, 21600);
      return jsonResponse({ success: true, products: JSON.parse(fallback) });
    }

    const products = fetchWooCommerceProducts();
    const jsonString = JSON.stringify(products);
    cache.put('products_list', jsonString, 21600);
    props.setProperty('products_fallback_list', jsonString);
    props.setProperty('products_last_fetch', Date.now().toString());

    return jsonResponse({ success: true, products });
  } catch (err) {
    return jsonResponse({ success: false, error: err.toString(), message: 'Script encountered a fatal error.' });
  }
}

// =========================
// WooCommerce product fetch
// =========================
function fetchWooCommerceProducts() {
  const credentials = Utilities.base64Encode(`${config.woocommerce_consumer_key}:${config.woocommerce_consumer_secret}`);
  const authHeaders = { Authorization: 'Basic ' + credentials, 'X-App-Name': config.integration_header_value };
  const parentFields = 'id,name,type,variations,stock_status,price,sku';
  const variationFields = 'id,name,attributes,stock_status,price,sku';

  // Parse comma-separated lists from Script Properties
  const excludeProductIds = (config.woocommerce_exclude_product_ids || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  const excludeProcessingLabels = (config.woocommerce_exclude_processing_labels || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  const excludeWeightLabels = (config.woocommerce_exclude_weight_labels || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);
  const categoryIds = (config.woocommerce_product_category_ids || '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

  // Fetch raw parent products across all category IDs, deduplicated by product ID.
  // The WC REST API does not accept multiple category values in a single request,
  // so we fan out one paginated fetch loop per category and merge afterwards.
  const seenProductIds = new Set();
  const rawProducts = [];

  categoryIds.forEach((categoryId) => {
    let page = 1;

    while (true) {
      let url = `${config.woocommerce_site_url}/wp-json/wc/v3/products` + `?category=${categoryId}&status=publish` + `&per_page=100&page=${page}&_fields=${parentFields}`;
      if (excludeProductIds.length) url += `&exclude=${excludeProductIds.join(',')}`;

      const res = UrlFetchApp.fetch(url, { method: 'get', headers: authHeaders, muteHttpExceptions: true });

      if (res.getResponseCode() !== 200) break;
      const batch = JSON.parse(res.getContentText());
      if (!Array.isArray(batch) || !batch.length) break;

      batch.forEach((p) => {
        if (!seenProductIds.has(p.id)) {
          seenProductIds.add(p.id);
          rawProducts.push(p);
        }
      });

      if (batch.length < 100) break;
      page++;
    }
  });

  // Expand variable products into individual variation records.
  const allProducts = [];

  rawProducts.forEach((p) => {
    if (p.type === 'variable' && p.variations?.length) {
      const varUrl = `${config.woocommerce_site_url}/wp-json/wc/v3/products/${p.id}/variations` + `?status=publish&per_page=100&_fields=${variationFields}`;
      const varRes = UrlFetchApp.fetch(varUrl, { method: 'get', headers: authHeaders, muteHttpExceptions: true });

      if (varRes.getResponseCode() === 200) {
        JSON.parse(varRes.getContentText())
          .filter((v) => {
            if (v.stock_status !== 'instock') return false;
            // Filter out excluded weight labels
            const weightAttr = (v.attributes || []).find((a) => a.slug === 'pa_weight');
            if (weightAttr && excludeWeightLabels.includes(weightAttr.option)) return false;
            // Filter out excluded processing labels
            const processingAttr = (v.attributes || []).find((a) => a.slug === 'pa_coffee-processing');
            if (processingAttr && excludeProcessingLabels.includes(processingAttr.option)) return false;
            return true;
          })
          .forEach((v) => {
            // Build flat attributes map: "attribute_pa_xxx" → option value.
            // a.slug from the WC variations endpoint already carries the "pa_" prefix
            // (e.g. "pa_coffee-processing"), so strip it before prepending "attribute_pa_"
            // to avoid the double-prefix bug ("attribute_pa_pa_coffee-processing").
            const attrMap = {};
            (v.attributes || []).forEach((a) => {
              const normalizedSlug = a.slug.startsWith('pa_') ? a.slug.slice(3) : a.slug;
              attrMap[`attribute_pa_${normalizedSlug}`] = a.option;
            });
            allProducts.push({ id: v.id, name: p.name, price: v.price || '0', sku: v.sku || '', attributes: attrMap });
          });
      }
    } else if (p.stock_status === 'instock') {
      allProducts.push({ id: p.id, name: p.name, price: p.price || '0', sku: p.sku || '', attributes: {} });
    }
  });

  allProducts.sort((a, b) => a.name.localeCompare(b.name, 'de', { sensitivity: 'base' }));
  return allProducts.map((p) => ({
    id: p.id,
    name: p.name,
    sku: p.sku,
    price: (parseFloat(p.price) || 0).toFixed(2).replace('.', ','),
    attributes: p.attributes,
  }));
}

// ===============
// Variant helpers
// ===============

/** Builds a human-readable variant label in attribute order.
 *  e.g. "Röstkaffee, Ganze Bohne, Ganze Bohne, 500 g, Verpackt"
 *  Attribute keys follow the same order as ATTR_COLUMNS in the HTML form.
 */
const ATTR_KEY_ORDER = ['attribute_pa_coffee-processing', 'attribute_pa_coffee-type', 'attribute_pa_coffee-grinding-degree', 'attribute_pa_weight', 'attribute_pa_packaging'];

function buildVariantLabel(attributes) {
  if (!attributes || !Object.keys(attributes).length) return '';
  return ATTR_KEY_ORDER.map((key) => attributes[key])
    .filter(Boolean)
    .join(', ');
}

// =============
// Google Sheets
// =============
function appendOrderToSheet(data) {
  const ss = SpreadsheetApp.openById(config.google_sheets_id);
  let sheet = ss.getSheetByName(config.google_sheets_tab_name);

  if (!sheet) {
    sheet = ss.insertSheet(config.google_sheets_tab_name);
    const headers = ['Timestamp', 'Name', 'Email', 'Phone', 'Products Ordered', 'Pickup?', 'Pickup Date', 'Pickup Time', 'Delivery Address', 'House Number', 'Post Code', 'City', 'Country', 'Notes', 'Invoice', 'Status'];
    sheet.appendRow(headers);
    sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }

  const productsSummary = data.items
    .map((i) => {
      const variant = buildVariantLabel(i.attributes);
      return `${i.quantity}x ${i.name}${variant ? ` (${variant})` : ''} (Variation ID: ${i.id})`;
    })
    .join('\n\n');
  const legalName = data.customerType === 'company' && data.companyName ? `${data.name} (im Auftrag von ${data.companyName})` : data.name;

  sheet.appendRow([
    new Date(),
    legalName,
    data.email,
    "'" + data.phone,
    productsSummary,
    data.pickup ? 'Ja' : 'Nein',
    data.pickup ? data.pickupDate : '',
    data.pickup ? data.pickupTime : '',
    !data.pickup ? data.deliveryAddress : '',
    !data.pickup ? data.deliveryNumber : '',
    !data.pickup ? data.deliveryPostCode : '',
    !data.pickup ? data.deliveryCity : '',
    !data.pickup ? data.deliveryCountry : '',
    data.notes || '',
    'Ausstehend', // Rechnung (Invoice)
    'Neu',
  ]);
}

// =================
// Email to customer
// =================
function sendCustomerEmail(data) {
  const lang = data.lang || config.lang;
  const isEn = lang === 'en';
  const { name: senderName, signature } = getGmailIdentity();

  const productList = data.items
    .map((i) => {
      const variant = buildVariantLabel(i.attributes);
      return `- ${i.quantity}x ${i.name}${variant ? ` (${variant})` : ''}`;
    })
    .join('<br>');
  const formattedDate = formatDate(data.pickupDate, lang);

  const logistics = data.pickup
    ? isEn
      ? `Pickup scheduled: ${formattedDate} at ${data.pickupTime}`
      : `Vereinbarte Abholung: ${formattedDate} um ${data.pickupTime} Uhr`
    : isEn
      ? `Delivery address:<br>${data.deliveryAddress} ${data.deliveryNumber}<br>${data.deliveryPostCode} ${data.deliveryCity}<br>${data.deliveryCountry}`
      : `Lieferadresse:<br>${data.deliveryAddress} ${data.deliveryNumber}<br>${data.deliveryPostCode} ${data.deliveryCity}<br>${data.deliveryCountry}`;

  const notesLine = data.notes ? (isEn ? `<br><br>Notes: ${data.notes}` : `<br><br>Anmerkungen: ${data.notes}`) : '';

  const fallbackSignature = `${isEn ? 'Best regards' : 'Viele Grüße'},<br>${config.store_shop_name}<br>${config.woocommerce_site_url}`;

  const body = isEn
    ? `Hi ${data.name},<br><br>thank you for your order! Here is your summary:<br><br>${productList}<br><br>${logistics}${notesLine}<br><br>We will be in touch shortly to confirm your order.<br><br>${signature || fallbackSignature}`
    : `Hallo ${data.name},<br><br>vielen Dank für deine Bestellung! Hier ist deine Zusammenfassung:<br><br>${productList}<br><br>${logistics}${notesLine}<br><br>${signature || fallbackSignature}`;

  GmailApp.sendEmail(data.email, isEn ? `${config.store_shop_name} - Order Confirmation` : `${config.store_shop_name} - Bestellbestätigung`, '', { name: senderName, htmlBody: body });
}

// =====================
// Email to Shop Manager
// =====================
function sendShopManagerEmail(data) {
  const { name: senderName } = getGmailIdentity();

  const productList = data.items
    .map((i) => {
      const variant = buildVariantLabel(i.attributes);
      return `- ${i.quantity}x ${i.name}${variant ? ` (${variant})` : ''}`;
    })
    .join('<br>');
  const logistics = data.pickup
    ? `Abholung am ${formatDate(data.pickupDate, 'de')} um ${data.pickupTime} Uhr`
    : `Lieferung an: ${data.deliveryAddress} ${data.deliveryNumber}, ${data.deliveryPostCode} ${data.deliveryCity}, ${data.deliveryCountry}`;
  const customerId = data.customerType === 'company' && data.companyName ? `${data.name} (im Auftrag von ${data.companyName})` : data.name;

  const body = `
  Neue Bestellung eingegangen!<br><br>
  Kunde: ${customerId}<br>
  E-Mail: ${data.email}<br>
  Telefon: ${data.phone}<br><br>
  Bestellte Produkte:<br>
  ${productList}<br><br>
  Logistik: ${logistics}${data.notes ? '<br>Kundennotiz: ' + data.notes : ''}<br><br>
  Eingereicht über das Online-Bestellformular
  `;

  GmailApp.sendEmail(config.store_manager_email, `${config.store_shop_name} - Neue Direktbestellung von ${data.name}`, '', { name: senderName, htmlBody: body });
}
