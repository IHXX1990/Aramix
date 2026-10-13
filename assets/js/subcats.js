(function($){
    $(document).ready(function(){
        var $list = $('.amcf-subcats-list');
        if (!$list.length) return;

        $list.each(function(){
            var $el = $(this);
            if (!$el.hasClass('amcf-subcats-slider')) return;

            var $controls = $('<div class="amcf-subcats-controls"/>');
			var $next = $('<button type="button" class="amcf-subcat-next">‹</button>');
            var $prev = $('<button type="button" class="amcf-subcat-prev">›</button>');
            
            $controls.append($prev).append($next);
            $el.after($controls);

            var scrollAmount = $el.outerWidth();

            $prev.on('click', function(){
                $el.animate({scrollLeft: $el.scrollLeft() - scrollAmount}, 2);
            });

            $next.on('click', function(){
                $el.animate({scrollLeft: $el.scrollLeft() + scrollAmount}, 2);
            });
        });
    });
})(jQuery);
