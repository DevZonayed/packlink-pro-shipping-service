(function ($) {
    function hideThemeSteps() {
        var selectors = [
            '.woocommerce-steps',
            '.woocommerce-checkout-steps',
            '.woocommerce-breadcrumb',
            '.cart-steps',
            '.checkout-steps',
            '.progress-tracker'
        ];
        selectors.forEach(function (sel) {
            $(sel).hide();
        });
    }

    function buildSteps() {
        var $form = $('form.woocommerce-checkout');
        if (!$form.length || $('#pl-multistep-checkout').length) {
            return;
        }

        // Base containers
        var $customerDetails = $('#customer_details');
        var $orderReview = $('#order_review');
        if (!$customerDetails.length || !$orderReview.length) {
            return;
        }

        // Wrap in multistep container
        var $wrapper = $('<div id="pl-multistep-checkout" class="pl-multistep-checkout"></div>');
        var $stepsHeader = $(
            '<div class="pl-steps-header">' +
            '<div class="pl-step-item pl-step-1 active"><span class="num">1</span><span class="label">Billing & Payment</span></div>' +
            '<div class="pl-step-item pl-step-2"><span class="num">2</span><span class="label">Review & Place Order</span></div>' +
            '</div>'
        );

        var $step1 = $('<section class="pl-step pl-step1 active"></section>'); // Was step 2
        var $step2 = $('<section class="pl-step pl-step2"></section>'); // Was step 3

        // Step 1: Left billing, right order review (move place order out)
        var $twoCol = $('<div class="pl-two-col"></div>');
        var $left = $('<div class="pl-col pl-left"></div>');
        var $right = $('<div class="pl-col pl-right"></div>');

        $left.append($customerDetails.detach());
        $right.append($orderReview.detach());

        // Detach the entire place-order container to move to step 2
        var $placeContainer = $right.find('.place-order');
        if ($placeContainer.length) {
            $placeContainer = $placeContainer.detach();
        }

        $twoCol.append($left).append($right);
        $step1.append($twoCol);

        // Step 2: Review (two columns) and move place_order here
        var $reviewWrap = $('<div class="pl-review-wrap"></div>');
        var $reviewTwoCol = $('<div class="pl-review-two-col"></div>');
        var $reviewLeft = $('<div class="pl-col pl-left"></div>');
        var $reviewRight = $('<div class="pl-col pl-right"></div>');

        function buildBillingSummary() {
            var fields = [
                { id: '#billing_first_name', label: 'First name' },
                { id: '#billing_last_name', label: 'Last name' },
                { id: '#billing_company', label: 'Company' },
                { id: '#billing_address_1', label: 'Address' },
                { id: '#billing_address_2', label: 'Address 2' },
                { id: '#billing_city', label: 'City' },
                { id: '#billing_postcode', label: 'Postcode' },
                { id: '#billing_country', label: 'Country' },
                { id: '#billing_state', label: 'State' },
                { id: '#billing_phone', label: 'Phone' },
                { id: '#billing_email', label: 'Email' }
            ];
            var $box = $('<div class="pl-review-box"><h4>Billing Details</h4><div class="pl-review-grid"></div></div>');
            var $grid = $box.find('.pl-review-grid');
            fields.forEach(function (f) {
                var $el = $(f.id);
                if ($el.length) {
                    var val = $el.is('select') ? $el.find('option:selected').text() : $el.val();
                    if (val) {
                        $grid.append('<div class="pl-row"><span class="pl-label">' + f.label + '</span><span class="pl-value">' + $('<div/>').text(val).html() + '</span></div>');
                    }
                }
            });
            return $box;
        }

        function buildPaymentSummary() {
            var $methods = $('#payment');
            var $box = $('<div class="pl-review-box"><h4>Payment Method</h4><div class="pl-payment-summary"></div><div class="pl-card-logos"></div></div>');
            if ($methods.length) {
                var $checked = $methods.find('input[name=payment_method]:checked');
                var label = '';
                if ($checked.length) {
                    var id = $checked.attr('id');
                    var $label = $methods.find('label[for=' + id + ']');
                    label = $label.text() || $checked.val();
                }
                $box.find('.pl-payment-summary').text(label || 'Not selected');
            }

            // Append brand logos (if localized data exists)
            try {

                if (window.packlink_checkout_steps && window.packlink_checkout_steps.brand_logos) {
                    var bl = window.packlink_checkout_steps.brand_logos;
                    var $logos = $box.find('.pl-card-logos');
                    console.log($logos)
                    var items = [
                        { key: 'maestro', alt: 'Maestro' },
                        { key: 'mastercard', alt: 'Mastercard' },
                        { key: 'visa', alt: 'VISA' }
                    ];
                    items.forEach(function (it) {
                        var img = bl[it.key];
                        var href = bl.links && bl.links[it.key] ? bl.links[it.key] : '#';
                        if (img) {
                            var $a = $('<a/>', { href: href, target: '_blank', rel: 'noopener' });
                            var $img = $('<img/>', { src: img, alt: it.alt });
                            $a.append($img);
                            $logos.append($a);
                        }
                    });
                }
            } catch (e) { }
            return $box;
        }

        // Left column: billing + payment summaries
        $reviewLeft.append(buildBillingSummary());
        $reviewLeft.append(buildPaymentSummary());

        // Right column: package/shipping info
        // Shipping details review block removed as requested

        // Move the actual place_order container here so submission works
        if ($placeContainer && $placeContainer.length) {
            $placeContainer.show();
            $reviewRight.append($placeContainer);
        } else {
            var $placeOrder = $('#place_order');
            if ($placeOrder.length) {
                var $placeRow = $placeOrder.closest('.form-row');
                if ($placeRow.length) {
                    $placeRow.show();
                    $reviewRight.append($placeRow.detach());
                } else {
                    $placeOrder.show();
                    $reviewRight.append($placeOrder.detach());
                }
            }
        }

        $reviewTwoCol.append($reviewLeft).append($reviewRight);
        $reviewWrap.append($reviewTwoCol);
        $step2.append($reviewWrap);

        // Nav controls
        var $nav1 = $('<div class="pl-nav"><button type="button" class="button pl-next">Next</button></div>');
        var $nav2 = $('<div class="pl-nav"><button type="button" class="button pl-prev">Back</button></div>');

        $step1.append($nav1);
        $step2.append($nav2);

        // Assemble
        $wrapper.append($stepsHeader).append($step1).append($step2);
        $form.prepend($wrapper);

        // Handlers
        function goTo(step) {
            $('.pl-step').removeClass('active');
            $('.pl-steps-header .pl-step-item').removeClass('active completed');
            for (var i = 1; i < step; i++) {
                $('.pl-steps-header .pl-step-' + i).addClass('completed');
            }
            $('.pl-step' + step).addClass('active');
            $('.pl-steps-header .pl-step-' + step).addClass('active');
            $('html, body').animate({ scrollTop: $('#pl-multistep-checkout').offset().top - 20 }, 200);

            // Refresh summaries on step 2 (left column only)
            if (step === 2) {
                var $leftCol = $('.pl-step2 .pl-review-wrap .pl-col.pl-left');
                if ($leftCol.length) {
                    $leftCol.find('.pl-review-box').remove();
                    $leftCol.append(buildBillingSummary());
                    $leftCol.append(buildPaymentSummary());
                }
            }
        }

        $step1.on('click', '.pl-next', function () {
            goTo(2);
        });
        $step2.on('click', '.pl-prev', function () {
            goTo(1);
        });
    }

    function syncShippingDetailsOnUpdate() {
        // When checkout fragments update
        $(document.body).on('updated_checkout', function () {
            var $existing = $('#pl-multistep-checkout');
            if ($existing.length) {
                var $step2Wrap = $existing.find('.pl-step2 .pl-review-wrap');
                var $step2Right = $existing.find('.pl-step2 .pl-review-wrap .pl-col.pl-right');

                // Ensure place-order lives in step 2
                var $freshPlace = $('#order_review .place-order');
                if ($freshPlace.length) {
                    $freshPlace = $freshPlace.detach();
                    $freshPlace.show();
                    // Remove any existing duplicate in step 2 right column
                    if ($step2Right.length) {
                        $step2Right.find('.place-order').remove();
                        $step2Right.append($freshPlace);
                    } else if ($step2Wrap.length) {
                        $step2Wrap.find('.place-order').remove();
                        $step2Wrap.append($freshPlace);
                    }
                }
            } else {
                // First build
                buildSteps();
            }
        });
    }

    $(function () {
        if ($('body').hasClass('woocommerce-checkout')) {
            // hideThemeSteps();
            // buildSteps();
            // syncShippingDetailsOnUpdate();
        }
    });
})(jQuery);
