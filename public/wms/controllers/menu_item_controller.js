/**
 */
$.Controller.extend('Wms.Controllers.MenuItem',
/* @Static */
{
    pluginName: 'menuItem'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all person_addresses to be displayed.
     */
    "{window} load": function(){
        var idRecurso = $('#idRecurso').val();
        if(idRecurso != '') {
            Wms.Models.MenuItem.findAll({
                idRecurso:idRecurso
            }, this.callback('list'));
            
            // call check state
            setTimeout(this.callback('checkSelectedPermissao', {
                selectedPermissaoId : $('#idRecursoAcaoTemp').val()
            }), 2000);
        }
    },
    
    /**
     * check selected state if exist
     */
    checkSelectedPermissao: function( obj ) {
        if($('#idRecursoAcao option').size() > 0)
            $('#idRecursoAcao').val( obj.selectedPermissaoId );
        else
            setTimeout( this.callback( 'checkSelectedState', {
                selectedPermissaoId : obj.selectedPermissaoId
            } ), 1000 );
    },
    
    /**
     * Get all the relative states of the country
     */
    '#idRecurso change': function(el, ev){
        Wms.Models.MenuItem.findAll({
            idRecurso:el.val()
        }, this.callback('list'));
    },
    
    /**
     * Displays a list of states
     * @param {Array} states An array of Json objects.
     */
    list: function( permissoes ){
        $('#idRecursoAcao').html(this.view('init', {
            permissoes:permissoes
        } ));
    }
    
});