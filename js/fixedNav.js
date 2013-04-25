(function($) {
  var globalel = 0;
    var fixnav = function(e, options){
		var settings = $.extend({}, $.fn.fixedNav.defaults, options);
		
		/*
		 *
		 * Create Vars
		 *
		*/
		var vars = {
			start: e.offset().top - parseInt(e.css('padding-top')) - parseInt(e.css('margin-top')),
			parent: e.parent(),
			parentpos: e.parent().offset(),
			currentStyles: e.attr('style'),
			parentWidth: parseInt(e.parent().width()) + parseInt(e.parent().css('padding-left')) + parseInt(e.parent().css('padding-right')) + parseInt(e.parent().css('margin-left')) + parseInt(e.parent().css('margin-right')),
			elWidth: parseInt(e.width()) + parseInt(e.css('padding-left')) + parseInt(e.css('padding-right')) + parseInt(e.css('margin-left')) + parseInt(e.css('margin-right')),
			elHeight: parseInt(e.height()) + parseInt(e.css('padding-top')) + parseInt(e.css('padding-bottom')) + parseInt(e.css('margin-top')) + parseInt(e.css('margin-bottom')),
			clonediv: 'fixednav-'+globalel,
			cloned: false,
			tm: 0
		};		
		
		$(window).scroll(function() {
			move();
		});
		
		$(window).resize(function() {
			vars.parentpos = e.parent().offset();
			move();
		});
				
		
		var move = function() {
			
			vars.tm = settings.topMargin;
			
			var scrolltop = $(window).scrollTop();
			var p = vars.start;
			if(scrolltop > p){
				var pos = scrolltop;
				
				if(!vars.cloned) {
					var c = e.clone();
						c.insertAfter(e);
						c.attr('data-clone',vars.clonediv).css('opacity',0);
					vars.cloned = true;
				}
				
				if(settings.detectFooter) {
					var footertop = $(settings.footerDiv).offset().top - parseInt($(settings.footerDiv).css('padding-top')) - parseInt($(settings.footerDiv).css('margin-top'));
					if(scrolltop + vars.elHeight > footertop) {
						vars.tm = settings.topMargin - (scrolltop+vars.elHeight-footertop);
					}
				}
				
				e.css({
					'position':'fixed',
					'top': vars.tm+'px',
				});
				if(settings.position === 'left') {
					e.css('left',vars.parentpos.left-$(window).scrollLeft());
				} else if(settings.position === 'right') {
					e.css('left',vars.parentpos.left+vars.parentWidth-vars.elWidth-$(window).scrollLeft());
				}
			} else if (scrolltop < p) {
				$('[data-clone="'+vars.clonediv+'"]').remove();
				vars.cloned = false;
				e.removeAttr('style');
				e.attr('style',vars.currentStyles);
			}
		}
		
						
	}

	$.fn.fixedNav = function(options) {
		return this.each(function() {
        	var e = $(this);
			var nav = new fixnav(e, options);
			globalel++;
		});
	};
	
	//Default settings
	$.fn.fixedNav.defaults = {
		topMargin: 10,				//margin when fixed to top of window
		position: 'left',
		detectFooter: true,
		footerDiv: '.footer'
	};
	
})(jQuery);
