// WooCommerce - Elementor "Loop Grid" product search
// Last update: 2025-10-21

// Elementor "HTML" Element
// <div id="search-wrapper" style="display: flex; justify-content: flex-end; margin-bottom: 20px">
// <div id="search-container" style="display: flex; align-items: center; border: 1px solid #ddd; border-radius: 0; overflow: hidden; transition: all 0.3s ease">
// <input type="text" id="woocommerce-product-search" placeholder="Search products..." style="width: 0; padding: 10px 0; border: none; outline: none; transition: all 0.3s ease; opacity: 0; background: #6565651a; color: #777777" />
// <div id="search-icon" style="background: transparent; border: none; padding: 10px 15px; cursor: pointer; display: flex; align-items: center; color: #777777">
// <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
// <circle cx="11" cy="11" r="8"></circle>
// <path d="m21 21-4.35-4.35"></path>
// </svg>
// </div>
// </div>
// </div>

// <style>
// #search-icon {
// background: transparent !important;
// }
// #search-icon:hover,
// #search-icon:focus,
// #search-icon:active {
// background: transparent !important;
// }
// #woocommerce-product-search::placeholder {
// color: #777777;
// opacity: 0.7;
// }
// </style>

// <div id="woocommerce-product-search-no-results" style="display: none; text-align: center; padding: 20px; width: 100%; clear: both; color: #777777">No products found</div>

// Code snippet
jQuery(document).ready(function ($) {
  if ($("body").hasClass("post-type-archive-product")) {
    // Language detection
    var lang = $("html").attr("lang").startsWith("de") ? "de" : "en";
    var text = {
      en: { placeholder: "Search products...", noResults: "No products found" },
      de: {
        placeholder: "Produkte durchsuchen...",
        noResults: "Keine Produkte gefunden",
      },
    };

    $("#woocommerce-product-search").attr("placeholder", text[lang].placeholder);
    $("#woocommerce-product-search-no-results").text(text[lang].noResults);

    // Search expand/collapse
    var isExpanded = false;

    $("#search-icon").on("click", function (e) {
      e.preventDefault();
      if (!isExpanded) {
        $("#woocommerce-product-search").css({ width: "250px", padding: "10px 15px", opacity: "1" }).focus();
        isExpanded = true;
      } else if ($("#woocommerce-product-search").val() === "") {
        $("#woocommerce-product-search").css({ width: "0", padding: "10px 0", opacity: "0" });
        isExpanded = false;
        performSearch("");
      }
    });

    $(document).on("click", function (e) {
      if (!$(e.target).closest("#search-container").length && $("#woocommerce-product-search").val() === "") {
        $("#woocommerce-product-search").css({ width: "0", padding: "10px 0", opacity: "0" });
        isExpanded = false;
      }
    });

    // Search functionality
    var searchTimeout,
      checkInterval,
      lastItemCount = 0,
      stableChecks = 0;

    $("#woocommerce-product-search").on("keyup", function () {
      var searchValue = $(this).val().toLowerCase();
      clearTimeout(searchTimeout);
      clearInterval(checkInterval);
      $("#woocommerce-product-search-no-results").hide();
      lastItemCount = 0;
      stableChecks = 0;

      searchTimeout = setTimeout(function () {
        performSearch(searchValue);
      }, 300);
    });

    // Monitor new items from infinite scroll
    var observer = new MutationObserver(function () {
      var currentSearch = $("#woocommerce-product-search").val();
      if (currentSearch !== "") {
        $("#woocommerce-product-search-no-results").hide();
        performSearch(currentSearch);
      }
    });

    var container = document.querySelector(".elementor-loop-container, .products");
    if (container) observer.observe(container, { childList: true });

    function isLoading() {
      return $(".e-fas-spinner:visible, .eicon-loading:visible, .elementor-loading:visible").length > 0;
    }

    function normalizeString(str) {
      return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }

    function performSearch(searchValue) {
      $("#woocommerce-product-search-no-results").hide();
      clearInterval(checkInterval);

      var itemsFound = 0;
      var items = $(".elementor-loop-container .elementor-loop-item, .products .product, .e-loop-item");
      var normalizedSearch = normalizeString(searchValue);

      items.each(function () {
        if (searchValue === "" || normalizeString($(this).text().toLowerCase()).indexOf(normalizedSearch) > -1) {
          $(this).show();
          itemsFound++;
        } else {
          $(this).hide();
        }
      });

      if (searchValue !== "" && itemsFound === 0) {
        lastItemCount = items.length;
        stableChecks = 0;

        checkInterval = setInterval(function () {
          var currentCount = $(".elementor-loop-container .elementor-loop-item, .products .product, .e-loop-item").length;
          var loading = isLoading();

          if (loading || currentCount !== lastItemCount) {
            stableChecks = 0;
            lastItemCount = currentCount;
            performSearch(searchValue);
          } else {
            stableChecks++;
            if (stableChecks >= 4) {
              clearInterval(checkInterval);
              if ($(".elementor-loop-container .elementor-loop-item:visible, .products .product:visible, .e-loop-item:visible").length === 0) {
                $("#woocommerce-product-search-no-results").insertAfter(".elementor-loop-container, .products").show();
              }
            }
          }
        }, 1000);
      }
    }
  }
});
