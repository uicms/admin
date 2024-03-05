/* Dropzone */
Dropzone.autoDiscover = false;
var csv_uploaded = 0;
var linked_files_uploaded = 0;
var files_uploaded = 0;

if(document.querySelector('.cpnt_import')) {
    document.querySelector('.cpnt_import .start_import').addEventListener('click', function() {
        window.location = document.querySelector('.cpnt_import .start_import').getAttribute('data-url');
    }); 
    var myDropzone = new Dropzone(".dropzone.csv", {
        paramName: "file",
        maxFilesize: 1000,
        init: function () {
            this.on("complete", function (file) {
                document.querySelector('.cpnt_import .start_import').removeAttribute('disabled');
            });
        }
    });
    var myDropzone = new Dropzone(".dropzone.files-linked", {
        paramName: "file",
        maxFilesize: 1000,
        init: function () {
            this.on("complete", function (file) {
            
            });
        }
    });
}
if(document.querySelector('.cpnt_upload')) {
    var myDropzone = new Dropzone(".dropzone.files", {
        paramName: "file",
        maxFilesize: 1000,
        init: function () {
            this.on("complete", function (file) {
                if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
                    setTimeout(window.location = document.querySelector('body').getAttribute('data-url'), 1000);
                }
            });
        }
    });
}



/* Import buttons */
document.querySelectorAll('.dropdown.import a.files, .cpnt_upload .close').forEach(function(button) {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.cpnt_upload').classList.toggle('active');
    });
});
document.querySelectorAll('.dropdown.import a.data, .cpnt_import .close').forEach(function(button) {
    button.addEventListener('click', function(e) {
        e.stopPropagation();
        document.querySelector('.cpnt_import').classList.toggle('active');
    });
});


/* Tinymce */
tinymce.init({
    selector: '.tinymce',
    content_css : "/themes/app/css/mce.css",
    plugins: [
        'colorpicker advlist autolink lists link image charmap anchor',
        'searchreplace visualblocks code fullscreen',
        'importcss insertdatetime media table contextmenu paste code save autoresize spellchecker textcolor nonbreaking'
    ],
    setup: function(editor) {
        editor.on('keydown', function(e) {
        });
        editor.on('change', function(e) {
            $('.cpnt_form_buttons button').attr('disabled', false);
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
    autoresize_max_height: 500,
    extended_valid_elements : 'script[type|src],iframe[width|height|src]'
});


/* Fancybox */
$('[data-fancybox="gallery"]').fancybox({});


/* Menu */
document.querySelector('#menu_button').addEventListener('click', function(e) {
    e.stopPropagation();
    document.querySelector('body').classList.toggle('menu_expanded');
});
document.querySelector('body').addEventListener('click', function(e) {
    this.classList.remove('menu_expanded');
});


// External links
document.querySelectorAll('a.external').forEach(function(link) {
    link.setAttribute('target', '_blank');
});

// Warn fading
if(message = document.querySelector('.cpnt_message')) {
    setTimeout(function(){ message.classList.add('fade') }, 2000);
}


/* Init functions */
initItems();
initActionButton();
initFormTypes();


/* Folder functions */
$('.button.new_folder').click(function() {
    $('.cpnt_new_folder').toggleClass('active');
    $('.cpnt_new_folder input[type=text]').focus();
});
$('.cpnt_new_folder footer button[type=submit]').click(function() {
    if($('.cpnt_new_folder input[type=text]').val()) {
        $('.cpnt_new_folder form').submit();
    }
});
$('.cpnt_new_folder .close,.cpnt_new_folder .cancel').click(function() {
    $('.cpnt_new_folder').removeClass('active');
});


/* Action functions */
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

/* Items functions */
$(window).on('select', function() {
    $('body').addClass('body-selection');
    $('.btn-valid-selection').attr('disabled', false);
});

$(window).on('unselect', function() {
    $('.btn-valid-selection').attr('disabled', true);
    $('body').removeClass('body-selection');
});


function action(container, action, params) {
    var submit = true;

    if(action == 'delete' && !confirm('Confirmez-vous la suppression ?')) {
        submit = false;
    }

    if(submit) {
        var url = $(container).data('action')  + '&action=' + action + '&';
        $(container + " :checkbox:checked").each(function () {
            url += "selection[]=" + $(this).val() + "&";
        });
        url = url.slice(0, -1);
    
        if(params) {
            url += '&' + params;
        }
        window.location = url;
    }
}


function initItems() {
    var positionTimer = null;
    var countdownTimer = null;
    var axis = $('body').data('display')=='list' ? 'y' : '';
    var countdown = 5;
    
    /* Stop propafation on item name */
    document.querySelectorAll('a.name').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

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
    
    // Allow sorting if not in template select
    if(!$('section#select').length) {
        
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
                
                // Mode thumbnails
                $('.items.thumbnails .selected').each(function(i) {
                    if($(this).attr('id') != ui.item.attr("id")) $(this).hide();
                });
                if($('.items.thumbnails .selected').length > 1) {
                    $('body').prepend('<div id="tooltip">+' + ($('.selected').length-1) + '</div>');
                }
            },
            sort: function(ev, ui) {
                var explorer = ui.item.parentsUntil('.explorer').parent();
                
                // Tooltip if more than one element dragged
                if($('.items.thumbnails .selected').length > 1) {
                    $('#tooltip').css({'top': (ev.pageY+10) + 'px', 'left': (ev.pageX+10) + 'px'});
                }
            
                // Display a tooltip if drag outside the grid
                if(!isOver($(explorer).find('.results'), ev)) {
                    $(explorer).find('.selected,.placeholder').hide();
                    if(!$('#clone').length) {
                        $('body').prepend('<div id="clone">' + ($('.selected').length) + ' elements</div>');
                    }
                    $('#clone').css({'top': (ev.pageY+10) + 'px', 'left': (ev.pageX+10) + 'px'});
                } 
                // If drag over the grid
                else {
                    $(explorer).find('.selected,.placeholder').show();
                    $('.items.thumbnails .selected').each(function(i) { 
                        $(this).attr('id') != ui.item.attr("id") ? $(this).hide() : $(this).show();
                    });
                    $('#clone').remove();
                }
                
                // Droppable elements
                $('.droppable').each(function() {
                    var is_touched = false;
                    var droppable_element = $(this);
                
                    // Outside the grid
                    if($('#clone').length) {
                        if(collision(droppable_element, $('#clone'))) {
                            is_touched = true;
                        }
                        $('#tooltip').hide();
                    } else 
                    // List mode
                    if($('.items.list').length){
                        $('#tooltip').show();
                        $('.selected').each(function() {
                            if(!droppable_element.hasClass('selected') && collision(droppable_element, $(this))) {
                                is_touched = true;
                            }
                        });
                    } else 
                    // Thumbnails mode
                    if($('.items.thumbnails').length){
                        $('#tooltip').show();
                        if(!droppable_element.hasClass('selected') && collision(droppable_element.find('.wrapper'), ui.item.find('.wrapper'))) {
                            is_touched = true;
                        } 
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
                        action('#explorer_results', 'move', 'target=' + $('.ui-droppable-hover').data('id'));
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
        
        /* Keyboard */
        $(document).keydown(function(event) {
            if((event.ctrlKey || event.metaKey) && event.which == 65) {
                event.preventDefault();
                checkAllItems();
                return false;
            };
            if($('#form_results').length && (event.which == 46 || event.which == 8)) {
                if($('.selected').length) {
                    event.preventDefault();
                    action('#form_results', 'delete', '');
                    return false;
                }
                return true;
            };
        });
        
        /* Selecting with lasso */
        $(document).lasso({
            cancel: "input, textarea, button, select, option, a, .button, .btn, .mce-tinymce",
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
        
        /* Select item on drag */
        $('.selectable-item').on('mousedown', function(e) {
            var explorer = $(this).parentsUntil('.explorer').parent();
            if (!$(this).hasClass('selected') && $(explorer).find('.selectable-item.selected').length) {
                e.stopPropagation();
            } else if (!$(this).hasClass('selected')) {
                checkItem(this);
            }
        });
        
        /* Disable sorting if mousedown on the link (a) */
        $('.selectable-item a').on('mousedown', function(e) {
            e.stopPropagation(); 
        });
        
        /* Stop propagation of click on buttons and items */
        $('button, input, textarea, .dropdown, .button, .item').on('click', function(e) {
            e.stopPropagation();
        });
        
        /* Clear selection on click outside */
        $('body').on('click', function(e) {
            if(!selecting) {
                uncheckAllItems();
            }
        });
    }
    
    /* Item form */
    $('.item.has_form .expand_item_form').click(function(e) {
        e.stopPropagation();
        $(this).parent().toggleClass('item_form_expanded');
    });
    $('.item.has_form .expand_item_form').on('mousedown', function(e) {
        e.stopPropagation(); 
    });
    $('.item.has_form .item_form').on('mousedown', function(e) {
        e.stopPropagation();
    });
    $('.item.has_form .item_form').on('click', function(e) {
        e.stopPropagation();
    });
    $('.item.has_form .item_form').dblclick(function(e) {
        e.stopPropagation();
    });

    /* Private functions */
    function updatePositions(explorer) {
        $(explorer).find('.sortable .result').each(function(i) {
            $(this).find('.position_input').val(i + $(explorer).data('offset'));
            $(this).find('.position').html(i + $(explorer).data('offset'));
        });
    }
    
    function savePositions(explorer) {
        $('body').append('<div id="loading" class="positions">Saving positions...</div>');
        
        var params = '';
        $(explorer).find('.results .result').each(function(i) {
            params += '&selection[]=' + $(this).data('id') + '&position[]=' + $(this).find('.position_input').val();
        });
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
    var h1 = $div1.outerHeight(false);
    var w1 = $div1.outerWidth(false);
    var b1 = y1 + h1;
    var r1 = x1 + w1;
    var x2 = $div2.offset().left;
    var y2 = $div2.offset().top;
    var h2 = $div2.outerHeight(false);
    var w2 = $div2.outerWidth(false);
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
    var h2 = $(element).outerHeight(false);
    var w2 = $(element).outerWidth(false);
    var b2 = y2 + h2;
    var r2 = x2 + w2;

    if (b1 < y2 || y1 > b2 || r1 < x2 || x1 > r2) return false;
    return true;
}

function uncheckItem(element) {
    if($(element).length) {
        $(element).removeClass("selected").find('input[type=checkbox].check_item').prop('checked', false);
        if(!$('.selectable-item.selected').length) {
            $(window).trigger('unselect');
        }
    }
}

function checkItem(element) {
    if($(element).length) {
        $(element).removeClass('selecting').addClass('selected').find('input[type=checkbox].check_item').prop('checked', true);
        $(window).trigger('select');
    }
}

function checkAllItems() {
    $('.selectable-item').addClass('selected').find('input[type=checkbox].check_item').prop('checked', true);
    $(window).trigger('select');
}

function uncheckAllItems(excepted_element) {
    $('.selectable-item').not('#'+$(excepted_element).attr('id')).removeClass('selected').find('input[type=checkbox].check_item').prop('checked', false);
    if(!excepted_element) $(window).trigger('unselect');
}


/*
/* Form functions
*/

// Upload preview
$('form input[type=file]').on('change', function() {
    previewFile($(this));
});

function previewFile(input){
    var file = input.get(0).files[0];
    var extension = file.name.split('.').pop();

    if(file){
        var reader = new FileReader();

        reader.onload = function(){
            if(extension == 'jpg' || extension == 'png' || extension == 'jpeg' || extension == 'gif') {
                var image = input.parentsUntil('.form_field').find('.preview_file');
                $(image).attr("src", reader.result);
                $(image).removeClass('empty');
                var rotation_buttons = input.parentsUntil('.form_field').find('.rotation_buttons');
                rotation_buttons.addClass('active');
            }
        }

        reader.readAsDataURL(file);
    }
}

$(".rotate_right").click(function() {
    image = $(this).parentsUntil('.form_field').find('.preview_file');
    rotation_input = $('#ui_form_Rotation' + $(this).parent().data('field'));
    rotation = parseInt(rotation_input.val());
    rotation = (rotation + 90) % 360;
    $(".preview_file").css({'transform': 'rotate('+rotation+'deg)'});
    rotation_input.val(rotation);
    $(this).parentsUntil('form').parent().find('.cpnt_form_buttons button').attr('disabled', false);
});

$(".rotate_left").click(function() {
    image = $(this).parentsUntil('.form_field').find('.preview_file');
    rotation_input = $('#ui_form_Rotation' + $(this).parent().data('field'));
    rotation = parseInt(rotation_input.val());
    rotation = (rotation - 90) % 360;
    $(".preview_file").css({'transform': 'rotate('+rotation+'deg)'});
    rotation_input.val(rotation);
    $(this).parentsUntil('form').parent().find('.cpnt_form_buttons button').attr('disabled', false);
});


// Enable submit buttons and alert when leaving without saving
var has_confirmed = false;
var is_form_submitted = false;

$("form select, form input, form textarea").on('input', function(){
   $(this).parentsUntil('form').parent().find('.cpnt_form_buttons button').attr('disabled', false);
});

$('#form').submit(function() {
    is_form_submitted = true;
});

$(window).bind('beforeunload', function(){
    if($('#form').length && $('.cpnt_form_buttons button').attr('disabled') != 'disabled' && !is_form_submitted && !has_confirmed) {
        return 'Are you sure you want to leave?';
    }
});


// Save+action
var buttons = document.querySelectorAll('.cpnt_form_buttons .btn[type=button]');
buttons.forEach(function(button) {
    var step = button.getAttribute('data-step');
    button.addEventListener('click', function(e) {
        is_form_submitted = true;
        document.querySelector('.next_step').value = step;
        document.querySelector('form[name=ui_form]').submit();
    });
})


// Confirm window on a link (a:href)
$('a.confirm').each(function() {
    var url = $(this).attr('href');
    $(this).attr('href', 'javascript:;');
    $(this).click(function() {
        if(confirm($(this).data('message'))) {
            has_confirmed = true;
            window.location = url;
        }
    });
});


// Form translations tabs
tabs = document.querySelectorAll('.nav-tabs .nav-item a');
tabs.forEach(function(tab) {
    var pane_id = tab.getAttribute('href');
    tab.setAttribute('href', 'javascript:;');
    tab.addEventListener('click', function(e) { 
        document.querySelector('.nav-item a.active').classList.remove('active');
        document.querySelector('.tab-content .tab-pane.active').classList.remove('active');
        document.querySelector(pane_id).classList.add('active');
        this.classList.add('active');
    });
});

function initFormTypes() {
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
