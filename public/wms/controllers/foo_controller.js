/**
 * @tag controllers, home
 * Displays a table of foos.	 Lets the user 
 * ["Wms.Controllers.Foo.prototype.form submit" create], 
 * ["Wms.Controllers.Foo.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.Foo.prototype.&#46;destroy click" destroy] foos.
 */
$.Controller.extend('Wms.Controllers.Foo',
/* @Static */
{
    onDocument: true
},
/* @Prototype */
{
    /**
 * When the page loads, gets all foos to be displayed.
 */
    load: function(){
        if(!$("#foo").length){
            $(document.body).append($('<div/>').attr('id','foo'));
            Wms.Models.Foo.findAll({}, this.callback('list'));
        }
    },
    /**
 * Displays a list of foos and the submit form.
 * @param {Array} foos An array of Wms.Models.Foo objects.
 */
    list: function( foos ){
        $('#foo').html(this.view('init', {
            foos:foos
        } ));
    },
    /**
 * Responds to the create form being submitted by creating a new Wms.Models.Foo.
 * @param {jQuery} el A jQuery wrapped element.
 * @param {Event} ev A jQuery event whose default action is prevented.
 */
    'form submit': function( el, ev ){
        ev.preventDefault();
        new Wms.Models.Foo(el.formParams()).save();
    },
    /**
 * Listens for foos being created.	 When a foo is created, displays the new foo.
 * @param {String} called The open ajax event that was called.
 * @param {Event} foo The new foo.
 */
    'foo.created subscribe': function( called, foo ){
        $("#foo tbody").append( this.view("list", {
            foos:[foo]
            }) );
        $("#foo form input[type!=submit]").val(""); //clear old vals
    },
    /**
 * Creates and places the edit interface.
 * @param {jQuery} el The foo's edit link element.
 */
    '.edit click': function( el ){
        var foo = el.closest('.foo').model();
        foo.elements().html(this.view('edit', foo));
    },
    /**
 * Removes the edit interface.
 * @param {jQuery} el The foo's cancel link element.
 */
    '.cancel click': function( el ){
        this.show(el.closest('.foo').model());
    },
    /**
 * Updates the foo from the edit values.
 */
    '.update click': function( el ){
        var $foo = el.closest('.foo'); 
        $foo.model().update($foo.formParams());
    },
    /**
 * Listens for updated foos.	 When a foo is updated, 
 * update's its display.
 */
    'foo.updated subscribe': function( called, foo ){
        this.show(foo);
    },
    /**
 * Shows a foo's information.
 */
    show: function( foo ){
        foo.elements().html(this.view('show',foo));
    },
    /**
 *	 Handle's clicking on a foo's destroy link.
 */
    '.destroy click': function( el ){
        if(confirm("Are you sure you want to destroy?")){
            el.closest('.foo').model().destroy();
        }
    },
    /**
 *	 Listens for foos being destroyed and removes them from being displayed.
 */
    "foo.destroyed subscribe": function(called, foo){
        foo.elements().remove();	 //removes ALL elements
    }
});