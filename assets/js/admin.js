/**
 * JavaScript for IP Search Log plugin admin panel
 */
(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
        // Clear search log button
        $('#clear-search-log').on('click', function(e) {
            e.preventDefault();
            showConfirmModal();
        });
        
        // Export log to Excel button
        $('#export-search-log').on('click', function(e) {
            e.preventDefault();
            exportSearchLog();
        });
        
        // Confirm clear button
        $('#confirm-clear').on('click', function() {
            clearSearchLog();
            hideConfirmModal();
        });
        
        // Cancel clear button
        $('#cancel-clear').on('click', function() {
            hideConfirmModal();
        });
    });
    
    // Show confirmation modal
    function showConfirmModal() {
        $('#confirm-modal').show();
    }
    
    // Hide confirmation modal
    function hideConfirmModal() {
        $('#confirm-modal').hide();
    }
    
    // Clear search log AJAX call
    function clearSearchLog() {
        $.ajax({
            url: ipSearchLogData.ajaxurl,
            type: 'POST',
            data: {
                action: 'clear_search_log',
                nonce: ipSearchLogData.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data, 'success');
                    // Reload page after delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while processing the request.', 'error');
            }
        });
    }
    
    // Export search log AJAX call
    function exportSearchLog() {
        $.ajax({
            url: ipSearchLogData.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_search_log',
                nonce: ipSearchLogData.nonce
            },
            success: function(response) {
                if (response.success) {
                    var downloadLink = '<a href="' + response.data.download_url + '" target="_blank">' + response.data.download_text + '</a>';
                    showMessage(response.data.message + ' ' + downloadLink, 'success');
                } else {
                    showMessage(response.data, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred while processing the request.', 'error');
            }
        });
    }
    
    // Show message
    function showMessage(message, type) {
        var $messageContainer = $('#search-log-message');
        
        $messageContainer.html(message);
        $messageContainer.removeClass('notice-success notice-error');
        
        if (type === 'success') {
            $messageContainer.addClass('notice-success');
        } else {
            $messageContainer.addClass('notice-error');
        }
        
        $messageContainer.show();
    }
})(jQuery); 