/**
 * @tag models, home
 * Wraps backend perfil_usuario services.  Enables 
 * [Wms.Models.PerfilUsuario.static.findAll retrieving],
 * [Wms.Models.PerfilUsuario.static.update updating],
 * [Wms.Models.PerfilUsuario.static.destroy destroying], and
 * [Wms.Models.PerfilUsuario.static.create creating] perfil_usuarios.
 */
$.Model.extend('Wms.Models.PerfilUsuario',
/* @Static */
{
    /**
     * Updates a perfil_usuario's data.
     * @param {String} id A unique id representing your perfil_usuario.
     * @param {Object} attrs Data to update your perfil_usuario with.
     * @param {Function} success a callback function that indicates a successful update.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    update: function( id, attrs, success, complete ){
        // Assign handlers immediately after making the request,
        // and remember the jqxhr object for this request
        
        $.ajax({
                url: URL_MODULO + '/perfil-usuario/edit/id/'+id,
                type: 'post',
                dataType: 'json',
                data: attrs
            })
            .success(success)
            .complete(complete);
    },
    /**
     * Creates a perfil_usuario.
     * @param {Object} attrs A perfil_usuario's attributes.
     * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
     * @param {Function} error a callback that should be called with an object of errors.
     */
    create: function( attrs, success, complete ){
        // Assign handlers immediately after making the request,
        // and remember the jqxhr object for this request
        $.ajax({
                url: URL_MODULO + '/perfil-usuario/add',
                type: 'post',
                dataType: 'json',
                data: attrs
            })
            .success(success)
            .complete(complete);
        
    }
},
/* @Prototype */
{});