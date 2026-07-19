// Google Apps Script - Syncs contact birthdays from the People API to a dedicated Google Calendar, recreating events daily
// Last update: 2026-07-19

// https://script.google.com → New project →
// - Editor → Services → Add a service → Peopleapi
// - Triggers → Add Trigger →
// -- Choose which function to run: syncBirthdays
// -- Choose which deployment should run: Head
// -- Select event source: Time-driven
// -- Select type of time based trigger: Day timer
// -- Select time of day: Midnight to 1 am

// Settings
const options = {
  calendar_name: 'Birthdays',
  max_contacts_per_page: 250,
};

function getOrCreateCalendar(name) {
  const found = CalendarApp.getCalendarsByName(name);
  return found.length > 0 ? found[0] : CalendarApp.createCalendar(name);
}

function syncBirthdays() {
  const calendar = getOrCreateCalendar(options.calendar_name);
  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);

  // Wipe from today (not "now") so today's birthdays aren't skipped
  const oneYearOut = new Date(todayStart.getFullYear() + 1, todayStart.getMonth(), todayStart.getDate());
  calendar.getEvents(todayStart, oneYearOut).forEach((e) => e.deleteEvent());

  // Fetch contacts via People API (ContactsApp is deprecated)
  // Requires: Services → People API → Add
  let pageToken = '';
  do {
    const response = People.People.Connections.list('people/me', {
      personFields: 'names,birthdays',
      pageSize: options.max_contacts_per_page,
      pageToken: pageToken,
    });

    (response.connections || [])
      .sort((a, b) => {
        const nameA = a.names?.[0]?.displayName || '';
        const nameB = b.names?.[0]?.displayName || '';
        return nameA.localeCompare(nameB);
      })
      .forEach((person) => {
        const bd = person.birthdays?.[0]?.date;
        if (!bd?.month || !bd?.day) return;

        const name = person.names?.[0]?.displayName || 'Unknown';
        const month = bd.month - 1; // People API is 1-based, JS is 0-based
        const day = bd.day; // .day is the day of month (not .getDay()!)

        let date = new Date(todayStart.getFullYear(), month, day);
        if (date < todayStart) date.setFullYear(date.getFullYear() + 1);

        calendar.createAllDayEvent(`${name}'s Birthday`, date);
        Logger.log(`Synced: ${name}`);
      });

    pageToken = response.nextPageToken;
  } while (pageToken);

  Logger.log('Done.');
}
