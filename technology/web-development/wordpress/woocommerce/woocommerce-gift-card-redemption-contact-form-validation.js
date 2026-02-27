// WooCommerce - Gift Card Redemption Contact Form Validation
// Last update: 2026-02-03

document.addEventListener('DOMContentLoaded', function () {
  const giftCardInput = document.getElementById('gift-card-id');
  const warningDiv = document.getElementById('gift-card-warning');
  const submitBtn = document.querySelector('.wpcf7-submit');

  if (giftCardInput) {
    giftCardInput.addEventListener('input', function () {
      const val = this.value.toUpperCase();

      if (val.startsWith('KA-')) {
        // Show warning
        warningDiv.style.display = 'block';

        // Block the button
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.style.opacity = '0.5';
          submitBtn.style.cursor = 'not-allowed';
        }
      } else {
        // Hide warning
        warningDiv.style.display = 'none';

        // Re-enable the button
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.style.opacity = '1';
          submitBtn.style.cursor = 'pointer';
        }
      }
    });
  }
});
