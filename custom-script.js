
jQuery(function ($) {
    // Show the loader when the cart totals are updated
    jQuery(document.body).on('updated_cart_totals', function () {
        // Show custom loader
        jQuery('.custom-loader').show();

        // Trigger page reload (hard refresh)
        location.reload(true);
    });

    // Wait for the page to load completely
    jQuery(window).on('load', function () {
        // Hide the custom loader after the page has loaded
        jQuery('.custom-loader').hide();
    });
});






 

jQuery(document).ready(function () {
    // Wait for the billing state field to exist
    var stateField = jQuery('#billing_state');

    // Ensure we are on the checkout page and the field exists
    if (stateField.length) {
        // Initialize Select2 on the billing state field
        console.log("if condition run");
        stateField.select2({
            placeholder: "Select an option…",
            allowClear: true,
            width: '100%', // Ensure full-width display
            minimumInputLength: 2, // Only start searching after 1 character is typed
            // You can also add more options like:
            // language: {
            //     noResults: function() {
            //         return "No matching states found";
            //     }
            // }
        });
    }
});

jQuery(document).ready(function () {
    // Function to initialize the counter
    function initializeCounter() {
        // Check if counter div already exists
        if (jQuery('.pp-testimonials .counter').length === 0) {
            // Create counter element and append it to .pp-testimonials
            var counterHtml = '<div class="counter"></div>';
            jQuery('.pp-testimonials').append(counterHtml);
        }

        // Update counter initially
        updateCounter();
    }

    // Initialize counter on page load
    initializeCounter();

    // Function to update the counter
    function updateCounter() {
        var activeDotIndex = jQuery('.owl-dots .owl-dot.active').index() + 1;
        var totalDots = jQuery('.owl-dots .owl-dot').length;
        jQuery('.pp-testimonials .counter').text(activeDotIndex + '/' + totalDots);
    }

    // Listen to carousel slide change event (if using Owl Carousel events)
    // Replace `.owl-carousel` with your actual Owl Carousel selector
    jQuery('.owl-carousel').on('changed.owl.carousel', function (event) {
        setTimeout(function () {
            updateCounter();
        }, 50); // Adding a slight delay to ensure DOM updates
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('body.single-product .woocommerce-Tabs-panel p').forEach(p => {
        if (p.textContent.includes('2 to 5 – 3% off')) {
            p.style.textAlign = 'center';
            p.style.fontWeight = 'bold';
        }
    });
});


function parseSize(text) {
    // Remove the trailing quote and any non-numeric characters
    text = text.replace(/"/g, '').trim();

    // Handle fractional sizes
    const parts = text.split('/');
    if (parts.length === 2) {
        // Convert fraction to decimal
        const numerator = parseFloat(parts[0]);
        const denominator = parseFloat(parts[1]);
        return numerator / denominator;
    } else {
        // Handle whole numbers or single part sizes
        return parseFloat(text);
    }
}

// Function to sort the list of items
function sortList(list) {
    const items = list.children('li').get();

    items.sort(function (a, b) {
        const sizeA = parseSize(jQuery(a).text());
        const sizeB = parseSize(jQuery(b).text());

        return sizeA - sizeB;
    });

    jQuery.each(items, function (index, item) {
        list.append(item);
    });
}

// Check if the sorting should be applied
jQuery(document).ready(function () {
    jQuery('.variants .attribute-group').each(function () {
        const $attributeGroup = jQuery(this);
        const strongText = $attributeGroup.find('strong').text();

        if (strongText.includes('Available Thicknesses:')) {
            const $list = $attributeGroup.find('.attributes-list');
            if ($list.length) {
                sortList($list);
            }
        }
    });
});
jQuery(document).ready(function () {
    jQuery('.attr-group').each(function () {
        const $attributeGroup = jQuery(this);
        const $list = $attributeGroup.find('.attr-list.available-thicknesses');
            if ($list.length) {
                sortList($list);
            }
    });
});

//Add pause on cursor functionality for related product slider
jQuery(document).ready(function ($) {
    var $slider = $('.owl-carousel');
    $slider.on('mouseenter', function () {
        $slider.trigger('stop.owl.autoplay');
    });
    $slider.on('mouseleave', function () {
        $slider.trigger('play.owl.autoplay');
    });
});


