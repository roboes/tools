// WooCommerce - Gift Card Redemption Google Apps Script
// Last update: 2026-01-27

function doGet(e) {
  var action = e.parameter.action;

  // Return all order numbers from multiple sheets as JSON array
  if (action === 'getAllOrderNumbers') {
    var spreadsheet = SpreadsheetApp.getActiveSpreadsheet();
    var sheetNames = ['Trainings', 'Open', 'Deleted'];
    var allOrderNumbers = [];

    for (var i = 0; i < sheetNames.length; i++) {
      var sheet = spreadsheet.getSheetByName(sheetNames[i]);

      if (!sheet) {
        Logger.log('Sheet "' + sheetNames[i] + '" not found - skipping');
        continue;
      }

      var lastRow = sheet.getLastRow();
      if (lastRow < 2) {
        continue; // No data in this sheet
      }

      // Get order numbers from column 7 (G)
      var columnValues = sheet.getRange(2, 7, lastRow - 1).getValues();
      var orderNumbers = columnValues
        .flat()
        .filter(function (val) {
          return val !== '';
        })
        .map(function (val) {
          return val.toString();
        });

      allOrderNumbers = allOrderNumbers.concat(orderNumbers);
    }

    // Remove duplicates
    var uniqueOrderNumbers = allOrderNumbers.filter(function (value, index, self) {
      return self.indexOf(value) === index;
    });

    Logger.log('Found ' + uniqueOrderNumbers.length + ' unique order numbers across all sheets');

    return ContentService.createTextOutput(JSON.stringify(uniqueOrderNumbers)).setMimeType(ContentService.MimeType.JSON);
  }

  return ContentService.createTextOutput('Invalid_Action').setMimeType(ContentService.MimeType.TEXT);
}

function doPost(e) {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName('Trainings');

  Logger.log('Received POST request');
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

    sheet.appendRow([
      insertedDate,
      productVariationAppointmentDate,
      productVariationAppointmentTime,
      productName,
      productQuantity,
      productVariationOwnPortafilterMachine,
      orderNumber,
      giftCardId,
      customerName,
      customerEmail,
      customerPhone,
      customerOrderNotes,
    ]);

    Logger.log('Data appended to sheet');

    return ContentService.createTextOutput('Success').setMimeType(ContentService.MimeType.JSON);
  } catch (error) {
    Logger.log('Error: ' + error.toString());
    return ContentService.createTextOutput('Error: ' + error.toString()).setMimeType(ContentService.MimeType.JSON);
  }
}
