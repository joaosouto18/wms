/**
 * @tag controllers, home
 */
$.Controller.extend('Wms.Controllers.Ajuda',
/* @Static */
{
    pluginName: 'ajuda'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all bars to be displayed.
     */
    'click': function(){
  
        $( "#dialog-ajuda" ).dialog({
            height: 500,
            width: 300
        });
    }
});