/*
 * jQuery UI Lasso 
 *
 * Dual licensed under the MIT (MIT-LICENSE.txt)
 * and GPL (GPL-LICENSE.txt) licenses.
 *
 * Pulled out of jquery-ui-selectable
 * http://docs.jquery.com/UI/Selectables
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.mouse.js
 *	jquery.ui.widget.js
 */
(function($) {

$.widget("ui.lasso", $.ui.mouse, {
	options: {
		appendTo: 'body'
	},
	_create: function() {
		var self = this;

		this.dragged = false;

		this._mouseInit();

		this.helper = $(document.createElement('div'))
			.css({border:'1px dotted black'})
			.addClass("ui-lasso-helper");
	},

	destroy: function() {
		this.element
			.unbind(".lasso");
		this._mouseDestroy();

		return this;
	},

	_mouseStart: function(event) {
		var self = this;

		this.opos = [event.pageX, event.pageY];

		if (this.options.disabled)
			return;

		var options = this.options;

		this._trigger("start", event);

		$(options.appendTo).append(this.helper);
		
		// position helper (lasso)
		this.helper.css({
			"z-index": 100,
			"position": "absolute",
			"left": event.clientX,
			"top": event.clientY,
			"width": 0,
			"height": 0
		});

	},

	_mouseDrag: function(event) {
		var self = this;
		this.dragged = true;
		
		
		if (this.options.disabled)
			return;

		var options = this.options;

		var x1 = this.opos[0], y1 = this.opos[1], x2 = event.pageX, y2 = event.pageY;
		if (x1 > x2) { var tmp = x2; x2 = x1; x1 = tmp; }
		if (y1 > y2) { var tmp = y2; y2 = y1; y1 = tmp; }
		var props = {left: x1, top: y1, width: x2-x1, height: y2-y1};
		this.helper.css(props);
  	  	
		this._trigger("drag", event, props);
		
		return false;
	},

	_mouseStop: function(event) {
		var self = this;

		this.dragged = false;
		
		if (this.options.disabled)
			return;
		
		var props = {
		  left: this.helper.css('left'),
		  top: this.helper.css('top'),
		  width: this.helper.css('width'),
		  height: this.helper.css('height')
		}

		this._trigger("stop", event, props);
		
		this.helper.remove();

		return false;
	}

});

$.extend($.ui.lasso, {
	version: "1.8rc1"
});

})(jQuery);

/**
 * jquery.multisortable.js - v0.2
 * https://github.com/shvetsgroup/jquery.multisortable
 *
 * Author: Ethan Atlakson, Jay Hayes, Gabriel Such, Alexander Shvets
 * Last Revision 3/16/2012
 * multi-selectable, multi-sortable jQuery plugin
 */

!function($) {

	$.fn.multiselectable = function(options) {
		if (!options) {
			options = {}
		}
		options = $.extend({}, $.fn.multiselectable.defaults, options);

		function mouseDown(e) {
			var item = $(this),
				parent = item.parent(),
				myIndex = item.index();

			var prev = parent.find('.multiselectable-previous');
			// If no previous selection found, start selecting from first selected item.
			prev = prev.length ? prev : $(parent.find('.' + options.selectedClass)[0]).addClass('multiselectable-previous');
			var prevIndex = prev.index();

			if (e.ctrlKey || e.metaKey) {
				if (item.hasClass(options.selectedClass)) {
					item.removeClass(options.selectedClass).removeClass('multiselectable-previous')
					if (item.not('.child').length) {
						item.nextUntil(':not(.child)').removeClass(options.selectedClass);
					}
				}
				else {
					parent.find('.multiselectable-previous').removeClass('multiselectable-previous');
					item.addClass(options.selectedClass).addClass('multiselectable-previous')
					if (item.not('.child').length) {
						item.nextUntil(':not(.child)').addClass(options.selectedClass);
					}
				}
			}

			if (e.shiftKey) {
				var last_shift_range = parent.find('.multiselectable-shift');
				last_shift_range.removeClass(options.selectedClass).removeClass('multiselectable-shift');

				var shift_range;
				if (prevIndex < myIndex) {
					shift_range = item.prevUntil('.multiselectable-previous').add(prev).add(item);
				}
				else if (prevIndex > myIndex) {
					shift_range = item.nextUntil('.multiselectable-previous').add(prev).add(item);
				}
				shift_range.addClass(options.selectedClass).addClass('multiselectable-shift');
			}
			else {
				parent.find('.multiselectable-shift').removeClass('multiselectable-shift');
			}

			if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
				parent.find('.multiselectable-previous').removeClass('multiselectable-previous');
				if (!item.hasClass(options.selectedClass)) {
					parent.find('.' + options.selectedClass).removeClass(options.selectedClass);
					item.addClass(options.selectedClass).addClass('multiselectable-previous');
					if (item.not('.child').length) {
						item.nextUntil(':not(.child)').addClass(options.selectedClass);
					}
				}
			}

			options.mousedown(e, item);
		}

		function click(e) {
			if ( $(this).is('.ui-draggable-dragging') ) {
				return;
			}

			var item = $(this),	parent = item.parent();

			// If item wasn't draged and is not multiselected, it should reset selection for other items.
			if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
				parent.find('.multiselectable-previous').removeClass('multiselectable-previous');
				parent.find('.' + options.selectedClass).removeClass(options.selectedClass);
				item.addClass(options.selectedClass).addClass('multiselectable-previous');
				if (item.not('.child').length) {
					item.nextUntil(':not(.child)').addClass(options.selectedClass);
				}
			}

			options.click(e, item);
		}

		return this.each(function() {
			var list = $(this);

			if (!list.data('multiselectable')) {
				list.data('multiselectable', true)
					.delegate(options.items, 'mousedown', mouseDown)
					.delegate(options.items, 'click', click)
					.disableSelection();
			}
		})
	};

	$.fn.multiselectable.defaults = {
		click: function(event, elem) {},
		mousedown: function(event, elem) {},
		selectedClass: 'selected',
		items: 'li'
	};

	$.fn.multisortable = function(options) {
		if (!options) {
			options = {}
		}
		var settings = $.extend({}, $.fn.multisortable.defaults, options);

		function regroup(item, list) {
			if (list.find('.' + settings.selectedClass).length > 0) {
				var myIndex = item.data('i');

				var itemsBefore = list.find('.' + settings.selectedClass).filter(function() {
					return $(this).data('i') < myIndex
				}).css({
						position: '',
						width: '',
						left: '',
						top: '',
						zIndex: ''
					});

				item.before(itemsBefore);

				var itemsAfter = list.find('.' + settings.selectedClass).filter(function() {
					return $(this).data('i') > myIndex
				}).css({
						position: '',
						width: '',
						left: '',
						top: '',
						zIndex: ''
					});

				item.after(itemsAfter);

				setTimeout(function() {
					itemsAfter.add(itemsBefore).addClass(settings.selectedClass);
				}, 0);
			}
		}

		return this.each(function() {
			var list = $(this);

			//enable multi-selection
			list.multiselectable({
				selectedClass: settings.selectedClass,
				click: settings.click,
				items: settings.items,
				mousedown: settings.mousedown
			});

			//enable sorting
			options.cancel = settings.items + ':not(.' + settings.selectedClass + ')';
			options.placeholder = settings.placeholder;
			options.start = function(event, ui) {
				if (ui.item.hasClass(settings.selectedClass)) {
					var parent = ui.item.parent();

					//assign indexes to all selected items
					parent.find('.' + settings.selectedClass).each(function(i) {
						$(this).data('i', i);
					});

					// adjust placeholder size to be size of items
					var height = parent.find('.' + settings.selectedClass).length * ui.item.outerHeight();
					ui.placeholder.height(height);
				}

				settings.start(event, ui);
			};

			options.stop = function(event, ui) {
				regroup(ui.item, ui.item.parent());
				settings.stop(event, ui);
			};

			options.sort = function(event, ui) {
				var parent = ui.item.parent(),
					myIndex = ui.item.data('i'),
					top = parseInt(ui.item.css('top').replace('px', '')),
					left = parseInt(ui.item.css('left').replace('px', ''));

				// fix to keep compatibility using prototype.js and jquery together
				$.fn.reverse = Array.prototype._reverse || Array.prototype.reverse

				var height = 0;
				$('.' + settings.selectedClass, parent).filter(function() {
					return $(this).data('i') < myIndex;
				}).reverse().each(function() {
						height += $(this).outerHeight();
						$(this).css({
							left: left,
							top: top - height,
							position: 'absolute',
							zIndex: 1000,
							width: ui.item.width()
						})
					});

				height = ui.item.outerHeight();
                
				$('.' + settings.selectedClass, parent).filter(function() {
					return $(this).data('i') > myIndex;
				}).each(function() {
						var item = $(this);
						item.css({
							left: left,
							top: top + height,
							position: 'absolute',
							zIndex: 1000,
							width: ui.item.width()
						});

						height += item.outerHeight();
					});

				settings.sort(event, ui);
			};

			options.receive = function(event, ui) {
				regroup(ui.item, ui.sender);
				settings.receive(event, ui);
			};

			list.sortable(options).disableSelection();
		})
	};

	$.fn.multisortable.defaults = {
		start: function(event, ui) {},
		stop: function(event, ui) {},
		sort: function(event, ui) {},
		receive: function(event, ui) {},
		click: function(event, elem) {},
		mousedown: function(event, elem) {},
		selectedClass: 'selected',
		placeholder: 'placeholder',
		items: 'li'
	};

}(jQuery);