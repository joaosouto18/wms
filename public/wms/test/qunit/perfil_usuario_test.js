module("Model: Wms.Models.PerfilUsuario")

test("findAll", function(){
	stop(2000);
	Wms.Models.PerfilUsuario.findAll({}, function(perfil_usuarios){
		start()
		ok(perfil_usuarios)
        ok(perfil_usuarios.length)
        ok(perfil_usuarios[0].name)
        ok(perfil_usuarios[0].description)
	});
	
})

test("create", function(){
	stop(2000);
	new Wms.Models.PerfilUsuario({name: "dry cleaning", description: "take to street corner"}).save(function(perfil_usuario){
		start();
		ok(perfil_usuario);
        ok(perfil_usuario.id);
        equals(perfil_usuario.name,"dry cleaning")
        perfil_usuario.destroy()
	})
})
test("update" , function(){
	stop();
	new Wms.Models.PerfilUsuario({name: "cook dinner", description: "chicken"}).
            save(function(perfil_usuario){
            	equals(perfil_usuario.description,"chicken");
        		perfil_usuario.update({description: "steak"},function(perfil_usuario){
        			start()
        			equals(perfil_usuario.description,"steak");
        			perfil_usuario.destroy();
        		})
            })

});
test("destroy", function(){
	stop(2000);
	new Wms.Models.PerfilUsuario({name: "mow grass", description: "use riding mower"}).
            destroy(function(perfil_usuario){
            	start();
            	ok( true ,"Destroy called" )
            })
})