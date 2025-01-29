// Google Apps Script - Automatically forwards Gmail emails from specified senders with .pdf and .tiff attachments only, applies a label, and ensures they are not marked as spam
// Last update: 2025-01-22

// https://script.google.com > New project >
// - Editor > Services > Add a service > Gmail API
// - Triggers > Add Trigger >
// -- Choose which function to run: forwardNewEmails
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Minutes timer
// -- Select minute interval: Every 30 minutes

function forwardNewEmails() {
  // Get last run time
  var lastRun = PropertiesService.getScriptProperties().getProperty('lastRun'); // Get last run time

  if (!lastRun || isNaN(Number(lastRun))) {
    // If this is the first time the script runs or lastRun is invalid, set the time to now
    lastRun = new Date().getTime();
    PropertiesService.getScriptProperties().setProperty('lastRun', lastRun); // Save the current timestamp
  } else {
    // Convert lastRun to a number (milliseconds since epoch)
    lastRun = Number(lastRun);
  }

  // Convert lastRun to ISO string for use in the search query
  lastRun = new Date(lastRun).toISOString();

  // Settings
  var forwardingEmailTo = "email_to@email.com";
  var sendersEmailFrom = ["email_from@email.com"];
  var labelName = "Label Name";

  // Search for emails from these senders, with attachments, and received after the last run
  var query = 'from:(' + sendersEmailFrom.join(' OR ') + ') has:attachment after:' + lastRun;
  var threads = GmailApp.search(query);

  // Loop through all threads that meet the search criteria
  for (var i = 0; i < threads.length; i++) {
    var thread = threads[i];
    var messages = thread.getMessages();
    var forwarded = false; // Flag to track if the email has been forwarded

    // Check each message in the thread
    for (var j = 0; j < messages.length; j++) {
      var message = messages[j];
      var attachments = message.getAttachments();
      var allowedAttachments = [];

      // Loop through all attachments in the message and filter for PDFs and TIFFs
      for (var k = 0; k < attachments.length; k++) {
        var attachment = attachments[k];

        // Check if the attachment is a PDF or TIFF file
        if (attachment.getContentType() === 'application/pdf' || attachment.getContentType() === 'image/tiff') {
          allowedAttachments.push(attachment); // Add PDF or TIFF to the array
        }
      }

      // If there are allowed attachments, forward the email with only PDFs and TIFFs
      if (allowedAttachments.length > 0 && !forwarded) {
        var subject = message.getSubject();
        var body = message.getBody(); // You can customize this if needed

        // Create a new email with only the allowed attachments (PDFs and TIFFs)
        GmailApp.sendEmail(forwardingEmailTo, subject, body, {
          attachments: allowedAttachments
        });

        forwarded = true; // Set the flag to true to prevent forwarding again

        // Apply the "labelName" label to the message
        var label = GmailApp.getUserLabelByName(labelName);
        if (!label) {
          label = GmailApp.createLabel(labelName); // Create the label if it doesn't exist
        }
        message.addLabel(label);

        // Ensure the message is not marked as spam
        message.markSpam(false);
        break; // Stop looping through attachments after forwarding the email
      }
    }
  }

  // Update the last run time
  PropertiesService.getScriptProperties().setProperty('lastRun', new Date().getTime());
}
