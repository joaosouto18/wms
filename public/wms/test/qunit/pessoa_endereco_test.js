module("Model: Wms.Models.PessoaEndereco")

asyncTest("findAll", function(){
	stop(2000);
	Wms.Models.PessoaEndereco.findAll({}, function(pessoa_enderecos){
		ok(pessoa_enderecos)
        ok(pessoa_enderecos.length)
        ok(pessoa_enderecos[0].name)
        ok(pessoa_enderecos[0].description)
		start()
	});
	
})

asyncTest("create", function(){
	stop(2000);
	new Wms.Models.PessoaEndereco({name: "dry cleaning", description: "take to street corner"}).save(function(pessoa_endereco){
		ok(pessoa_endereco);
        ok(pessoa_endereco.id);
        equals(pessoa_endereco.name,"dry cleaning")
        pessoa_endereco.destroy()
		start();
	})
})
asyncTest("update" , function(){
	stop();
	new Wms.Models.PessoaEndereco({name: "cook dinner", description: "chicken"}).
            save(function(pessoa_endereco){
            	equals(pessoa_endereco.description,"chicken");
        		pessoa_endereco.update({description: "steak"},function(pessoa_endereco){
        			equals(pessoa_endereco.description,"steak");
        			pessoa_endereco.destroy();
        			start()
        		})
            })

});
asyncTest("destroy", function(){
	stop(2000);
	new Wms.Models.PessoaEndereco({name: "mow grass", description: "use riding mower"}).
            destroy(function(pessoa_endereco){
            	ok( true ,"Destroy called" )
            	start();
            })
})