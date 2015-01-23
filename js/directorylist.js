function CustomDropHandler(url){
 
    return {
        _handleFileTreeEntry: function (entry, path, upload) {			
            var that = this,
                dfd = $.Deferred(),
                errorHandler = function (e) {
                    if (e && !e.entry) {
                        e.entry = entry;
                    }
                    // Since $.when returns immediately if one
                    // Deferred is rejected, we use resolve instead.
                    // This allows valid files and invalid items
                    // to be returned together in one set:
                    dfd.resolve([e]);
                },
                dirReader;
            path = path || '';
            if (entry.isFile) {
				
				

                if (entry._file) {
                    // Workaround for Chrome bug #149735
                    entry._file.relativePath = path;
					
					
					if(upload) {					
							var fd = new FormData();
							fd.append('path','/'+path+entry.name);
							fd.append('create','file');
							fd.append('data[]',entry._file);
			
							$.ajax({ url: url, data:fd ,xhr: function() { 
									myXhr = $.ajaxSettings.xhr();
									if(myXhr.upload){
										myXhr.upload.addEventListener('progress',updateProgress, false);
									}
									return myXhr;
								}, processData: false, contentType: false, type: 'POST',success:function(data) {
									dfd.resolve(entry._file);
								}
							});
							
						}
					
					
                    
                } else {
                    entry.file(function (file) {
						
						if(upload) {					
							var fd = new FormData();
							//console.log('/'+path+entry.name);
							fd.append('path','/'+path+entry.name);
							fd.append('create','file');
							fd.append('data[]',file);
			
							$.ajax({ url: url, data:fd ,xhr: function() { 
									myXhr = $.ajaxSettings.xhr();
									if(myXhr.upload){
										myXhr.upload.addEventListener('progress',updateProgress, false);
									}
									return myXhr;
								}, processData: false, contentType: false, type: 'POST',success:function(data) {
									file.relativePath = path;
                       				dfd.resolve(file);
								}
							});
							
						}
						
                        
                    }, errorHandler);
                }
				
				
				
            } else if (entry.isDirectory) {
                var dirName = entry.name;
				dirReader = entry.createReader();
				
				if(upload) {					
					$.post(url,'create=folder&path=/'+path+entry.name,function(m) {
						dirReader.readEntries(function (entries) {
							that._handleFileTreeEntries(upload, entries, path + entry.name + '/').done(function (files) {
									dfd.resolve(files);
								}).fail(errorHandler);
						}, errorHandler);
					});
				} else {
					dirReader.readEntries(function (entries) {
						that._handleFileTreeEntries(upload, entries, path + entry.name + '/').done(function (files) {
								dfd.resolve(files);
							}).fail(errorHandler);
					}, errorHandler);
				}
 
            } else {
                // Return an empy list for file system items
                // other than files or directories:
                dfd.resolve([]);
            }
            return dfd.promise();
        },

 
        _handleFileTreeEntries: function (upload, entries, path) {
            var that = this;
            return $.when.apply(
                    $,
                    $.map(entries, function (entry) {
                        return that._handleFileTreeEntry(entry, path, upload);
                    })
                ).pipe(function () {
                    return Array.prototype.concat.apply(
                        [],
                        arguments
                    );
                });
        },
        getDroppedFiles: function (dataTransfer, upload) {
            dataTransfer = dataTransfer || {};
            var items = dataTransfer.items;
			
            if (items && items.length && (items[0].webkitGetAsEntry ||
                items[0].getAsEntry)) {

                return this._handleFileTreeEntries(
 					upload,
                    $.map(items, function (item) {
                        var entry;
                        if (item.webkitGetAsEntry) {
                            entry = item.webkitGetAsEntry();
                            if (entry) {
                                // Workaround for Chrome bug #149735:
                                entry._file = item.getAsFile();
                            }
                            return entry;
                        }
                        return item.getAsEntry();
                    })
                );
            }
            return $.Deferred().resolve(
                $.makeArray(dataTransfer.files)
            ).promise();
        }
    }
}




function dlistFunctions() {
	
	var d = $('.dirlist');
	
	function getParameterByName(name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
			results = regex.exec(location.search);
		return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	}
	
	function update(url,state) {
					
		$.post('/brokerpages/_includes/updatedirlist.php',url,function(r) {
			console.log(r);
			if(state != undefined) history.pushState(null, '', window.location.pathname+'?loc='+state);
			
			l = getParameterByName('loc');
			/*if(l == '/' || l == '') $('.addfolder, .filedrop').addClass('hide');
			else $('.addfolder, .filedrop').removeClass('hide');*/
			
			
			$('.dirlist').html(r);
			dragdrop($('.dirlist'));
		});
	}
	
	function dragdrop(d) {
		var l = getParameterByName('loc');
		d.find('li[data-protect!="1"]').not('[data-name="up"]').draggable({
			axis: 'y',
			revert: true,
			revertDuration: 100,
			start: function() { $(this).addClass('dragging') },
			stop: function() { $(this).removeClass('dragging') }
		});
		d.find('li.folder[data-protect!="1"]').droppable({
			accept: '.file',
			hoverClass: 'folderhover',
			drop: function(event, ui) {
				var $t = $(this);
				var $e = $(ui.draggable);
				var n = $e.attr('data-name');
				update('move='+n+'&to='+$t.attr('data-loc')+'&goto='+l);
				$e.remove();
			}
		});
	}
	
	
	
	$(window).on('popstate',function(event) {
		var l = getParameterByName('loc');
		$.post('/brokerpages/_includes/updatedirlist.php','goto='+l,function(r) {
			$('.dirlist').html(r);
		});
	});
	
	$('body').on('mousedown','.dirlist',function(e) { 
		if(!$(e.target).hasClass('changename') && !$('input.changename').is(':focus')) e.preventDefault(); 
	});
	
	$('body').on('dblclick','.dirlist .folder',function() {
		var page = $(this).attr('data-loc');
		if(page == '') page = '/';
		update('goto='+$(this).attr('data-loc'),page);
	});
	
	//$('.dirlist .folder').contextmenu(function() { return false; });
	
	$('body').on('dblclick','.dirlist .file',function() {
		$t = '/brokers/file?f='+$(this).attr('data-loc');
		window.open($t,'_blank');
	});
	
	/*$('body').mousedown(function(e) {
		if($(e.target).hasClass('folder') && e.button == 2) {
			

		} else return true;
	});*/
	
	
var cmtarget;

$('body').on("contextmenu",'.dirlist .folder, .dirlist .file', function (event) {
	if(!$(this).hasClass('prevdir')) {
	
	event.preventDefault();
	
	cmtarget = event.target;
	
	if($('.custom-menu').length == 0) $('body').append("<ul class='custom-menu'></ul>");
	$('.custom-menu').html('<li data-action="rename">Rename</li><li data-action="report">Report a Problem</li><li data-action="download">Download</li><li data-action="delete">Delete</li>');
	
	$(".custom-menu li").each(function() {
		if($(this).attr('data-action') == 'download') {
			$(this).attr('data-action',$(event.target).attr('data-loc')).attr('data-name',$(event.target).text().replace(/delete/i,''));
		}
		if($(this).attr('data-action') == 'delete' || $(this).attr('data-action') == 'rename') {
			if($(cmtarget).attr('data-protect') == '1') $(this).remove();
		}
		if($(this).attr('data-action') == 'report') {
			if(!$(cmtarget).closest('.bottomblock').attr('data-admin')) $(this).remove();
		}
		
	});
	
	$(".custom-menu").toggle(100).css({
		top: event.pageY + "px",
		left: event.pageX + "px"
	});
	}
});

$('body').on("contextmenu",'.checklistblock label', function (event) {
	cmtarget = event.target;
	if($(cmtarget).closest('.bottomblock').attr('data-admin')) {
		event.preventDefault();
		
		cmtarget = event.target;
		
		
		if($('.custom-menu').length == 0) $('body').append("<ul class='custom-menu'></ul>");
		$('.custom-menu').html('<li data-action="report">Report a Problem</li>');
		
		$(".custom-menu").toggle(100).css({
			top: event.pageY + "px",
			left: event.pageX + "px"
		});
	}
});


$('body').on('contextmenu','.dirlist',function(event) {
	
	if(!$(event.target).hasClass('folder') && !$(event.target).hasClass('file')) {
	event.preventDefault();
	
	
	if($('.custom-menu-dirlist').length == 0) $('body').append("<ul class='custom-menu-dirlist'><li data-action='download'>Download Entire Folder</li></ul>");
	
	$(".custom-menu-dirlist").toggle(100).css({
		top: event.pageY + "px",
		left: event.pageX + "px"
	});
	
	if($(".custom-menu-dirlist li").attr('data-action') == 'download') {
		
		$(".custom-menu-dirlist li[data-action='download']").attr('data-action',$('.dirlist').attr('data-loc')).attr('data-name',$('.dirlist').attr('data-name').replace(/delete/i,''));
	}
	}
	
});

$("body").on('click','.custom-menu li',function(){
	
	var a = $(this).attr('data-action');
	
	if(a == 'delete') {
		var f = $(cmtarget);
		if(confirm('Are you sure you want to delete this folder and all of its contents?')) {
			var l = getParameterByName('loc');
			update('delete='+f.attr('data-loc')+'&goto='+l);
		} else return false;
	} else if(a=='rename') {
		var $t = $(cmtarget);
		if($t.attr('data-name')){
			renaming = true;
			if($t.find('input').length == 0) {
				$t.find('a').remove();
				$t.html('<input type="text" name="changename" class="changename" value="'+$t.text().trim()+'" />');
				$t.find('input.changename').focus();
			}
		}
	} else if(a == 'report') {
		var $t = $(cmtarget);
		
		$('body').append('<form name="mortgagereport" class="mortgagereport" method="post"><a href="javascript:void(0)" class="close">X</a></form>');
		$form = $('.mortgagereport');
		if($t.hasClass('folder') || $t.hasClass('file')) {
			var f = decodeURIComponent($t.attr('data-loc').replace(/\+/g, '%20'));
			
			$form.append('<div class="reportrow"><div class="reportlabel">File:</div><div class="reportvalue">'+f+'</div><div class="reportrow"><div class="reportlabel">What\'s Wrong?:</div><div class="reportvalue"><textarea name="message" placeholder="More details of why there is a problem with this file."></textarea></div></div><input type="submit" name="submit" value="Send to Broker" class="reportbtn" /><input type="hidden" name="problemfile" value="'+f+'" /><input type="hidden" name="reportitem" value="1" />');

		
		} else {
			var f = $t.text();
			$form.append('<div class="reportrow"><div class="reportlabel">Checklist Item:</div><div class="reportvalue">'+f+'</div><div class="reportrow"><div class="reportlabel">What\'s Wrong?:</div><div class="reportvalue"><textarea name="message" placeholder="More details of why there is a problem with this file."></textarea></div></div><input type="submit" name="submit" value="Send to Broker" class="reportbtn" /><input type="hidden" name="problemfile" value="'+f+'" /><input type="hidden" name="reportitem" value="1" />');
		}
		
		$('.mortgagereport').hide().fadeIn(200);
		$('.mortgagereport .close').click(function() { $('.mortgagereport').fadeOut(200,function() { $(this).remove(); }); });
	} else {
		var $t = '/brokers/folder?f='+$(this).attr("data-action")+'&name='+$(this).attr('data-name');
		window.open($t,'_blank');
	}
	$(".custom-menu").hide(100);
});
	
$("body").on('click','.custom-menu-dirlist li',function(){

	var $t = '/brokers/folder?f='+$(this).attr("data-action")+'&name='+$(this).attr('data-name');
	window.open($t,'_blank');
	$(".custom-menu-dirlist").hide(100);
});
	
	
	
	
	$('body').on('blur','input.changename',function() {
		var $t = $(this);
		var v = $t.val();
		v.trim();
		var l = getParameterByName('loc');
		var ext = '';
		if($t.parent().attr('data-ext')) ext = '&ext='+$t.parent().attr('data-ext');
		
		if(v != '') update('goto='+l+ext+'&old='+$t.parent().attr('data-name')+'&update='+v);
	});
	
	var timeout;
	d.on('click','li',function(m) {
		$t = $(this);
		var targ = m.target;
		if(!$(m.target).hasClass('fdelete')) {
			
			if(!$t.hasClass('select')) {
				d.find('li.select').removeClass('select');
				$t.addClass('select');
			} else $t.removeClass('select');
		} else { $(this).removeClass('rename'); renaming = false; }
	});
	
	
	
	$('body').on('click','a[data-exact]',function() {
		var $t = $(this);
		update('goto='+$t.attr('data-exact'),$t.attr('data-exact'));
	});
	
	$('body').on('click','.addfolder',function() {
		//var l = getParameterByName('loc');
		//if(l != '' && l != '/') {	
			
			var path = getParameterByName('loc');
			var name = 'New Folder';
			var i = 1;
			while($('.dirlist').find('li.folder[data-name="'+name+'"]').length > 0) {
				name = 'New Folder ('+i+')';
				i++;
			}	
			$.post('/brokerpages/_includes/upload.php','create=folder&path=/'+name,function(m) {
				var l = getParameterByName('loc');
				update('goto='+l);
			});
		//}
	});
	
	
	$('input.dirsearch').keyup(function(e) {
		var v = $(this).val().toLowerCase();
		$('.dirlist').find('li').each(function() {
			if($(this).text().toLowerCase().indexOf(v) > -1 || $(this).attr('data-name') == 'up') $(this).show();
			else $(this).hide();
		});
	});
	
	dragdrop(d);

	var extractBase64Data = function(dataUrl) {
	  return dataUrl.substring(dataUrl.indexOf(',') + 1);
	};
	
	var total = 0;
	
	var obj = $('.filedrop');
	
	obj.on('dragover',function(e) {
		$(this).addClass('hover');	
	});
	obj.on('dragleave',function(e) {
		$(this).removeClass('hover');	
	});
	obj.on('drop',function(ev) {
		
		ev.preventDefault();
		
		$t = $(this);
		$t.removeClass('hover');
		$t.addClass('uploading');
		$t.find('span').text('Uploading...(0%)');
		
		total = 0;
		cd = CustomDropHandler("/brokerpages/_includes/upload.php");
		
		var o = ev.originalEvent.dataTransfer;
		var items = ev.originalEvent.dataTransfer.items;
		var length = items.length;

					
		$.when(cd.getDroppedFiles(o,true)).then(function(arr) {
			$t.find('span').text('Drag files to upload');
			
			var l = getParameterByName('loc');
			update('goto='+l);
		});
	});
	
}
