/**
 * Admin JavaScript for Adarok Divi Janitor
 */

(function($) {
    'use strict';

    /**
     * Document ready
     */
    $(document).ready(function() {
        DiviJanitor.init();
    });

    /**
     * Main Divi Janitor object
     */
    var DiviJanitor = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Tab switching
            $('.tab-button').on('click', this.switchTab);

            // Toggle usage details
            $(document).on('click', '.toggle-usage', this.toggleUsage);

            // Delete library item
            $(document).on('click', '.delete-library-item', this.deleteItem);
        },

        /**
         * Switch between tabs
         */
        switchTab: function(e) {
            e.preventDefault();

            var $button = $(this);
            var tabName = $button.data('tab');

            // Update active states
            $('.tab-button').removeClass('active');
            $button.addClass('active');

            $('.tab-content').removeClass('active');
            $('#tab-' + tabName).addClass('active');
        },

        /**
         * Toggle usage details visibility
         */
        toggleUsage: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $details = $button.siblings('.usage-details');

            $details.slideToggle(200);

            // Update button text
            if ($details.is(':visible')) {
                $button.html($button.html().replace('▼', '▲'));
            } else {
                $button.html($button.html().replace('▲', '▼'));
            }
        },

        /**
         * Delete a library item
         */
        deleteItem: function(e) {
            e.preventDefault();

            var $button = $(this);
            var postId = $button.data('item-id');
            var itemTitle = $button.data('item-title');

            // Confirm deletion
            if (!confirm(adarokDiviJanitor.confirmDelete)) {
                return;
            }

            // Disable button and show loading
            $button.prop('disabled', true).text(adarokDiviJanitor.scanningText);

            var $row = $button.closest('tr');
            $row.addClass('deleting-row');

            // Send AJAX request
            $.ajax({
                url: adarokDiviJanitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'adarok_delete_library_item',
                    nonce: adarokDiviJanitor.nonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        DiviJanitor.showNotice('success', adarokDiviJanitor.deleteSuccess);

                        // Remove row with animation
                        $row.addClass('deleted-row');
                        setTimeout(function() {
                            $row.remove();
                            DiviJanitor.updateCounts();
                            DiviJanitor.checkEmptyTables();
                        }, 500);
                    } else {
                        // Show error message
                        DiviJanitor.showNotice('error', response.data.message || adarokDiviJanitor.deleteError);

                        // Re-enable button
                        $button.prop('disabled', false).text('Delete');
                        $row.removeClass('deleting-row');
                    }
                },
                error: function(xhr, status, error) {
                    // Show error message
                    DiviJanitor.showNotice('error', adarokDiviJanitor.deleteError);

                    // Re-enable button
                    $button.prop('disabled', false).text('Delete');
                    $row.removeClass('deleting-row');
                }
            });
        },

        /**
         * Update tab counts after deletion
         */
        updateCounts: function() {
            var allCount = $('.tab-content#tab-all tr').length;
            var usedCount = $('.tab-content#tab-used tr').length;
            var unusedCount = $('.tab-content#tab-unused tr').length;

            $('.tab-button[data-tab="all"] .count').text('(' + allCount + ')');
            $('.tab-button[data-tab="used"] .count').text('(' + usedCount + ')');
            $('.tab-button[data-tab="unused"] .count').text('(' + unusedCount + ')');

            // Update statistics boxes
            $('.stat-box:eq(0) .stat-number').text(allCount);
            $('.stat-box:eq(1) .stat-number').text(usedCount);
            $('.stat-box:eq(2) .stat-number').text(unusedCount);
        },

        /**
         * Check if any tables are empty and show message
         */
        checkEmptyTables: function() {
            $('.tab-content').each(function() {
                var $content = $(this);
                var $table = $content.find('table');
                var rowCount = $table.find('tbody tr').length;

                if (rowCount === 0) {
                    $table.remove();
                    if ($content.find('.adarok-no-items').length === 0) {
                        $content.append(
                            '<div class="adarok-no-items">' +
                            '<p>No library items found.</p>' +
                            '</div>'
                        );
                    }
                }
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';

            var $notice = $('<div>', {
                class: 'notice ' + noticeClass + ' is-dismissible',
                html: '<p>' + message + '</p>'
            });

            // Add dismiss button
            $notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');

            // Insert notice
            $('.adarok-divi-janitor h1').after($notice);

            // Bind dismiss event
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(200, function() {
                    $(this).remove();
                });
            });

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

})(jQuery);
