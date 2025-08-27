jQuery(document).ready(function($) {
    'use strict';
    
    console.log('Enhanced add-to-cart script loaded');
    
    // Enhanced debug logging
    function debugLog() {
        var args = Array.prototype.slice.call(arguments);
        var timestamp = new Date().toISOString();
        args.unshift('[Add-to-Cart Debug ' + timestamp + ']');
        console.log.apply(console, args);
    }
    
    // Debug initial state
    debugLog('Script initialization');
    debugLog('jQuery version:', $.fn.jquery);
    debugLog('WC params available:', typeof wc_add_to_cart_params !== 'undefined');
    debugLog('Lilac vars available:', typeof lilac_vars !== 'undefined');
    
    // Find all add to cart buttons and log them
    var buttons = $('.single_add_to_cart_button, button[name="add-to-cart"], .add_to_cart_button');
    debugLog('Found buttons:', buttons.length);
    buttons.each(function(i) {
        debugLog('Button ' + i + ':', $(this).attr('class'), 'Text:', $(this).text().trim());
    });
    
    // Simple redirect to checkout after add to cart
    $(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {
        debugLog('Product added to cart event triggered');
        debugLog('Fragments:', fragments);
        debugLog('Cart hash:', cart_hash);
        debugLog('Button:', $button ? $button.attr('class') : 'No button');
        
        // Simple redirect to checkout
        var checkoutUrl = '/checkout/';
        if (typeof lilac_vars !== 'undefined' && lilac_vars.checkout_url) {
            checkoutUrl = lilac_vars.checkout_url;
        }
        
        debugLog('Redirecting to checkout:', checkoutUrl);
        
        // Add small delay to ensure cart is updated
        setTimeout(function() {
            window.location.href = checkoutUrl;
        }, 500);
    });
    
    // Try capturing all clicks first
    $(document).on('click', '*', function(e) {
        var $target = $(e.target);
        if ($target.hasClass('single_add_to_cart_button') || 
            $target.attr('name') === 'add-to-cart' || 
            $target.hasClass('add_to_cart_button') ||
            ($target.is('button') && $target.closest('form.cart').length > 0)) {
            debugLog('Captured click on potential add-to-cart button');
            debugLog('Target classes:', $target.attr('class'));
            debugLog('Target name:', $target.attr('name'));
            debugLog('Target text:', $target.text().trim());
            handleAddToCartClick($target, e);
        }
    });
    
    // Multiple event handlers to catch all possible button clicks
    $(document).on('click', '.single_add_to_cart_button', function(e) {
        debugLog('Single add to cart button clicked (selector 1)');
        handleAddToCartClick($(this), e);
    });
    
    $(document).on('click', 'button[name="add-to-cart"]', function(e) {
        debugLog('Add to cart button clicked (selector 2)');
        handleAddToCartClick($(this), e);
    });
    
    $(document).on('click', '.add_to_cart_button', function(e) {
        debugLog('Add to cart button clicked (selector 3)');
        handleAddToCartClick($(this), e);
    });
    
    // Catch all button clicks in cart forms
    $(document).on('click', 'form.cart button[type="submit"]', function(e) {
        debugLog('Cart form submit button clicked');
        handleAddToCartClick($(this), e);
    });
    
    // Try direct binding after a delay to catch dynamically created buttons
    setTimeout(function() {
        debugLog('Attempting direct binding to buttons');
        $('.single_add_to_cart_button').off('click.lilac').on('click.lilac', function(e) {
            debugLog('Direct bound button clicked');
            handleAddToCartClick($(this), e);
        });
    }, 1000);
    
    function handleAddToCartClick($button, e) {
        debugLog('Handling add to cart click');
        
        var $form = $button.closest('form.cart, form');
        
        debugLog('Button classes:', $button.attr('class'));
        debugLog('Button name:', $button.attr('name'));
        debugLog('Button type:', $button.attr('type'));
        debugLog('Form found:', $form.length > 0);
        debugLog('Form classes:', $form.attr('class'));
        debugLog('WC params available:', typeof wc_add_to_cart_params !== 'undefined');
        
        // Get product data
        var productId = $button.val() || $button.attr('value');
        var quantity = $form.find('input[name="quantity"]').val() || 1;
        
        debugLog('Product ID:', productId);
        debugLog('Quantity:', quantity);
        
        // If no form or WooCommerce AJAX not available, let it submit normally
        if (!$form.length || typeof wc_add_to_cart_params === 'undefined' || !productId) {
            debugLog('Using normal form submission - missing requirements');
            return true;
        }
        
        // Prevent default form submission
        e.preventDefault();
        
        // Update button text
        var originalText = $button.text();
        $button.text('מועבר לתשלום...');
        $button.prop('disabled', true);
        
        // Build AJAX data
        var ajaxData = {
            action: 'woocommerce_add_to_cart',
            product_id: productId,
            quantity: quantity
        };
        
        debugLog('Making AJAX request with data:', ajaxData);
        
        // Make the AJAX request
        $.ajax({
            url: wc_add_to_cart_params.ajax_url,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                debugLog('AJAX Success Response:', response);
                
                // Parse response if it's a string
                var jsonResponse = response;
                if (typeof response === 'string') {
                    try {
                        jsonResponse = JSON.parse(response);
                    } catch (e) {
                        debugLog('Response is not JSON:', response);
                    }
                }
                
                if (jsonResponse && jsonResponse.fragments) {
                    debugLog('Cart fragments received, triggering added_to_cart event');
                    
                    // Update cart fragments
                    if (jsonResponse.fragments) {
                        $.each(jsonResponse.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                    
                    // Trigger the added_to_cart event
                    $(document.body).trigger('added_to_cart', [jsonResponse.fragments, jsonResponse.cart_hash, $button]);
                } else {
                    debugLog('No fragments in response, redirecting directly');
                    // If no fragments, redirect immediately
                    var checkoutUrl = '/checkout/';
                    if (typeof lilac_vars !== 'undefined' && lilac_vars.checkout_url) {
                        checkoutUrl = lilac_vars.checkout_url;
                    }
                    window.location.href = checkoutUrl;
                }
            },
            error: function(xhr, status, error) {
                debugLog('AJAX Error:', status, error);
                debugLog('Response Text:', xhr.responseText);
                
                // Restore button
                $button.text(originalText);
                $button.prop('disabled', false);
                
                // Fall back to normal form submission
                debugLog('Falling back to normal form submission');
                $form.off('submit').submit();
            }
        });
        
        return false;
    }
    
    // Debug AJAX requests
    $(document).ajaxSend(function(event, xhr, settings) {
        if (settings.url && (settings.url.includes('add_to_cart') || settings.url.includes('wc-ajax'))) {
            debugLog('AJAX request starting:', settings.url);
            debugLog('Data:', settings.data);
        }
    });
    
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && (settings.url.includes('add_to_cart') || settings.url.includes('wc-ajax'))) {
            debugLog('AJAX request completed:', settings.url);
            debugLog('Status:', xhr.status);
            debugLog('Response:', xhr.responseText);
        }
    });
});
