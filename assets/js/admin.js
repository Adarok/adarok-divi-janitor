/**
 * Admin JavaScript for Adarok Divi Janitor
 *
 * @package Adarok_Divi_Janitor
 * @author  Adarok
 * @license GPL-2.0+
 * @link    https://adarok.com
 * @copyright 2025 Adarok
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
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
     * Window loaded (after all content including images)
     */
    $(window).on('load', function() {
        DiviJanitor.hideLoading();
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
            // Hide loading after a short delay if window.load hasn't fired
            setTimeout(function() {
                DiviJanitor.hideLoading();
            }, 1000);
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            var $overlay = $('.adarok-loading-overlay');
            var $content = $('.adarok-content-wrapper');

            if ($overlay.length) {
                $overlay.addClass('fade-out');
                $content.addClass('fade-in');

                // Remove overlay from DOM after fade out
                setTimeout(function() {
                    $overlay.remove();
                }, 300);
            }
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

            // Bulk delete operations
            $(document).on('click', '.bulk-delete-unused', this.bulkDeleteUnused);
            $(document).on('click', '.bulk-delete-safe', this.bulkDeleteSafe);
            $(document).on('click', '.bulk-delete-copies', this.bulkDeleteCopies);
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
            var $row = $button.closest('tr');

            // Check if item has usage indicators (copies)
            var $usageCell = $row.find('.column-usage');
            var hasCopies = $usageCell.find('.usage-type-icon-copy').length > 0;
            var hasGlobal = $usageCell.find('.usage-type-icon-global').length > 0;

            // Determine appropriate confirmation message
            var confirmMessage = adarokDiviJanitor.confirmDelete;
            var forceDelete = false;

            if (hasCopies && !hasGlobal) {
                confirmMessage = adarokDiviJanitor.confirmDeleteWithCopies;
                forceDelete = true;
            }

            // Confirm deletion
            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading
            $button.prop('disabled', true).text(adarokDiviJanitor.scanningText);
            $row.addClass('deleting-row');

            // Send AJAX request
            $.ajax({
                url: adarokDiviJanitor.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'adarok_delete_library_item',
                    nonce: adarokDiviJanitor.nonce,
                    post_id: postId,
                    force_delete: forceDelete ? 'true' : 'false'
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        DiviJanitor.showNotice('success', adarokDiviJanitor.deleteSuccess);

                        // Remove row from ALL tabs (same item appears in multiple tables)
                        var $allRowsForItem = $('tr[data-item-id="' + postId + '"]');
                        $allRowsForItem.addClass('deleted-row');

                        setTimeout(function() {
                            $allRowsForItem.remove();
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
            var allCount = $('.tab-content#tab-all tbody tr').length;
            var usedCount = $('.tab-content#tab-used tbody tr').length;
            var unusedCount = $('.tab-content#tab-unused tbody tr').length;
            var safeCount = $('.tab-content#tab-safe tbody tr').length;
            var copiesCount = $('.tab-content#tab-copies tbody tr').length;
            var globalCount = $('.tab-content#tab-global tbody tr').length;

            $('.tab-button[data-tab="all"] .count').text('(' + allCount + ')');
            $('.tab-button[data-tab="used"] .count').text('(' + usedCount + ')');
            $('.tab-button[data-tab="unused"] .count').text('(' + unusedCount + ')');
            $('.tab-button[data-tab="safe"] .count').text('(' + safeCount + ')');
            $('.tab-button[data-tab="copies"] .count').text('(' + copiesCount + ')');
            $('.tab-button[data-tab="global"] .count').text('(' + globalCount + ')');

            // Update statistics boxes if they exist
            if ($('.stat-box').length >= 3) {
                $('.stat-box:eq(0) .stat-number').text(allCount);
                $('.stat-box:eq(1) .stat-number').text(usedCount);
                $('.stat-box:eq(2) .stat-number').text(unusedCount);
            }
            if ($('.stat-box').length >= 5) {
                $('.stat-box:eq(3) .stat-number').text(safeCount);
                $('.stat-box:eq(4) .stat-number').text(copiesCount);
            }

            // Update Usage Breakdown counts
            var globalRefsCount = 0;
            var instantiatedCopiesCount = 0;

            // Count usage indicators across all visible rows in all tabs
            $('.tab-content tbody tr').each(function() {
                var $row = $(this);
                if ($row.find('.usage-type-icon-global').length > 0) {
                    globalRefsCount++;
                }
                if ($row.find('.usage-type-icon-copy').length > 0) {
                    instantiatedCopiesCount++;
                }
            });

            // Update the usage breakdown stat boxes
            $('.usage-stat-global .usage-stat-number').text(globalRefsCount);
            $('.usage-stat-copy .usage-stat-number').text(instantiatedCopiesCount);

            // Hide usage breakdown section if both counts are zero
            if (globalRefsCount === 0 && instantiatedCopiesCount === 0) {
                $('.adarok-divi-janitor-usage-stats').fadeOut(300);
            }
        },

        /**
         * Remove item from all tabs
         */
        removeItemFromAllTabs: function(itemId) {
            // Find and remove all rows with this item ID across all tabs
            $('tr[data-item-id="' + itemId + '"]').addClass('deleted-row');
            setTimeout(function() {
                $('tr[data-item-id="' + itemId + '"]').remove();
            }, 300);
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
                    $content.find('.bulk-actions-bar').remove();
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
         * Bulk delete all safe-to-delete items
         */
        bulkDeleteSafe: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $rows = $('.tab-content#tab-safe tbody tr');
            var itemCount = $rows.length;

            if (itemCount === 0) {
                return;
            }

            // Confirm bulk deletion
            var confirmMessage = 'Are you sure you want to delete all ' + itemCount + ' safe-to-delete library items?\n\n' +
                                'This includes items with no usage and items with only instantiated copies.\n\n' +
                                'This action cannot be undone.';
            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading
            var originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting 0/' + itemCount + '...');

            var deletedCount = 0;
            var failedCount = 0;
            var currentIndex = 0;

            // Delete items one by one
            function deleteNextItem() {
                if (currentIndex >= $rows.length) {
                    // All done
                    var message = deletedCount + ' item(s) deleted successfully.';
                    if (failedCount > 0) {
                        message += ' ' + failedCount + ' item(s) failed.';
                    }
                    DiviJanitor.showNotice(failedCount > 0 ? 'warning' : 'success', message);

                    if (deletedCount > 0) {
                        DiviJanitor.updateCounts();
                        DiviJanitor.checkEmptyTables();
                    }

                    $button.remove();
                    return;
                }

                var $row = $($rows[currentIndex]);
                var postId = $row.data('item-id');
                // For safe to delete: force delete if item has any usage (which means it has copies)
                var hasUsage = $row.find('.toggle-usage').length > 0;
                var forceDelete = hasUsage;

                currentIndex++;
                $button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting ' + currentIndex + '/' + itemCount + '...');

                // Mark row as deleting
                $row.addClass('deleting-row');

                $.ajax({
                    url: adarokDiviJanitor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'adarok_delete_library_item',
                        nonce: adarokDiviJanitor.nonce,
                        post_id: postId,
                        force_delete: forceDelete ? 'true' : 'false'
                    },
                    success: function(response) {
                        if (response.success) {
                            deletedCount++;
                            // Remove from all tabs and update counts
                            DiviJanitor.removeItemFromAllTabs(postId);
                            setTimeout(function() {
                                DiviJanitor.updateCounts();
                            }, 350);
                        } else {
                            failedCount++;
                            $row.removeClass('deleting-row');
                        }
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    },
                    error: function() {
                        failedCount++;
                        $row.removeClass('deleting-row');
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    }
                });
            }

            // Start the deletion chain
            deleteNextItem();
        },

        /**
         * Bulk delete items with only copies
         */
        bulkDeleteCopies: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $rows = $('.tab-content#tab-copies tbody tr');
            var itemCount = $rows.length;

            if (itemCount === 0) {
                return;
            }

            // Confirm bulk deletion with warning
            var confirmMessage = adarokDiviJanitor.confirmDeleteWithCopies + '\n\n' +
                                'Are you sure you want to delete all ' + itemCount + ' items?\n\n' +
                                'This action cannot be undone.';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading
            var originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting 0/' + itemCount + '...');

            var deletedCount = 0;
            var failedCount = 0;
            var currentIndex = 0;

            // Delete items one by one
            function deleteNextItem() {
                if (currentIndex >= $rows.length) {
                    // All done
                    var message = deletedCount + ' item(s) deleted successfully.';
                    if (failedCount > 0) {
                        message += ' ' + failedCount + ' item(s) failed.';
                    }
                    DiviJanitor.showNotice(failedCount > 0 ? 'warning' : 'success', message);

                    if (deletedCount > 0) {
                        DiviJanitor.updateCounts();
                        DiviJanitor.checkEmptyTables();
                    }

                    $button.remove();
                    return;
                }

                var $row = $($rows[currentIndex]);
                var postId = $row.data('item-id');
                var forceDelete = true; // Always force delete for items with copies

                currentIndex++;
                $button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting ' + currentIndex + '/' + itemCount + '...');

                // Mark row as deleting
                $row.addClass('deleting-row');

                $.ajax({
                    url: adarokDiviJanitor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'adarok_delete_library_item',
                        nonce: adarokDiviJanitor.nonce,
                        post_id: postId,
                        force_delete: 'true'
                    },
                    success: function(response) {
                        if (response.success) {
                            deletedCount++;
                            // Remove from all tabs and update counts
                            DiviJanitor.removeItemFromAllTabs(postId);
                            setTimeout(function() {
                                DiviJanitor.updateCounts();
                            }, 350);
                        } else {
                            failedCount++;
                            $row.removeClass('deleting-row');
                        }
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    },
                    error: function() {
                        failedCount++;
                        $row.removeClass('deleting-row');
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    }
                });
            }

            // Start the deletion chain
            deleteNextItem();
        },        /**
         * Bulk delete all unused items
         */
        bulkDeleteUnused: function(e) {
            e.preventDefault();

            var $button = $(this);
            var $rows = $('.tab-content#tab-unused tbody tr');
            var itemCount = $rows.length;

            if (itemCount === 0) {
                return;
            }

            // Confirm bulk deletion
            var confirmMessage = 'Are you sure you want to delete all ' + itemCount + ' unused library items?\n\nThis action cannot be undone.';

            if (!confirm(confirmMessage)) {
                return;
            }

            // Disable button and show loading
            var originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting 0/' + itemCount + '...');

            var deletedCount = 0;
            var failedCount = 0;
            var currentIndex = 0;

            // Delete items one by one
            function deleteNextItem() {
                if (currentIndex >= $rows.length) {
                    // All done
                    var message = deletedCount + ' item(s) deleted successfully.';
                    if (failedCount > 0) {
                        message += ' ' + failedCount + ' item(s) failed.';
                    }
                    DiviJanitor.showNotice(failedCount > 0 ? 'warning' : 'success', message);

                    if (deletedCount > 0) {
                        DiviJanitor.updateCounts();
                        DiviJanitor.checkEmptyTables();
                    }

                    $button.remove();
                    return;
                }

                var $row = $($rows[currentIndex]);
                var postId = $row.data('item-id');

                currentIndex++;
                $button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deleting ' + currentIndex + '/' + itemCount + '...');

                // Mark row as deleting
                $row.addClass('deleting-row');

                $.ajax({
                    url: adarokDiviJanitor.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'adarok_delete_library_item',
                        nonce: adarokDiviJanitor.nonce,
                        post_id: postId,
                        force_delete: 'false'
                    },
                    success: function(response) {
                        if (response.success) {
                            deletedCount++;
                            // Remove from all tabs and update counts
                            DiviJanitor.removeItemFromAllTabs(postId);
                            setTimeout(function() {
                                DiviJanitor.updateCounts();
                            }, 350);
                        } else {
                            failedCount++;
                            $row.removeClass('deleting-row');
                        }
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    },
                    error: function() {
                        failedCount++;
                        $row.removeClass('deleting-row');
                        // Continue to next item
                        setTimeout(deleteNextItem, 100);
                    }
                });
            }

            // Start the deletion chain
            deleteNextItem();
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
