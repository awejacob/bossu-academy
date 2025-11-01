    
jQuery(document).ready(function($) {
var startTime = Date.now();
$(document).on('paste', function(e) {
e.preventDefault();
$.post(ajaxurl, { action: 'log_paste_risk', user_id: wp_current_user_id }); // Localize wp_current_user_id
});
document.addEventListener('contextmenu', e => e.preventDefault());
document.addEventListener('copy', e => e.preventDefault());
$('form.quiz-form').submit(function() {
var dwell = (Date.now() - startTime) / 1000;
if (dwell < 60) { $.post(ajaxurl, { action: 'log_speed_risk' }); }
});
});
