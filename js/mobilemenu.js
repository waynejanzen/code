$('.mobile-menu').click(function() {
		if($('.nav').attr('data-open') == 'true') {
			$('.nav').slideUp(200, function() {
				$('body').removeAttr('style');
				$('.menu-links').removeAttr('style');
				$(this).attr('data-open','false');
				$('.menu-links').removeClass('longmenu');
			});
		} else {
			$('.nav').slideDown(200, function() {
				$(this).attr('data-open','true');
				menuheight();
			});
		}
	});
	
	var menuheight = function() {
		var wh = $(window).height();
		var h = $('.nav').outerHeight(true)+50;
		
		if(h>wh && $('.nav').attr('data-open') == 'true') {
			$('.menu-links').css({
				'height':$(window).height() - $('.header').height(),
				'top':$('.header').outerHeight(true)
			});
			if(parseInt($('.menu-links').css('top')) + $('.menu-links').height() > $('.mainwrap').height()) {
				$('html,body').animate({
					'scrollTop':($('.mainwrap').height() - $('.menu-links').height() - $('.header').height())
				},0);
				$('.menu-links').css({
					'top':$(window).scrollTop() + $('.header').outerHeight(true)
				});
			}
			$('body').css({
				'overflow':'hidden'
			});
			$('.menu-links').addClass('longmenu');
		} else {
			$('body').removeAttr('style');
			$('.menu-links').removeAttr('style');
			$('.menu-links').removeClass('longmenu');
		}
	}
	
	
	
	
	$('body').on('click','.mobile-location, .hn-title',function() {
		if($(window).width() < 650) {
			$(this).next('ul').slideToggle(300);
		}
	});
	
	$('ul.nav li').mouseenter(function() {
		var w = $(window).width();
		if(w > 650) {
			$(this).addClass('hover');
			var $sub = $(this).find('ul.submenu');
			$sub.hide().stop(true).slideDown();
			$(this).addClass('hover');
		}
	}).mouseleave(function() {
		var w = $(window).width();
		if(w > 650) {
			$(this).removeClass('hover');
			$(this).find('ul.submenu').slideUp(); 
			$(this).removeClass('hover');
		}
	});
	
	$('ul.submenu li').mouseenter(function() {
		var w = $(window).width();
		if(w > 650) {
			var $ssub = $(this).find('ul.subsubmenu');
			$ssub.hide().stop(true).slideDown();
			$(this).addClass('hover');
		}
	}).mouseleave(function() {
		var w = $(window).width();
		if(w > 650) {
			$(this).find('ul.subsubmenu').slideUp();
			$(this).removeClass('hover');
		}
	});
	
	
	$('ul.nav li a').click(function(e) {
		var w = $(window).width();
		if(w<=650) {
			var $this = $(this);
			if($this.hasClass('sublink') && $this.parent().find('.submenu').length > 0) {
				$this.parent().toggleClass('hover');
				$this.parent().find('ul.submenu').slideToggle(200, function() {
					$(this).attr('data-open',true);
					menuheight();
				});
				return false;
			}
			
			if($this.parent().hasClass('submenulink') && $this.parent().find('.subsubmenu').length > 0) {
				$this.parent().toggleClass('hover');
				$this.parent().find('ul.subsubmenu').slideToggle(200, function() {
					$(this).attr('data-open',true);
					menuheight();
				});
				return false;
			}
		
		
		}
	});
