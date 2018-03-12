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
            'width': (!!settings.width)? settings.width : 350,
            'height': (!!settings.height)? settings.height : 'auto',
            'resizable': (!!settings.resizable)? settings.resizable : false,
            'position' : (!!settings.position)? settings.position : 'center',
            'modal' :  (!!settings.modal)? settings.modal :  true,
            'buttons': (!!settings.buttons)? settings.buttons :  {
                "Ok": function () {
                    // now we are calling our own callback function
                    if($.isFunction(callbackFnk)){
                        callbackFnk.call(this);
                    }
                    $(this).remove();
                }
            }
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
            buttons: config.buttons,
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            }
        });
    };

    $.wmsDialogConfirm = function(settings, callback, params, returnFunction){
        var config = {
            'title': settings.title,
            'msg': settings.msg,
            'width': (!!settings.width)? settings.width : 350,
            'height': (!!settings.height)? settings.height : 130,
            'resizable': (!!settings.resizable)? settings.resizable : false,
            'position' : (!!settings.position)? settings.position : 'center',
            'modal' :  (!!settings.modal)? settings.modal :  true,
            'buttons': (!!settings.buttons)? settings.buttons :  {
                "Confirmar": function (){
                    if($.isFunction(callback)){
                        callback.call(this, params);
                    }
                    if($.isFunction(returnFunction)){
                        returnFunction.call(this, true);
                    }
                    $(this).remove();
                },
                "Cancelar": function () {
                    if($.isFunction(returnFunction)){
                        returnFunction.call(this, false);
                    }
                    $(this).remove();
                }
            }
        };

        // show a spinner or something via css
        var dialog = $('<div id="wms-dialog-confirm" style="display:none; font-size: 12px;">' + config.msg + '</div>').appendTo('body');

        // open the dialog
        dialog.dialog({
            width : config.width,
            height : config.height,
            resizable: config.resizable,
            title : config.title,
            modal: config.modal,
            position: config.position,
            buttons: config.buttons,
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
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

    $.wmsDialogModal = function(settings, htmlBodyModal){
        var config = {
            'title': settings.title,
            'width': (!!settings.width)? settings.width : 500,
            'height': (!!settings.height)? settings.height : 'auto',
            'resizable': (!!settings.resizable)? settings.resizable : true,
            'position' : (!!settings.position)? settings.position : 'center',
            'modal' :  (!!settings.modal)? settings.modal :  true
        };

        // show a spinner or something via css
        var dialog = $('<div id="wms-dialog-modal" style="display:none; font-size: 12px;">' + htmlBodyModal + '</div>').appendTo('body');

        // open the dialog
        dialog.dialog({
            width : config.width,
            height : config.height,
            resizable: config.resizable,
            title : config.title,
            modal: config.modal,
            position: config.position,
            buttons: config.buttons,
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            }
        });
    };
})(jQuery);