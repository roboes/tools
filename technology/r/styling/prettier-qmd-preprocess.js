// Preprocess Quarto files (.qmd) for Prettier
// Last update: 2026-08-09

// Notes: Preprocess a Quarto file to fix common formatting issues with Prettier

// Imports
const fs = require('fs');

// Get files
const pattern = process.argv[2] || '**/*.qmd';
const files = fs.globSync(pattern);
if (files.length === 0) {
  console.error('No files matched the pattern:', pattern);
  process.exit(1);
}

// Functions

// Function to add a blank line before lines that contain only colons, but only if the previous line is not empty
function preprocessQuartoFile(filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');
    let linesAdded = false;

    const modifiedLines = lines.reduce((acc, line, i) => {
      const trimmed = line.trim();
      const isColonOnly = /^:+$/.test(trimmed);

      if (isColonOnly && acc.length > 0 && acc[acc.length - 1].trim() !== '') {
        acc.push('');
        linesAdded = true;
      }

      acc.push(line);
      return acc;
    }, []);

    if (linesAdded) {
      fs.writeFileSync(filePath, modifiedLines.join('\n'), 'utf8');
      console.log(`Successfully added blank lines to ${filePath}`);
    }
  } catch (err) {
    console.error('Error processing file:', err.message);
    process.exit(1);
  }
}

// Apply preprocessing
files.forEach((file) => preprocessQuartoFile(file));
