/*
 * jQuery Pixelgems Thumbnail Slider V0.0.2
 * http://pixelgems.com
 *
 * Copyright 2013, Wayne Janzen
 * 
 * October 2013
 */

(function($) {

    var PGSlider = function(e, options){
		var settings = $.extend({}, $.fn.pgSlider.defaults, options);
		
		/*
		 *
		 * Create Vars
		 *
		*/
		var vars = {
            currentSlide: 0,
            totalImgs: 0,
            running: false,
            paused: false,
			bodyWidth: $(window).width(),
			totalClones: 0,
			slidesInWindow: 0,
			childWidth: [],
			distance: 0,
			ratio: settings.distance/settings.speed,
			loaded: false,
			sliding: null
        };
						
		var s = $(e);
        s.data('pg:vars', vars);
        s.css({'position':'relative'});
        s.addClass('pgSlider');
		
		
		var k = s.children('img');
		k.wrapAll('<div class="pgsliderframe"></div>');
		var f = $('.pgsliderframe');
		
		if(settings.pauseOnMouseover) {
			f.mouseover(function() {
				if(vars.loaded) vars.sliding.stop(true);
			}).mouseout(function() {
				if(vars.loaded) {
					var remaining = (vars.distance+f.position().left)
					var speed = (remaining/vars.ratio);
					f.animate({
						'left':-(vars.distance)+'px'
					}, speed, 'linear',function() { checkslides(); });
				}
			});
		}
		
		/*
		 *
		 * LOAD THE CHILD IMG ELEMENTS
		 *	  Set any variables related to the images also
		*/
		k.each(function() {
			var c = $(this);
			c.on('load',function(t) {
				$(this).css({
					'display':'block',
					'float':'left'
				});
				c.attr('data-n',vars.totalImgs);
				vars.totalImgs++;
				if($(this).position().left < vars.bodyWidth) vars.slidesInWindow++;
				
				if(vars.totalImgs == k.length) {
					f.css({'opacity':0,'display':'block'}).animate({
						'opacity':1
					},300, function() { 
						vars.loaded = true; 
						var w = f.width();
						var m = (Math.ceil((vars.bodyWidth*2)/w));
						k.each(function(x) { vars.childWidth[x] = $(this).width(); });
						for(var x=0; x<=m;x++) {
							duplicateSlides(k,f);
						}
						f.css('width',(w*(m+2)));
						startSlidin();
					});
				}
				
			});
		});
		
		
		/*
		 *
		 * DUPLICATE SLIDES SO IT LOOKS CONTINUOUS
		 *
		*/
		var duplicateSlides = function(k,f) {
			var c = k.clone();
			vars.totalClones++;
			f.append(c);
			c.each(function() { 
				if($(this).position().left < vars.bodyWidth) vars.slidesInWindow++;
				$(this).attr('data-clone',vars.totalClones);
				$(this).attr('data-n',vars.totalImgs);
				vars.totalImgs++;
				k[k.length] = $(this);
				vars.childWidth[vars.childWidth.length] = $(this).width();
			});
		}
		
		/*
		 *
		 * START THE SLIDER
		 *
		*/
		var startSlidin = function() {
			k = f.children('img');
			vars.distance = parseInt(vars.childWidth[vars.currentSlide]);
			//get next animation distance
			var speed = (vars.distance/vars.ratio);
			vars.sliding = f.animate({
				'left':-(vars.distance)+'px'
			}, speed, 'linear',function() { checkslides(); });
		}
		
		/*
		 *
		 * Check where the slides are
		 *
		*/
		var checkslides = function() {
			f.append(k.eq(0)).css('left',0);
			vars.currentSlide++;
			if(vars.currentSlide == k.length) vars.currentSlide = 0;
			startSlidin();
		}
		
		
	}
	
	
	
	
	$.fn.pgSlider = function(options) {
        return this.each(function(){
            var e = $(this);
            var pgslider = new PGSlider(this, options);
        });
	};
	
	//Default settings
	$.fn.pgSlider.defaults = {
		speed: 5000,				// 5 second animation default
		distance: 200,				// distance to cover in that 5 seconds
		pauseOnMouseover: false
	};
	
})(jQuery);
