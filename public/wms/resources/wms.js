/*
 * WMS Dialog Plugin
 * Created By Renato Medina medinadato [at] gmail [dot] com
 * Version: 0.1
 * Release: 2012-05-15
 */

(function($){
    /**
     * Method to load a message by dialog
     */
    $.wmsDialogAlert = function(settings, callbackFnk){
        var config = {
            'title': settings.title,
            'msg': settings.msg,
            'width': 350,
            'height': 130,
            'resizable':    true,
            'position' :    'center',
            'modal' :       true
        };

        // show a spinner or something via css
        var dialog = $('<div id="wms-dialog-msg" style="display:none; font-size: 12px;">' + config.msg + '</div>').appendTo('body');
        
        // open the dialog
        dialog.dialog({
            width : config.width,
            height : config.height,
            resizable: config.resizable,
            title : config.title,
            modal: config.modal,
            position: config.position,
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
                
                // now we are calling our own callback function
                if($.isFunction(callbackFnk)){
                    callbackFnk.call(this);
                }
            }
        });
    };
    /**
     * Method to load a dialog with ajax
     */
    $.wmsDialogAjax = function(settings, callbackFnk){
        var config = {
            'title': '',
            'url': '',
            'width': 500,
            'height': 350,
            'resizable':    true,
            'position' :    'center',
            'modal' :       true
        };
        
        if (settings){
            $.extend(config, settings);
        }
        
        // show a spinner or something via css
        var dialog = $('<div id="view-details-dialog" style="display:none"></div>').appendTo('body');
        
        // open the dialog
        dialog.dialog({
            width : config.width,
            height : config.height,
            resizable: config.resizable,
            title : config.title,
            modal: config.modal,
            position: config.position,
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            }
        });
        // load remote content
        dialog.load(
            config.url, 
            {}, // omit this param object to issue a GET request instead a POST request, otherwise you may provide post parameters within the object
            function (responseText, textStatus, XMLHttpRequest) {
                // remove the loading class
                dialog.removeClass('loading');
            }
            );
        //prevent the browser to follow the link
        return false;
    };
    /**
     * Method to load a frame window
     */
    $.wmsDialogFrame = function(settings){
        var config = {
            'title':        '',
            'url':          '',
            'width':        500,
            'height':       350,
            'resizable':    true,
            'position' :    'center',
            'modal' :       true
        };
        
        if (settings){
            $.extend(config, settings);
        }
        
        // internal width
        var inWidth  = config.width - 25;

        // creates iframe
        var dialog = $('<iframe id="ajax-dialog" style="display:none;" src="' + config.url + '"></iframe>').appendTo('body');
        // open the dialog
        dialog.dialog({
            width : config.width,
            height : config.height,
            resizable: config.resizable,
            title : config.title,
            modal: config.modal,
            position: config.position,
            open: function (event, ui) {
                $(this).css("width",inWidth + "px");
                dialog.load(function() {
                    $.unblockUI();
                });
            },
            create: function(event, ui) { 
                $.blockUI();
            }
        }); 
    };
})(jQuery);