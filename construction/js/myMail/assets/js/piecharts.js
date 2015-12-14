if (jQuery)(function ($) {

	"use strict"
	
	$.extend($.fn, {

		piechart: function (o, data) {

			var create = function (el, o) {

					var defaults = {
						percentage: true,
						value: 0,
						radius: null,
						total: 100,
						animate: true,
						duration: 1000,
						easing: 'swing',
						color: "#21759B",
						centercolor: "#ffffff"
					},

						width = el.width(),
						height = el.height(),

						opts = $.extend({
							x: width / 2,
							y: height / 2,
							w: width,
							h: height,
							startAngle: 180,
							startValue: 0
						}, defaults, o, el.data());

					opts.radius = (!opts.radius) ? Math.min(width / 2, height / 2) : opts.radius;

					opts.canvas = $('<canvas>').attr({
						width: width,
						height: height
					}).prependTo(el);

					opts.label = el.find('span');
					if (!opts.label.length) opts.label = $('<span>').appendTo(el);

					opts.startAngle -= 90;
					opts.ctx = opts.canvas[0].getContext("2d");


					el.data('piechart', opts);

					$(window).load(function () {
						update(el, opts.value);
					});
					return;

				};


			var destroy = function (el) {
				el.empty();
				el.removeData('piechart');
			};


			var update = function (el, data) {
				
				if (!isNaN(data)) {
					data = {
						value: data
					};
				}
				data = $.extend(el.data('piechart'), data);

				el.stop().delay(0).css({
					pievalue: 0
				}).animate({
					pievalue: 100
				}, {
					duration: (data.animate) ? data.duration : 0,
					easing: data.easing,
					step: function (now) {
						draw(data, now / 100);
					},
					complete: function () {
						//data.startValue = data.value;
						data.lastVal = data.value / data.total;
						el.data('piechart', data);
						draw(data, 100);
					}
				});


			};

			var draw = function (data, percentage) {

				var _start = data.lastVal || 0,
					offset = data.value / data.total - _start,
					label = '',
					value = deg2rad((_start + (offset * percentage)) * 360);

				data.endAngle = value;
				data.endAngle = deg2rad(data.startAngle) + value;

				data.ctx.clearRect(0, 0, data.w, data.h);
				data.ctx.beginPath();
				data.ctx.moveTo(data.x, data.y);
				data.ctx.arc(data.x, data.y, data.radius, deg2rad(data.startAngle), data.endAngle, false);
				data.ctx.closePath();
				data.ctx.fillStyle = data.color;
				data.ctx.fill();

				data.ctx.beginPath();
				data.ctx.moveTo(data.x, data.y);
				data.ctx.arc(data.x, data.y, data.radius * 0.55, 0, Math.PI * 2, false);
				data.ctx.closePath();
				data.ctx.fillStyle = data.centercolor;
				data.ctx.fill();
				
				label = (data.percentage) ? rad2per(value || 0).toFixed(0) + '%' : ((rad2per(value) * data.total / 100) || 0).toFixed(0);
				if(value && rad2per(value) < 1) label = '&lsaquo;1%';

				data.label.html(label);


			};

			var rad2per = function (rad) {
				return 100 * (rad / Math.PI / 2);
			};

			var rad2deg = function (rad) {
				return rad * (180 / Math.PI);
			};

			var deg2rad = function (deg) {
				return deg * (Math.PI / 180);
			};



			switch (o) {

				case 'update':
	
					$(this).each(function () {
						var el = $(this);
						(!el.data('piechart')) ? create(el, data) : update(el, data);
					});
	
	
					return $(this);
	
					break;
	
				case 'destroy':
	
					$(this).each(function () {
						destroy($(this));
					});
	
					return $(this);
	
				default:
	
					if (!o) o = {};
	
					$(this).each(function () {
						// Create the control
						create($(this), o);
	
					});
	
					return $(this);
	
			}


		}


	});





})(jQuery);