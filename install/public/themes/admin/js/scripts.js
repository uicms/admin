Dropzone.options.dropzoneForm = {
  	paramName: "file", // The name that will be used to transfer the file
  	maxFilesize: 500, // MB
    init: function () {
        this.on("complete", function (file) {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
              setTimeout(window.location = $('body').data('url'), 1000);
          }
        });
      }
};

$(document).ready(function() {
    /* Explorer */      
    initItems();
    initActionButton();
    initForm();
    //initSelectSection();
    
    /* Translations tab */
    $('.nav-tabs .nav-item a').each(function() {
        var tav_pane_id = $(this).attr('href');
        $(this).attr('href', 'javascript:;');
        $(this).click(function() {
            $('.nav-item a, .tab-content .tab-pane').removeClass('active');
            $(this).addClass('active');
            $(tav_pane_id).addClass('active');
        });
    });
    
    /* Buttons */
    $('#menu_button').click(function(e) {
        e.stopPropagation();
        $('body').toggleClass('menu_expanded');
    });
    $('body').click(function() {
        $('body').removeClass('menu_expanded');
    });
    
    $('a.name').click(function(e) {
        e.stopPropagation();
    });
    
    $('.button.import,#dropzoneForm_container .close').click(function(e) {
        e.stopPropagation();
        $('#dropzoneForm_container').toggleClass('active'); 
    });
    $('.button.new_folder').click(function() {
        $('#new_folder_popup').toggleClass('active');
        $('#new_folder_popup input[type=text]').focus();
    });
    $('#new_folder_popup .close,#new_folder_popup .cancel').click(function() {
        $('#new_folder_popup').removeClass('active');
    });
    
    /* Plugins */
    $('[data-fancybox="gallery"]').fancybox({});
    initTinymce();
    
    /* Misc */
    $('a.external').attr('target', '_blank');
    $('.alert').delay(3000).fadeOut();
});

$(window).on('load', function() {
});

$(window).on('select', function() {
    $('body').addClass('body-selection');
    $('.btn-valid-selection').attr('disabled', false);
});

$(window).on('unselect', function() {
    $('.btn-valid-selection').attr('disabled', true);
    $('body').removeClass('body-selection');
});


function initForm() {
    /* Repeated type */
    if($('.repeatedType').length) {
        $('.repeatedType').prepend('<button type="button" class="modify-password">Modifier le mot de passe</button>');
        $('.repeatedType').append('<button type="button" class="cancel-modify-password">Annuler</button>');
        $('.repeatedType .form-group, .repeatedType .cancel-modify-password').hide();
        $('.repeatedType .modify-password').click(function() {
            $(this).parent().find('.form-group').show();
            $(this).parent().find('.cancel-modify-password').show();
            $(this).hide();
        });
        $('.repeatedType .cancel-modify-password').click(function() {
            $(this).parent().find('.form-group').hide();
            $(this).parent().find('.modify-password').show();
            $(this).hide();
        });
        
    }
    
    /* Collection Type */
    if($('.collectionType').length) {
        $('.collectionType').append('<button type="button" class="add-another-collection-widget">Ajouter</button>');
        $('.collectionType .add-another-collection-widget').click(function (e) {
            var counter = $(this).parent().find('.form-group').length;
            var html = $(this).parent().data('prototype').replace(/__name__label__/g, counter).replace(/__name__/g, counter);
            $(this).parent().append(html);
        });
        $('.collectionType .form-group').append('<button type="button" class="delete-a-collection-widget">Supprimer</button>');
        $('.collectionType .delete-a-collection-widget').click(function (e) {
            $(this).parent().remove();
        });
    }
}

function action(form, action) {
    $(form + ' input[name=action]').val(action);
    $(form).submit();
}

function initActionButton() {
    if(!$('section#select').length) {
        $(window).on('select', function(e) {
            var explorer = $('.selected').parentsUntil('.explorer').parent();
            $(explorer).find('.dropdown.action').addClass('active');
        });

        $(window).on('unselect', function() {
            $('.dropdown.action').removeClass('active');
        });
    }
}


/* Items manipulations */
function initItems() {
    var positionTimer = null;
    var countdownTimer = null;
    var axis = $('body').data('display')=='list' ? 'y' : '';
    var countdown = 5;
    
    /* if template == select */
    if($('section#select').length) {
        $('.item').not('.folder').each(function() {
            $(this).find('a.name').attr('href', 'javascript:;');
        });
        $('.item.folder').dblclick(function() {
            if(url = $(this).find('a.name').attr('href')) {
                window.location = url;
            }
        });
    }
    
    if(!$('section#select').length) { // Allow selecting if not in template select
        
        /* Double click, on item */
        $('.item').dblclick(function() {
            if(url = $(this).find('a.name').attr('href')) {
                window.location = url;
            }
        });
        
         /* Sorting */
        $('.sortable').multisortable({
            axis: axis,
            delay: 300,
            start: function(ev, ui) {
                ui.item.data('start_pos', ui.item.index());
                $('.selected').addClass('dragging ui-sortable-helper ui-sortable-handle');
                $('.explorer.thumbnails .selected').each(function(i) { if($(this).attr('id') != ui.item.attr("id")) $(this).hide(); });
                if($('.explorer.thumbnails .selected').length > 1) $('body').prepend('<div id="tooltip">+' + ($('.selected').length-1) + '</div>');
            },
        
            sort: function(ev, ui) {
                var explorer = ui.item.parentsUntil('.explorer').parent();
                
                if($('.explorer.thumbnails .selected').length > 1) $('#tooltip').css({'top': (ev.pageY+10) + 'px', 'left': (ev.pageX+10) + 'px'});
            
                if(!isOver($(explorer).find('.results'), ev)) {
                    $(explorer).find('.selected,.placeholder').hide();
                    if(!$('#clone').length) $('body').prepend('<div id="clone">' + ($('.selected').length) + ' elements</div>');
                    $('#clone').css({'top': (ev.pageY+10) + 'px', 'left': (ev.pageX+10) + 'px'});
                } else {
                    $(explorer).find('.selected,.placeholder').show();
                    $('.explorer.thumbnails .selected').each(function(i) { 
                        $(this).attr('id') != ui.item.attr("id") ? $(this).hide() : $(this).show();
                    });
                    $('#clone').remove();
                }

                $('.droppable').each(function() {
                    var is_touched = false;
                    var droppable_element = $(this);
                
                    // Outside the grid
                    if($('#clone').length) {
                        if(collision(droppable_element, $('#clone'))) is_touched = true;
                        $('#tooltip').hide();
                    } else 
                    // List mode
                    if($('.explorer.list').length){
                        $('#tooltip').show();
                        $('.selected').each(function() {
                            if(!droppable_element.hasClass('selected') && collision(droppable_element, $(this))) is_touched = true;
                        });
                    } else 
                    // Thumbnails mode
                    if($('.explorer.thumbnails').length){
                        $('#tooltip').show();
                        if(!droppable_element.hasClass('selected') && collision(droppable_element.find('.wrapper'), ui.item.find('.wrapper'))) is_touched = true;
                    }
                
                    if(is_touched) {
                        $('.ui-droppable-hover').removeClass('ui-droppable-hover');
                        $(this).addClass('ui-droppable-hover');
                        return false;
                    } else {
                        $(this).removeClass('ui-droppable-hover');
                    }
                });
            
            },
        
            stop: function(ev, ui) {
                $('#tooltip,#clone').remove();
                $('.selected').removeClass('dragging ui-sortable-helper ui-sortable-handle').show();
                
                /* Sort items */
                var start_pos = ui.item.data('start_pos');
                if (start_pos != ui.item.index()) {
                    var explorer = ui.item.parentsUntil('.explorer').parent();
                    updatePositions(explorer);
                    savePositions(explorer);
                }
                
                /* Move elements into folder */
                if($('.ui-droppable-hover').length) {
                    $('.selected').hide();
                    if(confirm('Confirmez-vous le déplacement ?')) {
                        $('#form_results input[name=target]').val($('.ui-droppable-hover').data('id'));
                        action('#form_results', 'move');
                    } else {
                        $('.ui-droppable-hover').removeClass('ui-droppable-hover');
                        $('.selected').show();
                    }
                }
            }
        });
    }
    
    /* Selecting */
    if($('.selectable-item').length) {
        var selecting = false;
        
        /* Select all */
        $(document).keydown(function(event) {
            if((event.ctrlKey || event.metaKey) && event.which == 65) {
                event.preventDefault();
                checkAllItems();
                return false;
            };
        });
        
        /* Selecting with lasso */
        $(document).lasso({
            cancel: "input, a, .button, .btn, .mce-tinymce",
            delay: 100, 
            start: function(event, props) {
                selecting = true;
                if (event.metaKey == false) {
                    uncheckAllItems();
                }
            },
            stop: function (event, props) {;
                checkItem('.selectable-item.selecting');
                setTimeout(function() {
                    selecting = false;
                }, 100);
            }, 
            drag: function (event, props) {
                $('.selectable-item').each(function() {
                    if(collision($(this), $('.ui-lasso-helper'))) {
                        $(this).addClass('selecting');
                    } else {
                        $(this).removeClass('selecting');
                    }
                });
                if(event.pageY >= ($(window).height() + $('html,body').scrollTop() - 20)) {
                    $('html,body').scrollTop($('html,body').scrollTop() + 20);
                }
                if(event.pageY <= ($('html,body').scrollTop() + 20)) {
                    $('html,body').scrollTop($('html,body').scrollTop() - 20);
                }
            }
        });
        
        /* Selecting by click */
        $('.selectable-item').on('click', function(e) {			
            if (!$(this).hasClass('selected')) {
		        checkItem(this);
            } else if (e.metaKey) {
				uncheckItem(this);
			}
            if (e.metaKey == false) {
				uncheckAllItems(this);
			}
		});
        /*$('.selectable-item').on('mousedown', function(e) {
            var explorer = $(this).parentsUntil('.explorer').parent();
            if (!$(this).hasClass('selected') && $(explorer).find('.selectable-item.selected').length) {
                e.stopPropagation();
            } else if (!$(this).hasClass('selected')) {
                checkItem(this);
            }
        });*/
        
        /* Disable sorting if mousedown on the link (a) */
        $('.selectable-item a').on('mousedown', function(e) {
            e.stopPropagation(); 
	    });
        
        /* Stop propagation of click on buttons and items */
        $('button, input, select, textarea, .dropdown, .button, .item').on('click', function(e) {
            e.stopPropagation();
        });
        
        /* Clear selection on click outside */
        $('body').on('click', function(e) {
            if(!selecting) {
                uncheckAllItems();
            }
        });
	}
    
    
    /* Private functions */
    function updatePositions(explorer) {
        $(explorer).find('.sortable .result').each(function(i) {
        	$(this).find('.position_input').val(i + $('.explorer').data('offset'));
            $(this).find('.position').html(i + $('.explorer').data('offset'));
        });
    }
    
    function savePositions(explorer) {
        $('body').append('<div id="loading" class="positions">Saving positions...</div>');
        
        var params = '';
        $(explorer).find('.results .result').each(function(i) {
            console.log('result');
        	params += '&selection[]=' + $(this).data('id') + '&position[]=' + $(this).find('.position_input').val();
        });
        console.log(params);
    	$.ajax(
            {
    	        url: $(explorer).data('url') + params,
                success: function() {
            		$('#loading').remove();
            	}
    	    }
        );
    }  
}

function collision($div1, $div2) {
	var x1 = $div1.offset().left;
	var y1 = $div1.offset().top;
	var h1 = $div1.outerHeight(true);
	var w1 = $div1.outerWidth(true);
	var b1 = y1 + h1;
	var r1 = x1 + w1;
	var x2 = $div2.offset().left;
	var y2 = $div2.offset().top;
	var h2 = $div2.outerHeight(true);
	var w2 = $div2.outerWidth(true);
	var b2 = y2 + h2;
	var r2 = x2 + w2;
    
	if (b1 < y2 || y1 > b2 || r1 < x2 || x1 > r2) return false;
	return true;
}

function isOver(element, ev) {
	var x1 = ev.pageX;
	var y1 = ev.pageY;
	var h1 = 1;
	var w1 = 1;
	var b1 = y1 + h1;
	var r1 = x1 + w1;
	var x2 = $(element).offset().left;
	var y2 = $(element).offset().top;
	var h2 = $(element).outerHeight(true);
	var w2 = $(element).outerWidth(true);
	var b2 = y2 + h2;
	var r2 = x2 + w2;

	if (b1 < y2 || y1 > b2 || r1 < x2 || x1 > r2) return false;
	return true;
}


/* Items checking */
function uncheckItem(element) {
    if($(element).length) {
	    $(element).removeClass("selected").find('input[type=checkbox]').prop('checked', false);
        if(!$('.selectable-item.selected').length) {
            $(window).trigger('unselect');
        }
    }
}

function checkItem(element) {
    if($(element).length) {
        $(element).removeClass('selecting').addClass('selected').find('input[type=checkbox]').prop('checked', true);
        $(window).trigger('select');
    }
}

function checkAllItems() {
	$('.selectable-item').addClass('selected').find('input[type=checkbox]').prop('checked', true);
    $(window).trigger('select');
}

function uncheckAllItems(excepted_element) {
	$('.selectable-item').not('#'+$(excepted_element).attr('id')).removeClass('selected').find('input[type=checkbox]').prop('checked', false);
    if(!excepted_element) $(window).trigger('unselect');
}


/* Plugins */
function initTinymce() {
    tinymce.init({
    		selector: '.tinymce',
            content_css : "/themes/app/css/mce.css",
    		plugins: [
    			'colorpicker advlist autolink lists link image charmap print preview anchor',
    			'searchreplace visualblocks code fullscreen',
    			'importcss insertdatetime media table contextmenu paste code save autoresize spellchecker textcolor'
    		],
    		setup: function(editor) {
    			editor.on('keydown', function(e) {
              		if (e.keyCode == 83 && (e.ctrlKey || e.metaKey)) {
    					    e.preventDefault();
    					if ($('.submit_button').attr('disabled') != 'disabled') {
    						//submitForm();
    					}
    				}
          	    });

            	editor.on('change', function(e) {
            	});
        	},
        	paste_as_text: true,
        	paste_strip_class_attributes : true,
        	paste_remove_styles : true,
        	paste_auto_cleanup_on_paste : true,
        	forced_root_block : false,
        	toolbar: 'insertfile undo redo | styleselect | bold italic | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | spellchecker',
        	relative_urls : false,
      		convert_urls : false,
      		theme_advanced_resizing: true,
       		autoresize_max_height: 500
    	});
}


/*$('*').click(function(e) {
    var tags = ['H1', 'DIV', 'SECTION', 'BODY', 'HTML'];
    if(tags.indexOf($(this).prop('tagName')) == -1) {
        e.stopPropagation();
        console.log($(this).prop('tagName'));
    }
    if($(this).prop('tagName') == 'SECTION') {
        $(window).trigger('click_section');
        console.log('click_section ' + $(this).prop('tagName'));
    }
});*/

