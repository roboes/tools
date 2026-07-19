// Direct Orders Form
// Last update: 2026-07-08

// https://script.google.com → New project →
// - Editor → Services → Add a service → Gmail API

// =======
// Options
// =======
const options = {
  // WooCommerce
  woocommerce_site_url: 'https://website.com',
  woocommerce_consumer_key: 'ck_xxxxxxxx',
  woocommerce_consumer_secret: 'cs_xxxxxxxx',
  woocommerce_product_category_id: 1,

  // Integration
  integration_header_value: 'google-apps-script-integration',

  // Google Sheets
  google_sheets_id: '1ABC',
  google_sheets_tab_name: 'Orders',

  // Email
  store_manager_email: 'email@website.com',
  store_shop_name: 'Shop Name',

  // Language
  lang: 'de',
};

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
      name: me?.displayName || options.store_shop_name,
      signature: me?.signature || '',
    };
  } catch (e) {
    return { name: options.store_shop_name, signature: '' };
  }
}

function jsonResponse(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
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
  const credentials = Utilities.base64Encode(`${options.woocommerce_consumer_key}:${options.woocommerce_consumer_secret}`);
  const authHeaders = { Authorization: 'Basic ' + credentials, 'X-App-Name': options.integration_header_value };
  const parentFields = 'id,name,type,variations,stock_status,price,sku';
  const variationFields = 'id,attributes,stock_status,price,sku';

  const allProducts = [];
  let page = 1;

  while (true) {
    const url = `${options.woocommerce_site_url}/wp-json/wc/v3/products` + `?category=${options.woocommerce_product_category_id}&status=publish` + `&per_page=100&page=${page}&_fields=${parentFields}`;
    const res = UrlFetchApp.fetch(url, { method: 'get', headers: authHeaders, muteHttpExceptions: true });

    if (res.getResponseCode() !== 200) break;
    const batch = JSON.parse(res.getContentText());
    if (!Array.isArray(batch) || !batch.length) break;

    batch.forEach((p) => {
      if (p.type === 'variable' && p.variations?.length) {
        const varUrl = `${options.woocommerce_site_url}/wp-json/wc/v3/products/${p.id}/variations` + `?status=publish&per_page=100&_fields=${variationFields}`;
        const varRes = UrlFetchApp.fetch(varUrl, { method: 'get', headers: authHeaders, muteHttpExceptions: true });

        if (varRes.getResponseCode() === 200) {
          JSON.parse(varRes.getContentText())
            .filter((v) => v.stock_status === 'instock')
            .forEach((v) => {
              const attrs = v.attributes?.map((a) => a.option).join(', ') || '';
              const fullName = attrs ? `${p.name} (${attrs})` : p.name;
              allProducts.push({ id: v.id, name: fullName, price: v.price || '0', sku: v.sku || '' });
            });
        }
      } else if (p.stock_status === 'instock') {
        allProducts.push({ id: p.id, name: p.name, price: p.price || '0', sku: p.sku || '' });
      }
    });

    if (batch.length < 100) break;
    page++;
  }

  allProducts.sort((a, b) => a.name.localeCompare(b.name, 'de', { sensitivity: 'base' }));
  return allProducts.map((p) => ({
    id: p.id,
    name: p.name,
    sku: p.sku,
    price: (parseFloat(p.price) || 0).toFixed(2).replace('.', ','),
  }));
}

// =============
// Google Sheets
// =============
function appendOrderToSheet(data) {
  const ss = SpreadsheetApp.openById(options.google_sheets_id);
  let sheet = ss.getSheetByName(options.google_sheets_tab_name);

  if (!sheet) {
    sheet = ss.insertSheet(options.google_sheets_tab_name);
    const headers = ['Timestamp', 'Name', 'Email', 'Telefon', 'Bestellte Produkte', 'Abholung?', 'Abholdatum', 'Abholzeit', 'Lieferadresse', 'Hausnummer', 'PLZ', 'Stadt', 'Land', 'Anmerkungen', 'Status'];
    sheet.appendRow(headers);
    sheet.getRange(1, 1, 1, headers.length).setFontWeight('bold');
    sheet.setFrozenRows(1);
  }

  const productsSummary = data.items.map((i) => `${i.quantity}x ${i.name}`).join('\n\n');
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
    'Neu',
  ]);
}

// =================
// Email to customer
// =================
function sendCustomerEmail(data) {
  const lang = data.lang || options.lang;
  const isEn = lang === 'en';
  const { name: senderName, signature } = getGmailIdentity();

  const productList = data.items.map((i) => `- ${i.quantity}x ${i.name}`).join('<br>');
  const formattedDate = formatDate(data.pickupDate, lang);

  const logistics = data.pickup
    ? isEn
      ? `Pickup scheduled: ${formattedDate} at ${data.pickupTime}`
      : `Vereinbarte Abholung: ${formattedDate} um ${data.pickupTime} Uhr`
    : isEn
      ? `Delivery address:<br>${data.deliveryAddress} ${data.deliveryNumber}<br>${data.deliveryPostCode} ${data.deliveryCity}<br>${data.deliveryCountry}`
      : `Lieferadresse:<br>${data.deliveryAddress} ${data.deliveryNumber}<br>${data.deliveryPostCode} ${data.deliveryCity}<br>${data.deliveryCountry}`;

  const notesLine = data.notes ? (isEn ? `<br><br>Notes: ${data.notes}` : `<br><br>Anmerkungen: ${data.notes}`) : '';

  const fallbackSignature = `${isEn ? 'Best regards' : 'Viele Grüße'},<br>${options.store_shop_name}<br>${options.woocommerce_site_url}`;

  const body = isEn
    ? `Hi ${data.name},<br><br>thank you for your order! Here is your summary:<br><br>${productList}<br><br>${logistics}${notesLine}<br><br>We will be in touch shortly to confirm your order.<br><br>${signature || fallbackSignature}`
    : `Hallo ${data.name},<br><br>vielen Dank für deine Bestellung! Hier ist deine Zusammenfassung:<br><br>${productList}<br><br>${logistics}${notesLine}<br><br>${signature || fallbackSignature}`;

  GmailApp.sendEmail(data.email, isEn ? `${options.store_shop_name} - Order Confirmation` : `${options.store_shop_name} - Bestellbestätigung`, '', { name: senderName, htmlBody: body });
}

// =====================
// Email to Shop Manager
// =====================
function sendShopManagerEmail(data) {
  const { name: senderName } = getGmailIdentity();

  const productList = data.items.map((i) => `- ${i.quantity}x ${i.name}`).join('<br>');
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

  GmailApp.sendEmail(options.store_manager_email, `${options.store_shop_name} - Neue Direktbestellung von ${data.name}`, '', { name: senderName, htmlBody: body });
}
