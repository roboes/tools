// Elementor - Form Select Placeholder
// Last update: 2024-07-30
// Source: https://daveden.co.uk/tutorials/elementor-pro-form-hacks-add-placeholder-to-your-required-select-fields/


document.addEventListener("DOMContentLoaded", function () {
  // Find all forms with class name "elementor-form-select"
  const forms = document.querySelectorAll(".elementor-form-select");

  // Iterate through each form
  forms.forEach(function (form) {
    // Find all select fields with the required attribute within the form
    const selects = form.querySelectorAll("select[required]");

    // Iterate through each select field
    selects.forEach(function (select) {
      // Check if the first option has a blank value before disabling and hiding it
      if (select.options.length > 0 && select.options[0].value.trim() === "") {
        select.options[0].disabled = true;
        select.options[0].hidden = true;
      }
    });
  });
});
