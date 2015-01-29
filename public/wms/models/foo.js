/**
 * @tag models, home
 * Wraps backend foo services.  Enables 
 * [Wms.Models.Foo.static.findAll retrieving],
 * [Wms.Models.Foo.static.update updating],
 * [Wms.Models.Foo.static.destroy destroying], and
 * [Wms.Models.Foo.static.create creating] foos.
 */
$.Model.extend('Wms.Models.Foo',
/* @Static */
{
	/**
 	 * Retrieves foos data from your backend services.
 	 * @param {Object} params params that might refine your results.
 	 * @param {Function} success a callback function that returns wrapped foo objects.
 	 * @param {Function} error a callback function for an error in the ajax request.
 	 */
	findAll: function( params, success, error ){
		$.ajax({
			url: '/foo',
			type: 'get',
			dataType: 'json',
			data: params,
			success: this.callback(['wrapMany',success]),
			error: error,
			fixture: "//wms/fixtures/foos.json.get" //calculates the fixture path from the url and type.
		});
	},
	/**
	 * Updates a foo's data.
	 * @param {String} id A unique id representing your foo.
	 * @param {Object} attrs Data to update your foo with.
	 * @param {Function} success a callback function that indicates a successful update.
 	 * @param {Function} error a callback that should be called with an object of errors.
         */
	update: function( id, attrs, success, error ){
		$.ajax({
			url: '/foos/'+id,
			type: 'put',
			dataType: 'json',
			data: attrs,
			success: success,
			error: error,
			fixture: "-restUpdate" //uses $.fixture.restUpdate for response.
		});
	},
	/**
 	 * Destroys a foo's data.
 	 * @param {String} id A unique id representing your foo.
	 * @param {Function} success a callback function that indicates a successful destroy.
 	 * @param {Function} error a callback that should be called with an object of errors.
	 */
	destroy: function( id, success, error ){
		$.ajax({
			url: '/foos/'+id,
			type: 'delete',
			dataType: 'json',
			success: success,
			error: error,
			fixture: "-restDestroy" // uses $.fixture.restDestroy for response.
		});
	},
	/**
	 * Creates a foo.
	 * @param {Object} attrs A foo's attributes.
	 * @param {Function} success a callback function that indicates a successful create.  The data that comes back must have an ID property.
	 * @param {Function} error a callback that should be called with an object of errors.
	 */
	create: function( attrs, success, error ){
		$.ajax({
			url: '/foos',
			type: 'post',
			dataType: 'json',
			success: success,
			error: error,
			data: attrs,
			fixture: "-restCreate" //uses $.fixture.restCreate for response.
		});
	}
},
/* @Prototype */
{});