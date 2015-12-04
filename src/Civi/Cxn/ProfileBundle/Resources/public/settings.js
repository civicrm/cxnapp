(function ($) {

    // Example: <button class="action-post" data-url="{{ url(...) }}">
    // Example: <a href="#" class="action-post" data-url="{{ url(...) }}">
    $('.action-post').click(function (evt) {
        evt.preventDefault();
        var url = $(this).data('url');
        var form = $('<form>')
            .attr('method', 'post')
            .attr('action', url)
            .css('display', 'none')
            .appendTo($('body'));
        form.submit();
    });

})(jQuery);