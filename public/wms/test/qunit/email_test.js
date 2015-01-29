module("Model: Wms.Models.Email")

test("findAll", function(){
	stop(2000);
	Wms.Models.Email.findAll({}, function(emails){
		start()
		ok(emails)
        ok(emails.length)
        ok(emails[0].name)
        ok(emails[0].description)
	});
	
})

test("create", function(){
	stop(2000);
	new Wms.Models.Email({name: "dry cleaning", description: "take to street corner"}).save(function(email){
		start();
		ok(email);
        ok(email.id);
        equals(email.name,"dry cleaning")
        email.destroy()
	})
})
test("update" , function(){
	stop();
	new Wms.Models.Email({name: "cook dinner", description: "chicken"}).
            save(function(email){
            	equals(email.description,"chicken");
        		email.update({description: "steak"},function(email){
        			start()
        			equals(email.description,"steak");
        			email.destroy();
        		})
            })

});
test("destroy", function(){
	stop(2000);
	new Wms.Models.Email({name: "mow grass", description: "use riding mower"}).
            destroy(function(email){
            	start();
            	ok( true ,"Destroy called" )
            })
})