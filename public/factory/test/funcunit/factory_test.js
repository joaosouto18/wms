module("factory test", { 
	setup: function(){
		S.open("//factory/factory.html");
	}
});

test("Copy Test", function(){
	equals(S("h1").text(), "Welcome to JavaScriptMVC 3.0!","welcome text");
});