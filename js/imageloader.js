function imageloader() {
	$('img.fill').each(function() {
		var limg = function(e) {
			var t = e;
			e.removeAttr('style');
			var p = t.parent();
			var r = t.height()/t.width();
			p.css({
				'overflow':'hidden',
				'position':'relative'
			});
			
			if(t.width() >= p.width()) {
				t.width(p.width());
				
				if(t.height() < p.height()) {
					t.height(p.height());
					t.width(p.height()/r);
				}
				
			} else {
				t.width(p.width());
				t.height(t.width()*r);
				
				if(t.height() < p.height()) {
					t.height(p.height());
					t.width(t.height()/r);
				}
			}
			
			t.css({
				'position':'absolute',
				'top':-((t.height()-p.height())/2)+'px',
				'left':-((t.width()-p.width())/2)+'px',
			}).fadeIn(200);
		}
		
		
		if(!this.complete) {
			$(this).hide();
			$(this).load(function() { limg($(this)); });
		} else limg($(this));
		
	});	
}
