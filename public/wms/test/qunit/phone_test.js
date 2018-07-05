module("Model: Wms.Models.Phone")

test("findAll", function(){
	stop(2000);
	Wms.Models.Phone.findAll({}, function(phones){
		start()
		ok(phones)
        ok(phones.length)
        ok(phones[0].name)
        ok(phones[0].description)
	});
	
})

test("create", function(){
	stop(2000);
	new Wms.Models.Phone({name: "dry cleaning", description: "take to street corner"}).save(function(phone){
		start();
		ok(phone);
        ok(phone.id);
        equals(phone.name,"dry cleaning")
        phone.destroy()
	})
})
test("update" , function(){
	stop();
	new Wms.Models.Phone({name: "cook dinner", description: "chicken"}).
            save(function(phone){
            	equals(phone.description,"chicken");
        		phone.update({description: "steak"},function(phone){
        			start()
        			equals(phone.description,"steak");
        			phone.destroy();
        		})
            })

});
test("destroy", function(){
	stop(2000);
	new Wms.Models.Phone({name: "mow grass", description: "use riding mower"}).
            destroy(function(phone){
            	start();
            	ok( true ,"Destroy called" )
            })
})