var used_uuids = {};
var Soopfw = {

};
var soopfw_ajax_queue = {};
$.extend(Soopfw, {
	behaviors: [],
	system_prio_behaviors: [],
	prio_behaviors: [],
	tab_behaviors: {},
	late_behaviors: [],
	current_tab: '',
	already_loaded_files: {},
	internal: {
		progressbars: {}
	},

	/**
	 * Holds all bindings for a specified tab.
	 */
	tab_bindings: {},
	
	/**
	 * Add a tab 
	 * 
	 * @param {string} tab
	 *   The tab id.
	 */
	add_tab: function(tab) {
		if (Soopfw.tab_bindings[tab] === undefined) {
			Soopfw.tab_bindings[tab] = {};
		}
		Soopfw.current_tab = tab;
	},
	
	/**
	 * Returns the size of the provided object.
	 * 
	 * @param {object} obj
	 *   The object to be counted.
	 *   
	 * @return int The object size.
	 */
	obj_size: function(obj) {
		var size = 0, key;
		for (key in obj) {
			if (obj.hasOwnProperty(key)) size++;
		}
		return size;
	},

	/**
	 * Behavious all js function should implement this instead of Jquery document ready
	 * Will be reloaded with every ajax_html and normal page request
	 */
	reload_behaviors: function() {
		//Priority behaviours will be loaded first
		for(var behavior_a in Soopfw.system_prio_behaviors) {
			if(Soopfw.system_prio_behaviors.hasOwnProperty(behavior_a)) {
				if(jQuery.isFunction(Soopfw.system_prio_behaviors[behavior_a])) {
					Soopfw.system_prio_behaviors[behavior_a]();
				}
			}
		}
		//Priority behaviours will be loaded first
		for(var behavior_x in Soopfw.prio_behaviors) {
			if(Soopfw.prio_behaviors.hasOwnProperty(behavior_x)) {
				if(jQuery.isFunction(Soopfw.prio_behaviors[behavior_x])) {
					Soopfw.prio_behaviors[behavior_x]();
				}
			}
		}
		for(var behavior_i in Soopfw.behaviors) {
			if(Soopfw.behaviors.hasOwnProperty(behavior_i)) {
				if(jQuery.isFunction(Soopfw.behaviors[behavior_i])) {
					Soopfw.behaviors[behavior_i]();
				}
			}
		}
		
		for(var behavior_y in Soopfw.late_behaviors) {
			if(Soopfw.late_behaviors.hasOwnProperty(behavior_y)) {
				if(jQuery.isFunction(Soopfw.late_behaviors[behavior_y])) {
					Soopfw.late_behaviors[behavior_y]();
				}
			}
		}
		Soopfw.system_footer_behaviour();
	},

	/**
	 * Translation function, key as an english text, args as an object {search => replace}.
	 * 
	 * @param {string} key
	 *   The english key (text).
	 * @param {object} args
	 *   The arguments which will be replaced within the text.
	 */
	t: function(key, args) {
		
		var translation = key;
		if(args !== undefined) {
			foreach(args, function(k, v) {
				translation = str_replace(k, v, translation);
			});
		}
		return translation;
	},

	/**
	 * Init a ajax queue with given identifier.
	 * 
	 * @param {string} identifier
	 *   The progress identifier.
	 */
	ajax_queue_init: function(identifier) {
		soopfw_ajax_queue[identifier] = [];
	},

	/**
	 * Adds to the given identifier queue an ajax call with the ajax options see Jquery ajax options for a complete list
	 * of ajax_options.
	 * 
	 * @param {string} identifier
	 *   The progress identifier.
	 * @param {object} ajax_options
	 */
	ajax_queue: function(identifier, ajax_options) {
		soopfw_ajax_queue[identifier].push(ajax_options);
	},

	/**
	 * Start the queue.
	 * 
	 * @param {string} identifier
	 *   The progress identifier.
	 */
	ajax_queue_start: function(identifier) {
		Soopfw.ajax_queue_worker(identifier);
	},

	/**
	 * Should not be called directly, will process the queue and on complete it will fetch next
	 * queue item and process until queue is empty
	 *
	 * @param {string} identifier
	 *   The progress identifier.
	 */
	ajax_queue_worker: function(identifier) {
		if(!empty(soopfw_ajax_queue[identifier])) {
			var o = soopfw_ajax_queue[identifier].shift();
			if(o === undefined) {
				return;
			}
			var old_complete = o.complete;
			o.complete = function() {
				if(old_complete !== undefined) {
					old_complete();
				}
				Soopfw.ajax_queue_worker(identifier);
			};
			$.ajax(o);
		}
	},

	/**
	 * Append an ajax load to the given div.
	 *
	 * @param {mixed} div 
	 *   Can be an jquery string or element object.
	 * @param {string} id 
	 *   an unique identifier for this ajax_loader.
	 */
	ajax_loader: function(div, id) {
		if(document.getElementById("ajax_loader_"+id) !== undefined) {
			$("#ajax_loader_"+id).remove();
			return;
		}
		$(div).append(
			create_element({input: 'div', attr: {id: 'ajax_loader_'+id, "class": 'ajax_loader'}, append:[
					create_element({input: 'img', attr: {src: Soopfw.config.template_path + '/images/ajax_loader_small.gif', valign:'absmiddle'}}),
					create_element({input: 'span', attr: {html: Soopfw.t("Loading content"), valign:'middle'}})
			]})
		);
	},

	/**
	 * Call an ajax_html ajax request to the given module, action with args and display the output html in a dialog
	 * After successfull load the ajax behaviours will be reloaded.
	 *
	 * @param {string} title 
	 *   the title of the dialog.
	 * @param {string} module 
	 *   The module.
	 * @param {string} action
	 *   the action to be called.
	 * @param {array} args 
	 *   The arguments for the action.
	 * @param {object} options 
	 *   Options which are used for the dialog.
	 * @param {array} get_params 
	 *   An array with get params.
	 *   
	 * @return string the created dialog id.
	 */
	default_action_dialog: function(title, module, action, args, options, get_params) {
		if(args !== undefined && args !== null) {
			args = '/'+implode('/', args);
		}
		else {
			args = "";
		}

		var get_param_string = '';
		if (!empty(get_params)) {
			var params = [];
			foreach (get_params, function(k,v) {
				params.push(k + '=' + v);
			});
			get_param_string = '?' + implode('&', params);
		}


		var id = module;
		if(action !== undefined && action !== true) {
			id += action;
			action = '/'+action;
		}
		else {
			action = '';
		}

		var url = module+action+args;


		if(!url.match(/^\//)) {
			url = '/'+url;
		}

		if(!url.match(/\.ajax_html$/i)) {
			url += '.ajax_html';
		}

		url += get_param_string;

		options = $.extend({
			title: title,
			modal: true,
			width: 500,
			open: function(event, ui) {
				Soopfw.reload_behaviors();
			}
		}, options);

		var matches = window.location.pathname.match(/^\/admin\/.*/g);
		if(matches !== null && matches.length > 0) {
			url = '/admin' + url;
		}
		id = 'jquery_dialog_' + id;
		wait_dialog();
		$.ajax({
			url: url,
			dataType: 'html',
			close: function() {
				$(this).dialog("destroy").remove();
			},
			success: function(result) {

				var matches = result.match(/<title>(.*)<\/title>/g);
				if(matches !== null && matches.length > 0) {
					matches = matches[0].replace("<title>","").replace("</title>","");
					if(!empty(matches)) {
						options['title'] = matches;
					}
				}
				$.alerts._hide();
				$('#'+id).remove();
				$('body').append(create_element({input: 'div', attr: {id:id, html: result}}) );
				$('#'+id).dialog(options);
			}
		});
		return id;
	},

	/**
	 * Redirects the user to the url what was configurated through 
	 * php within the redirect_url setting.
	 */
	redirect: function() {

		var url = Soopfw.config.redirect_url;
		if(!empty(url)) {
			Soopfw.location(url);
		}
	},
	
	reload: function() {
		document.location.reload();
	},

	/**
	 * Points the browser to the given url.
	 * 
	 * @param {string} url
	 *   The url
	 */
	location: function(url) {
		document.location.href = url;
	}
});

Soopfw.system_footer_behaviour = function() {
	$('*[data-pk]').editable();
        
        $('#save_cg_miner_config').off('click').on('click', function() {
            confirm("You are going to save the current changed values to cgminer config.\nAfter saving, when cgminer starts it will use the new settings.\n<b>Please make sure that your system runs stable with all overclocked settings.<b>", 'Warning', function() {
               ajax_request('/main/save_config.json', null, function() {
                   success_alert('Config saved', function() {
                       $('#save_cg_miner_config_container').fadeOut('slow');
                   });
               }); 
            });
        });
        
        $('*[data-toggle="tooltip"]').tooltip();
        
        $('.slider_toggle').each(function(){
            if ($(this).val() === '') {
                $(this).val(0);
            }
            $(this).before($('<div></div>').noUiSlider({
                range: [$(this).data('min'), $(this).data('max')],
                start: $(this).val(),
                handles: 1,
                margin: 2,
                step: $(this).data('steps'),
                decimals: 1,
                serialization: {
                    to: [$(this), 'value'],
                    resolution: 1
                }
            }));
        }); 
};

var tabs_loaded = {};
$(document).ready(function() {
    Soopfw.reload_behaviors();
});


function make_modal_dialog(title, html, buttons, options) {
    $('.modal').remove();
    
    var dialog_styles = '';
    if (options !== undefined) {
        if (options.width !== undefined) {
            dialog_styles += ' width:' + options.width + 'px;';
        }
    }
    
    if (dialog_styles !== '') {
        dialog_styles = ' style="' + dialog_styles + '"';
    }
    
    var dialog = "";
    dialog += '<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">';
    dialog += ' <div class="modal-dialog"' + dialog_styles + '>';
    dialog += '     <div class="modal-content">';
    
    dialog += '         <div class="modal-header">';
    if (options.cancelable === undefined || options.cancelable !== false) {
        dialog += '             <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>';
    }
    dialog += '             <h4 class="modal-title" id="myModalLabel">' + title + '</h4>';
    dialog += '         </div>';
    
    dialog += '         <div class="modal-body">';
    
    dialog += '             <div class="dialog-content">';
    dialog += html;
    dialog += '             </div>';
    
    dialog += '         </div>';
    
    if (!empty(buttons)) {
        dialog += '         <div class="modal-footer">';
        
        foreach (buttons, function(key, value){
            if (value === 'close') {
                dialog += '             <button type="button" class="btn btn-default" data-dismiss="modal">Cancel/Close</button>';
            }
            else {
                
                var attributes = "";
                if (value.id !== undefined) {
                    attributes += ' id="' + value.id + '"';
                }
                if (!empty(value.data)) {
                    foreach (value.data, function(attribute, attribute_value) {
                        attributes += ' data-' + attribute + '="' + attribute_value + '"';
                    });
                }
                
                dialog += '             <button type="button" class="btn btn-' + value.type + '"' + attributes + '>' + value.title + '</button>';
            }
        });
        dialog += '         </div>';
    }
    
    dialog += '     </div>';
    dialog += ' </div>';
    dialog += '</div>';
    
    var dlg_options = $.extend({
        backdrop: false,
    }, options);
    $(dialog).modal(dlg_options).on('shown.bs.modal', function(){
        if (!empty(buttons)) {
            foreach (buttons, function(key, value){
                if (value.data !== undefined && value.data['loading-text'] !== undefined) {
                    var old_click = function() {};
                    if (value.click !== undefined) {
                        old_click = value.click;
                    }
                    value.click = function() {
                        $(this).button('loading');
                        old_click();
                    }
                }
                if (value.click !== undefined) {
                    $('#' + value.id).on('click', value.click);
                }
            });
        }
        
        if (dlg_options.show !== undefined && dlg_options.show !== null) {
            dlg_options.show();
        }
    });
    return dialog;
}
