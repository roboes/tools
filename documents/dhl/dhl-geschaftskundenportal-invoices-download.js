// DHL Geschäftskundenportal Invoices Download - Puppeteer script that logs in, filters invoices for the previous month, and extracts their details for download
// Last update: 2025-10-03

const puppeteer = require("puppeteer");

// Settings
const username = $env.DHL_USERNAME;
const password = $env.DHL_PASSWORD;

// Define an async function to contain all your await calls
async function dhlGeschaftskundenportalInvoicesDownload() {
  const browser = await puppeteer.launch({
    headless: false,
    args: ["--no-sandbox", "--disable-setuid-sandbox", "--start-fullscreen"],
  });

  const page = await browser.newPage();

  // Settings
  await page.setViewport({ width: 1920, height: 1080 });
  await page.setUserAgent("Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/108.0.0.0 Safari/537.36");
  // page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  // Navigate to login page
  await page.goto("https://geschaeftskunden.dhl.de");

  // Cookies
  await page
    .waitForSelector("#onetrust-reject-all-handler", {
      visible: true,
      timeout: 5000,
    })
    .catch((e) => {
      console.log("Cookie consent button not found or already gone.");
    });
  if (await page.$("#onetrust-reject-all-handler")) {
    await page.click("#onetrust-reject-all-handler");
    await page.waitForNavigation();
  }

  // "Anmelden"
  await page.waitForSelector("#button-noName", { visible: true });
  await page.click("#button-noName");

  // Fill in credentials
  await page.waitForSelector("#username", { visible: true, timeout: 10000 });
  await page.type("#username", username);
  await page.type("#password", password);

  // Login
  await page.click('button[type="submit"]');
  await page.waitForNavigation();

  // Navigate to invoice section
  await page.goto("https://geschaeftskunden.dhl.de/billing/invoice/overview");

  // Display "Last 3 months" invoices
  await page.waitForSelector('[data-testid="date-range-input-field-function-icon-billingView-filter-dateRange"]');
  await page.click('[data-testid="date-range-input-field-function-icon-billingView-filter-dateRange"]');
  const presetDropdownSelector = '[data-testid="billingView-filter-dateRange-footer-presetRangeSelector"]';
  await page.waitForSelector(presetDropdownSelector, { visible: true });
  await page.select(presetDropdownSelector, "1");
  const submitButtonSelector = '[data-testid="billingView-filter-dateRange-footer-submit"]';
  await page.waitForSelector(submitButtonSelector, {
    visible: true,
    timeout: 5000,
  });
  await page.click(submitButtonSelector);

  // Define the month and year for the previous month
  const today = new Date();
  const targetDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
  const targetMonth = targetDate.getMonth();
  const targetYear = targetDate.getFullYear();

  // Get invoices details
  await page.waitForSelector('table[data-testid="billingView"] tbody > tr.dhlTable-row-default', { visible: true, timeout: 10000 });

  const invoices = await page.evaluate(
    (month, year) => {
      const table = document.querySelector('table[data-testid="billingView"]');
      const rows = Array.from(table.querySelectorAll("tbody > tr.dhlTable-row-default"));
      const data = [];

      for (const row of rows) {
        const dateCell = row.querySelector(".dhlTablecell:nth-child(2)");
        const invoiceNumberCell = row.querySelector(".dhlTablecell:nth-child(3)");
        const descriptionCell = row.querySelector(".dhlTablecell:nth-child(4)");
        const amountCell = row.querySelector(".dhlTablecell:nth-child(5)");

        if (dateCell && invoiceNumberCell && descriptionCell && amountCell) {
          const dateText = dateCell.textContent.trim();
          const parts = dateText.split(".");
          const invoiceMonth = parseInt(parts[1], 10) - 1;
          const invoiceYear = parseInt(parts[2], 10);

          if (invoiceMonth === month && invoiceYear === year) {
            const invoiceNumber = invoiceNumberCell.textContent.trim();
            const invoiceDescription = descriptionCell.textContent.trim();
            const invoiceAmount = amountCell.textContent.trim();

            const downloadButton = row.querySelector("td .svgIcon-pdf").closest("button");
            const buttonId = downloadButton ? downloadButton.id : null;

            data.push({
              date: dateText,
              invoiceNumber: invoiceNumber,
              description: invoiceDescription,
              amount: invoiceAmount,
              buttonId: buttonId,
            });
          }
        }
      }
      return data;
    },
    targetMonth,
    targetYear,
  );

  console.log("Filtered Invoices for Last Month:", invoices);

  // Close browser
  await browser.close();

  return invoices.map((invoice) => ({
    json: {
      date: invoice.dateText,
      invoiceNumber: invoice.invoiceNumber,
      description: invoice.description,
      amount: invoice.amount,
      buttonId: invoice.buttonId,
    },
  }));
}

dhlGeschaftskundenportalInvoicesDownload().catch(console.error);
