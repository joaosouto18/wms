/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Veiculo',
/* @Static */
{
    pluginName: 'veiculo'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all bars to be displayed.
     */
    '.view-veiculo click': function(el, ev){
        
        //Help
        var url = el.attr('href');
        // show a spinner or something via css
        var dialog = $('<div id="view-andamento-dialog" style="display:none"></div>').appendTo('body');
        
        // open the dialog
        dialog.dialog({
            width : 720,
            height : 280,
            resizable: false,
            title : "Detalhes do Veiculo",
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            modal: true
        });
        // load remote content
        dialog.load(
            url, 
            {}, // omit this param object to issue a GET request instead a POST request, otherwise you may provide post parameters within the object
            function (responseText, textStatus, XMLHttpRequest) {
                // remove the loading class
                dialog.removeClass('loading');
            }
            );
        //prevent the browser to follow the link
        return false;
    }
    
});