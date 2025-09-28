# R Styling

> [!NOTE]
> Last update: 2025-09-14

## R commands

```.r
# Settings

## Set working directory
setwd(file.path(path.expand("~"), "Documents", "Projects", "tools"))

# Lint an entire project folder
lintr::lint_dir(linters = lintr::linters_with_defaults(line_length_linter = lintr::line_length_linter(220)))

# Automatically reformat all R files in the current project directory
styler::style_dir()
```

## Prettier / Quarto commands

```.sh
npm init -y
npm install glob

node prettier-qmd-preprocess.js "**/*.qmd"
npx prettier --write "**/*.qmd"
node -e "const fs = require('fs'); const glob = require('glob'); const files = glob.sync('**/*.qmd'); files.forEach(file => { const content = fs.readFileSync(file, 'utf8'); const lines = content.split('\n'); if (lines.length >= 2 && lines[lines.length - 2].trim() === '\$\$') { lines.splice(lines.length - 2, 1); fs.writeFileSync(file, lines.join('\n'), 'utf8'); console.log(\`Removed \$\$ from \${file}\`); } });"
```
