// WooCommerce - Elementor "Loop Grid" product search
// Last update: 2025-10-20

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

    $("#live-search").attr("placeholder", text[lang].placeholder);
    $("#no-results").text(text[lang].noResults);

    // Search expand/collapse
    var isExpanded = false;

    $("#search-icon").on("click", function (e) {
      e.preventDefault();
      if (!isExpanded) {
        $("#live-search").css({ width: "250px", padding: "10px 15px", opacity: "1" }).focus();
        isExpanded = true;
      } else if ($("#live-search").val() === "") {
        $("#live-search").css({ width: "0", padding: "10px 0", opacity: "0" });
        isExpanded = false;
        performSearch("");
      }
    });

    $(document).on("click", function (e) {
      if (!$(e.target).closest("#search-container").length && $("#live-search").val() === "") {
        $("#live-search").css({ width: "0", padding: "10px 0", opacity: "0" });
        isExpanded = false;
      }
    });

    // Search functionality
    var searchTimeout,
      checkInterval,
      lastItemCount = 0,
      stableChecks = 0;

    $("#live-search").on("keyup", function () {
      var searchValue = $(this).val().toLowerCase();
      clearTimeout(searchTimeout);
      clearInterval(checkInterval);
      $("#no-results").hide();
      lastItemCount = 0;
      stableChecks = 0;

      searchTimeout = setTimeout(function () {
        performSearch(searchValue);
      }, 300);
    });

    // Monitor new items from infinite scroll
    var observer = new MutationObserver(function () {
      var currentSearch = $("#live-search").val();
      if (currentSearch !== "") {
        $("#no-results").hide();
        performSearch(currentSearch);
      }
    });

    var container = document.querySelector(".elementor-loop-container, .products");
    if (container) observer.observe(container, { childList: true });

    function isLoading() {
      return $(".e-fas-spinner:visible, .eicon-loading:visible, .elementor-loading:visible").length > 0;
    }

    function performSearch(searchValue) {
      $("#no-results").hide();
      clearInterval(checkInterval);

      var itemsFound = 0;
      var items = $(".elementor-loop-container .elementor-loop-item, .products .product, .e-loop-item");

      items.each(function () {
        if (searchValue === "" || $(this).text().toLowerCase().indexOf(searchValue) > -1) {
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
                $("#no-results").insertAfter(".elementor-loop-container, .products").show();
              }
            }
          }
        }, 1000);
      }
    }
  }
});
