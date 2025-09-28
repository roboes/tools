// Google Apps Script - Automatically forwards Gmail emails from specified senders, applies a label, and ensures they are not marked as spam
// Last update: 2025-04-04


// https://script.google.com → New project →
// - Editor → Services → Add a service → Gmail API
// - Triggers → Add Trigger →
// -- Choose which function to run: ForwardNewEmails
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Minutes timer
// -- Select minute interval: Every 30 minutes


function ForwardNewEmails() {

  // Settings
  var forwardingEmailTo = "email_to@email.com";
  var sendersEmailFrom = ["email_from@email.com"];
  var labelName = "Label Name";

  // Get last run time
  var lastRunStr = PropertiesService.getScriptProperties().getProperty('lastRun');
  var lastRun = lastRunStr ? new Date(Number(lastRunStr)).toISOString() : new Date().toISOString();
  if (!lastRunStr) {
    PropertiesService.getScriptProperties().setProperty('lastRun', Date.now());
  }

  Logger.log("Last run time: " + lastRun);

  // Search for emails from these senders, with attachments, and received after the last run
  var query = 'from:(' + sendersEmailFrom.join(' OR ') + ') has:attachment after:' + Utilities.formatDate(new Date(Number(lastRunStr)), Session.getScriptTimeZone(), "yyyy/MM/dd") + ' -label:' + labelName;
  Logger.log("Search query: " + query);
  var threads = GmailApp.search(query);
  Logger.log("Number of threads found: " + threads.length);

  // Loop through all threads that meet the search criteria
  for (var i = 0; i < threads.length; i++) {
    var thread = threads[i];
    var messages = thread.getMessages();
    Logger.log("Processing thread " + (i+1) + " with " + messages.length + " messages.");
    var forwarded = false; // Flag to track if the email has been forwarded

    // Check each message in the thread
    for (var j = 0; j < messages.length; j++) {
      var message = messages[j];
      Logger.log("Message " + (j+1) + " subject: " + message.getSubject());

      // Forward email
      if (!forwarded) {
        message.forward(forwardingEmailTo);

        forwarded = true; // Set the flag to true to prevent forwarding again

        // Apply the "labelName" label to the message
        var label = GmailApp.getUserLabelByName(labelName);
        if (!label) {
          label = GmailApp.createLabel(labelName); // Create the label if it doesn't exist
          Logger.log("Label created: " + labelName);
        }
        thread.addLabel(label);
        Logger.log("Label applied: " + labelName);

        // Ensure the message is not marked as spam
        // Note: GmailApp does not have an unmarkSpam function.
        // Use moveToInbox to ensure the message is in the inbox.
        thread.moveToInbox();
        Logger.log("Message moved to inbox.");

        // Stop looping through attachments after forwarding the email
        break;
      }
    }
  }

  // Update the last run time
  var currentTime = new Date().getTime();
  PropertiesService.getScriptProperties().setProperty('lastRun', currentTime);
  Logger.log("Updated last run time: " + currentTime);
}
