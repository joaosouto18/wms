module("Model: Wms.Models.Foo")

test("findAll", function(){
	stop(2000);
	Wms.Models.Foo.findAll({}, function(foos){
		start()
		ok(foos)
        ok(foos.length)
        ok(foos[0].name)
        ok(foos[0].description)
	});
	
})

test("create", function(){
	stop(2000);
	new Wms.Models.Foo({name: "dry cleaning", description: "take to street corner"}).save(function(foo){
		start();
		ok(foo);
        ok(foo.id);
        equals(foo.name,"dry cleaning")
        foo.destroy()
	})
})
test("update" , function(){
	stop();
	new Wms.Models.Foo({name: "cook dinner", description: "chicken"}).
            save(function(foo){
            	equals(foo.description,"chicken");
        		foo.update({description: "steak"},function(foo){
        			start()
        			equals(foo.description,"steak");
        			foo.destroy();
        		})
            })

});
test("destroy", function(){
	stop(2000);
	new Wms.Models.Foo({name: "mow grass", description: "use riding mower"}).
            destroy(function(foo){
            	start();
            	ok( true ,"Destroy called" )
            })
})