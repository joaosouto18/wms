module("Model: Wms.Models.Dudu")

asyncTest("findAll", function(){
	stop(2000);
	Wms.Models.Dudu.findAll({}, function(dudus){
		ok(dudus)
        ok(dudus.length)
        ok(dudus[0].name)
        ok(dudus[0].description)
		start()
	});
	
})

asyncTest("create", function(){
	stop(2000);
	new Wms.Models.Dudu({name: "dry cleaning", description: "take to street corner"}).save(function(dudu){
		ok(dudu);
        ok(dudu.id);
        equals(dudu.name,"dry cleaning")
        dudu.destroy()
		start();
	})
})
asyncTest("update" , function(){
	stop();
	new Wms.Models.Dudu({name: "cook dinner", description: "chicken"}).
            save(function(dudu){
            	equals(dudu.description,"chicken");
        		dudu.update({description: "steak"},function(dudu){
        			equals(dudu.description,"steak");
        			dudu.destroy();
        			start()
        		})
            })

});
asyncTest("destroy", function(){
	stop(2000);
	new Wms.Models.Dudu({name: "mow grass", description: "use riding mower"}).
            destroy(function(dudu){
            	ok( true ,"Destroy called" )
            	start();
            })
})