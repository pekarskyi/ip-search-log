/**
 * JavaScript for IP Search Log plugin admin panel
 */
(function($) {
    'use strict';
    
    // Document ready
    $(document).ready(function() {
        // Initialize datepickers
        initDatePickers();

        // Clear search log button
        $('#clear-search-log').on('click', function(e) {
            e.preventDefault();
            showConfirmModal();
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
    
    // Initialize datepickers
    function initDatePickers() {
        // Check if jQuery UI datepicker is available
        if ($.fn.datepicker) {
            $('.date-picker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true,
                maxDate: '0', // Обмеження майбутніми датами
                showButtonPanel: true,
                closeText: 'Закрити',
                currentText: 'Сьогодні',
                monthNames: ['Січень', 'Лютий', 'Березень', 'Квітень', 'Травень', 'Червень', 'Липень', 'Серпень', 'Вересень', 'Жовтень', 'Листопад', 'Грудень'],
                monthNamesShort: ['Січ', 'Лют', 'Бер', 'Кві', 'Тра', 'Чер', 'Лип', 'Сер', 'Вер', 'Жов', 'Лис', 'Гру'],
                dayNames: ['Неділя', 'Понеділок', 'Вівторок', 'Середа', 'Четвер', 'П\'ятниця', 'Субота'],
                dayNamesShort: ['Нд', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                dayNamesMin: ['Нд', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                firstDay: 1, // Перший день тижня - понеділок
                beforeShow: function(input, inst) {
                    // Ensure the datepicker is above other elements
                    setTimeout(function() {
                        inst.dpDiv.css({
                            'z-index': 9999
                        });
                    }, 0);
                }
            });
        }
    }

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