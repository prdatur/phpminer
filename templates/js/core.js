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

function murl($controller, $action, $data, $is_html) {
    var $url;
    if (phpminer.settings.docroot === '/') {
        $url = '/' + $controller;
        if ($action !== undefined && $action !== null) {
            $url += '/' + $action;
            
            if ($data !== undefined && $data !== null) {
                $url += '/' + $data;
            }
        }
        if (!$is_html) {
            $url += '.json';
        }
        return $url;
    }
    else {
        $url = phpminer.settings.docroot + '/index.php?controller=' + $controller;
        if ($action !== undefined && $action !== null) {
            $url += '&action=' + $action;
            
            if ($data !== undefined && $data !== null) {
                $url += '&data=' + $data;
            }
        }
        if (!$is_html) {
            $url += '&type=json';
        }
        return $url;
    }
}

Soopfw.system_footer_behaviour = function() {
	$('*[data-pk]').editable();
        
        $('#save_cg_miner_config').off('click').on('click', function() {
            confirm("You are going to save the current changed values to cgminer config.\nAfter saving, when cgminer starts it will use the new settings.\n<b>Please make sure that your system runs stable with all overclocked settings.<b>", 'Warning', function() {
               ajax_request(murl('main', 'save_config'), null, function() {
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
        backdrop: false
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
                    };
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
