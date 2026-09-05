# R Styling

> [!NOTE]  
> Last update: 2026-09-05

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
```
