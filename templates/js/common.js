SoopfwPager = function(o) {
    this._options = {
        post_variable: '',
        container: '',
        effect: 'replace',
        current_page: 0,
        pages: 0,
        entries: 0,
        max_entries_per_page: 0,
        range: 10,
        front_range: 1,
        end_range: 0,
        link_template: 0,
        is_ajax: false,
        uuid: "",
        callback: null,
    };

    this.containers = {
    };
    $.extend(this._options, o);

    $.extend(SoopfwPager.prototype, {
        build_pager: function(page) {

            var pagerHTML = $("#pager_" + this._options.uuid);
            pagerHTML.html("");

            //Get our needed data
            var current_page = 0;
            if (page !== undefined) {
                current_page = page;
            }
            else {
                current_page = this._options.current_page;
            }
            var entries = this._options.entries;
            var max_entries_per_page = this._options.max_entries_per_page;
            var range = this._options.range;
            var front_range = this._options.front_range;
            var end_range = this._options.end_range;

            //Calculate the pages
            var pages = Math.ceil(entries / max_entries_per_page);

            //Setup next and prev page index
            var next_page = current_page;
            var prev_page = current_page;
            next_page++;
            prev_page--;

            if (next_page >= pages) { //Next page is more than we have pages so set it to first page
                next_page = 0;
            }

            if (prev_page < 0) { //prev page is smaller than 0 so set it to the max page
                prev_page = pages - 1;
            }

            var range_from = Math.floor(current_page - (range / 2));
            if (range_from < 0) {
                range_from = 0;
            }

            var range_to = range_from + range;
            if (range_to > pages) {
                range_to = pages;
                range_from = range_to - range;
            }

            if (range_from < 0) {
                range_from = 0;
            }

            //Build up previous page
            

            var first_container = $("<span class='pager_pagelinks pager_first'></span>");
            this.containers['first_container'] = first_container;
            if (prev_page !== pages - 1) {
                first_container.append(this.get_page_link(0, false, Soopfw.t("First")));
            }
            else {
                first_container.append("<span>First</span>");
            }
            pagerHTML.append(first_container);

            var previous_container = $("<span class='pager_pagelinks pager_previous'></span>");
            this.containers['previous_container'] = previous_container;
            if (prev_page !== pages - 1) {
                previous_container.append(this.get_page_link(prev_page, false, Soopfw.t("Previous")));
            }
            else {
                first_container.append("<span>Previous</span>");
            }
            pagerHTML.append(previous_container);

            //Build up the front range
            var front_container = $("<span class='pager_pagelinks pager_front_range'></span>");
            this.containers['front_container'] = front_container;
            var front_range_start = (range_from > front_range) ? front_range : range_from;
            if (0 < front_range_start) {
                pagerHTML.append(front_container);
            }
            for (var i = 0; i < front_range_start; i++) {
                front_container.append(this.get_page_link(i));
            }

            if (0 < front_range_start) {
                front_container.append(" ... ");
            }

            //Build up middle range
            var middle_container = $("<span class='pager_pagelinks pager_middle_range'></span>");
            this.containers['middle_container'] = middle_container;
            if (range_from < range_to) {
                pagerHTML.append(middle_container);
            }
            for (i = range_from; i < range_to; i++) {
                middle_container.append(this.get_page_link(i, (parseInt(i) === parseInt(current_page))));
            }

            //Build up the end range
            var end_container = $("<span class='pager_pagelinks pager_end_range'> ... </span>");
            this.containers['end_container'] = end_container;
            var end_range_start = pages - end_range;
            if (end_range_start <= range_to) {
                end_range_start = range_to;
            }

            if (end_range_start < pages) {
                pagerHTML.append(end_container);
            }

            for (i = end_range_start; i < pages; i++) {
                end_container.append(this.get_page_link(i));
            }

            //Build up next page
            

            var next_container = $("<span class='pager_pagelinks pager_next'></span>");
            this.containers['next_container'] = next_container;
            if (next_page !== 0) {
                next_container.append(this.get_page_link(next_page, false, Soopfw.t("Next")));
            }
            else {
                next_container.append("<span>Next</span>");
            }
            pagerHTML.append(next_container);

            var last_container = $("<span class='pager_pagelinks pager_last'></span>");
            this.containers['last_container'] = last_container;
            if (next_page !== 0) {
                next_container.append(this.get_page_link(pages - 1, false, Soopfw.t("Last")));
            }
            else {
                next_container.append("<span>Last</span>");
            }
            pagerHTML.append(last_container);
            

            //pagerHTML.append("<div class=\"clean\"></div>");

        },
        get_page_link: function(page, selected, text) {
            if (text === undefined || text === null) {
                text = page + 1;
                if (text < 10) {
                    text = "0" + text;
                }
            }

            if (this._options.is_ajax === false) {
                if (selected === true) {
                    return "<b>" + text + "</b>";
                }

                return "<a  href=\"" + str_replace("%page%", page, this._options.link_template) + "\">" + text + "</a>";
            }
            else {
                var css_class = "";
                if (selected === true) {
                    css_class = " page_link_selected";
                }
                var link = $("<a href=\"javascript:void(0);\" page=\"" + page + "\" class=\"" + css_class + "\">" + text + "</a>");
                var that = this;
                link.click(function() {
                    var page = $(this).attr("page");
                    var post_data = {};
                    if (that._options.post_variable !== undefined && that._options.post_variable !== "") {
                        post_data[that._options.post_variable] = page;
                    }
                    $('a', that.containers['middle_container']).removeClass("page_link_selected");
                    $('a[page="' + page + '"]', that.containers['middle_container']).addClass("page_link_selected");
                   
                    if (that._options.effect === "fade") {
                        $(that._options.container).hide('slow', function() {
                            that._options.callback(page);
                        });
                    }
                    else {
                        that._options.callback(page);
                        
                    }
                    that.build_pager(page);
                      
                });
            }
            return link[0];

        }
    });
};

var KEY_ENTER = 13;
var VK_ENTER = 13;

function soopfw_extend(original, new_elements) {
    if (original === undefined) {
        return {};
    }

    if (new_elements === undefined) {
        return original;
    }

    foreach(new_elements, function(k, v) {
        if (v === undefined) {
            return;
        }
        if (v === null) {
            original[k] = v;
        }
        else if (typeof v === 'object' || typeof v === 'array') {
            if (original[k] === undefined) {
                if (typeof v === 'array') {
                    original[k] = {};
                }
                else {
                    original[k] = [];
                }
            }
            original[k] = soopfw_extend(original[k], v);
        }
        else {
            original[k] = v;
        }
    });
    return original;
}
;

/**
 * JQuery :data selector.
 */
(function() {

    // original one.
    //var matcher = /\s*(?:((?:(?:\\\.|[^.,])+\.?)+)\s*([!~><=]=|[><])\s*("|')?((?:\\\3|.)*?)\3|(.+?))\s*(?:,|$)/g;
    var matcher = /\s*([a-zA-Z][a-zA-Z0-9_-]+)([=<>!]=?)([A-Z0-9a-z_-]+)(,\s*|$)/g;

    function resolve(element, data) {
        data = data.match(/(?:\\\.|[^.])+(?=\.|$)/g);
        var cur = $(element).data(data.shift());

        while (cur && data[0]) {
            cur = cur[data.shift()];
        }

        if (cur === 0) {
            return 0;
        }
        return cur || undefined;
    }

    jQuery.expr[':'].data = function(el, i, match) {

        matcher.lastIndex = 0;

        var expr = match[3], m, check, val, allMatch = null, foundMatch = false;
        while (m === matcher.exec(expr)) {

            check = m[3];
            val = $(el).data(m[1]);

            switch (m[2]) {
                case '==':
                    foundMatch = val === check;
                    break;
                case '!=':
                    foundMatch = val !== check;
                    break;
                case '<=':
                    foundMatch = val <= check;
                    break;
                case '>=':
                    foundMatch = val >= check;
                    break;
                case '~=':
                    foundMatch = RegExp(check).test(val);
                    break;
                case '>':
                    foundMatch = val > check;
                    break;
                case '<':
                    foundMatch = val < check;
                    break;
            }
            allMatch = allMatch === null ? foundMatch : allMatch && foundMatch;
        }

        return allMatch;
    };
}());

String.prototype.br2nl =
        function() {
            return this.replace(/<br\s*\/?>/mg, "\n");
        };

function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function foreach(arr, callback_func) {
    var i = "";
    for (i in arr) {
        if (arr.hasOwnProperty(i) === true) {
            if (callback_func(i, arr[i]) === true) {
                return;
            }
        }
    }
}

function gmdate(format, timestamp) {
    // http://kevin.vanzonneveld.net
    // +   original by: Brett Zamir (http://brett-zamir.me)
    // +   input by: Alex
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // -    depends on: date
    // *     example 1: gmdate('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400); // Return will depend on your timezone
    // *     returns 1: '07:09:40 m is month'
    var dt = typeof timestamp === 'undefined' ? new Date() : // Not provided
            typeof timestamp === 'object' ? new Date(timestamp) : // Javascript Date()
            new Date(timestamp * 1000); // UNIX timestamp (auto-convert to int)
    timestamp = Date.parse(dt.toUTCString().slice(0, -4)) / 1000;
    return date(format, timestamp);
}

Array.prototype.remove = function(removeItem) {
    $.grep(this, function(val) {
        return val !== removeItem;
    });
};

/**
 * Generate a password and return it, if elm is provided set the "value" attribute to the generated password
 * @param length (Int) The length of the password which will be generated (optional, default = 10)
 * @param elm (String) An jquery element selector (optional, default = undefined)
 * @param charSet (String) A charset which will be used to generate the random chars (optional, default = A-z0-9)
 * If you provide it, please do not use 0-9, you must provide every single char like 0123456789
 * @return String The generated password
 */
function generate_password(length, elm, charSet) {
    var rc = "";
    if (charSet === undefined) {
        charSet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";
    }
    var charArr = charSet.shuffleString();
    if (length === undefined) {
        length = 10;
    }
    for (var i = 0; i < length; i++)
    {
        charArr.shuffleString();
        rc += charArr.charAt(getRandomNum(0, charArr.length));
    }
    if (elm !== undefined) {
        $(elm).prop("value", rc);
    }
    return rc;
}

String.prototype.shuffleString = function()
{
    var tmpArr = [];
    var string = this;
    var len = string.length;
    var returnString = "";
    for (var i = 0; i < len; i++)
    {
        tmpArr.push(string.charAt(i));
    }
    tmpArr.shuffle();
    for (var i = 0; i < tmpArr.length; i++)
    {
        returnString += tmpArr[i];
    }
    return returnString;
};

Array.prototype.shuffle = function()
{
    var tmp, rand;
    for (var i = 0; i < this.length; i++)
    {
        rand = Math.floor(Math.random() * this.length);
        tmp = this[i];
        this[i] = this[rand];
        this[rand] = tmp;
    }
};

function getRandomNum(lbound, ubound) {
    return (Math.floor(Math.random() * (ubound - lbound)) + lbound);
}

function success_alert(msg, callback, title, timeout)
{
    if (msg === undefined) {
        msg = Soopfw.t("Operation successfully executed");
    }

    if (callback === undefined) {
        callback = function() {
        };
    }

    if (title === undefined) {
        title = Soopfw.t("Status");
    }
    $.alerts.iconClass = "success";
    if (timeout === undefined) {
        timeout = 2500;
    }
    $.alerts.timeout = timeout;
    return alert(msg, title, callback);
}

/**
 * Displays a question dialog and execute the callback_function if the user
 * click on "ok".
 * Questions are the same as confirmations but with a different icon
 *
 * @param {string} msg
 *   The message to display.
 * @param {string} html
 *   The html for this question.
 * @param {string} title
 *   The title for the dialog.
 * @param {mixed} callback_function
 *   An anonymous function or a function name as a string
 * @param {mixed} cancel_callback
 *   An anonymous function or a function name as a string
 * @param {object} options
 *   The options
 */
function question(msg, html, title, callback_function, cancel_callback, options)
{
    $.alerts.cancelButton = Soopfw.t("cancel");
    var data = {
        msg: msg,
        html: html
    };

    if (options !== undefined) {
        if (options['ok'] !== undefined) {
            data['ok'] = options['ok'];
        }
        if (options['cancel'] !== undefined) {
            data['cancel'] = options['cancel'];
        }
    }

    jQuestion(data, title, function(a, b) {
        if (a === true) {
            callback_function(b);
        }
        else if (cancel_callback !== undefined) {
            cancel_callback(b);
        }
    }, options);
}

/**
 * Displays a confirmation dialog and execute the callback_function if the user
 * click on "ok".
 *
 * @param {string} msg
 *   The message to display.
 * @param {string} title
 *   The title for the dialog.
 * @param {mixed} callback_function
 *   An anonymous function or a function name as a string
 * @param {boolean} parse_result
 *   If set to true we do not just execute the callback function if the user
 *   choose 'ok' instead we call EVERYTIME the callback function and provide
 *   as the first parameter the result if the use clicked 'ok' or 'cancel'
 */
function confirm(msg, title, callback_function, parse_result)
{
    if (callback_function === undefined) {
        callback_function = function() {
        };
    }
    $.alerts.cancelButton = Soopfw.t("cancel");
    return jConfirm(msg, title, function(r) {
        if (parse_result === true) {
            return callback_function(r);
        }
        if (r === true) {
            return callback_function();
        }
    });

}
/**
 * Displays a alert dialog and execute the callback_function if the user
 * click on "ok" or closes the dialog.
 *
 * @param {string} msg
 *   The message to display.
 * @param {string} title
 *   The title for the dialog.
 * @param {mixed} callback_function
 *   An anonymous function or a function name as a string
 */
function alert(msg, title, callback_function)
{
    if (callback_function === undefined) {
        callback_function = function() {
        };
    }
    $.alerts.cancelButton = Soopfw.t("cancel");
    if (title === undefined) {
        title = 'Error';
    }
    return jAlert(msg, title, callback_function);
}

function wait_dialog(msg, title)
{
    if (title === undefined) {
        title = Soopfw.t("Please wait");
    }
    if (msg === undefined) {
        msg = Soopfw.t("Action pending, please be patience");
    }
    return jWaitDialog(title, msg);
}

function get_form_by_class(classname, selector, checkboxFalse)
{
    if (selector === undefined || selector === null) {
        selector = "name";
    }
    if (classname === undefined) {
        classname = ".default_form";
    }
    else {
        var firstchar = classname.substr(0, 1);
        if (firstchar !== '.' && firstchar !== '#') {
            classname = "." + classname;
        }
    }
    var formVariables = {};
    $(classname).each(function(k, v) {
        if ($(v)[0].type === "checkbox") {
            if ($(v).prop("checked")) {
                formVariables[$(v).prop(selector)] = $(v).prop("value");
            }
            else if (checkboxFalse === true) {
                formVariables[$(v).prop(selector)] = "0";
            }
        }
        else if ($(v)[0].type === "radio") {
            if ($(v).prop("selected") || $(v).prop("checked")) {
                formVariables[$(v).prop(selector)] = $(v).prop("value");
            }
        }
        else if ($(v).data("sceditor") !== undefined) {
            formVariables[$(v).prop(selector)] = $(v).data("sceditor").val();
        }
        else {
            formVariables[$(v).prop(selector)] = $(v).prop("value");
        }
    });
    return formVariables;
}

function htmlspecialchars(str, typ) {
    if (typeof str === "undefined")
        str = "";
    if (typeof typ !== "number")
        typ = 2;
    typ = Math.max(0, Math.min(3, parseInt(typ)));
    var from = new Array(/&/g, /</g, />/g);
    var to = new Array("&amp;", "&lt;", "&gt;");
    if (typ === 1 || typ === 3) {
        from.push(/'/g);
        to.push("&#039;");
    }
    if (typ === 2 || typ === 3) {
        from.push(/"/g);
        to.push("&quot;");
    }
    for (var i in from)
        str = str.replace(from[i], to[i]);
    return str;
}

var reconnect_in_progress = false;
function try_reconnect(from_timeout) {
    if (from_timeout === undefined) {
        if (reconnect_in_progress === true) {
            return;
        }
        wait_dialog('PHPMiner can not connect to CGMiner/SGMiner api, it will now try to reconnect periodicly.', 'Connection to CGMiner/SGMiner lost');
    }
    reconnect_in_progress = true;
    ajax_request(murl('main', 'connection_reconnect'), null, function() {
        reconnect_in_progress = false;
        Soopfw.reload();
    }, function() {
        setTimeout(function() {
            try_reconnect(true);
        }, 5000);
    }, {}, true);
}

function parse_ajax_result(result, return_function, additionalParams, error_function, silent, prev_request_options)
{

    if (additionalParams === undefined || additionalParams === null)
    {
        var additionalParams = new Object();
    }

    if (return_function === undefined) {
        return_function = function() {
        };
    }

    var code = parseInt(result.code);

    if (code === 560) {
        confirm('An application error occured, would you like to send a bug report? You can view the included data at ' + result.data, 'Application error', function() {
            wait_dialog('Please wait, sending report', 'Bug report');
            ajax_success(murl('main', 'bugreport'), {bugreport: result.data}, 'Thank you', 'Bugreport');
        });
        return false;
    }
    if (code === 205 && !empty(result.data)) {
        $('#' + result.data).dialog("destroy");
        return true;
    }

    if (code === 301 && !empty(result.data)) {
        Soopfw.location(result.data);
        return true;
    }

    if (code >= 200 && code < 400) {
        var res = return_function(result.data, result.code, result.desc, additionalParams);
        if (code === 206) {
            Soopfw.reload();
        }
        return res;
    }

    if (code === 502) {
        try_reconnect();
        return false;
    }
    if (code >= 600 && code < 700) {
        if (error_function !== undefined && error_function !== null) {
            error_function(result);
        }
        if (silent !== true) {
            alert(Soopfw.t("No permission") + "\n" + result.desc);
        }
        return false;
    }

    if (code === 701) {
        confirm(result.desc, 'Confirm action', function() {
            if (prev_request_options['dataArray'] === undefined) {
                prev_request_options['dataArray'] = {};
            }
            prev_request_options['dataArray']['confirm'] = true;
            ajax_request(prev_request_options['url'], prev_request_options['dataArray'], prev_request_options['return_function'], prev_request_options['error_function'], prev_request_options['options'], prev_request_options['silent']);
        });
        return;
    }

    if (code === 406) {
        if (error_function !== undefined && error_function !== null) {
            error_function(result);
        }
        if (silent !== true) {
            if (result.desc !== undefined && result.desc !== "") {
                alert(result.desc);
            }
            else {
                alert(Soopfw.t("You did not filled out all required fields") + "\n" + result.desc);
            }
            
        }
        return false;
    }
    if (error_function !== undefined && error_function !== null) {
        error_function(result);
    }
    if (silent !== true) {
        if (result.desc === undefined || result.desc === "") {
            alert(Soopfw.t("Ajax call failed") + "\nUnknown error (" + result.code + ")");
        }
        else {
            alert(result.desc);
        }
    }
    return false;
}

function ajax_success(url, data, msg, title, return_function, error_function)
{
    if (title === undefined || title === '' || title === null)
    {
        title = Soopfw.t("Status");
    }
    if (msg === undefined || msg === '' || msg === null)
    {
        msg = Soopfw.t("Operation successfully executed");
    }
    ajax_request(url, data, function(result) {
        success_alert(msg, function() {
            if (return_function === undefined)
            {
                return true;
            }
            return_function(result);
        }, title);
    }, error_function);
}
function ajax_request(url, dataArray, return_function, error_function, options, silent)
{
    var prev_request_options = {
        url: url,
        dataArray: dataArray,
        return_function: return_function,
        error_function: error_function,
        options: options,
        silent: silent
    };
    $.ajax($.extend({
        type: 'POST',
        dataType: 'json',
        url: url,
        async: true,
        data: dataArray,
        success: function(result) {
            if (error_function === undefined) {
                error_function = function() {
                };
            }
            if (return_function === undefined) {
                return_function = function() {
                };
            }

            parse_ajax_result(result, function(result, code, desc, additionalParams) {
                return_function(result, code, desc, additionalParams);
            }, null, error_function, silent, prev_request_options);
        }
    }, options));
}

function implode(glue, pieces) {
    // Joins array elements placing glue string between items and return one string
    //
    // version: 911.718
    // discuss at: http://phpjs.org/functions/implode    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Waldo Malqui Silva
    // +   improved by: Itsacon (http://www.itsacon.net/)
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: implode(' ', ['Kevin', 'van', 'Zonneveld']);    // *     returns 1: 'Kevin van Zonneveld'
    // *     example 2: implode(' ', {first:'Kevin', last: 'van Zonneveld'});
    // *     returns 2: 'Kevin van Zonneveld'
    var i = '', retVal = '', tGlue = '';
    if (arguments.length === 1) {
        pieces = glue;
        glue = '';
    }
    if (typeof (pieces) === 'object') {
        if (pieces instanceof Array) {
            return pieces.join(glue);
        }
        else {
            for (i in pieces) {
                retVal += tGlue + pieces[i];
                tGlue = glue;
            }
            return retVal;
        }
    } else {
        return pieces;
    }
}

function explode(delimiter, string, limit) {
    // Splits a string on string separator and return array of components. If limit is positive only limit number of components is returned. If limit is negative all components except the last abs(limit) are returned.
    //
    // version: 909.322
    // discuss at: http://phpjs.org/functions/explode    // +     original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: kenneth
    // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     improved by: d3x
    // +     bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // *     example 1: explode(' ', 'Kevin van Zonneveld');
    // *     returns 1: {0: 'Kevin', 1: 'van', 2: 'Zonneveld'}
    // *     example 2: explode('=', 'a=bc=d', 2);
    // *     returns 2: ['a', 'bc=d']
    var emptyArray = {0: ''};

    // third argument is not required
    if (arguments.length < 2 || typeof arguments[0] === 'undefined' || typeof arguments[1] === 'undefined')
    {
        return null;
    }

    if (delimiter === '' || delimiter === false || delimiter === null)
    {
        return false;
    }

    if (typeof delimiter === 'function' || typeof delimiter === 'object' || typeof string === 'function' || typeof string === 'object')
    {
        return emptyArray;
    }
    if (delimiter === true)
    {
        delimiter = '1';
    }

    if (!limit)
    {
        return string.toString().split(delimiter.toString());
    }
    else
    {
        // support for limit argument
        var splitted = string.toString().split(delimiter.toString());
        var partA = splitted.splice(0, limit - 1);
        var partB = splitted.join(delimiter.toString());
        partA.push(partB);
        return partA;
    }
}

function call_user_func(cb) {
    if (typeof cb === 'string') {
        func = (typeof this[cb] === 'function') ? this[cb] : func = (new Function(null, 'return ' + cb))();
    } else if (Object.prototype.toString.call(cb) === '[object Array]') {
        func = (typeof cb[0] === 'string') ? eval(cb[0] + "['" + cb[1] + "']") : func = cb[0][cb[1]];
    }
    else if (typeof cb === 'function') {
        func = cb;
    }

    if (typeof func !== 'function') {
        throw new Error(func + ' is not a valid function');
    }
    var parameters = Array.prototype.slice.call(arguments, 1);
    return (typeof cb[0] === 'string') ? func.apply(eval(cb[0]), parameters) : (typeof cb[0] !== 'object') ? func.apply(null, parameters) : func.apply(cb[0], parameters);
}

function is_array(mixed_var) {
    var key = '';
    var getFuncName = function(fn) {
        var name = (/\W*function\s+([\w\$]+)\s*\(/).exec(fn);
        if (!name) {
            return '(Anonymous)';
        }
        return name[1];
    };

    if (!mixed_var) {
        return false;
    }



    if (typeof mixed_var === 'object') {
        if (mixed_var.hasOwnProperty) {
            for (key in mixed_var) {
                // Checks whether the object has the specified property
                // if not, we figure it's not an object in the sense of a php-associative-array.
                if (false === mixed_var.hasOwnProperty(key)) {
                    return false;
                }
            }
        }
        // Read discussion at: http://kevin.vanzonneveld.net/techblog/article/javascript_equivalent_for_phps_is_array/
        return true;
    }

    return false;
}

function count(mixed_var, mode) {
    var key, cnt = 0;

    if (mixed_var === null) {
        return 0;

    }
    else if (!is_array(mixed_var)) {
        return 1;
    }
    if (mode === 'COUNT_RECURSIVE') {
        mode = 1;
    }
    if (mode !== 1) {
        mode = 0;
    }

    for (key in mixed_var) {
        cnt++;
        if (mode === 1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object)) {
            cnt += this.count(mixed_var[key], 1);
        }
    }

    return cnt;
}

function array_keys(input, search_value, argStrict) {

    var tmp_arr = {}, strict = !!argStrict, include = true, cnt = 0;
    var key = '';

    for (key in input) {
        include = true;
        if (search_value !== undefined) {
            if (strict && input[key] !== search_value) {
                include = false;
            } else if (input[key] !== search_value) {
                include = false;
            }
        }

        if (include) {
            tmp_arr[cnt] = key;
            cnt++;
        }
    }
    return tmp_arr;
}

function trim(zeichenkette) {
    // Erst führende, dann Abschließende Whitespaces entfernen
    // und das Ergebnis dieser Operationen zurückliefern
    return zeichenkette.replace(/^\s+/, '').replace(/\s+$/, '');
}


function nl2br(str, is_xhtml) {
    // http://kevin.vanzonneveld.net
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Philip Peterson
    // +   improved by: Onno Marsman
    // +   improved by: Atli Þór
    // +   bugfixed by: Onno Marsman
    // +      input by: Brett Zamir (http://brett-zamir.me)
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // +   improved by: Maximusya
    // *     example 1: nl2br('Kevin\nvan\nZonneveld');
    // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
    // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
    // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
    // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
    // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'

    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';

    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
}

function str_replace(search, replace, subject) {
    return subject.split(search).join(replace);
}

function date_format(date, format)
{
    return format_date(date, format);
}
function format_date(date, format)
{

    if (format === undefined)
    {
        format = "dd.mm.yyyy HH:MM:ss";
    }
    if (date === undefined || date === null)
    {
        date = new Date();
    }
    else
    {
        if (parseInt(date) !== date && parseFloat(date) !== date)
        {
            date = date + " GMT+0100";
            date = Date.parse(date);
        }
    }
    var tmpDate = new Date(date);
    return tmpDate.format(format);
}

function date_compare(todate, fromdate)
{
    //console.log(todate);
    if (parseInt(todate) !== todate)
    {
        todate = todate + " GMT+0100";
        todate = Date.parse(todate);
    }
    else
    {
        todate = new Date(todate);
    }


    if (fromdate === undefined)
    {
        fromdate = new Date();
    }
    else
    {
        fromdate = new Date(Date.parse(fromdate));
    }


    //console.log(fromdate);
    //console.log(todate);
    return todate.compareTo(fromdate);
}

function empty(mixed_var) {
    // !No description available for empty. @php.js developers: Please update the function summary text file.
    //
    // version: 911.1619
    // discuss at: http://phpjs.org/functions/empty    // +   original by: Philippe Baumann
    // +      input by: Onno Marsman
    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: LH
    // +   improved by: Onno Marsman    // +   improved by: Francesco
    // +   improved by: Marc Jansen
    // +   input by: Stoyan Kyosev (http://www.svest.org/)
    // *     example 1: empty(null);
    // *     returns 1: true    // *     example 2: empty(undefined);
    // *     returns 2: true
    // *     example 3: empty([]);
    // *     returns 3: true
    // *     example 4: empty({});    // *     returns 4: true
    // *     example 5: empty({'aFunc' : function () { alert('humpty'); } });
    // *     returns 5: false

    var key;
    if (mixed_var === "" ||
            mixed_var === 0 ||
            mixed_var === "0" ||
            mixed_var === null || mixed_var === false ||
            typeof mixed_var === 'undefined'
            ) {
        return true;
    }
    if (typeof mixed_var === 'object') {
        for (key in mixed_var) {
            if (mixed_var.hasOwnProperty(key)) {
                return false;
            }
        }
        return true;
    }

    return false;
}

function parseID(value, index, splitchar) {
    if (splitchar === undefined)
    {
        splitchar = "_";
    }

    if (index === undefined)
    {
        return $(value).prop("id").split(splitchar);
    }
    else
    {
        return $(value).prop("id").split(splitchar)[index];
    }
}

// mredkj.com
function NumberFormat(num, inputDecimal)
{
    this.VERSION = 'Number Format v1.5.4';
    this.COMMA = ',';
    this.PERIOD = '.';
    this.DASH = '-';
    this.LEFT_PAREN = '(';
    this.RIGHT_PAREN = ')';
    this.LEFT_OUTSIDE = 0;
    this.LEFT_INSIDE = 1;
    this.RIGHT_INSIDE = 2;
    this.RIGHT_OUTSIDE = 3;
    this.LEFT_DASH = 0;
    this.RIGHT_DASH = 1;
    this.PARENTHESIS = 2;
    this.NO_ROUNDING = -1;
    this.num;
    this.numOriginal;
    this.hasSeparators = false;
    this.separatorValue;
    this.inputDecimalValue;
    this.decimalValue;
    this.negativeFormat;
    this.negativeRed;
    this.hasCurrency;
    this.currencyPosition;
    this.currencyValue;
    this.places;
    this.roundToPlaces;
    this.truncate;
    this.setNumber = setNumberNF;
    this.toUnformatted = toUnformattedNF;
    this.setInputDecimal = setInputDecimalNF;
    this.setSeparators = setSeparatorsNF;
    this.setCommas = setCommasNF;
    this.setNegativeFormat = setNegativeFormatNF;
    this.setNegativeRed = setNegativeRedNF;
    this.setCurrency = setCurrencyNF;
    this.setCurrencyPrefix = setCurrencyPrefixNF;
    this.setCurrencyValue = setCurrencyValueNF;
    this.setCurrencyPosition = setCurrencyPositionNF;
    this.setPlaces = setPlacesNF;
    this.toFormatted = toFormattedNF;
    this.toPercentage = toPercentageNF;
    this.getOriginal = getOriginalNF;
    this.moveDecimalRight = moveDecimalRightNF;
    this.moveDecimalLeft = moveDecimalLeftNF;
    this.getRounded = getRoundedNF;
    this.preserveZeros = preserveZerosNF;
    this.justNumber = justNumberNF;
    this.expandExponential = expandExponentialNF;
    this.getZeros = getZerosNF;
    this.moveDecimalAsString = moveDecimalAsStringNF;
    this.moveDecimal = moveDecimalNF;
    this.addSeparators = addSeparatorsNF;
    if (inputDecimal === null) {
        this.setNumber(num, this.PERIOD);
    } else {
        this.setNumber(num, inputDecimal);
    }
    this.setCommas(true);
    this.setNegativeFormat(this.LEFT_DASH);
    this.setNegativeRed(false);
    this.setCurrency(false);
    this.setCurrencyPrefix('$');
    this.setPlaces(2);
}
function setInputDecimalNF(val)
{
    this.inputDecimalValue = val;
}
function setNumberNF(num, inputDecimal)
{
    if (inputDecimal !== null) {
        this.setInputDecimal(inputDecimal);
    }
    this.numOriginal = num;
    this.num = this.justNumber(num);
}
function toUnformattedNF()
{
    return (this.num);
}
function getOriginalNF()
{
    return (this.numOriginal);
}
function setNegativeFormatNF(format)
{
    this.negativeFormat = format;
}
function setNegativeRedNF(isRed)
{
    this.negativeRed = isRed;
}
function setSeparatorsNF(isC, separator, decimal)
{
    this.hasSeparators = isC;
    if (separator === null)
        separator = this.COMMA;
    if (decimal === null)
        decimal = this.PERIOD;
    if (separator === decimal) {
        this.decimalValue = (decimal === this.PERIOD) ? this.COMMA : this.PERIOD;
    } else {
        this.decimalValue = decimal;
    }
    this.separatorValue = separator;
}
function setCommasNF(isC)
{
    this.setSeparators(isC, this.COMMA, this.PERIOD);
}
function setCurrencyNF(isC)
{
    this.hasCurrency = isC;
}
function setCurrencyValueNF(val)
{
    this.currencyValue = val;
}
function setCurrencyPrefixNF(cp)
{
    this.setCurrencyValue(cp);
    this.setCurrencyPosition(this.LEFT_OUTSIDE);
}
function setCurrencyPositionNF(cp)
{
    this.currencyPosition = cp;
}
function setPlacesNF(p, tr)
{
    this.roundToPlaces = !(p === this.NO_ROUNDING);
    this.truncate = (tr !== null && tr);
    this.places = (p < 0) ? 0 : p;
}
function addSeparatorsNF(nStr, inD, outD, sep)
{
    nStr += '';
    var dpos = nStr.indexOf(inD);
    var nStrEnd = '';
    if (dpos !== -1) {
        nStrEnd = outD + nStr.substring(dpos + 1, nStr.length);
        nStr = nStr.substring(0, dpos);
    }
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(nStr)) {
        nStr = nStr.replace(rgx, '$1' + sep + '$2');
    }
    return nStr + nStrEnd;
}
function toFormattedNF()
{
    var pos;
    var nNum = this.num;
    var nStr;
    var splitString = new Array(2);
    if (this.roundToPlaces) {
        nNum = this.getRounded(nNum);
        nStr = this.preserveZeros(Math.abs(nNum));
    } else {
        nStr = this.expandExponential(Math.abs(nNum));
    }
    if (this.hasSeparators) {
        nStr = this.addSeparators(nStr, this.PERIOD, this.decimalValue, this.separatorValue);
    } else {
        nStr = nStr.replace(new RegExp('\\' + this.PERIOD), this.decimalValue);
    }
    var c0 = '';
    var n0 = '';
    var c1 = '';
    var n1 = '';
    var n2 = '';
    var c2 = '';
    var n3 = '';
    var c3 = '';
    var negSignL = (this.negativeFormat === this.PARENTHESIS) ? this.LEFT_PAREN : this.DASH;
    var negSignR = (this.negativeFormat === this.PARENTHESIS) ? this.RIGHT_PAREN : this.DASH;
    if (this.currencyPosition === this.LEFT_OUTSIDE) {
        if (nNum < 0) {
            if (this.negativeFormat === this.LEFT_DASH || this.negativeFormat === this.PARENTHESIS)
                n1 = negSignL;
            if (this.negativeFormat === this.RIGHT_DASH || this.negativeFormat === this.PARENTHESIS)
                n2 = negSignR;
        }
        if (this.hasCurrency)
            c0 = this.currencyValue + " ";
    } else if (this.currencyPosition === this.LEFT_INSIDE) {
        if (nNum < 0) {
            if (this.negativeFormat === this.LEFT_DASH || this.negativeFormat === this.PARENTHESIS)
                n0 = negSignL;
            if (this.negativeFormat === this.RIGHT_DASH || this.negativeFormat === this.PARENTHESIS)
                n3 = negSignR;
        }
        if (this.hasCurrency)
            c1 = this.currencyValue + " ";
    }
    else if (this.currencyPosition === this.RIGHT_INSIDE) {
        if (nNum < 0) {
            if (this.negativeFormat === this.LEFT_DASH || this.negativeFormat === this.PARENTHESIS)
                n0 = negSignL;
            if (this.negativeFormat === this.RIGHT_DASH || this.negativeFormat === this.PARENTHESIS)
                n3 = negSignR;
        }
        if (this.hasCurrency)
            c2 = " " + this.currencyValue;
    }
    else if (this.currencyPosition === this.RIGHT_OUTSIDE) {
        if (nNum < 0) {
            if (this.negativeFormat === this.LEFT_DASH || this.negativeFormat === this.PARENTHESIS)
                n1 = negSignL;
            if (this.negativeFormat === this.RIGHT_DASH || this.negativeFormat === this.PARENTHESIS)
                n2 = negSignR;
        }
        if (this.hasCurrency)
            c3 = " " + this.currencyValue;
    }
    nStr = c0 + n0 + c1 + n1 + nStr + n2 + c2 + n3 + c3;
    if (this.negativeRed && nNum < 0) {
        nStr = '<font color="red">' + nStr + '</font>';
    }
    return (nStr);
}
function toPercentageNF()
{
    nNum = this.num * 100;
    nNum = this.getRounded(nNum);
    return nNum + '%';
}
function getZerosNF(places)
{
    var extraZ = '';
    var i;
    for (i = 0; i < places; i++) {
        extraZ += '0';
    }
    return extraZ;
}
function expandExponentialNF(origVal)
{
    if (isNaN(origVal))
        return origVal;
    var newVal = parseFloat(origVal) + '';
    var eLoc = newVal.toLowerCase().indexOf('e');
    if (eLoc !== -1) {
        var plusLoc = newVal.toLowerCase().indexOf('+');
        var negLoc = newVal.toLowerCase().indexOf('-', eLoc);
        var justNumber = newVal.substring(0, eLoc);
        if (negLoc !== -1) {
            var places = newVal.substring(negLoc + 1, newVal.length);
            justNumber = this.moveDecimalAsString(justNumber, true, parseInt(places));
        } else {
            if (plusLoc === -1)
                plusLoc = eLoc;
            var places = newVal.substring(plusLoc + 1, newVal.length);
            justNumber = this.moveDecimalAsString(justNumber, false, parseInt(places));
        }
        newVal = justNumber;
    }
    return newVal;
}
function moveDecimalRightNF(val, places)
{
    var newVal = '';
    if (places === null) {
        newVal = this.moveDecimal(val, false);
    } else {
        newVal = this.moveDecimal(val, false, places);
    }
    return newVal;
}
function moveDecimalLeftNF(val, places)
{
    var newVal = '';
    if (places === null) {
        newVal = this.moveDecimal(val, true);
    } else {
        newVal = this.moveDecimal(val, true, places);
    }
    return newVal;
}
function moveDecimalAsStringNF(val, left, places)
{
    var spaces = (arguments.length < 3) ? this.places : places;
    if (spaces <= 0)
        return val;
    var newVal = val + '';
    var extraZ = this.getZeros(spaces);
    var re1 = new RegExp('([0-9.]+)');
    if (left) {
        newVal = newVal.replace(re1, extraZ + '$1');
        var re2 = new RegExp('(-?)([0-9]*)([0-9]{' + spaces + '})(\\.?)');
        newVal = newVal.replace(re2, '$1$2.$3');
    } else {
        var reArray = re1.exec(newVal);
        if (reArray !== null) {
            newVal = newVal.substring(0, reArray.index) + reArray[1] + extraZ + newVal.substring(reArray.index + reArray[0].length);
        }
        var re2 = new RegExp('(-?)([0-9]*)(\\.?)([0-9]{' + spaces + '})');
        newVal = newVal.replace(re2, '$1$2$4.');
    }
    newVal = newVal.replace(/\.$/, '');
    return newVal;
}
function moveDecimalNF(val, left, places)
{
    var newVal = '';
    if (places === null) {
        newVal = this.moveDecimalAsString(val, left);
    } else {
        newVal = this.moveDecimalAsString(val, left, places);
    }
    return parseFloat(newVal);
}
function getRoundedNF(val)
{
    val = this.moveDecimalRight(val);
    if (this.truncate) {
        val = val >= 0 ? Math.floor(val) : Math.ceil(val);
    } else {
        val = Math.round(val);
    }
    val = this.moveDecimalLeft(val);
    return val;
}
function preserveZerosNF(val)
{
    var i;
    val = this.expandExponential(val);
    if (this.places <= 0)
        return val;
    var decimalPos = val.indexOf('.');
    if (decimalPos === -1) {
        val += '.';
        for (i = 0; i < this.places; i++) {
            val += '0';
        }
    } else {
        var actualDecimals = (val.length - 1) - decimalPos;
        var difference = this.places - actualDecimals;
        for (i = 0; i < difference; i++) {
            val += '0';
        }
    }
    return val;
}
function justNumberNF(val)
{
    newVal = val + '';
    var isPercentage = false;
    if (newVal.indexOf('%') !== -1) {
        newVal = newVal.replace(/\%/g, '');
        isPercentage = true;
    }
    var re = new RegExp('[^\\' + this.inputDecimalValue + '\\d\\-\\+\\(\\)eE]', 'g');
    newVal = newVal.replace(re, '');
    var tempRe = new RegExp('[' + this.inputDecimalValue + ']', 'g');
    var treArray = tempRe.exec(newVal);
    if (treArray !== null) {
        var tempRight = newVal.substring(treArray.index + treArray[0].length);
        newVal = newVal.substring(0, treArray.index) + this.PERIOD + tempRight.replace(tempRe, '');
    }
    if (newVal.charAt(newVal.length - 1) === this.DASH) {
        newVal = newVal.substring(0, newVal.length - 1);
        newVal = '-' + newVal;
    }
    else if (newVal.charAt(0) === this.LEFT_PAREN
            && newVal.charAt(newVal.length - 1) === this.RIGHT_PAREN) {
        newVal = newVal.substring(1, newVal.length - 1);
        newVal = '-' + newVal;
    }
    newVal = parseFloat(newVal);
    if (!isFinite(newVal)) {
        newVal = 0;
    }
    if (isPercentage) {
        newVal = this.moveDecimalLeft(newVal, 2);
    }
    return newVal;
}

function money_format(value, currency)
{
    var num = new NumberFormat();
    num.setInputDecimal('.');
    num.setNumber(value);
    num.setPlaces('2');
    num.setCurrencyValue(currency);
    num.setCurrency(true);
    num.setCurrencyPosition(num.RIGHT_OUTSIDE);
    num.setNegativeFormat(num.LEFT_DASH);
    num.setNegativeRed(true);
    num.setSeparators(true, '\'', ',');
    return num.toFormatted();
}

//
// // jQuery Alert Dialogs Plugin
//
// Version 1.1
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 14 May 2009
//
// Visit http://abeautifulsite.net/notebook/87 for more information
//
// Usage:
//		jAlert( message, [title, callback] )
//		jConfirm( message, [title, callback] )
//		jQuestion( html, [title, callback] )
//		jPrompt( message, [value, title, callback] )
//
// History:
//
//		1.00 - Released (29 December 2008)
//
//		1.01 - Fixed bug where unbinding would destroy all resize events
//
// License:
//
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC.
//
(function($) {


    $.alerts = {
        // These properties can be read/written by accessing $.alerts.propertyName from your scripts at any time

        timeout: -1, // timeout, will hide alert after x milliseconds
        last_timeout: null,
        verticalOffset: -75, // vertical offset of the dialog from center screen, in pixels
        horizontalOffset: 0, // horizontal offset of the dialog from center screen, in pixels/
        repositionOnResize: true, // re-centers the dialog on window resize
        overlayOpacity: .71, // transparency level of overlay
        overlayColor: 'rgba(82, 101, 114, 0.45);', // base color of overlay
        draggable: true, // make the dialogs draggable (requires UI Draggables plugin)
        okButton: '&nbsp;OK&nbsp;', // text for the OK button
        cancelButton: '&nbsp;Cancel&nbsp;', // text for the Cancel button
        dialogClass: null, // if specified, this class will be applied to all dialogs
        iconClass: "",
        // Public methods

        alert: function(message, title, callback, options) {
            if (title === null) {
                title = Soopfw.t('Alert');
            }
            var alert = $.alerts._show(title, message, null, 'alert', function(result) {
                if (callback) {
                    callback(result);
                }
            }, options);
            $.alerts.iconClass = "";
            return alert;
        },
        wait_dialog: function(title, message, options) {
            if (title === null) {
                title = Soopfw.t("Please wait");
            }
            var alert = $.alerts._show(title, message, null, 'wait_dialog', function() {
            }, options);
            $.alerts.iconClass = "";
            return alert;
        },
        question: function(html, title, callback, options) {
            if (title === null) {
                title = Soopfw.t('Choose');
            }

            $.alerts._show(title, html, null, 'question', function(result, html) {
                if (callback) {
                    callback(result, html);
                }
            }, options);
        },
        confirm: function(message, title, callback, options) {
            if (title === null) {
                title = Soopfw.t('Confirm');
            }

            var alert = $.alerts._show(title, message, null, 'confirm', function(result) {
                if (callback) {
                    return callback(result);
                }
                return true;
            }, options);
            return alert;
        },
        prompt: function(message, value, title, callback, options) {
            if (title === null) {
                title = Soopfw.t('Prompt');
            }
            $.alerts._show(title, message, value, 'prompt', function(result) {
                if (callback) {
                    callback(result);
                }
            }, options);
        },
        // Private methods

        _show: function(title, msg, value, type, callback, options) {

            if ($.alerts.last_timeout !== null) {
                clearTimeout($.alerts.last_timeout);
                $.alerts.last_timeout = null;
            }
            $.alerts._hide();
            $.alerts._overlay('show');
            var container_styles = '';
            if (options !== undefined && options['width'] !== undefined) {
                container_styles += 'width:' + options['width'] + 'px;';
            }
            $("BODY").append(
                    '<div id="popup_container" style="' + container_styles + '">' +
                    '<h1 id="popup_title"><div id="popup_titletxt"></div><div id="popup_cancel_corner"><i id="popup_corner_cancel" class="icon-cancel"></i></h1>' +
                    '<div id="popup_content">' +
                    '<div id="popup_message"></div>' +
                    '</div>' +
                    '</div>');

            if ($.alerts.dialogClass)
                $("#popup_container").addClass($.alerts.dialogClass);

            // IE6 Fix
            var pos = 'fixed';

            $("#popup_container").css({
                position: pos,
                zIndex: 99999,
                padding: 0,
                margin: 0
            });


            $("#popup_titletxt").html('<i class="icon-attention"></i>' + title);
            if ($.alerts.iconClass !== "")
            {
                $("#popup_content").addClass($.alerts.iconClass);
                $("#popup_message").css("padding-left", "0px");
                $("#popup_message").css("text-align", "center");
            }
            else
            {
                $("#popup_content").addClass(type);
            }
            if (type !== 'question')
            {
                $("#popup_message").html(msg);
            }
            else
            {
                $("#popup_message").html(msg['msg']);
            }
            $("#popup_message").html($("#popup_message").html().replace(/\n/g, '<br />'));

            /*$("#popup_container").css({
             minWidth: $("#popup_container").outerWidth(),
             maxWidth: $("#popup_container").outerWidth()
             });*/

            $.alerts._reposition();
            $.alerts._maintainPosition(true);

            //$("#popup_container").expose({closeSpeed: 0, loadSpeed: 'fast', opacity: 0.4});
            $("#popup_corner_cancel").click(function() {
                if (type === "alert") {
                    $("#popup_ok").click();
                } else {
                    $("#popup_cancel").click();
                }
            });
            //$("#popup_corner_cancel").click(function(){$("#popup_container").expose({api: true}).close();$.alerts._hide();});
            switch (type) {
                case 'alert':
                    $("#popup_message").after('<div id="popup_panel"><button id="popup_ok" class="btn btn-danger">' + $.alerts.okButton + '</button></div>');
                    $("#popup_ok").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        $.alerts._hide();
                        if (callback)
                            callback(true);
                    });
                    $("#popup_ok").focus().keypress(function(e) {
                        if (e.keyCode === 13 || e.keyCode === 27)
                            $("#popup_ok").trigger('click');
                    });
                    break;
                case 'wait_dialog':
                    $("#popup_message").after('<div id="popup_panel"><br /></div>');

                    break;
                case 'question':
                    if (msg['ok'] === undefined) {
                        msg['ok'] = $.alerts.okButton;
                    }
                    if (msg['cancel'] === undefined) {
                        msg['cancel'] = $.alerts.cancelButton;
                    }
                    //msg['html'] = msg['html'].html();
                    $("#popup_message").after('<div id="popup_panel"><button id="popup_ok" style="margin-right: 10px;" class="btn btn-primary">' + msg['ok'] + '</button> <button id="popup_cancel"  class="btn btn-primary">' + msg['cancel'] + '</button></div>');
                    if (msg['html'] !== undefined && msg['html'] !== null) {
                        msg['html'].css("margin-bottom", "10px");
                        $("#popup_panel").prepend(msg['html']);
                    }
                    $("#popup_panel").css("margin-left", '35px');
                    $("#popup_ok").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        formVariables = {};
                        var selector = "name";
                        $("#popup_panel").find("input,select,textarea").each(function(x, v)
                        {
                            if ($(v)[0].type === "checkbox")
                            {
                                if ($(v).prop("checked"))
                                {
                                    formVariables[$(v).prop(selector)] = $(v).prop("value");
                                }
                            }
                            else if ($(v)[0].type === "radio")
                            {
                                if ($(v).prop("selected") || $(v).prop("checked"))
                                {
                                    formVariables[$(v).prop(selector)] = $(v).prop("value");
                                }
                            }
                            else
                            {
                                formVariables[$(v).prop(selector)] = $(v).prop("value");
                            }
                        });
                        $.alerts._hide();
                        if (callback)
                            callback(true, formVariables);
                    });
                    $("#popup_cancel").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        formVariables = {};
                        var selector = "name";
                        $("#popup_panel").find("input,select,textarea").each(function(x, v)
                        {
                            if ($(v)[0].type === "checkbox")
                            {
                                if ($(v).prop("checked"))
                                {
                                    formVariables[$(v).prop(selector)] = $(v).prop("value");
                                }
                            }
                            else if ($(v)[0].type === "radio")
                            {
                                if ($(v).prop("selected") || $(v).prop("checked"))
                                {
                                    formVariables[$(v).prop(selector)] = $(v).prop("value");
                                }
                            }
                            else
                            {
                                formVariables[$(v).prop(selector)] = $(v).prop("value");
                            }
                        });
                        $.alerts._hide();

                        if (callback)
                            callback(false, formVariables);
                    });
                    $("#popup_ok").focus();
                    $("#popup_ok, #popup_cancel").keypress(function(e) {
                        //$("#popup_container").expose({api: true}).close();
                        if (e.keyCode === 13)
                            $("#popup_ok").trigger('click');
                        if (e.keyCode === 27)
                            $("#popup_cancel").trigger('click');
                    });
                    break;
                case 'confirm':
                    $("#popup_message").after('<div id="popup_panel"><button id="popup_ok" style="margin-right: 10px;" class="form_button taom_button btnGreen">' + $.alerts.okButton + '</button> <button id="popup_cancel" class="form_button taom_button btnRed">' + $.alerts.cancelButton + '</button></div>');
                    $("#popup_ok").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        $.alerts._hide();

                        if (callback)
                            callback(true);
                    });
                    $("#popup_cancel").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        $.alerts._hide();

                        if (callback)
                            callback(false);
                    });
                    $("#popup_ok").focus();
                    $("#popup_ok, #popup_cancel").keypress(function(e) {
                        //$("#popup_container").expose({api: true}).close();
                        if (e.keyCode === 13)
                            $("#popup_ok").trigger('click');
                        if (e.keyCode === 27)
                            $("#popup_cancel").trigger('click');
                    });
                    break;
                case 'prompt':
                    $("#popup_message").append('<br /><input type="text" size="30" id="popup_prompt" />').after('<div id="popup_panel"><button id="popup_ok" style="margin-right: 10px;" class="form_button taom_button btnGreen">' + $.alerts.okButton + '</button> <button  style="margin-left: 10px;" id="popup_cancel" class="taom_button btnRed">' + $.alerts.cancelButton + '</button></div>');
                    $("#popup_prompt").width($("#popup_message").width());
                    $("#popup_ok").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        var val = $("#popup_prompt").val();
                        $.alerts._hide();
                        if (callback)
                            callback(val);
                    });
                    $("#popup_cancel").click(function() {
                        //$("#popup_container").expose({api: true}).close();
                        $.alerts._hide();

                        if (callback)
                            callback(null);
                    });
                    $("#popup_prompt, #popup_ok, #popup_cancel").keypress(function(e) {
                        //$("#popup_container").expose({api: true}).close();
                        if (e.keyCode === 13)
                            $("#popup_ok").trigger('click');
                        if (e.keyCode === 27)
                            $("#popup_cancel").trigger('click');
                    });
                    if (value)
                        $("#popup_prompt").val(value);
                    $("#popup_prompt").focus().select();
                    break;
            }

            // Make draggable
            if ($.alerts.draggable) {
                try {
                    $("#popup_container").draggable({handle: $("#popup_title")});
                    $("#popup_title").css({cursor: 'move'});
                } catch (e) { /* requires jQuery UI draggables */
                }
            }

            if ($.alerts.timeout !== -1)
            {
                if ($.alerts.last_timeout !== null) {
                    clearTimeout($.alerts.last_timeout);
                    $.alerts.last_timeout = null;
                }
                $.alerts.last_timeout = setTimeout(function() {
                    $("#popup_ok").trigger('click');
                }, $.alerts.timeout);

            }
            $.alerts.timeout = -1;
            $('#popup_container').animate({opacity: 1}, 400);
            //	$("#popup_container").expose({api: true}).load();
        },
        _hide: function() {
            $("#popup_container").remove();

            $.alerts._overlay('hide');
            $.alerts._maintainPosition(false);

        },
        _overlay: function(status) {
            switch (status) {
                case 'show':
                    $.alerts._overlay('hide');
                    $("BODY").append('<div class="popup_overlay" style="display: none;"></div>');
                    $(".popup_overlay").css({
                        position: 'absolute',
                        zIndex: 99997,
                        top: '0px',
                        left: '0px',
                        width: '100%',
                        height: $(document).height(),
                        "background-color": $.alerts.overlayColor,
                        opacity: $.alerts.overlayOpacity
                    }).fadeIn(400);
                    break;
                case 'hide':
                    $(".popup_overlay").fadeOut(300, function() {
                        $(this).remove();
                    });
                    break;
            }
        },
        _reposition: function() {
            var top = (($(window).height() / 2) - ($("#popup_container").outerHeight() / 2)) + $.alerts.verticalOffset;
            var left = (($(window).width() / 2) - ($("#popup_container").outerWidth() / 2)) + $.alerts.horizontalOffset;
            if (top < 0)
                top = 0;
            if (left < 0)
                left = 0;

            $("#popup_container").css({
                top: top + 'px',
                left: left + 'px'
            });
            $(".popup_overlay").height($(document).height());
        },
        _maintainPosition: function(status) {
            if ($.alerts.repositionOnResize) {
                switch (status) {
                    case true:
                        $(window).bind('resize', $.alerts._reposition);
                        break;
                    case false:
                        $(window).unbind('resize', $.alerts._reposition);
                        break;
                }
            }
        }

    };

    // Shortuct functions
    jAlert = function(message, title, callback, timeout, options) {
        return $.alerts.alert(message, title, callback, timeout, options);
    };

    // Shortuct functions
    jWaitDialog = function(title, message, options) {
        $.alerts.iconClass = "wait";
        return $.alerts.wait_dialog(title, message, options);
    };

    jConfirm = function(message, title, callback, options) {
        return $.alerts.confirm(message, title, callback, options);
    };

    jQuestion = function(message, title, callback, options) {
        $.alerts.question(message, title, callback, options);
    };

    jPrompt = function(message, value, title, callback, options) {
        $.alerts.prompt(message, value, title, callback, options);
    };

})(jQuery);

function strtotime(str, now) {
    // Convert string representation of date and time to a timestamp
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/strtotime    // +   original by: Caio Ariede (http://caioariede.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: David
    // +   improved by: Caio Ariede (http://caioariede.com)
    // +   improved by: Brett Zamir (http://brett-zamir.me)    // +   bugfixed by: Wagner B. Soares
    // +   bugfixed by: Artur Tchernychev
    // %        note 1: Examples all have a fixed timestamp to prevent tests to fail because of variable time(zones)
    // *     example 1: strtotime('+1 day', 1129633200);
    // *     returns 1: 1129719600    // *     example 2: strtotime('+1 week 2 days 4 hours 2 seconds', 1129633200);
    // *     returns 2: 1130425202
    // *     example 3: strtotime('last month', 1129633200);
    // *     returns 3: 1127041200
    // *     example 4: strtotime('2009-05-04 08:30:00');    // *     returns 4: 1241418600
    var i, match, s, strTmp = '',
            parse = '';

    strTmp = str;
    strTmp = strTmp.replace(/\s{2,}|^\s|\s$/g, ' '); // unecessary spaces
    strTmp = strTmp.replace(/[\t\r\n]/g, ''); // unecessary chars
    if (strTmp === 'now') {
        return (new Date()).getTime() / 1000; // Return seconds, not milli-seconds
    } else if (!isNaN(parse = Date.parse(strTmp))) {
        return (parse / 1000);
    } else if (now) {
        now = new Date(now * 1000); // Accept PHP-style seconds
    } else {
        now = new Date();
    }

    strTmp = strTmp.toLowerCase();

    var __is = {day: {
            'sun': 0,
            'mon': 1,
            'tue': 2,
            'wed': 3, 'thu': 4,
            'fri': 5,
            'sat': 6
        },
        mon: {'jan': 0,
            'feb': 1,
            'mar': 2,
            'apr': 3,
            'may': 4, 'jun': 5,
            'jul': 6,
            'aug': 7,
            'sep': 8,
            'oct': 9, 'nov': 10,
            'dec': 11
        }
    };
    var process = function(m) {
        var ago = (m[2] && m[2] === 'ago');
        var num = (num = m[0] === 'last' ? -1 : 1) * (ago ? -1 : 1);

        switch (m[0]) {
            case 'last':
            case 'next':
                switch (m[1].substring(0, 3)) {
                    case 'yea':
                        now.setFullYear(now.getFullYear() + num);
                        break;
                    case 'mon':
                        now.setMonth(now.getMonth() + num);
                        break;
                    case 'wee':
                        now.setDate(now.getDate() + (num * 7));
                        break;
                    case 'day':
                        now.setDate(now.getDate() + num);
                        break;
                    case 'hou':
                        now.setHours(now.getHours() + num);
                        break;
                    case 'min':
                        now.setMinutes(now.getMinutes() + num);
                        break;
                    case 'sec':
                        now.setSeconds(now.getSeconds() + num);
                        break;
                    default:
                        var day;
                        if (typeof (day = __is.day[m[1].substring(0, 3)]) !== 'undefined') {
                            var diff = day - now.getDay();
                            if (diff === 0) {
                                diff = 7 * num;
                            } else if (diff > 0) {
                                if (m[0] === 'last') {
                                    diff -= 7;
                                }
                            } else {
                                if (m[0] === 'next') {
                                    diff += 7;
                                }
                            }
                            now.setDate(now.getDate() + diff);
                        }
                }
                break;

            default:
                if (/\d+/.test(m[0])) {
                    num *= parseInt(m[0], 10);

                    switch (m[1].substring(0, 3)) {
                        case 'yea':
                            now.setFullYear(now.getFullYear() + num);
                            break;
                        case 'mon':
                            now.setMonth(now.getMonth() + num);
                            break;
                        case 'wee':
                            now.setDate(now.getDate() + (num * 7));
                            break;
                        case 'day':
                            now.setDate(now.getDate() + num);
                            break;
                        case 'hou':
                            now.setHours(now.getHours() + num);
                            break;
                        case 'min':
                            now.setMinutes(now.getMinutes() + num);
                            break;
                        case 'sec':
                            now.setSeconds(now.getSeconds() + num);
                            break;
                    }
                } else {
                    return false;
                }
                break;
        }
        return true;
    };

    match = strTmp.match(/^(\d{2,4}-\d{2}-\d{2})(?:\s(\d{1,2}:\d{2}(:\d{2})?)?(?:\.(\d+))?)?$/);
    if (match !== null) {
        if (!match[2]) {
            match[2] = '00:00:00';
        } else if (!match[3]) {
            match[2] += ':00';
        }

        s = match[1].split(/-/g);

        for (i in __is.mon) {
            if (__is.mon[i] === s[1] - 1) {
                s[1] = i;
            }
        }
        s[0] = parseInt(s[0], 10);
        s[0] = (s[0] >= 0 && s[0] <= 69) ? '20' + (s[0] < 10 ? '0' + s[0] : s[0] + '') : (s[0] >= 70 && s[0] <= 99) ? '19' + s[0] : s[0] + '';
        return parseInt(this.strtotime(s[2] + ' ' + s[1] + ' ' + s[0] + ' ' + match[2]) + (match[4] ? match[4] / 1000 : ''), 10);
    }
    var regex = '([+-]?\\d+\\s' + '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?' + '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday' + '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday)' + '|(last|next)\\s' + '(years?|months?|weeks?|days?|hours?|min|minutes?|sec|seconds?' + '|sun\\.?|sunday|mon\\.?|monday|tue\\.?|tuesday|wed\\.?|wednesday' + '|thu\\.?|thursday|fri\\.?|friday|sat\\.?|saturday))' + '(\\sago)?';

    match = strTmp.match(new RegExp(regex, 'gi')); // Brett: seems should be case insensitive per docs, so added 'i'
    if (match === null) {
        return false;
    }

    for (i = 0; i < match.length; i++) {
        if (!process(match[i].split(' '))) {
            return false;
        }
    }

    return (now.getTime() / 1000);
}

function in_array(needle, haystack, argStrict) {
    // Checks if the given value exists in the array
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/in_array    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: vlado houba
    // +   input by: Billy
    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);    // *     returns 1: true
    // *     example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
    // *     returns 2: false
    // *     example 3: in_array(1, ['1', '2', '3']);
    // *     returns 3: true    // *     example 3: in_array(1, ['1', '2', '3'], false);
    // *     returns 3: true
    // *     example 4: in_array(1, ['1', '2', '3'], true);
    // *     returns 4: false
    var key = '', strict = !!argStrict;

    if (strict) {
        for (key in haystack) {
            if (haystack[key] === needle) {
                return true;
            }
        }
    } else {
        for (key in haystack) {
            if (haystack[key] === needle) {
                return true;
            }
        }
    }
    return false;
}

function date(format, timestamp) {
    // http://kevin.vanzonneveld.net
    // + original by: Carlos R. L. Rodrigues (http://www.jsfromhell.com)
    // + parts by: Peter-Paul Koch (http://www.quirksmode.org/js/beat.html)
    // + improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // + improved by: MeEtc (http://yass.meetcweb.com)
    // + improved by: Brad Touesnard
    // + improved by: Tim Wiel
    // + improved by: Bryan Elliott
    //
    // + improved by: Brett Zamir (http://brett-zamir.me)
    // + improved by: David Randall
    // + input by: Brett Zamir (http://brett-zamir.me)
    // + bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // + improved by: Brett Zamir (http://brett-zamir.me)
    // + improved by: Brett Zamir (http://brett-zamir.me)
    // + improved by: Theriault
    // + derived from: gettimeofday
    // + input by: majak
    // + bugfixed by: majak
    // + bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // + input by: Alex
    // + bugfixed by: Brett Zamir (http://brett-zamir.me)
    // + improved by: Theriault
    // + improved by: Brett Zamir (http://brett-zamir.me)
    // + improved by: Theriault
    // + improved by: Thomas Beaucourt (http://www.webapp.fr)
    // + improved by: JT
    // + improved by: Theriault
    // + improved by: Rafał Kukawski (http://blog.kukawski.pl)
    // + bugfixed by: omid (http://phpjs.org/functions/380:380#comment_137122)
    // + input by: Martin
    // + input by: Alex Wilson
    // + bugfixed by: Chris (http://www.devotis.nl/)
    // % note 1: Uses global: php_js to store the default timezone
    // % note 2: Although the function potentially allows timezone info (see notes), it currently does not set
    // % note 2: per a timezone specified by date_default_timezone_set(). Implementers might use
    // % note 2: this.php_js.currentTimezoneOffset and this.php_js.currentTimezoneDST set by that function
    // % note 2: in order to adjust the dates in this function (or our other date functions!) accordingly
    // * example 1: date('H:m:s \\m \\i\\s \\m\\o\\n\\t\\h', 1062402400);
    // * returns 1: '09:09:40 m is month'
    // * example 2: date('F j, Y, g:i a', 1062462400);
    // * returns 2: 'September 2, 2003, 2:26 am'
    // * example 3: date('Y W o', 1062462400);
    // * returns 3: '2003 36 2003'
    // * example 4: x = date('Y m d', (new Date()).getTime()/1000);
    // * example 4: (x+'').length == 10 // 2009 01 09
    // * returns 4: true
    // * example 5: date('W', 1104534000);
    // * returns 5: '53'
    // * example 6: date('B t', 1104534000);
    // * returns 6: '999 31'
    // * example 7: date('W U', 1293750000.82); // 2010-12-31
    // * returns 7: '52 1293750000'
    // * example 8: date('W', 1293836400); // 2011-01-01
    // * returns 8: '52'
    // * example 9: date('W Y-m-d', 1293974054); // 2011-01-02
    // * returns 9: '52 2011-01-02'
    var that = this,
            jsdate,
            f,
            formatChr = /\\?([a-z])/gi,
            formatChrCb,
            // Keep this here (works, but for code commented-out
            // below for file size reasons)
            //, tal= [],
            _pad = function(n, c) {
                n = n.toString();
                return n.length < c ? _pad('0' + n, c, '0') : n;
            },
            txt_words = ["Sun", "Mon", "Tues", "Wednes", "Thurs", "Fri", "Satur", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
    formatChrCb = function(t, s) {
        return f[t] ? f[t]() : s;
    };
    f = {
        // Day
        d: function() { // Day of month w/leading 0; 01..31
            return _pad(f.j(), 2);
        },
        D: function() { // Shorthand day name; Mon...Sun
            return f.l().slice(0, 3);
        },
        j: function() { // Day of month; 1..31
            return jsdate.getDate();
        },
        l: function() { // Full day name; Monday...Sunday
            return txt_words[f.w()] + 'day';
        },
        N: function() { // ISO-8601 day of week; 1[Mon]..7[Sun]
            return f.w() || 7;
        },
        S: function() { // Ordinal suffix for day of month; st, nd, rd, th
            var j = f.j();
            return j < 4 | j > 20 && (['st', 'nd', 'rd'][j % 10 - 1] || 'th');
        },
        w: function() { // Day of week; 0[Sun]..6[Sat]
            return jsdate.getDay();
        },
        z: function() { // Day of year; 0..365
            var a = new Date(f.Y(), f.n() - 1, f.j()),
                    b = new Date(f.Y(), 0, 1);
            return Math.round((a - b) / 864e5);
        },
        // Week
        W: function() { // ISO-8601 week number
            var a = new Date(f.Y(), f.n() - 1, f.j() - f.N() + 3),
                    b = new Date(a.getFullYear(), 0, 4);
            return _pad(1 + Math.round((a - b) / 864e5 / 7), 2);
        },
        // Month
        F: function() { // Full month name; January...December
            return txt_words[6 + f.n()];
        },
        m: function() { // Month w/leading 0; 01...12
            return _pad(f.n(), 2);
        },
        M: function() { // Shorthand month name; Jan...Dec
            return f.F().slice(0, 3);
        },
        n: function() { // Month; 1...12
            return jsdate.getMonth() + 1;
        },
        t: function() { // Days in month; 28...31
            return (new Date(f.Y(), f.n(), 0)).getDate();
        },
        // Year
        L: function() { // Is leap year?; 0 or 1
            var j = f.Y();
            return j % 4 === 0 & j % 100 !== 0 | j % 400 === 0;
        },
        o: function() { // ISO-8601 year
            var n = f.n(),
                    W = f.W(),
                    Y = f.Y();
            return Y + (n === 12 && W < 9 ? 1 : n === 1 && W > 9 ? -1 : 0);
        },
        Y: function() { // Full year; e.g. 1980...2010
            return jsdate.getFullYear();
        },
        y: function() { // Last two digits of year; 00...99
            return f.Y().toString().slice(-2);
        },
        // Time
        a: function() { // am or pm
            return jsdate.getHours() > 11 ? "pm" : "am";
        },
        A: function() { // AM or PM
            return f.a().toUpperCase();
        },
        B: function() { // Swatch Internet time; 000..999
            var H = jsdate.getUTCHours() * 36e2,
                    // Hours
                    i = jsdate.getUTCMinutes() * 60,
                    // Minutes
                    s = jsdate.getUTCSeconds(); // Seconds
            return _pad(Math.floor((H + i + s + 36e2) / 86.4) % 1e3, 3);
        },
        g: function() { // 12-Hours; 1..12
            return f.G() % 12 || 12;
        },
        G: function() { // 24-Hours; 0..23
            return jsdate.getHours();
        },
        h: function() { // 12-Hours w/leading 0; 01..12
            return _pad(f.g(), 2);
        },
        H: function() { // 24-Hours w/leading 0; 00..23
            return _pad(f.G(), 2);
        },
        i: function() { // Minutes w/leading 0; 00..59
            return _pad(jsdate.getMinutes(), 2);
        },
        s: function() { // Seconds w/leading 0; 00..59
            return _pad(jsdate.getSeconds(), 2);
        },
        u: function() { // Microseconds; 000000-999000
            return _pad(jsdate.getMilliseconds() * 1000, 6);
        },
        // Timezone
        e: function() { // Timezone identifier; e.g. Atlantic/Azores, ...
            // The following works, but requires inclusion of the very large
            // timezone_abbreviations_list() function.
            /* return that.date_default_timezone_get();
             */
            throw 'Not supported (see source code of date() for timezone on how to add support)';
        },
        I: function() { // DST observed?; 0 or 1
            // Compares Jan 1 minus Jan 1 UTC to Jul 1 minus Jul 1 UTC.
            // If they are not equal, then DST is observed.
            var a = new Date(f.Y(), 0),
                    // Jan 1
                    c = Date.UTC(f.Y(), 0),
                    // Jan 1 UTC
                    b = new Date(f.Y(), 6),
                    // Jul 1
                    d = Date.UTC(f.Y(), 6); // Jul 1 UTC
            return ((a - c) !== (b - d)) ? 1 : 0;
        },
        O: function() { // Difference to GMT in hour format; e.g. +0200
            var tzo = jsdate.getTimezoneOffset(),
                    a = Math.abs(tzo);
            return (tzo > 0 ? "-" : "+") + _pad(Math.floor(a / 60) * 100 + a % 60, 4);
        },
        P: function() { // Difference to GMT w/colon; e.g. +02:00
            var O = f.O();
            return (O.substr(0, 3) + ":" + O.substr(3, 2));
        },
        T: function() { // Timezone abbreviation; e.g. EST, MDT, ...
            // The following works, but requires inclusion of the very
            // large timezone_abbreviations_list() function.
            /* var abbr = '', i = 0, os = 0, default = 0;
             if (!tal.length) {
             tal = that.timezone_abbreviations_list();
             }
             if (that.php_js && that.php_js.default_timezone) {
             default = that.php_js.default_timezone;
             for (abbr in tal) {
             for (i=0; i < tal[abbr].length; i++) {
             if (tal[abbr][i].timezone_id === default) {
             return abbr.toUpperCase();
             }
             }
             }
             }
             for (abbr in tal) {
             for (i = 0; i < tal[abbr].length; i++) {
             os = -jsdate.getTimezoneOffset() * 60;
             if (tal[abbr][i].offset === os) {
             return abbr.toUpperCase();
             }
             }
             }
             */
            return 'UTC';
        },
        Z: function() { // Timezone offset in seconds (-43200...50400)
            return -jsdate.getTimezoneOffset() * 60;
        },
        // Full Date/Time
        c: function() { // ISO-8601 date.
            return 'Y-m-d\\TH:i:sP'.replace(formatChr, formatChrCb);
        },
        r: function() { // RFC 2822
            return 'D, d M Y H:i:s O'.replace(formatChr, formatChrCb);
        },
        U: function() { // Seconds since UNIX epoch
            return jsdate / 1000 | 0;
        }
    };
    this.date = function(format, timestamp) {
        that = this;
        jsdate = (timestamp === undefined ? new Date() : // Not provided
                (timestamp instanceof Date) ? new Date(timestamp) : // JS Date()
                new Date(timestamp * 1000) // UNIX timestamp (auto-convert to int)
                );
        return format.replace(formatChr, formatChrCb);
    };
    return this.date(format, timestamp);
}

function utf8_encode (argString) {
  // From: http://phpjs.org/functions
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +   improved by: sowberry
  // +    tweaked by: Jack
  // +   bugfixed by: Onno Marsman
  // +   improved by: Yves Sucaet
  // +   bugfixed by: Onno Marsman
  // +   bugfixed by: Ulrich
  // +   bugfixed by: Rafal Kukawski
  // +   improved by: kirilloid
  // +   bugfixed by: kirilloid
  // *     example 1: utf8_encode('Kevin van Zonneveld');
  // *     returns 1: 'Kevin van Zonneveld'

  if (argString === null || typeof argString === "undefined") {
    return "";
  }

  var string = (argString + ''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
  var utftext = '',
    start, end, stringl = 0;

  start = end = 0;
  stringl = string.length;
  for (var n = 0; n < stringl; n++) {
    var c1 = string.charCodeAt(n);
    var enc = null;

    if (c1 < 128) {
      end++;
    } else if (c1 > 127 && c1 < 2048) {
      enc = String.fromCharCode(
         (c1 >> 6)        | 192,
        ( c1        & 63) | 128
      );
    } else if (c1 & 0xF800 != 0xD800) {
      enc = String.fromCharCode(
         (c1 >> 12)       | 224,
        ((c1 >> 6)  & 63) | 128,
        ( c1        & 63) | 128
      );
    } else { // surrogate pairs
      if (c1 & 0xFC00 != 0xD800) { throw new RangeError("Unmatched trail surrogate at " + n); }
      var c2 = string.charCodeAt(++n);
      if (c2 & 0xFC00 != 0xDC00) { throw new RangeError("Unmatched lead surrogate at " + (n-1)); }
      c1 = ((c1 & 0x3FF) << 10) + (c2 & 0x3FF) + 0x10000;
      enc = String.fromCharCode(
         (c1 >> 18)       | 240,
        ((c1 >> 12) & 63) | 128,
        ((c1 >> 6)  & 63) | 128,
        ( c1        & 63) | 128
      );
    }
    if (enc !== null) {
      if (end > start) {
        utftext += string.slice(start, end);
      }
      utftext += enc;
      start = end = n + 1;
    }
  }

  if (end > start) {
    utftext += string.slice(start, stringl);
  }

  return utftext;
}

function md5 (str) {
  // From: http://phpjs.org/functions
  // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
  // + namespaced by: Michael White (http://getsprink.com)
  // +    tweaked by: Jack
  // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // +      input by: Brett Zamir (http://brett-zamir.me)
  // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // -    depends on: utf8_encode
  // *     example 1: md5('Kevin van Zonneveld');
  // *     returns 1: '6e658d4bfcb59cc13f96c14450ac40b9'
  var xl;

  var rotateLeft = function (lValue, iShiftBits) {
    return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
  };

  var addUnsigned = function (lX, lY) {
    var lX4, lY4, lX8, lY8, lResult;
    lX8 = (lX & 0x80000000);
    lY8 = (lY & 0x80000000);
    lX4 = (lX & 0x40000000);
    lY4 = (lY & 0x40000000);
    lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
    if (lX4 & lY4) {
      return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
    }
    if (lX4 | lY4) {
      if (lResult & 0x40000000) {
        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
      } else {
        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
      }
    } else {
      return (lResult ^ lX8 ^ lY8);
    }
  };

  var _F = function (x, y, z) {
    return (x & y) | ((~x) & z);
  };
  var _G = function (x, y, z) {
    return (x & z) | (y & (~z));
  };
  var _H = function (x, y, z) {
    return (x ^ y ^ z);
  };
  var _I = function (x, y, z) {
    return (y ^ (x | (~z)));
  };

  var _FF = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_F(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _GG = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_G(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _HH = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_H(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var _II = function (a, b, c, d, x, s, ac) {
    a = addUnsigned(a, addUnsigned(addUnsigned(_I(b, c, d), x), ac));
    return addUnsigned(rotateLeft(a, s), b);
  };

  var convertToWordArray = function (str) {
    var lWordCount;
    var lMessageLength = str.length;
    var lNumberOfWords_temp1 = lMessageLength + 8;
    var lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
    var lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
    var lWordArray = new Array(lNumberOfWords - 1);
    var lBytePosition = 0;
    var lByteCount = 0;
    while (lByteCount < lMessageLength) {
      lWordCount = (lByteCount - (lByteCount % 4)) / 4;
      lBytePosition = (lByteCount % 4) * 8;
      lWordArray[lWordCount] = (lWordArray[lWordCount] | (str.charCodeAt(lByteCount) << lBytePosition));
      lByteCount++;
    }
    lWordCount = (lByteCount - (lByteCount % 4)) / 4;
    lBytePosition = (lByteCount % 4) * 8;
    lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
    lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
    lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
    return lWordArray;
  };

  var wordToHex = function (lValue) {
    var wordToHexValue = "",
      wordToHexValue_temp = "",
      lByte, lCount;
    for (lCount = 0; lCount <= 3; lCount++) {
      lByte = (lValue >>> (lCount * 8)) & 255;
      wordToHexValue_temp = "0" + lByte.toString(16);
      wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
    }
    return wordToHexValue;
  };

  var x = [],
    k, AA, BB, CC, DD, a, b, c, d, S11 = 7,
    S12 = 12,
    S13 = 17,
    S14 = 22,
    S21 = 5,
    S22 = 9,
    S23 = 14,
    S24 = 20,
    S31 = 4,
    S32 = 11,
    S33 = 16,
    S34 = 23,
    S41 = 6,
    S42 = 10,
    S43 = 15,
    S44 = 21;

  str = this.utf8_encode(str);
  x = convertToWordArray(str);
  a = 0x67452301;
  b = 0xEFCDAB89;
  c = 0x98BADCFE;
  d = 0x10325476;

  xl = x.length;
  for (k = 0; k < xl; k += 16) {
    AA = a;
    BB = b;
    CC = c;
    DD = d;
    a = _FF(a, b, c, d, x[k + 0], S11, 0xD76AA478);
    d = _FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
    c = _FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
    b = _FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
    a = _FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
    d = _FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
    c = _FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
    b = _FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
    a = _FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
    d = _FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
    c = _FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
    b = _FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
    a = _FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
    d = _FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
    c = _FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
    b = _FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
    a = _GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
    d = _GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
    c = _GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
    b = _GG(b, c, d, a, x[k + 0], S24, 0xE9B6C7AA);
    a = _GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
    d = _GG(d, a, b, c, x[k + 10], S22, 0x2441453);
    c = _GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
    b = _GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
    a = _GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
    d = _GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
    c = _GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
    b = _GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
    a = _GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
    d = _GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
    c = _GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
    b = _GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
    a = _HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
    d = _HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
    c = _HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
    b = _HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
    a = _HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
    d = _HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
    c = _HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
    b = _HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
    a = _HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
    d = _HH(d, a, b, c, x[k + 0], S32, 0xEAA127FA);
    c = _HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
    b = _HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
    a = _HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
    d = _HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
    c = _HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
    b = _HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
    a = _II(a, b, c, d, x[k + 0], S41, 0xF4292244);
    d = _II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
    c = _II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
    b = _II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
    a = _II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
    d = _II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
    c = _II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
    b = _II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
    a = _II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
    d = _II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
    c = _II(c, d, a, b, x[k + 6], S43, 0xA3014314);
    b = _II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
    a = _II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
    d = _II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
    c = _II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
    b = _II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
    a = addUnsigned(a, AA);
    b = addUnsigned(b, BB);
    c = addUnsigned(c, CC);
    d = addUnsigned(d, DD);
  }

  var temp = wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d);

  return temp.toLowerCase();
}