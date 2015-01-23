// JavaScript Document


/****
 * Create YouTube Tag
 * **/
var tag = document.createElement('script');
tag.src = "https://www.youtube.com/player_api";
var firstScriptTag = document.getElementsByTagName('script')[0];
firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
var divid = '';

var player;


/****
 * Start Sliding Function
 * **/
$(function() {
	/****
	 * Set Variables
	 * **/
	var ul = $('.imgholder'),
		li = ul.find('li'),
		dots = $('.fs-circles li'),
		timer,
		c = 0,
		transition = 500,
		delay = 5000,
		sliding = false,
		resizeslider = false;
		
	/****
	 * Start Video Function
	 * **/	
	var startVideo = function(id,width,height,div,c) {
		divid = div
		player = new YT.Player(div, {
			height: width,
			width: height,
			videoId: id,
			playerVars: {'autoplay':1, 'controls':0,'rel':0,'showinfo':0},
			events: {
				'onStateChange': function(event) {
					if (event.data == YT.PlayerState.ENDED) {
						// when the video is done playing, change slide
						console.log(c);
						var n = c+1;
						flip(n); 
					}
				}
			}
		});
	}
	
	/****
	 * Change Slide Function
	 * **/
	var flip = function(n) {
		if(li.length > 0) {
		sliding = true;
		if(n >= li.length) n = 0;
		if(n < 0) n = li.length-1;
		
		
		// bring next slide forward and hide it
		li.eq(n).hide().css({
			'z-index':2
		});
		
		// activate current slide dots
		dots.removeClass('active');
		dots.eq(n).addClass('active');
		
		// fade the next slide in
		li.eq(n).fadeIn(transition,function() {
			
			// remove any video from the slider
			$('.slidervideo').remove();
			
			li.eq(c).css({'z-index':0});
			li.eq(n).css({'z-index':1});
			c = n;
			sliding = false;
			
			// check if there's a video
			if(li.eq(n).find('.vidholder').length > 0) {
				var ve = li.eq(n).find('.vidholder');
				ve.html('<div class="slidervideo" id="sv'+ve.attr('data-id')+'"></div>');
				var w = ve.width();
				var h = ve.height();
				// play video
				startVideo(ve.attr('data-ytid'),w,h,'sv'+ve.attr('data-id'),c);
				
			} else {
				// set Timeout to change the slide
				flipper = setTimeout(function() { 
					var n = c+1;
					flip(n); 
				}, delay);
			
			}
		});
		
		}
	}
	
	li.first().css({
		'z-index':1
	}).show();
	
	
	// check if the first slide is a video
	if(li.first().find('.vidholder').length > 0) {
		var ve = li.first().find('.vidholder');
		ve.html('<div class="slidervideo" id="sv'+ve.attr('data-id')+'"></div>');
		var w = ve.width();
		var h = ve.height();
		flipper = setTimeout(function() { 
		startVideo(ve.attr('data-ytid'),w,h,'sv'+ve.attr('data-id'),c);
		},500);
		
	} else if(li.length > 1) {
		flipper = setTimeout(function() { 
			var n = c+1;
			flip(n); 
		}, delay);
	}
	
	// right arrow event listener
	$('a.fs-rightarrow').click(function() {
		clearTimeout(flipper);
		if(!sliding) flip(c+1);
	});
	// left arrow event listener
	$('a.fs-leftarrow').click(function() {
		clearTimeout(flipper);
		if(!sliding) flip(c-1);
	});
	// dot click event listener
	$('.fs-circles a.circle').click(function() {
		if(c != $(this).attr('data-id')) {
			clearTimeout(flipper);
			if(!sliding) flip($(this).attr('data-id'));
		}
	});
	
	// resize slider maintaining aspect ratio
	var sliderresize = function() {
		// dimensions of the slider
		var sliderwidth = 1800;
		var sliderheight = 550;
		
		var ratio = sliderheight/sliderwidth;
		var f = $('#featureSlider');
		f.height(f.width() * ratio);
		f.find('img').each(function() {
			$(this).css({
				'width':'100%'
			});
		});
	}
	
	$(window).resize(function() {
		if(resizeslider) sliderresize();
	});
	
	if(resizeslider) sliderresize();

});
