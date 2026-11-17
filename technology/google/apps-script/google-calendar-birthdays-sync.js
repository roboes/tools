// Google Apps Script - Syncs contact birthdays from the People API to a dedicated Google Calendar (diff-based)
// Last update: 2026-11-17

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
  pause_ms: 250,
};

function getOrCreateCalendar(name) {
  const found = CalendarApp.getCalendarsByName(name);
  const cal = found.find((c) => c.isOwnedByMe()) || CalendarApp.createCalendar(name);
  if (cal.getTimeZone() !== Session.getScriptTimeZone()) {
    cal.setTimeZone(Session.getScriptTimeZone());
  }
  return cal;
}

// Build a timezone-safe key like "2026-12-25|Jane Doe's Birthday"
function eventKey(title, date, tz) {
  const dateStr = Utilities.formatDate(new Date(date), tz, 'yyyy-MM-dd');
  return `${dateStr}|${title}`;
}

function fetchWantedBirthdays(todayStart, tz) {
  const wanted = new Map();
  let pageToken = undefined;

  do {
    const response = People.People.Connections.list('people/me', {
      personFields: 'names,birthdays',
      pageSize: options.max_contacts_per_page,
      pageToken,
    });

    (response.connections || []).forEach((person) => {
      const bd = person.birthdays?.[0]?.date;
      if (!bd?.month || !bd?.day) return;

      const name = person.names?.[0]?.displayName || 'Unknown';
      const date = new Date(todayStart.getFullYear(), bd.month - 1, bd.day);
      if (date < todayStart) date.setFullYear(date.getFullYear() + 1);

      const title = `${name}'s Birthday`;
      wanted.set(eventKey(title, date, tz), { title, date });
    });

    pageToken = response.nextPageToken;
  } while (pageToken);

  return wanted;
}

function syncBirthdays() {
  const calendar = getOrCreateCalendar(options.calendar_name);
  const tz = Session.getScriptTimeZone();

  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);
  const oneYearOut = new Date(todayStart.getFullYear() + 1, todayStart.getMonth(), todayStart.getDate());

  const wanted = fetchWantedBirthdays(todayStart, tz);

  // Index existing events; delete duplicates and anything no longer wanted
  const existing = new Set();
  let deleted = 0;
  calendar.getEvents(todayStart, oneYearOut).forEach((e) => {
    // Skip non-all-day events just in case something else was manually added
    if (!e.isAllDayEvent()) return;

    const key = eventKey(e.getTitle(), e.getAllDayStartDate(), tz);
    if (wanted.has(key) && !existing.has(key)) {
      existing.add(key);
    } else {
      e.deleteEvent();
      deleted++;
      Utilities.sleep(options.pause_ms);
    }
  });

  // Create only what's missing
  let created = 0;
  wanted.forEach(({ title, date }, key) => {
    if (existing.has(key)) return;
    calendar.createAllDayEvent(title, date);
    created++;
    Utilities.sleep(options.pause_ms);
  });

  Logger.log(`Done. Created ${created}, deleted ${deleted}, unchanged ${existing.size}.`);
}
