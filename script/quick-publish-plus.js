//////////////////////////////////////////////////////
// Quick Publish Plus
//////////////////////////////////////////////////////

jQuery(document).ready(function($) {

	//////////////////////////////////////////////////////
	// Show box if [N] is pressed
	//////////////////////////////////////////////////////

	$('body').on('keydown', function(e){
		
		var key = (e.keyCode ? e.keyCode : e.which);

		if ($(e.target).is('input, textarea')){
			return;
		}

		if (key == 78){
			e.preventDefault();
			if($('body').hasClass('quick-post-active')){
				return;
			}
			$('body').addClass('quick-post-active');
			var $popup = $('#new-status-popup');
			$('body').prepend('<div class="new-post-popup-overlay"></div>');
			$popup.addClass('show');
			$popup.children('textarea').focus();
		}

	});

	//////////////////////////////////////////////////////
	// Close the box if [ESC] is pressed
	//////////////////////////////////////////////////////

	$('html').on('keydown', 'body.quick-post-active', function(e){

		var key = (e.keyCode ? e.keyCode : e.which);

		if (key == 27){
			if ($('body').hasClass('quick-post-active')){
				$('#new-status-popup a.close.enable').trigger('click');
			}
		}
	});

	//////////////////////////////////////////////////////
	// Close the box
	//////////////////////////////////////////////////////

	$('body').on('click', '.new-post-popup a.close.enable', function(e){
		e.preventDefault();
		$('body').removeClass('quick-post-active');
		$('.new-post-popup').addClass('hide');
		setTimeout(function(){ $('.new-post-popup-overlay').remove(); $('.new-post-popup').removeClass('hide show');}, 300);
	});

	$('body').on('click', '.new-post-popup a.close, div.new-post-popup-overlay', function(e){
		e.preventDefault();
	});

	//////////////////////////////////////////////////////
	// Check if the input fields are empty
	//////////////////////////////////////////////////////

	$('#new-status-popup input.status-title, #new-status-popup textarea.new-status').on('input', function(){
		var title  = $('#new-status-popup input.status-title').val();
		var status = $('#new-status-popup textarea.new-status').val();

		if (title == '' || status == ''){
			$('#new-status-popup a.submit').removeClass('enable').addClass('disable');
		} else {
			$('#new-status-popup a.submit').removeClass('disable').addClass('enable');
		}
	});

	$('body').on('click', '#new-status-popup a.submit.disable', function(e){
		e.preventDefault();
	});

	//////////////////////////////////////////////////////
	// Submit the post
	//////////////////////////////////////////////////////

	$('body').on('click', '#new-status-popup a.submit.enable', function(e){
		e.preventDefault();
		var $this   = $(this);
		var $title  = $this.siblings('input.status-title');
		var $status = $this.siblings('textarea.new-status');
		var $close  = $('#new-status-popup a.close.enable');

		var title   = $title.val();
		var status  = $status.val();

		$close.removeClass('enable');
		$title.attr('disabled', 'disabled');
		$status.attr('disabled', 'disabled');

		$this.removeClass('enable');
		$title.addClass('loading');

		var returnHTML = false;
		if($('body').hasClass('paged') == false && $('body').hasClass('blog') == true){
			var returnHTML = true;
		}

		if (title != '' && status != ''){
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: qp_ajax.ajaxurl,
				data: { 'action': 'quick_publish_status', 
						'post_title': title,
						'post_content': status,
						'return_html': returnHTML,
						'security': qp_ajax.nonce },
				success: function(data){
					$title.val($title.data('value')).removeClass('loading').removeAttr('disabled');
					setTimeout(function(){ $status.val('').removeAttr('disabled');  }, 400);
					$this.addClass('disable');
					$close.addClass('enable').trigger('click');
					if(data.showPost == true){
						$postHTML = $(data.postHTML);
						$postHTML.addClass('post new-quick-post hidden');
						if($('.content .post:first').hasClass('sticky')){
							setTimeout(function(){ $('.content .sticky:last').after($postHTML); }, 300);
						} else {
							setTimeout(function(){ $('.content').prepend($postHTML); }, 300);
						}
						setTimeout(function(){ $postHTML.removeClass('hidden'); }, 500);
					}
				}
			});
		}
	});

	//////////////////////////////////////////////////////
	// Image Publish
	//////////////////////////////////////////////////////

	$.event.props.push('dataTransfer');
	var dropArea = $('#wp-admin-bar-quick-image-publish');

	$(dropArea).on('dragenter, dragover', function(e){
		e.preventDefault();
		e.stopPropagation();
		$(this).addClass('hover');
	});

	$(dropArea).on('dragexit', function(e){
		e.stopPropagation();
		$(this).removeClass('hover');
	});

	$(dropArea).on('drop', function(e){
		e.preventDefault();
		e.stopPropagation();
		$(this).removeClass('hover');

		var url = e.dataTransfer.getData('URL');

		function IsValidImageUrl(url) {
			$("<img>", {
				src: url,
				load: function() { showQuickImage(); }
			});
		}

		IsValidImageUrl(url);

		function showQuickImage(){
			$('body').addClass('quick-post-active');
			var imageName = url.substr(url.lastIndexOf("/") + 1);
			var imageName = imageName.replace(/_/g, ' ').replace(/-/g, ' ').replace(/\.[^/.]+$/, "");
			imageName = imageName.toLowerCase().replace(/\b[a-z]/g, function(letter) {
				return letter.toUpperCase();
			});
			$('body').prepend('<div class="new-post-popup-overlay close"></div>');
			$('form#new-image-popup').addClass('show');
			$('form#new-image-popup a.submit').addClass('enable');
			$('form#new-image-popup input.image-title').focus().val(imageName);
			$('form#new-image-popup img.preview-image').hide().fadeIn(400).attr('src', url);
		}

	});

	$('body').on('click', '#new-image-popup a.submit.enable', function(e){

		e.preventDefault();

		$('div.new-post-popup-overlay.close').removeClass('close');
		$('.new-post-popup a.close').removeClass('enable');

		var $this     = $(this);
		var $title    = $this.siblings('div').children('input.image-title');
		var $excerpt  = $this.siblings('div').children('input.image-excerpt');

		var title     = $title.val();
		var excerpt   = $excerpt.val();
		var category  = $('.select-category option:selected').attr('value');
		var url       = $('#new-image-popup img.preview-image').attr('src');

		$title.attr('disabled', 'disabled');
		$excerpt.attr('disabled', 'disabled');

		$this.removeClass('enable').addClass('disable');
		$this.after('<span class="loading"></span>');

		var returnHTML = false;
		if($('body').hasClass('paged') == false && $('body').hasClass('blog') == true){
			var returnHTML = true;
		}

		if (title != ''){
			$.ajax({
				type: 'POST',
				dataType: 'json',
				url: qp_ajax.ajaxurl,
				data: { 'action': 'quick_publish_image', 
						'post_title': title,
						'post_excerpt': excerpt,
						'post_category': category,
						'image_url': url,
						'return_html': returnHTML,
						'security': qp_ajax.nonce },
				success: function(data){
					$('#new-image-popup span.loading').remove();
					$('#new-image-popup a.close').addClass('enable').trigger('click');
					setTimeout(function(){ $excerpt.removeAttr('disabled'); $title.removeAttr('disabled');  }, 500);
					if(data.showPost == true){
						$postHTML = $(data.postHTML);
						$postHTML.addClass('post new-quick-post hidden');
						if($('.content .post:first').hasClass('sticky')){
							setTimeout(function(){ $('.content .sticky:last').after($postHTML); }, 300);
						} else {
							setTimeout(function(){ $('.content').prepend($postHTML); }, 300);
						}
						setTimeout(function(){ $postHTML.removeClass('hidden'); }, 500);
					}
				}
			});
		}

	});

	$('body').on('click', '#new-image-popup a.submit.disable', function(e){
		e.preventDefault();
	});

	$('#new-image-popup input.image-title').on('input', function(){
		var title  = $('#new-image-popup input.image-title').val();

		if (title == ''){
			$('#new-image-popup a.submit').removeClass('enable').addClass('disable');
		} else {
			$('#new-image-popup a.submit').removeClass('disable').addClass('enable');
		}
	});

});