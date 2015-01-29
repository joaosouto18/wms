module("Model: Wms.Models.Address")

test("findAll", function(){
	stop(2000);
	Wms.Models.Address.findAll({}, function(addresses){
		start()
		ok(addresses)
        ok(addresses.length)
        ok(addresses[0].name)
        ok(addresses[0].description)
	});
	
})

test("create", function(){
	stop(2000);
	new Wms.Models.Address({name: "dry cleaning", description: "take to street corner"}).save(function(address){
		start();
		ok(address);
        ok(address.id);
        equals(address.name,"dry cleaning")
        address.destroy()
	})
})
test("update" , function(){
	stop();
	new Wms.Models.Address({name: "cook dinner", description: "chicken"}).
            save(function(address){
            	equals(address.description,"chicken");
        		address.update({description: "steak"},function(address){
        			start()
        			equals(address.description,"steak");
        			address.destroy();
        		})
            })

});
test("destroy", function(){
	stop(2000);
	new Wms.Models.Address({name: "mow grass", description: "use riding mower"}).
            destroy(function(address){
            	start();
            	ok( true ,"Destroy called" )
            })
})