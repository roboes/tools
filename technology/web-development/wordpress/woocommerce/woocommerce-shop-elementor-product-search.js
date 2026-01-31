// WooCommerce - Elementor "Loop Grid" product search
// Last update: 2026-01-15

// Elementor "HTML" Element
/*
<div id="search-wrapper" style="display: flex; justify-content: flex-end; margin-bottom: 20px">
	<div id="search-container" style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 0; overflow: hidden; transition: all 0.3s ease">
		<input type="text" id="woocommerce-product-search" placeholder="Search products..." style="width: 0; padding: 10px 0; border: none; outline: none; transition: all 0.3s ease; opacity: 0; background: #6565651a; color: #777777" />
		<div id="search-icon" style="background: transparent; border: none; padding: 10px 15px; cursor: pointer; display: flex; align-items: center; color: #777777">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<circle cx="11" cy="11" r="8"></circle>
				<path d="m21 21-4.35-4.35"></path>
			</svg>
		</div>
	</div>
</div>
<style>
#search-icon {
background: transparent !important;
}
#search-icon:hover,
#search-icon:focus,
#search-icon:active {
background: transparent !important;
}
#woocommerce-product-search::placeholder {
color: #777777;
opacity: 0.7;
}
</style>
<div id="woocommerce-product-search-no-results" style="display: none; text-align: center; padding: 20px; width: 100%; clear: both; color: #777777">No products found</div>
*/

// Code snippet
jQuery(document).ready(function ($) {
  // Setup
  const $input = $('#woocommerce-product-search');
  const $container = $('.elementor-loop-container, .products');
  const $noResults = $('#woocommerce-product-search-no-results');

  // Exit if not on a product archive or if search elements are missing
  if (!$('body').hasClass('post-type-archive-product') || !$input.length) return;

  // Multi-language support
  const lang = $('html').attr('lang').startsWith('de') ? 'de' : 'en';
  const uiText = {
    en: { placeholder: 'Search products...', noResults: 'No products found' },
    de: { placeholder: 'Produkte durchsuchen...', noResults: 'Keine Produkte gefunden' },
  };
  $input.attr('placeholder', uiText[lang].placeholder);
  $noResults.text(uiText[lang].noResults);

  // Filtering logic
  function performFilter() {
    // Normalize search value: lowercase and strip accents (e.g., "é" becomes "e")
    const val = $input
      .val()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
    const $items = $container.find('.elementor-loop-item, .product, .e-loop-item');
    let foundCount = 0;

    $items.each(function () {
      // Normalize item text for a more accurate search match
      const text = $(this)
        .text()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
      const isMatch = text.includes(val);
      $(this).toggle(isMatch); // Show if matches, hide if not
      if (isMatch) foundCount++;
    });

    // Show "No Results" message only if search is active and nothing is found
    foundCount === 0 && val !== '' ? $noResults.show() : $noResults.hide();
  }

  // Input performance (debouncing): prevents the filter from running on every single keystroke to save CPU power
  let debounceTimer;
  $input.on('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(performFilter, 150); // Waits for 150ms pause in typing
  });

  // Infinite scroll compatibility: watch for new products being added to the dom (e.g., via "Load More" buttons)
  const observer = new MutationObserver(() => {
    if ($input.val() !== '') performFilter();
  });

  if ($container.length) {
    observer.observe($container[0], { childList: true });
  }

  // UI interaction: search bar expansion
  function toggleSearchBar(open) {
    $input.css({
      width: open ? '250px' : '0',
      padding: open ? '10px 15px' : '10px 0',
      opacity: open ? '1' : '0',
    });
    if (open) $input.focus();
  }

  // Toggle on icon click
  $('#search-icon').on('click', function (e) {
    e.stopPropagation(); // Prevents the document click below from firing immediately
    const isCurrentlyOpen = $input.width() > 0;
    toggleSearchBar(!isCurrentlyOpen);
  });

  // Auto-collapse when clicking outside (only if input is empty)
  $(document).on('click', function (e) {
    const isOutside = !$(e.target).closest('#search-container').length;
    const isEmpty = $input.val() === '';

    if (isOutside && isEmpty) {
      toggleSearchBar(false);
    }
  });
});
