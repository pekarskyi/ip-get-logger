/**
 * JavaScript для адміністративної частини плагіна IP GET Logger
 */
(function($) {
    'use strict';

    // Функція для додавання завантажувача
    function showLoader(element) {
        $(element).append('<div class="ip-get-logger-loader"><span class="spinner is-active"></span></div>');
    }

    // Функція для видалення завантажувача
    function hideLoader() {
        $('.ip-get-logger-loader').remove();
    }

    // Додавання обробників подій при готовності DOM
    $(document).ready(function() {
        // Для попередження випадкових натискань на кнопки
        var buttonClickDelay = 500;
        var lastClickTime = 0;
        
        // Функція для перевірки чи можна натиснути кнопку
        function canClick() {
            var currentTime = new Date().getTime();
            if (currentTime - lastClickTime < buttonClickDelay) {
                return false;
            }
            lastClickTime = currentTime;
            return true;
        }
        
        // Додамо обробник для кнопок із затримкою
        $(document).on('click', '.button', function() {
            return canClick();
        });
        
        // Ініціалізація tooltips для довгих URL
        if (typeof $.fn.tooltip === 'function') {
            $('.ip-get-logger-url-container a').tooltip({
                position: { my: "left+15 center", at: "right center" }
            });
        }
        
        // Очищення форм після успішної відправки
        $(document).ajaxSuccess(function(event, xhr, settings) {
            if (settings.url === ip_get_logger_params.ajax_url) {
                if (settings.data && settings.data.indexOf('ip_get_logger_add_request') !== -1) {
                    $('#get-request').val('');
                }
                
                if (settings.data && settings.data.indexOf('ip_get_logger_import') !== -1) {
                    $('#import-file').val('');
                }
            }
        });
    });

})(jQuery); 