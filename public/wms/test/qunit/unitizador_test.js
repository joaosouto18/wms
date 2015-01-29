module("Model: Wms.Models.Unitizador")

asyncTest("findAll", function(){
	stop(2000);
	Wms.Models.Unitizador.findAll({}, function(unitizadors){
		ok(unitizadors)
        ok(unitizadors.length)
        ok(unitizadors[0].name)
        ok(unitizadors[0].description)
		start()
	});
	
})

asyncTest("create", function(){
	stop(2000);
	new Wms.Models.Unitizador({name: "dry cleaning", description: "take to street corner"}).save(function(unitizador){
		ok(unitizador);
        ok(unitizador.id);
        equals(unitizador.name,"dry cleaning")
        unitizador.destroy()
		start();
	})
})
asyncTest("update" , function(){
	stop();
	new Wms.Models.Unitizador({name: "cook dinner", description: "chicken"}).
            save(function(unitizador){
            	equals(unitizador.description,"chicken");
        		unitizador.update({description: "steak"},function(unitizador){
        			equals(unitizador.description,"steak");
        			unitizador.destroy();
        			start()
        		})
            })

});
asyncTest("destroy", function(){
	stop(2000);
	new Wms.Models.Unitizador({name: "mow grass", description: "use riding mower"}).
            destroy(function(unitizador){
            	ok( true ,"Destroy called" )
            	start();
            })
})