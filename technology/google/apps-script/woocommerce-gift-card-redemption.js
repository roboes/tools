// WooCommerce - Gift Card Redemption Google Apps Script
// Last update: 2025-01-22

function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Trainings');

  Logger.log("Received POST request");
  Logger.log(e.postData.contents);

  try {
    var data = JSON.parse(e.postData.contents);

    var insertedDate = data['inserted_date'] || '';
    var productVariationAppointmentDate = data['product_variation_appointment_date'] || '';
    var productVariationAppointmentTime = data['product_variation_appointment_time'] || '';
    var productName = data['product_name'] || '';
    var productQuantity = data['product_quantity'] || '';
    var productVariationOwnPortafilterMachine = data['product_variation_own_portafilter_machine'] || '';
    var orderNumber = data['order_number'] || '';
    var giftCardId = data['gift_card_id'] || '';
    var customerName = data['customer_name'] || '';
    var customerEmail = data['customer_email'] || '';
    var customerPhone = data['customer_phone'] ? "'" + data['customer_phone'] : '';
    var customerOrderNotes = data['customer_order_notes'] || '';

    sheet.appendRow([insertedDate, productVariationAppointmentDate, productVariationAppointmentTime, productName, productQuantity, productVariationOwnPortafilterMachine, orderNumber, giftCardId, customerName, customerEmail, customerPhone, customerOrderNotes]);

    Logger.log("Data appended to sheet");

    return ContentService.createTextOutput(JSON.stringify({result: 'Success'})).setMimeType(ContentService.MimeType.JSON);
  } catch (error) {
    Logger.log("Error: " + error.toString());
    return ContentService.createTextOutput(JSON.stringify({result: 'Error', message: error.toString()})).setMimeType(ContentService.MimeType.JSON);
  }
}
