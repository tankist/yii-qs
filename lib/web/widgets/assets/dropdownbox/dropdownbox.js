(function($) {
    $.fn.dropdownbox = function(config) {
        var defaults = {
            containerId:'drop-down-box',
            triggerOpenClass:'open',
            containerOpenClass:'open'
        };
        var options = $.extend(defaults, config);

        var trigger = $(this);
        var container = $('#'+options.containerId);

        trigger.click(function(e) {
			e.preventDefault();
            container.toggle();
			container.toggleClass(options.containerOpenClass);
            trigger.toggleClass(options.triggerOpenClass);
        });

		trigger.mouseup(function() {
			return false;
		});

		container.mouseup(function() {
			return false;
		});

		$(document).mouseup(function(e) {
			if($(e.target).parent('#'+options.containerId).length==0) {
				container.removeClass(options.containerOpenClass);
				container.hide();
                trigger.removeClass(options.triggerOpenClass);
			}
		});
        return this;
    }
})(jQuery)
