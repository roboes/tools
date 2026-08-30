# R Styling

> [!NOTE]  
> Last update: 2026-08-09

## Code Linter and Formatter

```sh
# Lint and format R code
Rscript -e '
for (package in c("lintr","styler")) if (!requireNamespace(package, quietly=TRUE)) install.packages(package)
lintr::lint_dir(linters = lintr::linters_with_defaults(line_length_linter = lintr::line_length_linter(220)))
styler::style_dir(filetype = c("qmd", "R", "r"))
'

# Protect lone colons
node prettier-qmd-preprocess.js "**/*.qmd"

# Remove trailing $$ before final newline node
node -e '
const fs = require("fs");
const files = fs.readdirSync(".", { recursive: true }).filter(f => f.endsWith(".qmd"));
files.forEach(file => {
  const content = fs.readFileSync(file, "utf8");
  const lines = content.split("\n");
  if (lines.length >= 2 && lines[lines.length - 2].trim() === "$$") {
    lines.splice(lines.length - 2, 1);
    fs.writeFileSync(file, lines.join("\n"), "utf8");
    console.log(`Removed $$from${file}`);
  }
});
'
```
